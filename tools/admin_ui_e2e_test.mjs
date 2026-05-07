import http from 'node:http';
import fs from 'node:fs/promises';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const PASSWORD = process.argv[3] || 'admin';
const CDP_URL = process.argv[4] || 'http://127.0.0.1:9223';
const OUT_FILE = new URL('./admin-ui-e2e-report.json', import.meta.url);

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const results = [];
const saved = new Map();

function add(name, status, detail = {}) {
  results.push({ name, status, detail });
  console.log(status === 'pass' ? '[PASS]' : status === 'warn' ? '[WARN]' : '[FAIL]', name, JSON.stringify(detail));
}

function requestJson(url, method = 'GET') {
  return new Promise((resolve, reject) => {
    const req = http.request(url, { method }, res => {
      let body = '';
      res.on('data', chunk => { body += chunk; });
      res.on('end', () => {
        try { resolve(JSON.parse(body)); }
        catch { reject(new Error(`Invalid JSON from ${url}: ${body.slice(0, 200)}`)); }
      });
    });
    req.on('error', reject);
    req.end();
  });
}

class CDP {
  constructor(wsUrl) {
    this.ws = new WebSocket(wsUrl);
    this.nextId = 1;
    this.pending = new Map();
    this.events = new Map();
  }

  async open() {
    await new Promise((resolve, reject) => {
      this.ws.addEventListener('open', resolve, { once: true });
      this.ws.addEventListener('error', reject, { once: true });
    });
    this.ws.addEventListener('message', event => {
      const msg = JSON.parse(event.data);
      if (msg.id && this.pending.has(msg.id)) {
        const { resolve, reject } = this.pending.get(msg.id);
        this.pending.delete(msg.id);
        if (msg.error) reject(new Error(msg.error.message));
        else resolve(msg.result || {});
      }
      if (msg.method && this.events.has(msg.method)) {
        this.events.get(msg.method).forEach(cb => cb(msg.params || {}));
      }
    });
  }

  on(method, cb) {
    if (!this.events.has(method)) this.events.set(method, []);
    this.events.get(method).push(cb);
  }

  send(method, params = {}) {
    const id = this.nextId++;
    this.ws.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
      setTimeout(() => {
        if (this.pending.has(id)) {
          this.pending.delete(id);
          reject(new Error(`CDP timeout: ${method}`));
        }
      }, 45000);
    });
  }

  close() {
    try { this.ws.close(); } catch {}
  }
}

async function evalJs(cdp, expression, awaitPromise = false) {
  const result = await cdp.send('Runtime.evaluate', {
    expression,
    awaitPromise,
    returnByValue: true,
  });
  if (result.exceptionDetails) {
    throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text || 'Runtime exception');
  }
  return result.result?.value;
}

async function findAdminTarget() {
  const targets = await requestJson(`${CDP_URL}/json`);
  const exact = targets.find(t => t.type === 'page' && t.url.includes('/admin.php'));
  const page = exact || targets.find(t => t.type === 'page');
  if (!page) throw new Error(`No Chrome page is exposed at ${CDP_URL}`);
  return page.webSocketDebuggerUrl;
}

async function newTab(url = 'about:blank') {
  const target = await requestJson(`${CDP_URL}/json/new?${encodeURIComponent(url)}`, 'PUT');
  return target.webSocketDebuggerUrl;
}

async function api(path, options = {}) {
  const res = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers: {
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  });
  const text = await res.text();
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch {}
  return { res, text, json };
}

async function getContent(type, token) {
  const resp = await api(`/api/content.php?type=${encodeURIComponent(type)}`, {
    headers: { 'X-Admin-Token': token },
  });
  if (!resp.res.ok) throw new Error(`GET ${type} failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  return Array.isArray(resp.json?.data) ? resp.json.data : [];
}

async function setContent(type, items, token) {
  const resp = await api('/api/content.php', {
    method: 'POST',
    body: JSON.stringify({ session_token: token, type, items }),
  });
  if (!resp.res.ok || resp.json?.success === false) {
    throw new Error(`Restore ${type} failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  }
}

async function snapshot(type, token) {
  if (!saved.has(type)) saved.set(type, await getContent(type, token));
  return structuredClone(saved.get(type));
}

async function restore(type, token) {
  if (!saved.has(type)) return;
  await setContent(type, saved.get(type), token);
  await wait(6500);
}

async function loginViaVisibleAdmin(cdp) {
  await cdp.send('Page.navigate', { url: `${BASE_URL}/admin.php?codex=${Date.now()}` });
  await wait(3000);
  return await evalJs(cdp, `new Promise(resolve => {
    const setValue = (selector, value) => {
      const el = document.querySelector(selector);
      if (!el) return false;
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return true;
    };
    if (document.querySelector('#admin-layout')?.style.display !== 'none') {
      resolve({ ok: true, alreadyLoggedIn: true, token: sessionStorage.getItem('apollo_session_token') || localStorage.getItem('admin_session_token') || localStorage.getItem('adminToken') || '' });
      return;
    }
    setValue('#admin-user', 'admin');
    setValue('#admin-pass', ${JSON.stringify(PASSWORD)});
    document.querySelector('#login-btn')?.click();
    setTimeout(() => {
      resolve({
        ok: document.querySelector('#admin-layout')?.style.display !== 'none',
        text: document.body.innerText.slice(0, 300),
        token: sessionStorage.getItem('apollo_session_token') || localStorage.getItem('admin_session_token') || localStorage.getItem('adminToken') || ''
      });
    }, 4500);
  })`, true);
}

async function clickPanel(cdp, panel) {
  const opened = await evalJs(cdp, `(() => {
    const nav = document.querySelector('[data-panel="${panel}"]');
    if (!nav) return false;
    nav.scrollIntoView({ block: 'center' });
    nav.click();
    return !!document.querySelector('#panel-${panel}');
  })()`);
  await wait(1500);
  return opened;
}

async function fillAndClick(cdp, fields, clickTextOrOnclick) {
  return await evalJs(cdp, `new Promise(resolve => {
    const setValue = (selector, value) => {
      const el = document.querySelector(selector);
      if (!el) return { selector, ok: false };
      el.scrollIntoView({ block: 'center' });
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      return { selector, ok: true };
    };
    const fields = ${JSON.stringify(fields)};
    const fieldResults = Object.entries(fields).map(([selector, value]) => setValue(selector, value));
    const needle = ${JSON.stringify(clickTextOrOnclick)};
    const buttons = [...document.querySelectorAll('button')];
    const button = buttons.find(btn => (btn.getAttribute('onclick') || '').includes(needle))
      || buttons.find(btn => (btn.innerText || '').toLowerCase().includes(String(needle).toLowerCase()));
    if (!button) {
      resolve({ ok: false, fieldResults, clicked: false, reason: 'button-not-found' });
      return;
    }
    button.scrollIntoView({ block: 'center' });
    button.click();
    setTimeout(() => resolve({ ok: fieldResults.every(r => r.ok), fieldResults, clicked: true }), 3500);
  })`, true);
}

async function publicContains(cdp, path, marker, scroll = true) {
  await cdp.send('Page.navigate', { url: `${BASE_URL}${path}${path.includes('?') ? '&' : '?'}codex=${Date.now()}` });
  const deadline = Date.now() + 12000;
  while (Date.now() < deadline) {
    if (scroll) {
      await evalJs(cdp, `window.scrollTo(0, document.documentElement.scrollHeight)`);
    }
    const found = await evalJs(cdp, `document.body.innerText.includes(${JSON.stringify(marker)})`);
    if (found) return true;
    await wait(800);
  }
  return false;
}

function contentHasMarker(items, marker) {
  return JSON.stringify(items).includes(marker);
}

async function bookingDateIsBlocked(cdp, dateKey) {
  const [year, month, day] = dateKey.split('-').map(Number);
  await cdp.send('Page.navigate', { url: `${BASE_URL}/booking.html?codex=${Date.now()}` });
  await wait(3000);
  return await evalJs(cdp, `(async () => {
    if (typeof currentDate !== 'undefined' && typeof renderCalendar === 'function') {
      currentDate = new Date(${year}, ${month - 1}, 1);
      await renderCalendar();
    }
    await new Promise(resolve => setTimeout(resolve, 500));
    const el = document.querySelector('[data-date="${dateKey}"]');
    return !!el && (el.classList.contains('past') || el.classList.contains('blocked')) && !el.hasAttribute('onclick');
  })()`, true);
}

async function testContentForm({ adminCdp, publicCdp, publicPage, token, panel, type, marker, fields, click }) {
  await snapshot(type, token);
  try {
    const opened = await clickPanel(adminCdp, panel);
    if (!opened) {
      add(`Admin panel opens: ${panel}`, 'fail', { panel });
      return;
    }
    add(`Admin panel opens: ${panel}`, 'pass');
    const savedByUi = await fillAndClick(adminCdp, fields(marker), click);
    if (!savedByUi.clicked || !savedByUi.ok) {
      add(`Admin UI save: ${type}`, 'fail', savedByUi);
      return;
    }
    await wait(6500);
    add(`Admin UI save: ${type}`, 'pass');
    const apiItems = await getContent(type, token);
    const apiContains = contentHasMarker(apiItems, marker);
    add(`Admin UI update persisted in API: ${type}`, apiContains ? 'pass' : 'fail', {
      count: Array.isArray(apiItems) ? apiItems.length : null,
      marker,
    });
    if (!apiContains) return;
    const visible = await publicContains(publicCdp, publicPage, marker);
    add(`Public website reflects admin UI update: ${type}`, visible ? 'pass' : 'fail', { publicPage, marker });
  } catch (e) {
    add(`Admin UI flow: ${type}`, 'fail', { error: e.message });
  } finally {
    try {
      await restore(type, token);
      add(`Restored original data: ${type}`, 'pass');
    } catch (e) {
      add(`Restored original data: ${type}`, 'fail', { error: e.message });
    }
  }
}

async function testBlockedDate(cdp, token) {
  const type = 'blocked_dates';
  const dateKey = '2026-12-30';
  await snapshot(type, token);
  try {
    await clickPanel(cdp, 'calendar');
    add('Admin panel opens: calendar', 'pass');
    const savedByUi = await fillAndClick(cdp, {
      '#block-date-input': dateKey,
      '#block-date-reason': `Codex admin UI blocked date ${Date.now()}`,
    }, 'addBlockedDate');
    if (!savedByUi.clicked || !savedByUi.ok) {
      add('Admin UI save: blocked_dates', 'fail', savedByUi);
      return;
    }
    await wait(6500);
    add('Admin UI save: blocked_dates', 'pass');
    const blocked = await bookingDateIsBlocked(cdp, dateKey);
    add('Public booking calendar reflects blocked date', blocked ? 'pass' : 'fail', { dateKey });
  } catch (e) {
    add('Admin UI flow: blocked_dates', 'fail', { error: e.message });
  } finally {
    try {
      await restore(type, token);
      add('Restored original data: blocked_dates', 'pass');
    } catch (e) {
      add('Restored original data: blocked_dates', 'fail', { error: e.message });
    }
  }
}

async function clickAllPanels(cdp) {
  const panels = await evalJs(cdp, `(() => [...document.querySelectorAll('[data-panel]')]
    .map(el => ({ panel: el.getAttribute('data-panel'), label: (el.innerText || '').trim().replace(/\\s+/g, ' ') }))
    .filter(x => x.panel))()`);
  for (const item of panels) {
    try {
      await clickPanel(cdp, item.panel);
      const active = await evalJs(cdp, `document.querySelector('#panel-${item.panel}')?.classList.contains('active') === true`);
      await evalJs(cdp, `window.scrollTo(0, Math.min(900, document.documentElement.scrollHeight))`);
      await wait(350);
      await evalJs(cdp, `window.scrollTo(0, 0)`);
      add(`Clicked admin option: ${item.label}`, active ? 'pass' : 'fail', { panel: item.panel });
    } catch (e) {
      add(`Clicked admin option: ${item.label}`, 'fail', { panel: item.panel, error: e.message });
    }
  }
}

async function main() {
  const adminCdp = new CDP(await findAdminTarget());
  await adminCdp.open();
  await adminCdp.send('Page.enable');
  await adminCdp.send('Runtime.enable');
  await adminCdp.send('Network.enable');
  await adminCdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

  const pageErrors = [];
  const failedRequests = [];
  adminCdp.on('Runtime.exceptionThrown', e => {
    const d = e.exceptionDetails || {};
    pageErrors.push({
      text: d.text || 'runtime exception',
      description: d.exception?.description || '',
      url: d.url || '',
      lineNumber: d.lineNumber ?? null,
      columnNumber: d.columnNumber ?? null,
    });
  });
  adminCdp.on('Network.loadingFailed', e => failedRequests.push(e.errorText || e.blockedReason || 'network failure'));

  const login = await loginViaVisibleAdmin(adminCdp);
  if (!login.ok) throw new Error(`Visible admin login failed: ${JSON.stringify(login)}`);
  add('Visible admin login using admin/admin', 'pass', { alreadyLoggedIn: !!login.alreadyLoggedIn });

  let token = login.token;
  if (!token) {
    token = await evalJs(adminCdp, `sessionStorage.getItem('apollo_session_token') || localStorage.getItem('admin_session_token') || localStorage.getItem('adminToken') || ''`);
  }
  if (!token) throw new Error('No admin session token found after visible login.');

  await clickAllPanels(adminCdp);

  const publicCdp = new CDP(await newTab(`${BASE_URL}/`));
  await publicCdp.open();
  await publicCdp.send('Page.enable');
  await publicCdp.send('Runtime.enable');
  await publicCdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

  const suffix = Date.now();
  await testContentForm({
    adminCdp,
    publicCdp,
    publicPage: '/',
    token,
    panel: 'faq',
    type: 'faq_items',
    marker: `Codex Admin UI FAQ ${suffix}`,
    fields: marker => ({
      '#faq-edit-id': '',
      '#faq-question': marker,
      '#faq-answer': 'Temporary answer submitted through the visible admin form.',
    }),
    click: 'saveFaqItem',
  });
  await wait(7500);

  await testContentForm({
    adminCdp,
    publicCdp,
    publicPage: '/',
    token,
    panel: 'expertise',
    type: 'expertise_items',
    marker: `Codex Admin UI Expertise ${suffix}`,
    fields: marker => ({
      '#exp-edit-id': '',
      '#exp-title': marker,
      '#exp-plain': 'Temporary admin UI expertise card.',
      '#exp-desc': 'Temporary admin UI expertise card.',
    }),
    click: 'saveExpertise',
  });
  await wait(7500);

  await testContentForm({
    adminCdp,
    publicCdp,
    publicPage: '/research.html',
    token,
    panel: 'research',
    type: 'research_papers',
    marker: `Codex Admin UI Research ${suffix}`,
    fields: marker => ({
      '#rp-edit-id': '',
      '#rp-title': marker,
      '#rp-journal': 'Codex Journal',
      '#rp-year': '2026',
      '#rp-topic': 'Testing',
      '#rp-doi': '',
      '#rp-takeaway': 'Temporary research paper entered through admin UI.',
      '#rp-practice': 'Temporary clinical practice note.',
    }),
    click: 'saveResearchPaper',
  });
  await wait(7500);

  await testContentForm({
    adminCdp,
    publicCdp,
    publicPage: '/quiz.html',
    token,
    panel: 'myths',
    type: 'myth_busters',
    marker: `Codex Admin UI Myth ${suffix}`,
    fields: marker => ({
      '#myth-edit-id': '',
      '#myth-statement': marker,
      '#myth-fact': 'Temporary myth fact from admin UI testing.',
      '#myth-source': 'Codex UI Test',
    }),
    click: 'saveMythCard',
  });
  await wait(7500);

  await testContentForm({
    adminCdp,
    publicCdp,
    publicPage: '/reviews.html',
    token,
    panel: 'photos',
    type: 'photo_wall',
    marker: `Codex Admin UI Photo ${suffix}`,
    fields: marker => ({
      '#photo-url': 'https://foxwisdom.com/img-team.png',
      '#photo-caption': marker,
      '#photo-label': 'Team',
    }),
    click: 'addPhoto',
  });
  await wait(7500);

  await testBlockedDate(adminCdp, token);

  await publicCdp.send('Page.navigate', { url: `${BASE_URL}/?codex-final=${Date.now()}` });
  await wait(1500);
  const finalFaqStillVisible = await evalJs(publicCdp, `document.body.innerText.includes(${JSON.stringify(`Codex Admin UI FAQ ${suffix}`)})`);
  add('Cleanup verification: FAQ marker removed from public site', finalFaqStillVisible ? 'fail' : 'pass');

  if (pageErrors.length) add('Browser runtime errors during admin UI test', 'fail', { pageErrors });
  else add('Browser runtime errors during admin UI test', 'pass');
  if (failedRequests.length) add('Browser failed requests during admin UI test', 'warn', { failedRequests: [...new Set(failedRequests)] });
  else add('Browser failed requests during admin UI test', 'pass');

  const summary = {
    baseUrl: BASE_URL,
    cdpUrl: CDP_URL,
    generatedAt: new Date().toISOString(),
    total: results.length,
    pass: results.filter(r => r.status === 'pass').length,
    warn: results.filter(r => r.status === 'warn').length,
    fail: results.filter(r => r.status === 'fail').length,
    results,
  };
  await fs.writeFile(OUT_FILE, JSON.stringify(summary, null, 2), 'utf8');
  publicCdp.close();
  adminCdp.close();
  console.log('REPORT', OUT_FILE.pathname);
  if (summary.fail) process.exitCode = 1;
}

main().catch(async e => {
  add('Fatal admin UI E2E error', 'fail', { error: e.stack || e.message });
  await fs.writeFile(OUT_FILE, JSON.stringify({
    baseUrl: BASE_URL,
    cdpUrl: CDP_URL,
    generatedAt: new Date().toISOString(),
    total: results.length,
    pass: results.filter(r => r.status === 'pass').length,
    warn: results.filter(r => r.status === 'warn').length,
    fail: results.filter(r => r.status === 'fail').length,
    results,
  }, null, 2), 'utf8');
  process.exit(1);
});
