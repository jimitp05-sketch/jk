import http from 'node:http';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const CDP_URL = process.argv[3] || 'http://127.0.0.1:9222';
const PASSWORD = process.argv[4] || 'admin';

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
        msg.error ? reject(new Error(`${msg.error.message}: ${msg.error.data || ''}`)) : resolve(msg.result || {});
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
      }, 15000);
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
    throw new Error(result.exceptionDetails.text || result.exceptionDetails.exception?.description || 'Runtime exception');
  }
  return result.result?.value;
}

async function navigate(cdp, url) {
  await cdp.send('Page.navigate', { url });
  await wait(2500);
}

async function loginIfNeeded(cdp) {
  return await evalJs(cdp, `new Promise(resolve => {
    const layout = document.querySelector('#admin-layout');
    if (layout && getComputedStyle(layout).display !== 'none') {
      resolve({ alreadyLoggedIn: true });
      return;
    }
    const setValue = (el, value) => {
      if (!el) return;
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    setValue(document.querySelector('#admin-user'), 'admin');
    setValue(document.querySelector('#admin-pass'), ${JSON.stringify(PASSWORD)});
    document.querySelector('#login-btn')?.click();
    setTimeout(() => {
      const shown = getComputedStyle(document.querySelector('#admin-layout')).display !== 'none';
      resolve({ alreadyLoggedIn: false, loggedIn: shown, error: document.querySelector('#login-error')?.innerText || '' });
    }, 2500);
  })`, true);
}

async function main() {
  const wsUrl = await newTab('about:blank');
  const cdp = new CDP(wsUrl);
  await cdp.open();
  const consoleErrors = [];
  const pageErrors = [];
  cdp.on('Runtime.consoleAPICalled', e => {
    if (['error', 'warning'].includes(e.type)) consoleErrors.push((e.args || []).map(a => a.value || a.description || '').join(' '));
  });
  cdp.on('Runtime.exceptionThrown', e => pageErrors.push(e.exceptionDetails?.exception?.description || e.exceptionDetails?.text || 'exception'));
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');

  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 1440, height: 1000, deviceScaleFactor: 1, mobile: false });
  await navigate(cdp, `${BASE_URL}/admin.php`);
  const login = await loginIfNeeded(cdp);
  const desktop = await evalJs(cdp, `(() => {
    const sidebar = document.querySelector('.admin-sidebar');
    const header = document.querySelector('.panel-header');
    const table = document.querySelector('.admin-table-wrap table');
    const navs = [...document.querySelectorAll('.admin-nav-link')];
    const sidebarStyle = getComputedStyle(sidebar);
    const headerStyle = getComputedStyle(header);
    return {
      loggedInVisible: getComputedStyle(document.querySelector('#admin-layout')).display !== 'none',
      navCount: navs.length,
      activeNav: document.querySelector('.admin-nav-link.active')?.innerText.trim().replace(/\\s+/g, ' '),
      sidebarWidth: sidebarStyle.width,
      sidebarPosition: sidebarStyle.position,
      headerPosition: headerStyle.position,
      tableMinWidth: table ? getComputedStyle(table).minWidth : null,
      bodyOverflowX: getComputedStyle(document.body).overflowX,
    };
  })()`);

  await cdp.send('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 2, mobile: true });
  await wait(500);
  const mobile = await evalJs(cdp, `new Promise(resolve => {
    const hamburger = document.querySelector('.admin-hamburger');
    const sidebar = document.querySelector('.admin-sidebar');
    const backdrop = document.querySelector('.admin-sidebar-backdrop');
    hamburger?.click();
    setTimeout(() => {
      const open = sidebar?.classList.contains('mobile-open');
      const backdropShown = backdrop?.classList.contains('show');
      document.querySelector('.admin-nav-link[data-panel="calendar"]')?.click();
      setTimeout(() => {
        resolve({
          hamburgerVisible: hamburger ? getComputedStyle(hamburger).display : null,
          opened: open,
          backdropShown,
          closedAfterNav: !sidebar?.classList.contains('mobile-open'),
          bodyLockedAfterOpen: document.body.classList.contains('admin-nav-open'),
          activePanel: document.querySelector('.admin-panel.active')?.id || '',
        });
      }, 400);
    }, 400);
  })`, true);

  const result = { baseUrl: BASE_URL, login, desktop, mobile, consoleErrors, pageErrors };
  console.log(JSON.stringify(result, null, 2));
  cdp.close();
  if (!desktop.loggedInVisible || !mobile.opened || !mobile.backdropShown || !mobile.closedAfterNav || consoleErrors.length || pageErrors.length) {
    process.exitCode = 1;
  }
}

main().catch(e => {
  console.error(e.stack || e.message);
  process.exit(1);
});
