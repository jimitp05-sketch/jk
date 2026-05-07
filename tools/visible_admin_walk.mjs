import http from 'node:http';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const CDP_URL = process.argv[3] || 'http://127.0.0.1:9223';
const PASSWORD = process.argv[4] || 'admin';

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

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));

class CDP {
  constructor(wsUrl) {
    this.ws = new WebSocket(wsUrl);
    this.nextId = 1;
    this.pending = new Map();
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
    });
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
}

async function evalJs(cdp, expression, awaitPromise = false) {
  const result = await cdp.send('Runtime.evaluate', { expression, awaitPromise, returnByValue: true });
  if (result.exceptionDetails) throw new Error(result.exceptionDetails.text || 'Runtime exception');
  return result.result?.value;
}

async function getVisibleTarget() {
  const targets = await requestJson(`${CDP_URL}/json`);
  const admin = targets.find(t => t.type === 'page' && t.url.includes('/admin.php')) || targets.find(t => t.type === 'page');
  if (!admin) throw new Error('No debuggable Chrome page found.');
  return admin.webSocketDebuggerUrl;
}

async function main() {
  const cdp = new CDP(await getVisibleTarget());
  await cdp.open();
  await cdp.send('Page.enable');
  await cdp.send('Runtime.enable');

  await cdp.send('Page.navigate', { url: `${BASE_URL}/admin.php` });
  await wait(2500);

  const login = await evalJs(cdp, `new Promise(resolve => {
    const setValue = (el, value) => {
      if (!el) return;
      el.focus();
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    };
    setValue(document.querySelector('#admin-user'), 'admin');
    setValue(document.querySelector('#admin-pass'), ${JSON.stringify(PASSWORD)});
    const btn = [...document.querySelectorAll('button')].find(b => /login|sign/i.test(b.innerText)) || document.querySelector('button');
    if (btn) btn.click();
    setTimeout(() => resolve(document.body.innerText.slice(0, 500)), 3500);
  })`, true);
  console.log('LOGIN_VIEW', login.replace(/\\s+/g, ' ').slice(0, 200));

  const labels = await evalJs(cdp, `(() => [...document.querySelectorAll('.sidebar-item, [data-panel]')]
    .filter(el => (el.innerText || '').trim())
    .map((el, i) => ({ i, label: el.innerText.trim().replace(/\\s+/g, ' ') }))
    .slice(0, 30))()`);
  console.log('PANELS', labels.map(x => x.label).join(' | '));

  for (const { i, label } of labels) {
    console.log('OPEN', label);
    await evalJs(cdp, `(() => {
      const els = [...document.querySelectorAll('.sidebar-item, [data-panel]')].filter(el => (el.innerText || '').trim());
      const el = els[${i}];
      if (el) el.click();
    })()`);
    await wait(2000);
    await evalJs(cdp, `window.scrollTo(0, Math.min(600, document.documentElement.scrollHeight))`);
    await wait(1000);
    await evalJs(cdp, `window.scrollTo(0, 0)`);
    await wait(500);
  }
}

main().catch(err => {
  console.error(err.stack || err.message);
  process.exit(1);
});
