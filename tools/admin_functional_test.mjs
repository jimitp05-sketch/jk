import fs from 'node:fs/promises';

const BASE_URL = process.argv[2] || 'https://foxwisdom.com';
const PASSWORD = process.argv[3] || 'admin';
const OUT_FILE = new URL('./admin-functional-report.json', import.meta.url);

const results = [];

function add(name, status, detail = {}) {
  results.push({ name, status, detail });
  const icon = status === 'pass' ? '[PASS]' : status === 'warn' ? '[WARN]' : '[FAIL]';
  console.log(icon, name, JSON.stringify(detail));
}

async function api(path, options = {}) {
  const res = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers: {
      ...(options.body && !(options.body instanceof FormData) ? { 'Content-Type': 'application/json' } : {}),
      ...(options.headers || {}),
    },
  });
  const text = await res.text();
  let json = null;
  try { json = text ? JSON.parse(text) : null; } catch {}
  return { res, text, json };
}

function okJson(resp) {
  return resp.res.ok && resp.json && resp.json.success !== false;
}

async function login() {
  const resp = await api('/api/settings.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'check_auth', admin_pass: PASSWORD }),
  });
  if (!resp.res.ok || !resp.json?.session_token) {
    throw new Error(`Admin login failed: ${resp.res.status} ${resp.text.slice(0, 200)}`);
  }
  add('Admin login admin/admin', 'pass', { status: resp.res.status, user: resp.json.user });
  return resp.json.session_token;
}

async function getContent(type, token) {
  const resp = await api(`/api/content.php?type=${encodeURIComponent(type)}`, {
    headers: token ? { 'X-Admin-Token': token } : {},
  });
  if (!resp.res.ok) throw new Error(`GET content ${type} failed ${resp.res.status}: ${resp.text.slice(0, 200)}`);
  return Array.isArray(resp.json?.data) || typeof resp.json?.data === 'object' ? resp.json.data : [];
}

async function setContent(type, items, token) {
  const resp = await api('/api/content.php', {
    method: 'POST',
    body: JSON.stringify({ session_token: token, type, items }),
  });
  if (!okJson(resp)) throw new Error(`POST content ${type} failed ${resp.res.status}: ${resp.text.slice(0, 200)}`);
  return resp.json;
}

async function testContentRoundTrip(type, item, token) {
  const original = await getContent(type, token);
  const originalItems = Array.isArray(original) ? original : original || {};
  try {
    if (Array.isArray(originalItems)) {
      const testItems = [...originalItems, item];
      await setContent(type, testItems, token);
      const after = await getContent(type, token);
      const found = Array.isArray(after) && after.some(x => x.id === item.id);
      add(`CMS round-trip: ${type}`, found ? 'pass' : 'fail', { before: originalItems.length, after: Array.isArray(after) ? after.length : 'non-array' });
    } else {
      const testObj = { ...originalItems, ...item };
      await setContent(type, testObj, token);
      const after = await getContent(type, token);
      const found = after && Object.keys(item).every(k => after[k] === item[k]);
      add(`CMS round-trip: ${type}`, found ? 'pass' : 'fail', { keys: Object.keys(item) });
    }
  } finally {
    await setContent(type, originalItems, token);
  }
}

async function testPublicReviewModeration(token) {
  const original = await getContent('peer_recognitions', token);
  try {
    const resp = await api('/api/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'submit_review',
        author: 'Codex Functional Test',
        platform: 'others',
        body: `Automated moderation test ${Date.now()}`,
      }),
    });
    const id = resp.json?.data?.id;
    const adminItems = await getContent('peer_recognitions', token);
    const publicItems = await getContent('peer_recognitions', '');
    const inAdmin = adminItems.some(x => x.id === id && x.status === 'pending');
    const inPublic = publicItems.some(x => x.id === id);
    add('Public review submission enters admin pending queue only', inAdmin && !inPublic ? 'pass' : 'fail', { id, inAdmin, inPublic, status: resp.res.status });
  } finally {
    await setContent('peer_recognitions', original, token);
  }
}

async function testPublicPhotoModeration(token) {
  const original = await getContent('photo_wall', token);
  try {
    const png1x1 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';
    const upload = await api('/api/upload_photo.php', {
      method: 'POST',
      body: JSON.stringify({ context: 'photo_wall', photo_data: png1x1 }),
    });
    if (!okJson(upload) || !upload.json?.url) {
      add('Public photo upload', 'fail', { status: upload.res.status, body: upload.text.slice(0, 200) });
      return;
    }
    const submit = await api('/api/content.php', {
      method: 'POST',
      body: JSON.stringify({
        action: 'submit_photo',
        url: upload.json.url,
        name: 'Codex Functional Test',
        caption: `Automated photo moderation test ${Date.now()}`,
        label: 'General',
      }),
    });
    const id = submit.json?.data?.id;
    const adminItems = await getContent('photo_wall', token);
    const publicItems = await getContent('photo_wall', '');
    const inAdmin = adminItems.some(x => x.id === id && x.status === 'pending');
    const inPublic = publicItems.some(x => x.id === id);
    add('Public photo submission enters admin pending queue only', inAdmin && !inPublic ? 'pass' : 'fail', { id, inAdmin, inPublic, uploadUrl: upload.json.url });
  } finally {
    await setContent('photo_wall', original, token);
  }
}

async function testBookingFlow(token) {
  const csrf = await api('/api/settings.php?action=csrf_token');
  const tokenValue = csrf.json?.csrf_token;
  if (!tokenValue) {
    add('Booking CSRF token', 'fail', { status: csrf.res.status });
    return;
  }

  const bookingDate = '2026-12-31';
  const bookingTime = `15:${String(Math.floor(Math.random() * 50)).padStart(2, '0')}`;
  const booking = await api('/api/booking.php', {
    method: 'POST',
    body: JSON.stringify({
      name: 'Codex Test Patient',
      phone: '9876543210',
      email: 'codex-test@example.com',
      preferred_date: bookingDate,
      preferred_slot: bookingTime,
      reason: 'Automated functional test. Safe to cancel.',
      csrf_token: tokenValue,
    }),
  });
  if (!okJson(booking)) {
    add('Booking create', 'fail', { status: booking.res.status, body: booking.text.slice(0, 250) });
    return;
  }
  const id = booking.json?.data?.booking_id;
  const list = await api('/api/get_bookings.php?action=list&limit=100', { headers: { 'X-Admin-Token': token } });
  const found = (list.json?.bookings || []).some(b => Number(b.id) === Number(id));
  const confirm = await api(`/api/get_bookings.php?action=update_status&id=${id}&status=confirmed`, { headers: { 'X-Admin-Token': token } });
  const cancel = await api(`/api/get_bookings.php?action=update_status&id=${id}&status=cancelled`, { headers: { 'X-Admin-Token': token } });
  add('Booking create/list/confirm/cancel', found && okJson(confirm) && okJson(cancel) ? 'pass' : 'fail', {
    id, found, confirm: confirm.res.status, cancel: cancel.res.status, date: bookingDate, time: bookingTime,
  });
}

async function testSubscribers(token) {
  const email = `codex-test-${Date.now()}@example.com`;
  const sub = await api('/api/subscribers.php', {
    method: 'POST',
    body: JSON.stringify({ email, name: 'Codex Test', source: 'functional_test' }),
  });
  const list = await api('/api/subscribers.php', { headers: { 'X-Admin-Token': token } });
  if (!list.res.ok) {
    add('Subscribers public subscribe/admin list', 'fail', { subscribeStatus: sub.res.status, listStatus: list.res.status, listBody: list.text.slice(0, 250) });
    return;
  }
  const match = (list.json?.data || []).find(s => s.email === email);
  if (match?.id) {
    await api('/api/subscribers.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'delete', id: match.id, session_token: token }),
    });
  }
  add('Subscribers public subscribe/admin list/delete cleanup', match ? 'pass' : 'fail', { subscribeStatus: sub.res.status, found: !!match });
}

async function testDiya(token) {
  const name = `Codex Test Diya ${Date.now()}`;
  const light = await api('/api/diya.php', {
    method: 'POST',
    body: JSON.stringify({ action: 'light', name, prayer: 'Automated functional test prayer.', lit_by: 'Codex' }),
  });
  const admin = await api('/api/diya.php?action=admin', { headers: { 'X-Admin-Token': token } });
  const item = (admin.json?.data || []).find(d => d.name === name);
  if (item?.id) {
    await api('/api/diya.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'delete', id: item.id, session_token: token }),
    });
  }
  add('Diya public light/admin visible/delete cleanup', okJson(light) && !!item ? 'pass' : 'fail', { lightStatus: light.res.status, found: !!item });
}

async function testMemories(token) {
  const title = `Codex Test Story ${Date.now()}`;
  const submit = await api('/api/memories.php', {
    method: 'POST',
    body: JSON.stringify({
      action: 'submit',
      type: 'healing_stories',
      title,
      patient_name: 'Codex Patient',
      family_name: 'Codex Family',
      relationship: 'Test',
      duration: '1 day',
      story: 'Automated functional test story. Safe to delete.',
    }),
  });
  const admin = await api('/api/memories.php?type=healing_stories&action=admin', { headers: { 'X-Admin-Token': token } });
  const item = (admin.json?.data || []).find(s => s.title === title);
  if (item?.id) {
    await api('/api/memories.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'delete', type: 'healing_stories', id: item.id, session_token: token }),
    });
  }
  add('Memories public story/admin visible/delete cleanup', okJson(submit) && !!item ? 'pass' : 'fail', { submitStatus: submit.res.status, found: !!item });
}

async function testSettings(token) {
  const before = await api('/api/settings.php', { headers: { 'X-Admin-Token': token } });
  const original = before.json?.wa_message || 'Hello, I would like to consult Dr. Jay Kothari';
  const changed = `${original} `;
  const update = await api('/api/settings.php', {
    method: 'POST',
    body: JSON.stringify({ session_token: token, wa_message: changed }),
  });
  const restore = await api('/api/settings.php', {
    method: 'POST',
    body: JSON.stringify({ session_token: token, wa_message: original }),
  });
  add('Settings update/restore', okJson(update) && okJson(restore) ? 'pass' : 'fail', { update: update.res.status, restore: restore.res.status });
}

async function main() {
  const token = await login();

  const contentCases = [
    ['faq_items', { id: `codex_faq_${Date.now()}`, q: 'Codex test FAQ?', a: 'Automated test answer.', status: 'draft' }],
    ['myth_busters', { id: `codex_myth_${Date.now()}`, statement: 'Codex test myth', fact: 'Automated test fact.', source: 'Codex', status: 'draft' }],
    ['quiz_questions', { id: `codex_quiz_${Date.now()}`, q: 'Codex test question?', options: ['A', 'B'], answer: 0, explanation: 'Automated test.', status: 'draft' }],
    ['research_papers', { id: `codex_research_${Date.now()}`, title: 'Codex test paper', topic: 'Testing', journal: 'Codex', year: 2026, status: 'draft' }],
    ['knowledge_articles', { id: `codex_article_${Date.now()}`, title: 'Codex test article', pillar: 'Testing', subtitle: 'Automated test.', body: '<p>Automated test article.</p>', status: 'draft' }],
    ['expertise_items', { id: `codex_expertise_${Date.now()}`, title: 'Codex Expertise', text: 'Automated test.', icon: 'T', status: 'draft' }],
    ['institute_recognitions', { id: `codex_inst_${Date.now()}`, title: 'Codex Institute', text: 'Automated test.', status: 'draft' }],
    ['media_mentions', { id: `codex_media_${Date.now()}`, title: 'Codex Media', outlet: 'Codex', url: 'https://foxwisdom.com', status: 'draft' }],
    ['blocked_dates', { date: '2026-12-30', reason: 'Codex automated test' }],
    ['social_settings', { codex_test_marker: `marker_${Date.now()}` }],
  ];

  for (const [type, item] of contentCases) {
    try { await testContentRoundTrip(type, item, token); }
    catch (e) { add(`CMS round-trip: ${type}`, 'fail', { error: e.message }); }
  }

  for (const fn of [testPublicReviewModeration, testPublicPhotoModeration, testBookingFlow, testSubscribers, testDiya, testMemories, testSettings]) {
    try { await fn(token); }
    catch (e) { add(fn.name, 'fail', { error: e.message }); }
  }

  const summary = {
    baseUrl: BASE_URL,
    generatedAt: new Date().toISOString(),
    total: results.length,
    pass: results.filter(r => r.status === 'pass').length,
    warn: results.filter(r => r.status === 'warn').length,
    fail: results.filter(r => r.status === 'fail').length,
    results,
  };
  await fs.writeFile(OUT_FILE, JSON.stringify(summary, null, 2), 'utf8');
  console.log('REPORT', OUT_FILE.pathname);
  if (summary.fail > 0) process.exitCode = 1;
}

main().catch(async e => {
  add('Fatal test runner error', 'fail', { error: e.stack || e.message });
  await fs.writeFile(OUT_FILE, JSON.stringify({ results }, null, 2), 'utf8');
  process.exit(1);
});
