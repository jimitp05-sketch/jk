import http from 'node:http';
import fs from 'node:fs/promises';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const CDP_URL = process.argv[3] || 'http://127.0.0.1:9222';
const OUT_FILE = new URL('./browser-audit-report.json', import.meta.url);

function requestJson(url, method = 'GET') {
  return new Promise((resolve, reject) => {
    const req = http.request(url, { method }, res => {
      let body = '';
      res.on('data', chunk => { body += chunk; });
      res.on('end', () => {
        try { resolve(JSON.parse(body)); }
        catch (e) { reject(new Error(`Invalid JSON from ${url}: ${body.slice(0, 200)}`)); }
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
        if (msg.error) reject(new Error(`${msg.error.message}: ${msg.error.data || ''}`));
        else resolve(msg.result || {});
        return;
      }
      if (msg.method && this.events.has(msg.method)) {
        for (const cb of this.events.get(msg.method)) cb(msg.params || {});
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
      }, 30000);
    });
  }

  close() {
    this.ws.close();
  }
}

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));

async function evalJs(cdp, expression, awaitPromise = false) {
  const result = await cdp.send('Runtime.evaluate', {
    expression,
    awaitPromise,
    returnByValue: true,
  });
  if (result.exceptionDetails) {
    throw new Error(result.exceptionDetails.text || 'Runtime exception');
  }
  return result.result?.value;
}

async function navigate(cdp, url) {
  let loaded = false;
  const onLoad = () => { loaded = true; };
  cdp.on('Page.loadEventFired', onLoad);
  await cdp.send('Page.navigate', { url });
  for (let i = 0; i < 80 && !loaded; i++) await wait(250);
  await wait(1200);
}

async function scrollAndInspect(cdp) {
  return await evalJs(cdp, `new Promise(resolve => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    (async () => {
      const heights = [];
      for (let y = 0; y <= document.documentElement.scrollHeight; y += Math.max(350, Math.floor(window.innerHeight * 0.75))) {
        window.scrollTo(0, y);
        await sleep(120);
        heights.push({ y: window.scrollY, height: document.documentElement.scrollHeight });
      }
      window.scrollTo(0, document.documentElement.scrollHeight);
      await sleep(250);
      resolve({
        title: document.title,
        url: location.href,
        height: document.documentElement.scrollHeight,
        viewport: { width: innerWidth, height: innerHeight },
        h1: [...document.querySelectorAll('h1')].map(h => h.innerText.trim()).filter(Boolean).slice(0, 5),
        visibleButtons: [...document.querySelectorAll('a,button')].filter(el => {
          const r = el.getBoundingClientRect();
          return r.width > 0 && r.height > 0;
        }).map(el => el.innerText.trim() || el.getAttribute('aria-label') || el.href || el.id).filter(Boolean).slice(0, 40),
        forms: [...document.querySelectorAll('form')].map(f => ({
          id: f.id || '',
          inputs: [...f.querySelectorAll('input,textarea,select')].map(i => i.id || i.name || i.type).filter(Boolean)
        })),
        imagesMissingAlt: [...document.images].filter(img => !img.alt).map(img => img.src).slice(0, 20),
        brokenImages: [...document.images].filter(img => img.complete && img.naturalWidth === 0).map(img => img.src).slice(0, 20),
        scrollSamples: heights.slice(0, 5).concat(heights.slice(-3))
      });
    })();
  })`, true);
}

async function auditPublicPages(cdp, pageErrors, consoleErrors, failedRequests) {
  const paths = ['/', '/booking.html', '/knowledge.html', '/research.html', '/quiz.html', '/reviews.html', '/memories.html', '/diya.html'];
  const pages = [];
  for (const path of paths) {
    const before = {
      pageErrors: pageErrors.length,
      consoleErrors: consoleErrors.length,
      failedRequests: failedRequests.length,
    };
    await navigate(cdp, `${BASE_URL}${path}`);
    const info = await scrollAndInspect(cdp);
    pages.push({
      path,
      ...info,
      newPageErrors: pageErrors.slice(before.pageErrors),
      newConsoleErrors: consoleErrors.slice(before.consoleErrors),
      newFailedRequests: failedRequests.slice(before.failedRequests),
    });
  }
  return pages;
}

async function auditAdmin(cdp) {
  await navigate(cdp, `${BASE_URL}/admin.php`);
  const loginInfo = await evalJs(cdp, `(() => {
    const inputs = [...document.querySelectorAll('input')].map(i => ({ id: i.id, name: i.name, type: i.type, placeholder: i.placeholder }));
    return { title: document.title, inputs, text: document.body.innerText.slice(0, 800) };
  })()`);

  const loginResult = await evalJs(cdp, `new Promise(resolve => {
    const setValue = (el, value) => {
      if (!el) return;
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    const user = document.querySelector('#admin-user, input[name="username"], input[type="text"], input:not([type])');
    const pass = document.querySelector('#admin-pass, input[name="password"], input[type="password"]');
    setValue(user, 'admin');
    setValue(pass, 'admin');
    const buttons = [...document.querySelectorAll('button,input[type="submit"]')];
    const loginButton = buttons.find(b => /login|sign|enter/i.test(b.innerText || b.value || b.id || '')) || buttons[0];
    if (loginButton) loginButton.click();
    setTimeout(() => resolve({
      text: document.body.innerText.slice(0, 1500),
      loggedInHints: /dashboard|settings|credentials|consultations|logout/i.test(document.body.innerText),
      loginError: document.querySelector('.error,[role="alert"],#login-error')?.innerText || ''
    }), 3500);
  })`, true);

  const panels = await evalJs(cdp, `new Promise(resolve => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    (async () => {
      const navs = [...document.querySelectorAll('[data-panel], .sidebar-item, .nav-item, .admin-nav-item, aside button, aside a')]
        .filter(el => {
          const txt = (el.innerText || el.textContent || '').trim();
          const r = el.getBoundingClientRect();
          return txt && r.width > 0 && r.height > 0;
        })
        .slice(0, 35);
      const results = [];
      for (const el of navs) {
        const label = (el.innerText || el.textContent || '').trim().replace(/\\s+/g, ' ').slice(0, 80);
        el.click();
        await sleep(450);
        window.scrollTo(0, document.documentElement.scrollHeight);
        await sleep(120);
        const activePanel = [...document.querySelectorAll('.admin-panel, [id^="panel-"], main section')]
          .filter(p => {
            const s = getComputedStyle(p);
            const r = p.getBoundingClientRect();
            return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0 && r.height > 0;
          })
          .map(p => ({ id: p.id, text: p.innerText.slice(0, 400) }))
          .slice(0, 3);
        results.push({ label, activePanel });
      }
      resolve(results);
    })();
  })`, true);

  return { loginInfo, loginResult, panels };
}

async function main() {
  const wsUrl = await newTab('about:blank');
  const cdp = new CDP(wsUrl);
  await cdp.open();

  const pageErrors = [];
  const consoleErrors = [];
  const failedRequests = [];
  const statusErrors = [];

  cdp.on('Runtime.exceptionThrown', e => pageErrors.push(e.exceptionDetails?.text || e.exceptionDetails?.exception?.description || 'Unknown exception'));
  cdp.on('Runtime.consoleAPICalled', e => {
    if (['error', 'warning'].includes(e.type)) {
      consoleErrors.push({ type: e.type, text: (e.args || []).map(a => a.value || a.description || '').join(' ') });
    }
  });
  cdp.on('Network.loadingFailed', e => failedRequests.push({ url: e.requestId, errorText: e.errorText, canceled: e.canceled }));
  cdp.on('Network.responseReceived', e => {
    const status = e.response?.status || 0;
    if (status >= 400) statusErrors.push({ url: e.response.url, status });
  });

  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');
  await cdp.send('Network.enable');
  await cdp.send('Log.enable');
  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });

  const publicPages = await auditPublicPages(cdp, pageErrors, consoleErrors, failedRequests);
  const admin = await auditAdmin(cdp);

  const report = {
    baseUrl: BASE_URL,
    generatedAt: new Date().toISOString(),
    publicPages,
    admin,
    totals: {
      pageErrors: pageErrors.length,
      consoleErrors: consoleErrors.length,
      failedRequests: failedRequests.length,
      statusErrors: statusErrors.length,
    },
    pageErrors,
    consoleErrors,
    failedRequests,
    statusErrors,
  };

  await fs.writeFile(OUT_FILE, JSON.stringify(report, null, 2), 'utf8');
  console.log(JSON.stringify({
    report: OUT_FILE.pathname,
    pages: publicPages.length,
    adminLoggedInHints: admin.loginResult.loggedInHints,
    adminPanelsVisited: admin.panels.length,
    totals: report.totals,
  }, null, 2));
  cdp.close();
}

main().catch(err => {
  console.error(err.stack || err.message);
  process.exit(1);
});
