import http from 'node:http';
import fs from 'node:fs/promises';
import path from 'node:path';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const PASSWORD = process.argv[3] || 'admin';
const CDP_URL = process.argv[4] || 'http://127.0.0.1:9223';
const ROOT = process.cwd();
const OUT_FILE = new URL('./public-admin-ui-flow-report.json', import.meta.url);

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const results = [];

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
  const result = await cdp.send('Runtime.evaluate', { expression, awaitPromise, returnByValue: true });
  if (result.exceptionDetails) {
    throw new Error(result.exceptionDetails.exception?.description || result.exceptionDetails.text || 'Runtime exception');
  }
  return result.result?.value;
}

async function connectOrCreate(url) {
  const targets = await requestJson(`${CDP_URL}/json`);
  const page = targets.find(t => t.type === 'page' && t.url.includes('/admin.php')) || targets.find(t => t.type === 'page');
  const ws = page?.webSocketDebuggerUrl || (await requestJson(`${CDP_URL}/json/new?${encodeURIComponent(url)}`, 'PUT')).webSocketDebuggerUrl;
  const cdp = new CDP(ws);
  await cdp.open();
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('DOM.enable');
  await cdp.send('Network.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
  cdp.on('Page.javascriptDialogOpening', () => cdp.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {}));
  return cdp;
}

async function newVisibleTab(url) {
  const target = await requestJson(`${CDP_URL}/json/new?${encodeURIComponent(url)}`, 'PUT');
  const cdp = new CDP(target.webSocketDebuggerUrl);
  await cdp.open();
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('DOM.enable');
  await cdp.send('Network.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
  cdp.on('Page.javascriptDialogOpening', () => cdp.send('Page.handleJavaScriptDialog', { accept: true }).catch(() => {}));
  return cdp;
}

async function navigate(cdp, pathOrUrl) {
  const url = pathOrUrl.startsWith('http') ? pathOrUrl : `${BASE_URL}${pathOrUrl}`;
  await cdp.send('Page.navigate', { url: `${url}${url.includes('?') ? '&' : '?'}ui=${Date.now()}` });
  await wait(3500);
}

async function pageText(cdp) {
  return await evalJs(cdp, `document.body ? document.body.innerText : ''`);
}

async function pageContains(cdp, marker, timeout = 12000) {
  const deadline = Date.now() + timeout;
  while (Date.now() < deadline) {
    await evalJs(cdp, `window.scrollTo(0, document.documentElement.scrollHeight)`);
    if ((await pageText(cdp)).includes(marker)) return true;
    await wait(800);
  }
  return false;
}

async function fillFields(cdp, fields) {
  return await evalJs(cdp, `(() => {
    const out = [];
    const fields = ${JSON.stringify(fields)};
    for (const [selector, value] of Object.entries(fields)) {
      const el = document.querySelector(selector);
      if (!el) { out.push({ selector, ok: false }); continue; }
      el.scrollIntoView({ block: 'center' });
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
      out.push({ selector, ok: true });
    }
    return out;
  })()`);
}

async function clickBySelector(cdp, selector) {
  const ok = await evalJs(cdp, `(() => {
    const el = document.querySelector(${JSON.stringify(selector)});
    if (!el) return false;
    el.scrollIntoView({ block: 'center' });
    el.click();
    return true;
  })()`);
  await wait(2500);
  return ok;
}

async function clickButtonContaining(cdp, text) {
  const ok = await evalJs(cdp, `(() => {
    const needle = ${JSON.stringify(text)}.toLowerCase();
    const el = [...document.querySelectorAll('button,a,[role="button"]')]
      .find(x => (x.innerText || x.textContent || '').toLowerCase().includes(needle));
    if (!el) return false;
    el.scrollIntoView({ block: 'center' });
    el.click();
    return true;
  })()`);
  await wait(2500);
  return ok;
}

async function setFileInput(cdp, selector, filePath) {
  const { root } = await cdp.send('DOM.getDocument');
  const { nodeId } = await cdp.send('DOM.querySelector', { nodeId: root.nodeId, selector });
  if (!nodeId) return false;
  await cdp.send('DOM.setFileInputFiles', { nodeId, files: [filePath] });
  await wait(1500);
  return true;
}

async function loginAdmin(cdp) {
  await navigate(cdp, '/admin.php');
  const login = await evalJs(cdp, `new Promise(resolve => {
    if (document.querySelector('#admin-layout')?.style.display !== 'none') {
      resolve({ ok: true, alreadyLoggedIn: true });
      return;
    }
    const set = (id, value) => {
      const el = document.querySelector(id);
      if (!el) return;
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    set('#admin-user', 'admin');
    set('#admin-pass', ${JSON.stringify(PASSWORD)});
    document.querySelector('#login-btn')?.click();
    setTimeout(() => resolve({ ok: document.querySelector('#admin-layout')?.style.display !== 'none' }), 4000);
  })`, true);
  add('Admin login through visible login form', login.ok ? 'pass' : 'fail', login);
  return login.ok;
}

async function openAdminPanel(cdp, panel) {
  const ok = await evalJs(cdp, `(() => {
    const nav = document.querySelector('[data-panel="${panel}"]');
    if (!nav) return false;
    nav.scrollIntoView({ block: 'center' });
    nav.click();
    return true;
  })()`);
  await wait(2500);
  return ok;
}

async function clickRowAction(cdp, marker, actionRegex) {
  const result = await evalJs(cdp, `(() => {
    const marker = ${JSON.stringify(marker)};
    const rx = new RegExp(${JSON.stringify(actionRegex)}, 'i');
    const roots = [...document.querySelectorAll('tr,.photo-review-item,.mem-photo-card')];
    const row = roots.find(el => (el.innerText || '').includes(marker));
    if (!row) return { ok: false, reason: 'row-not-found' };
    const btn = [...row.querySelectorAll('button')].find(b => rx.test((b.innerText || b.textContent || '') + ' ' + (b.getAttribute('onclick') || '')));
    if (!btn) return { ok: false, reason: 'button-not-found', rowText: row.innerText.slice(0, 250) };
    btn.scrollIntoView({ block: 'center' });
    btn.click();
    return { ok: true, rowText: row.innerText.slice(0, 250) };
  })()`);
  await wait(3500);
  return result;
}

async function submitPublicReview(publicCdp, marker) {
  await navigate(publicCdp, '/reviews.html');
  await fillFields(publicCdp, {
    '#rv-name': 'Codex UI Tester',
    '#rv-platform': 'others',
    '#rv-body': marker,
  });
  const clicked = await clickButtonContaining(publicCdp, 'Submit Recognition');
  add('Public Reviews form submitted from website UI', clicked ? 'pass' : 'fail');
  await wait(3500);
}

async function reviewApprovalFlow(adminCdp, publicCdp, marker) {
  await openAdminPanel(adminCdp, 'reviews');
  const pendingVisible = await pageContains(adminCdp, marker, 10000);
  add('Submitted review appears pending in admin panel', pendingVisible ? 'pass' : 'fail', { marker });
  if (!pendingVisible) return;
  const approved = await clickRowAction(adminCdp, marker, 'approve|approved');
  add('Admin approved submitted review via approval button', approved.ok ? 'pass' : 'fail', approved);
  await navigate(publicCdp, '/reviews.html');
  const publicVisible = await pageContains(publicCdp, marker, 12000);
  add('Approved review is visible on public Reviews page', publicVisible ? 'pass' : 'fail', { marker });
  await openAdminPanel(adminCdp, 'reviews');
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add('Admin deleted test review through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
}

async function submitPublicPulsePhoto(publicCdp, marker) {
  await navigate(publicCdp, '/reviews.html');
  await clickButtonContaining(publicCdp, 'Share Your Story');
  await fillFields(publicCdp, {
    '#ps-name': 'Codex UI Tester',
    '#ps-caption': marker,
    '#ps-story': 'Temporary UI-only Photo Wall approval test.',
    '#ps-label': 'Team',
    '#ps-date': '2026-05',
  });
  const filePath = path.join(ROOT, 'img-team.png');
  const fileSet = await setFileInput(publicCdp, '#photo-file-input', filePath);
  const clicked = fileSet && await clickButtonContaining(publicCdp, 'Submit for Review');
  add('Public Photo Wall upload submitted from website UI', clicked ? 'pass' : 'fail', { fileSet });
  await wait(5000);
}

async function pulsePhotoApprovalFlow(adminCdp, publicCdp, marker) {
  await openAdminPanel(adminCdp, 'photos');
  const pendingVisible = await pageContains(adminCdp, marker, 10000);
  add('Submitted Photo Wall item appears pending in admin panel', pendingVisible ? 'pass' : 'fail', { marker });
  if (!pendingVisible) return;
  const approved = await clickRowAction(adminCdp, marker, 'approve|approved');
  add('Admin approved Photo Wall item via approval button', approved.ok ? 'pass' : 'fail', approved);
  await navigate(publicCdp, '/reviews.html');
  const publicVisible = await pageContains(publicCdp, marker, 12000);
  add('Approved Photo Wall item is visible on public Reviews page', publicVisible ? 'pass' : 'fail', { marker });
  await openAdminPanel(adminCdp, 'photos');
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add('Admin deleted test Photo Wall item through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
}

async function submitMemory(publicCdp, type, marker) {
  await navigate(publicCdp, '/memories.html');
  if (type === 'story') {
    await clickBySelector(publicCdp, '#card-story');
    await fillFields(publicCdp, {
      '#s-patient': 'Codex Patient',
      '#s-relation': 'Family',
      '#s-duration': '1 Day',
      '#s-tag': 'Other',
      '#s-title': marker,
      '#s-story': `${marker} body submitted through the public Memories story form.`,
      '#s-quote': 'Temporary UI approval test.',
      '#s-family': 'Codex Family',
    });
  } else if (type === 'note') {
    await clickBySelector(publicCdp, '#card-note');
    await fillFields(publicCdp, {
      '#n-name': 'Codex Family',
      '#n-relation': 'Family',
      '#n-note': marker,
    });
  } else {
    await clickBySelector(publicCdp, '#card-photo');
    await fillFields(publicCdp, {
      '#p-uploader': 'Codex Family',
      '#p-label': 'Other',
      '#p-caption': marker,
    });
    await setFileInput(publicCdp, '#p-file', path.join(ROOT, 'img-team.png'));
  }
  const clicked = await clickBySelector(publicCdp, '#submit-btn');
  add(`Public Memories ${type} submitted from website UI`, clicked ? 'pass' : 'fail', { marker });
  await wait(5500);
}

async function memoryApprovalFlow(adminCdp, publicCdp, type, marker) {
  const panelType = type === 'story' ? 'healing_stories' : type === 'note' ? 'gratitude_notes' : 'memory_photos';
  await openAdminPanel(adminCdp, 'memories');
  if (type === 'note') await clickButtonContaining(adminCdp, 'Gratitude Notes');
  if (type === 'photo') await clickButtonContaining(adminCdp, 'Photo Memories');
  const pendingVisible = await pageContains(adminCdp, marker, 12000);
  add(`Submitted Memories ${type} appears pending in admin panel`, pendingVisible ? 'pass' : 'fail', { marker });
  if (!pendingVisible) return;
  const approved = await clickRowAction(adminCdp, marker, 'approve|✓');
  add(`Admin approved Memories ${type} via approval button`, approved.ok ? 'pass' : 'fail', approved);
  await navigate(publicCdp, '/memories.html');
  const publicVisible = await pageContains(publicCdp, marker, 14000);
  add(`Approved Memories ${type} is visible on public Memories page`, publicVisible ? 'pass' : 'fail', { marker });
  await openAdminPanel(adminCdp, 'memories');
  if (type === 'note') await clickButtonContaining(adminCdp, 'Gratitude Notes');
  if (type === 'photo') await clickButtonContaining(adminCdp, 'Photo Memories');
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add(`Admin deleted test Memories ${type} through admin UI`, deleted.ok ? 'pass' : 'fail', { panelType, ...deleted });
}

async function submitDiya(publicCdp, adminCdp, marker) {
  await navigate(publicCdp, '/diya.html');
  await fillFields(publicCdp, {
    '#diya-name': marker,
    '#diya-prayer': 'Temporary UI-only diya test.',
    '#diya-litby': 'Codex UI Tester',
  });
  const clicked = await clickBySelector(publicCdp, '#light-btn');
  add('Public Diya form submitted from website UI', clicked ? 'pass' : 'fail', { marker });
  await wait(5000);
  const publicVisible = await pageContains(publicCdp, marker, 10000);
  add('New public Diya appears on Diya wall without approval', publicVisible ? 'pass' : 'fail', { marker });
  await openAdminPanel(adminCdp, 'diya');
  const adminVisible = await pageContains(adminCdp, marker, 10000);
  add('New Diya appears in admin Diya panel', adminVisible ? 'pass' : 'fail', { marker });
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add('Admin deleted test Diya through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
}

async function submitSubscriber(publicCdp, adminCdp, marker) {
  await navigate(publicCdp, '/');
  await fillFields(publicCdp, { '#nl-email': marker });
  const clicked = await evalJs(publicCdp, `(() => {
    const form = document.querySelector('.newsletter-form');
    if (!form) return false;
    form.scrollIntoView({ block: 'center' });
    form.requestSubmit();
    return true;
  })()`);
  add('Newsletter subscription submitted from homepage UI', clicked ? 'pass' : 'fail', { marker });
  await wait(4500);
  await openAdminPanel(adminCdp, 'subscribers');
  const adminVisible = await pageContains(adminCdp, marker, 10000);
  add('New subscriber appears in admin Subscribers panel', adminVisible ? 'pass' : 'fail', { marker });
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add('Admin deleted test subscriber through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
}

async function adminContentToPulseFlow(adminCdp, publicCdp, type, marker) {
  if (type === 'institute') {
    await openAdminPanel(adminCdp, 'pulse-institutes');
    await fillFields(adminCdp, {
      '#inst-edit-id': '',
      '#inst-name': marker,
      '#inst-icon': '🏥',
      '#inst-body': 'Temporary institution recognition created through admin UI.',
      '#inst-source': 'Codex UI Test',
    });
    const saved = await clickButtonContaining(adminCdp, 'Save Recognition');
    add('Admin saved Institute Recognition through admin UI', saved ? 'pass' : 'fail', { marker });
    await navigate(publicCdp, '/reviews.html');
    const visible = await pageContains(publicCdp, marker, 12000);
    add('Institute Recognition is visible on public Pulse page', visible ? 'pass' : 'fail', { marker });
    await openAdminPanel(adminCdp, 'pulse-institutes');
    const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
    add('Admin deleted test Institute Recognition through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
    return;
  }

  await openAdminPanel(adminCdp, 'pulse-media');
  await fillFields(adminCdp, {
    '#media-edit-id': '',
    '#media-title': marker,
    '#media-pub': 'Codex Test Publication',
    '#media-date': '2026-05-07',
    '#media-url': '',
    '#media-excerpt': 'Temporary media mention created through admin UI.',
  });
  const saved = await clickButtonContaining(adminCdp, 'Save Media Mention');
  add('Admin saved Media Mention through admin UI', saved ? 'pass' : 'fail', { marker });
  await navigate(publicCdp, '/reviews.html');
  const visible = await pageContains(publicCdp, marker, 12000);
  add('Media Mention is visible on public Pulse page', visible ? 'pass' : 'fail', { marker });
  await openAdminPanel(adminCdp, 'pulse-media');
  const deleted = await clickRowAction(adminCdp, marker, 'delete|remove');
  add('Admin deleted test Media Mention through admin UI', deleted.ok ? 'pass' : 'fail', deleted);
}

async function main() {
  const adminCdp = await connectOrCreate(`${BASE_URL}/admin.php`);
  const publicCdp = await newVisibleTab(`${BASE_URL}/`);
  const pageErrors = [];
  adminCdp.on('Runtime.exceptionThrown', e => pageErrors.push(e.exceptionDetails?.exception?.description || e.exceptionDetails?.text || 'admin runtime exception'));
  publicCdp.on('Runtime.exceptionThrown', e => pageErrors.push(e.exceptionDetails?.exception?.description || e.exceptionDetails?.text || 'public runtime exception'));

  if (!(await loginAdmin(adminCdp))) throw new Error('Admin login failed');

  const stamp = Date.now();
  await submitPublicReview(publicCdp, `Codex UI Review Approval ${stamp}`);
  await reviewApprovalFlow(adminCdp, publicCdp, `Codex UI Review Approval ${stamp}`);

  await submitPublicPulsePhoto(publicCdp, `Codex UI Pulse Photo Approval ${stamp}`);
  await pulsePhotoApprovalFlow(adminCdp, publicCdp, `Codex UI Pulse Photo Approval ${stamp}`);

  await submitMemory(publicCdp, 'story', `Codex UI Memory Story ${stamp}`);
  await memoryApprovalFlow(adminCdp, publicCdp, 'story', `Codex UI Memory Story ${stamp}`);

  await submitMemory(publicCdp, 'note', `Codex UI Memory Note ${stamp}`);
  await memoryApprovalFlow(adminCdp, publicCdp, 'note', `Codex UI Memory Note ${stamp}`);

  await submitMemory(publicCdp, 'photo', `Codex UI Memory Photo ${stamp}`);
  await memoryApprovalFlow(adminCdp, publicCdp, 'photo', `Codex UI Memory Photo ${stamp}`);

  await submitDiya(publicCdp, adminCdp, `Codex UI Diya ${stamp}`);
  await submitSubscriber(publicCdp, adminCdp, `codex-ui-${stamp}@example.com`);
  await adminContentToPulseFlow(adminCdp, publicCdp, 'institute', `Codex UI Institute ${stamp}`);
  await adminContentToPulseFlow(adminCdp, publicCdp, 'media', `Codex UI Media ${stamp}`);

  if (pageErrors.length) add('Browser runtime errors during UI-only flow', 'fail', { pageErrors });
  else add('Browser runtime errors during UI-only flow', 'pass');

  const summary = {
    baseUrl: BASE_URL,
    cdpUrl: CDP_URL,
    generatedAt: new Date().toISOString(),
    note: 'UI-only run: no direct foxwisdom API calls were made by this script. All writes were triggered through website/admin browser UI.',
    total: results.length,
    pass: results.filter(r => r.status === 'pass').length,
    warn: results.filter(r => r.status === 'warn').length,
    fail: results.filter(r => r.status === 'fail').length,
    results,
  };
  await fs.writeFile(OUT_FILE, JSON.stringify(summary, null, 2), 'utf8');
  adminCdp.close();
  publicCdp.close();
  console.log('REPORT', OUT_FILE.pathname);
  if (summary.fail) process.exitCode = 1;
}

main().catch(async e => {
  add('Fatal UI-only public/admin flow error', 'fail', { error: e.stack || e.message });
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
