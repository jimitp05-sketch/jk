import http from 'node:http';
import fs from 'node:fs/promises';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const PASSWORD = process.argv[3] || 'admin';
const CDP_URL = process.argv[4] || 'http://127.0.0.1:9222';
const OUT_FILE = new URL('./admin-live-sync-report.json', import.meta.url);

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const results = [];

function add(name, status, detail = {}) {
  results.push({ name, status, detail });
  console.log(status === 'pass' ? '[PASS]' : status === 'warn' ? '[WARN]' : '[FAIL]', name, JSON.stringify(detail));
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

async function login() {
  const resp = await api('/api/settings.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'check_auth', admin_pass: PASSWORD }),
  });
  if (!resp.json?.session_token) throw new Error(`Login failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  add('Admin login for sync testing', 'pass', { user: resp.json.user });
  return resp.json.session_token;
}

async function getContent(type, token) {
  const resp = await api(`/api/content.php?type=${encodeURIComponent(type)}`, {
    headers: token ? { 'X-Admin-Token': token } : {},
  });
  if (!resp.res.ok) throw new Error(`GET ${type} failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  return resp.json?.data ?? [];
}

async function setContent(type, items, token) {
  const resp = await api('/api/content.php', {
    method: 'POST',
    body: JSON.stringify({ session_token: token, type, items }),
  });
  if (!resp.res.ok || resp.json?.success === false) throw new Error(`POST ${type} failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  await wait(6500);
  return resp.json;
}

function requestJson(url, method = 'GET') {
  return new Promise((resolve, reject) => {
    const req = http.request(url, { method }, res => {
      let body = '';
      res.on('data', chunk => { body += chunk; });
      res.on('end', () => {
        try { resolve(JSON.parse(body)); }
        catch { reject(new Error(`Invalid JSON from ${url}`)); }
      });
    });
    req.on('error', reject);
    req.end();
  });
}

async function newTab(url = 'about:blank') {
  const target = await requestJson(`${CDP_URL}/json/new?${encodeURIComponent(url)}`, 'PUT');
  return target.webSocketDebuggerUrl;
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
      if (msg.method && this.events.has(msg.method)) this.events.get(msg.method).forEach(cb => cb(msg.params || {}));
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
      }, 30000);
    });
  }
  close() { this.ws.close(); }
}

async function evalJs(cdp, expression, awaitPromise = false) {
  const result = await cdp.send('Runtime.evaluate', { expression, awaitPromise, returnByValue: true });
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Runtime exception');
  return result.result?.value;
}

async function pageContains(cdp, path, marker) {
  await cdp.send('Page.navigate', { url: `${BASE_URL}${path}?codex=${Date.now()}` });
  await wait(3500);
  await evalJs(cdp, `window.scrollTo(0, document.documentElement.scrollHeight)`);
  await wait(1000);
  return await evalJs(cdp, `document.body.innerText.includes(${JSON.stringify(marker)})`);
}

async function withRestoredContent(type, token, mutator, testFn) {
  const original = await getContent(type, token);
  const copy = structuredClone(original);
  try {
    const updated = mutator(structuredClone(original));
    await setContent(type, updated, token);
    await testFn(updated);
  } finally {
    await setContent(type, copy, token);
  }
}

async function main() {
  const token = await login();
  const wsUrl = await newTab('about:blank');
  const cdp = new CDP(wsUrl);
  await cdp.open();
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('Network.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

  const tests = [
    {
      type: 'faq_items',
      page: '/',
      marker: `Codex FAQ live marker ${Date.now()}`,
      make: marker => ({ id: `codex_faq_${Date.now()}`, q: marker, a: 'This is a temporary admin-to-live test answer.', status: 'approved' }),
    },
    {
      type: 'expertise_items',
      page: '/',
      marker: `Codex Expertise live marker ${Date.now()}`,
      make: marker => ({ id: `codex_exp_${Date.now()}`, title: marker, text: 'Temporary expertise test.', icon: 'T', status: 'approved' }),
    },
    {
      type: 'knowledge_articles',
      page: '/knowledge.html',
      marker: `Codex Knowledge live marker ${Date.now()}`,
      make: marker => ({ id: `codex_knowledge_${Date.now()}`, title: marker, pillar: 'Testing', subtitle: 'Temporary knowledge test.', body: '<p>Temporary knowledge body.</p>', status: 'published' }),
    },
    {
      type: 'research_papers',
      page: '/research.html',
      marker: `Codex Research live marker ${Date.now()}`,
      make: marker => ({ id: `codex_research_${Date.now()}`, title: marker, topic: 'Testing', journal: 'Codex Journal', year: 2026, takeaway: 'Temporary research test.', status: 'approved' }),
    },
    {
      type: 'myth_busters',
      page: '/quiz.html',
      marker: `Codex Myth live marker ${Date.now()}`,
      make: marker => ({ id: `codex_myth_${Date.now()}`, statement: marker, fact: 'Temporary myth fact.', source: 'Codex Test', status: 'approved' }),
    },
    {
      type: 'peer_recognitions',
      page: '/reviews.html',
      marker: `Codex Recognition live marker ${Date.now()}`,
      make: marker => ({ id: `codex_review_${Date.now()}`, author: 'Codex Tester', name: 'Codex Tester', platform: 'others', text: marker, status: 'approved', date: new Date().toISOString().slice(0, 10) }),
    },
    {
      type: 'photo_wall',
      page: '/reviews.html',
      marker: `Codex Photo live marker ${Date.now()}`,
      make: marker => ({ id: `codex_photo_${Date.now()}`, url: 'https://foxwisdom.com/img-team.png', caption: marker, label: 'Testing', status: 'approved', added: new Date().toISOString() }),
    },
    {
      type: 'blocked_dates',
      page: '/booking.html',
      marker: `Codex blocked date ${Date.now()}`,
      make: marker => ({ date: '2026-12-29', reason: marker }),
    },
  ];

  for (const test of tests) {
    try {
      await withRestoredContent(
        test.type,
        token,
        current => {
          if (!Array.isArray(current)) current = [];
          return [...current, test.make(test.marker)];
        },
        async () => {
          const visible = await pageContains(cdp, test.page, test.marker);
          add(`Admin update visible on live page: ${test.type}`, visible ? 'pass' : 'fail', { page: test.page, marker: test.marker });
        }
      );
    } catch (e) {
      add(`Admin update visible on live page: ${test.type}`, 'fail', { error: e.message });
    }
  }

  cdp.close();
  const summary = {
    baseUrl: BASE_URL,
    generatedAt: new Date().toISOString(),
    total: results.length,
    pass: results.filter(r => r.status === 'pass').length,
    fail: results.filter(r => r.status === 'fail').length,
    results,
  };
  await fs.writeFile(OUT_FILE, JSON.stringify(summary, null, 2), 'utf8');
  console.log('REPORT', OUT_FILE.pathname);
  if (summary.fail) process.exitCode = 1;
}

main().catch(async e => {
  add('Fatal sync test error', 'fail', { error: e.stack || e.message });
  await fs.writeFile(OUT_FILE, JSON.stringify({ results }, null, 2), 'utf8');
  process.exit(1);
});
