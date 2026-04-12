// ============================================================
// AUTH
// ============================================================
const ADMIN_USER = 'admin';
const ADMIN_PASS = 'apollo2024';

async function doLogin() {
    const u = document.getElementById('admin-user').value.trim();
    const p = document.getElementById('admin-pass').value.trim();
    const err = document.getElementById('login-error');
    if (!u || !p) { err.textContent = 'Enter username and password'; err.style.display = 'block'; return; }

    try {
        const r = await fetch('./api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_auth', admin_pass: p })
        });
        if (!r.ok) throw new Error(`HTTP ${r.status} ${r.statusText}`);
        const d = await r.json();
        if (d.success) {
            localStorage.setItem('apollo_admin_logged', 'true');
            localStorage.setItem('apollo_admin_temp_pass', p);
            document.getElementById('login-screen').style.display = 'none';
            document.getElementById('admin-layout').style.display = 'flex';
            loadAll();
        } else {
            err.textContent = 'Invalid credentials'; err.style.display = 'block';
        }
    } catch (e) {
        console.error('Auth Error:', e);
        err.innerHTML = `Server error: ${e.message}<br><small>Check if <b>api/settings.php</b> was uploaded to Hostinger.</small>`;
        err.style.display = 'block';
    }
}
document.getElementById('admin-pass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
document.getElementById('admin-user').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('admin-pass').focus(); });

function doLogout() {
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('admin-layout').style.display = 'none';
    document.getElementById('admin-user').value = '';
    document.getElementById('admin-pass').value = '';
}

// ============================================================
// SITE SETTINGS â€” reads/writes via /api/settings.php
// ============================================================
async function loadCurrentSettings() {
    try {
        const r = await fetch('./api/settings.php');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const s = await r.json();

        document.getElementById('st-wa-number').value = s.wa_number || '';
        document.getElementById('st-icu-phone').value = s.icu_phone || '';
        document.getElementById('st-wa-message').value = s.wa_message || '';
        document.getElementById('st-site-name').value = s.site_name || '';
        document.getElementById('st-hero-title').value = s.hero_title || '';
        document.getElementById('st-hero-desc').value = s.hero_desc || '';
        document.getElementById('st-hero-badge').value = s.hero_badge || '';
        document.getElementById('st-hero-img').value = s.hero_img || '';
        document.getElementById('st-hero-stat1-val').value = s.hero_stat1_val || '';
        document.getElementById('st-hero-stat1-lbl').value = s.hero_stat1_lbl || '';
        document.getElementById('st-hero-stat2-val').value = s.hero_stat2_val || '';
        document.getElementById('st-hero-stat2-lbl').value = s.hero_stat2_lbl || '';
        document.getElementById('st-hero-stat3-val').value = s.hero_stat3_val || '';
        document.getElementById('st-hero-stat3-lbl').value = s.hero_stat3_lbl || '';
        document.getElementById('st-admin-user').value = s.admin_user || 'admin';

        // We don't pre-fill the password for security reasons (it would show up as dots),
        // but we'll show a placeholder indicating it's not required to save.
        document.getElementById('st-admin-pass').placeholder = 'â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢ (Leave blank to keep same)';
        document.getElementById('st-admin-pass').value = '';

    } catch (err) { console.error('Failed to load settings'); }
}

async function saveSettings() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    const g = id => document.getElementById(id).value;
    const body = {
        admin_pass: pass,
        wa_number: g('st-wa-number'),
        wa_message: g('st-wa-message'),
        icu_phone: g('st-icu-phone'),
        site_name: g('st-site-name'),
        hero_title: g('st-hero-title'),
        hero_desc: g('st-hero-desc'),
        hero_badge: g('st-hero-badge'),
        hero_img: g('st-hero-img'),
        hero_stat1_val: g('st-hero-stat1-val'),
        hero_stat1_lbl: g('st-hero-stat1-lbl'),
        hero_stat2_val: g('st-hero-stat2-val'),
        hero_stat2_lbl: g('st-hero-stat2-lbl'),
        hero_stat3_val: g('st-hero-stat3-val'),
        hero_stat3_lbl: g('st-hero-stat3-lbl'),
        admin_user: g('st-admin-user')
    };
    const newPass = g('st-admin-pass');
    if (newPass) body.new_admin_pass = newPass;

    try {
        const res = await fetch('./api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        const data = await res.json();
        if (data.success) {
            toast('âœ… Settings updated successfully!', 'success');
            if (newPass) localStorage.setItem('apollo_admin_temp_pass', newPass);
            loadCurrentSettings(); // Sync local fields
            loadHeroContent();    // Sync hero panel too
        } else {
            toast('âŒ Save failed: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (err) {
        toast('âŒ Connection error: ' + err.message, 'error');
    }
}

// ============================================================
// PANEL SWITCHING
// ============================================================
function switchPanel(name) {
    document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-nav-link').forEach(l => l.classList.remove('active'));
    document.getElementById('panel-' + name).classList.add('active');
    document.querySelector(`[data-panel="${name}"]`)?.classList.add('active');
    if (name === 'requests') renderRequests();
    if (name === 'calendar') renderAdminCal();
    if (name === 'knowledge') renderKnowledge();
    if (name === 'settings') loadCurrentSettings();

    if (name === 'reviews') renderReviews();
    if (name === 'photos') renderPhotos();
    if (name === 'myths') renderMyths();
    if (name === 'quizeditor') renderQuizEditor();
    if (name === 'research') renderResearch();
    if (name === 'images') { /* Just stay current */ }

}

// ============================================================
// DATA HELPERS (Now syncing with MySQL)
// ============================================================
let serverBookingsCache = [];

async function getBookingsFromServer() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch('./api/get_bookings.php', {
            headers: { 'X-Admin-Token': pass }
        });
        const data = await res.json();
        if (data.success) {
            serverBookingsCache = data.bookings || [];
            return serverBookingsCache;
        }
        return [];
    } catch (e) {
        console.error('Failed to fetch bookings:', e);
        return [];
    }
}

// BACKWARD COMPAT: Map server bookings to expected local format if needed
const getBookings = () => serverBookingsCache;
const saveBookings = async (id, status) => {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch(`./api/get_bookings.php?action=update_status&id=${id}&status=${status}`, {
            headers: { 'X-Admin-Token': pass }
        });
        const data = await res.json();
        return data.success;
    } catch (e) {
        toast('Failed to update status', 'error');
        return false;
    }
};

const getPendingReviews = () => { try { return JSON.parse(localStorage.getItem('apollo_pending_reviews') || '[]'); } catch { return []; } };
const savePendingReviews = d => localStorage.setItem('apollo_pending_reviews', JSON.stringify(d));
const getAdminArticles = () => { console.warn('getAdminArticles is deprecated. Use fetch instead.'); return []; };
const saveAdminArticles = () => { console.warn('saveAdminArticles is deprecated. Use fetch instead.'); };
const getAdminPhotos = () => { try { return JSON.parse(localStorage.getItem('apollo_admin_photos') || '[]'); } catch { return []; } };
const saveAdminPhotos = d => localStorage.setItem('apollo_admin_photos', JSON.stringify(d));

async function updateBadges() {
    const bookings = await getBookingsFromServer();
    const pb = bookings.filter(b => b.status === 'pending').length;

    // Reviews and Photos still use localStorage for now as they don't have a DB table yet
    const pr = getPendingReviews().filter(r => r.status === 'pending').length;
    const pp = getPendingPhotos().filter(p => p.status === 'pending').length;

    const bReq = document.getElementById('badge-requests');
    const bRev = document.getElementById('badge-reviews');
    const bPho = document.getElementById('badge-photos');

    if (bReq) { bReq.textContent = pb; bReq.style.display = pb > 0 ? '' : 'none'; }
    if (bRev) { bRev.textContent = pr; bRev.style.display = pr > 0 ? '' : 'none'; }
    if (bPho) { bPho.textContent = pp; bPho.style.display = pp > 0 ? '' : 'none'; }
}

// Pending photo submissions from reviews.html upload form
const getPendingPhotos = () => { try { return JSON.parse(localStorage.getItem('apollo_pending_photos') || '[]'); } catch { return []; } };
const savePendingPhotos = d => localStorage.setItem('apollo_pending_photos', JSON.stringify(d));

// ============================================================
// DASHBOARD
// ============================================================
async function renderDashboard() {
    const bookings = await getBookingsFromServer();
    document.getElementById('stat-bookings').textContent = bookings.length;
    document.getElementById('stat-pending-books').textContent = bookings.filter(b => b.status === 'pending').length;
    document.getElementById('stat-pending-reviews').textContent = getPendingReviews().filter(r => r.status === 'pending').length;
    const recent = bookings.slice(0, 5); // Already sorted by created_at DESC from server
    const tbody = document.getElementById('dashboard-recent-bookings');
    if (recent.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No bookings yet.</td></tr>';
    } else {
        tbody.innerHTML = recent.map(b => `<tr>
        <td><strong>${b.name}</strong><br><span style="font-size:0.75rem;color:var(--text-muted);">${b.phone}</span></td>
        <td>${b.booking_date}</td><td>${b.booking_time}</td>
        <td><span style="font-size:0.83rem;">${b.reason}</span></td>
        <td><span class="status-badge badge-${b.status}">${b.status}</span></td>
      </tr>`).join('');
    }
    updateBadges();
}

// ============================================================
// REQUESTS
// ============================================================
async function renderRequests() {
    const bookings = await getBookingsFromServer();
    const tbody = document.getElementById('requests-table');
    if (bookings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:28px;">No booking requests yet.</td></tr>';
        return;
    }
    tbody.innerHTML = bookings.map((b, i) => `<tr>
      <td><strong>${b.name}</strong><br><span style="font-size:0.75rem;color:var(--text-muted);">${b.phone}</span></td>
      <td>${b.phone}</td>
      <td>${b.booking_date}<br><span style="color:var(--accent-primary);font-weight:700;">${b.booking_time}</span></td>
      <td style="max-width:200px;">${b.reason}</td>
      <td><span class="status-badge badge-${b.status}">${b.status}</span></td>
      <td><div class="action-btns">
        ${b.status === 'pending' ? `<button class="action-btn action-btn-approve" onclick="updateBookingStatus(${b.id},'confirmed')">✓ Confirm</button>
        <button class="action-btn action-btn-reject" onclick="updateBookingStatus(${b.id},'cancelled')">✕ Reject</button>` : ''}
        ${b.status === 'confirmed' ? `<button class="action-btn action-btn-reject" onclick="updateBookingStatus(${b.id},'cancelled')">Cancel</button>` : ''}
      </div></td>
    </tr>`).join('');
    updateBadges();
}

async function updateBookingStatus(id, status) {
    const ok = await saveBookings(id, status);
    if (ok) {
        renderRequests();
        renderDashboard();
    }
}

// ============================================================
// ADMIN CALENDAR
// ============================================================
let adminCalDate = new Date();
let adminSelectedDate = null;
const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function adminCalNav(dir) {
    adminCalDate.setMonth(adminCalDate.getMonth() + dir);
    renderAdminCal();
}

async function renderAdminCal() {
    const year = adminCalDate.getFullYear();
    const month = adminCalDate.getMonth();
    document.getElementById('admin-cal-month').textContent = `${MONTH_NAMES[month]} ${year}`;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    const bookings = await getBookingsFromServer();
    const confirmedBookings = bookings.filter(b => b.status === 'confirmed');

    const bookedDates = {};
    confirmedBookings.forEach(b => {
        const d = b.booking_date; // YYYY-MM-DD
        bookedDates[d] = (bookedDates[d] || 0) + 1;
    });

    const today = new Date(); today.setHours(0, 0, 0, 0);

    let html = '';
    ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'].forEach(d => html += `<div class="mini-cal-day label">${d}</div>`);
    for (let i = 0; i < firstDay; i++) html += `<div class="mini-cal-day empty"></div>`;
    for (let day = 1; day <= daysInMonth; day++) {
        const d = new Date(year, month, day);
        d.setHours(0, 0, 0, 0);
        const dateKey = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const isSun = d.getDay() === 0;
        const isToday = d.getTime() === today.getTime();
        const hasBooking = bookedDates[dateKey] > 0;
        let cls = 'mini-cal-day';
        if (isSun) cls += ' sunday';
        if (isToday) cls += ' today';
        else if (hasBooking) cls += ' has-booking';
        html += `<div class="${cls}" onclick="adminSelectDate('${dateKey}','${parseInt(day)} ${MONTH_NAMES[month]} ${year}')">${day}</div>`;
    }
    document.getElementById('admin-cal-grid').innerHTML = html;
}

function adminSelectDate(dateKey, label) {
    adminSelectedDate = dateKey;
    document.getElementById('selected-day-label').textContent = label;
    const bookings = serverBookingsCache.filter(b => b.booking_date === dateKey && b.status === 'confirmed');
    const el = document.getElementById('day-booking-list');
    if (bookings.length === 0) {
        el.innerHTML = '<div class="empty-bookings">No confirmed bookings for this date.</div>';
    } else {
        el.innerHTML = bookings.map(b => `<div class="booking-list-item">
        <div class="booking-time">${b.booking_time}</div>
        <div><div class="booking-patient">${b.name}</div><div class="booking-reason-sm">${b.reason}</div></div>
        <span class="status-badge badge-approved">Confirmed</span>
      </div>`).join('');
    }
}

// ============================================================
// KNOWLEDGE
// ============================================================
const BUILT_IN_ARTICLES = [
    { id: 'sepsis', title: 'The Silent Killer: Recognising Sepsis Before It\'s Too Late', pillar: 'Sepsis & Infection', status: 'published' },
    { id: 'ecmo', title: 'ECMO Explained: When Modern Medicine Buys Time', pillar: 'ECMO & Advanced Life Support', status: 'published' },
    { id: 'crrt', title: 'Kidney Failure in the ICU: CRRT and How It Saves Lives', pillar: 'Organ Failure & CRRT', status: 'published' },
    { id: 'ventilation', title: 'Mechanical Ventilation: What the Machine Is Doing and Why', pillar: 'Respiratory & Ventilation', status: 'published' },
    { id: 'mof', title: 'Multi-Organ Failure: When the Body\'s Systems Begin to Shut Down', pillar: 'Multi-Organ Failure', status: 'published' },
    { id: 'family-guide', title: 'Your Loved One Is in the ICU: A Family\'s Complete Guide', pillar: 'Family Guide to the ICU', status: 'published' },
    { id: 'delirium', title: 'ICU Delirium & Brain Health: Why Critical Illness Affects the Mind', pillar: 'ICU Delirium & Brain Health', status: 'published' },
    { id: 'nutrition', title: 'Critical Care Nutrition: Feeding as a Life-Saving Therapy', pillar: 'Critical Care Nutrition', status: 'published' },
    { id: 'antimicrobial', title: 'Infection Control & Antimicrobial Stewardship in the ICU', pillar: 'Infection Control & AMR', status: 'published' },
    { id: 'pics', title: 'Post-ICU Recovery & PICS: Life After the Intensive Care Unit', pillar: 'Post-ICU Recovery & PICS', status: 'published' },
];

let currentArticles = []; // Global state â€” populated by renderKnowledge()

async function renderKnowledge() {
    const tbody = document.getElementById('knowledge-table');
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">â³ Loading articles...</td></tr>`;

    let serverArticles = [];
    try {
        const res = await fetch('./api/content.php?type=knowledge_articles');
        const json = await res.json();
        const data = json.data ?? json;
        serverArticles = Array.isArray(data) ? data : [];
    } catch (e) {
        console.warn('Could not load server knowledge articles:', e);
    }

    // Merge: built-ins first, then server-added articles (those with art- prefix)
    // If a built-in was overridden by the editor (overridesId matches), prefer server version
    const overrideIds = new Set(serverArticles.filter(a => a.overridesId).map(a => a.overridesId));
    const builtInsToShow = BUILT_IN_ARTICLES.filter(b => !overrideIds.has(b.id));
    currentArticles = [...serverArticles, ...builtInsToShow.map(b => ({ ...b, _builtIn: true }))];

    // Sort: server-saved (non-built-in) first, then built-ins
    currentArticles.sort((a, b) => {
        if (a._builtIn && !b._builtIn) return 1;
        if (!a._builtIn && b._builtIn) return -1;
        return 0;
    });

    // Update header count
    const countEl = document.querySelector('#panel-knowledge .table-title');
    if (countEl) countEl.textContent = `All Articles (${currentArticles.length})`;

    if (currentArticles.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:24px;color:var(--text-muted);">No articles yet. Click + New Article to create one.</td></tr>`;
        return;
    }

    tbody.innerHTML = currentArticles.map(a => `<tr>
                <td style="max-width:280px;"><strong style="font-size:0.88rem;">${a.title}</strong>${a._builtIn ? ' <span style="font-size:0.7rem;color:var(--text-muted);font-weight:400;">(built-in)</span>' : ''}</td>
                <td><span style="font-size:0.8rem;color:var(--accent-secondary);">${a.pillar || 'â€”'}</span></td>
                <td><span class="status-badge badge-${a.status || 'published'}">${a.status || 'published'}</span></td>
                <td><div class="action-btns">
                    <button class="action-btn action-btn-edit" onclick="${a._builtIn ? `editBuiltInArticle('${a.id}')` : `editArticle('${a.id}')`}">Edit</button>
                    ${!a._builtIn ? `<button class="action-btn action-btn-delete" onclick="deleteArticle('${a.id}')">Delete</button>` : ''}
                    <a href="knowledge.html#${a.overridesId || a.id}" target="_blank" class="action-btn" style="padding:5px 10px;border:1.5px solid var(--ad-border);border-radius:var(--radius-sm);font-size:0.76rem;font-weight:700;color:var(--text-secondary);text-decoration:none;">View â†—</a>
                </div></td>
            </tr>`).join('');
}


// ============================================================
// SECTION BUILDER HELPERS
// ============================================================
function addSection(data) {
    data = data || {};
    const container = document.getElementById('sections-builder');
    const idx = container.children.length + 1;
    const div = document.createElement('div');
    div.className = 'section-card';
    div.innerHTML = `
                <div class="section-card-header">
                    <span>Section ${idx}</span>
                    <button type="button" class="action-btn action-btn-delete" style="padding:3px 10px;" onclick="this.closest('.section-card').remove();renumberSections();">âœ• Remove</button>
                </div>
                <div class="editor-field">
                    <label>Section Heading</label>
                    <input type="text" class="sec-heading" placeholder="e.g. What Is Sepsis?" />
                </div>
                <div class="editor-field">
                    <label>Section Content <span style="font-weight:400;color:var(--text-muted);">(plain text â€” no HTML needed)</span></label>
                    <textarea class="sec-content" rows="4" placeholder="Write the section content here in plain English..."></textarea>
                </div>
                <div class="editor-field">
                    <label>💡 Callout Box <span style="font-weight:400;color:var(--text-muted);">(optional â€” highlighted insight)</span></label>
                    <input type="text" class="sec-callout" placeholder="e.g. Key Definition: Sepsis is life-threatening organ dysfunction..." />
                </div>
                <div class="editor-field" style="margin-bottom:0;">
                    <label>⚠️ Warning Note <span style="font-weight:400;color:var(--text-muted);">(optional â€” important caution)</span></label>
                    <input type="text" class="sec-warning" placeholder="e.g. Do NOT give IV fluids without dynamic assessment..." />
                </div>`;
    container.appendChild(div);
    if (data.heading) div.querySelector('.sec-heading').value = data.heading;
    if (data.content) div.querySelector('.sec-content').value = data.content;
    if (data.callout) div.querySelector('.sec-callout').value = data.callout;
    if (data.warning) div.querySelector('.sec-warning').value = data.warning;
}

function renumberSections() {
    document.querySelectorAll('#sections-builder .section-card').forEach((card, i) => {
        card.querySelector('.section-card-header span').textContent = `Section ${i + 1}`;
    });
}

function clearSectionsOnly() {
    document.getElementById('sections-builder').innerHTML = '';
    for (let i = 1; i <= 3; i++) {
        document.getElementById(`stat${i}-num`).value = '';
        document.getElementById(`stat${i}-lbl`).value = '';
    }
}

function loadStructured(structured) {
    clearSectionsOnly();
    (structured.sections || []).forEach(s => addSection(s));
    const stats = structured.stats || [];
    for (let i = 0; i < 3; i++) {
        document.getElementById(`stat${i + 1}-num`).value = (stats[i] && stats[i].num) || '';
        document.getElementById(`stat${i + 1}-lbl`).value = (stats[i] && stats[i].lbl) || '';
    }
}

function getStructuredData() {
    const sections = [];
    document.querySelectorAll('#sections-builder .section-card').forEach(card => {
        sections.push({
            heading: card.querySelector('.sec-heading').value.trim(),
            content: card.querySelector('.sec-content').value.trim(),
            callout: card.querySelector('.sec-callout').value.trim(),
            warning: card.querySelector('.sec-warning').value.trim()
        });
    });
    const stats = [];
    for (let i = 1; i <= 3; i++) {
        const num = document.getElementById(`stat${i}-num`).value.trim();
        const lbl = document.getElementById(`stat${i}-lbl`).value.trim();
        if (num) stats.push({ num, lbl });
    }
    return { sections, stats };
}

function buildArticleHTML(structured) {
    let html = '';
    const stats = structured.stats || [];
    if (stats.length > 0) {
        html += '<div class="article-stats-row">';
        stats.forEach(s => { html += `<div class="article-stat-card"><div class="num">${s.num}</div><div class="lbl">${s.lbl}</div></div>`; });
        html += '</div>';
    }
    (structured.sections || []).forEach(s => {
        if (s.heading) html += `<h3>${s.heading}</h3>`;
        if (s.content) {
            const paras = s.content.split(/\n\n+/).map(p => p.replace(/\n/g, '<br>'));
            html += paras.map(p => `<p>${p}</p>`).join('');
        }
        if (s.callout) html += `<div class="article-callout"><p>${s.callout}</p></div>`;
        if (s.warning) html += `<div class="article-warning-box"><div class="warn-title">⚠️ Important</div><p>${s.warning}</p></div>`;
    });
    return html;
}

// ============================================================
// ARTICLE CRUD
// ============================================================
async function saveArticle() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) return; // Silent return if no pass

    const id = document.getElementById('editor-id').value || ('art-' + Date.now());
    const title = document.getElementById('editor-title').value.trim();
    const pillar = document.getElementById('editor-pillar').value;
    const subtitle = document.getElementById('editor-subtitle').value.trim();
    const author = document.getElementById('editor-author').value;
    const status = document.getElementById('editor-status').value || 'published';
    const overridesId = document.getElementById('editor-overrides-id').value || '';
    const structured = getStructuredData();

    if (!title || !structured.sections.length || !structured.sections[0].heading) {
        toast('Title and at least one section with a heading are required.', 'error'); return;
    }

    const body = buildArticleHTML(structured);
    const newArt = { id, title, pillar, subtitle, body, author, status, overridesId, structured, savedAt: new Date().toISOString() };

    let updatedList = [...currentArticles];
    const idx = updatedList.findIndex(a => a.id === id);
    if (idx > -1) updatedList[idx] = newArt; else updatedList.push(newArt);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'knowledge_articles', items: updatedList })
        });
        const result = await res.json();
        if (result.success) {
            toast('âœ… Article saved successfully!', 'success');
            clearEditor();
            renderKnowledge();
            switchPanel('knowledge');
        } else {
            toast('Save failed: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (err) { toast('Error saving article: ' + err.message, 'error'); }
}

function editArticle(id) {
    const article = currentArticles.find(a => a.id === id);
    if (!article) return;
    document.getElementById('editor-id').value = article.id;
    document.getElementById('editor-title').value = article.title;
    document.getElementById('editor-pillar').value = article.pillar;
    document.getElementById('editor-subtitle').value = article.subtitle || '';
    document.getElementById('editor-author').value = article.author;
    document.getElementById('editor-status').value = article.status;
    document.getElementById('editor-overrides-id').value = article.overridesId || '';
    if (article.structured && article.structured.sections && article.structured.sections.length) {
        loadStructured(article.structured);
    } else {
        clearSectionsOnly(); addSection();
    }
    switchPanel('editor');
}

const BUILT_IN_CONTENT = {
    sepsis: {
        subtitle: "Sepsis kills 11 million people every year â€” more than all cancers combined. Learn the warning signs, the timeline of deterioration, and why the first hour is everything.",
        structured: {
            stats: [
                { num: "11M", lbl: "sepsis deaths annually worldwide" },
                { num: "7%", lbl: "mortality increase per hour of antibiotic delay" },
                { num: "1hr", lbl: "the critical window â€” the Hour-1 Bundle" }
            ],
            sections: [
                { heading: "What Is Sepsis?", content: "Sepsis is not an infection. It is your body's dysregulated, life-threatening response to an infection â€” a cascade of inflammation that, paradoxically, begins to destroy the very organs it is trying to protect. Any infection can trigger sepsis: a urinary tract infection, a chest infection, an abdominal infection, even a dental abscess.", callout: "Key Definition (Sepsis-3, 2016): Sepsis is life-threatening organ dysfunction caused by a dysregulated host response to infection. It is identified clinically by an increase in the SOFA score of â‰¥2 points.", warning: "" },
                { heading: "Warning Signs Families Must Know", content: "The most dangerous thing about sepsis is how quickly it can progress â€” from apparent wellness to life-threatening collapse in hours. The THINK acronym is useful for families:", callout: "", warning: "T: Temperature â€” very high (>38.5Â°C) or very low (<36Â°C) | H: Heart rate â€” unusually fast, above 90 beats per minute | I: Infection â€” known or suspected | N: Neurological â€” confusion, altered behaviour, or unusual drowsiness | K: Kidney â€” not passing urine, or much less than usual" },
                { heading: "The Sepsis-3 Criteria", content: "In 2016, the international Sepsis-3 taskforce redefined how we diagnose sepsis. The Quick SOFA (qSOFA) score is used for rapid bedside screening: Respiratory rate â‰¥ 22/min, Altered mental status, Systolic BP â‰¤ 100 mmHg. A qSOFA score of 2 or more in a patient with suspected infection warrants urgent clinical assessment and escalation.", callout: "", warning: "" },
                { heading: "The SSC Hour-1 Bundle", content: "The Surviving Sepsis Campaign Hour-1 Bundle is the global standard for sepsis management. At Apollo ICU, this bundle is initiated within 60 minutes of sepsis recognition: measure lactate, obtain blood cultures, administer broad-spectrum antibiotics, begin crystalloid resuscitation (30 mL/kg), apply vasopressors if MAP <65 mmHg â€” Norepinephrine first-line.", callout: "", warning: "" },
                { heading: "What Happens in the ICU", content: "Once admitted to the ICU with sepsis, the focus shifts to source control, haemodynamic stabilisation, organ support, and daily reassessment of antibiotics. Dr. Kothari's team uses dynamic fluid assessments â€” passive leg raise tests and pulse pressure variation â€” rather than aggressive fixed-volume protocols, to guide resuscitation precisely.", callout: "", warning: "" }
            ]
        }
    },
    ecmo: {
        subtitle: "A plain-language guide to Extracorporeal Membrane Oxygenation â€” who needs it, how it works, and what outcomes families can realistically expect.",
        structured: {
            stats: [
                { num: "50â€“70%", lbl: "VV-ECMO survival in experienced centres" },
                { num: "5â€“7L", lbl: "blood volume processed per minute" },
                { num: "1971", lbl: "year of first successful ECMO use (Dr. Bartlett)" }
            ],
            sections: [
                { heading: "What Is ECMO?", content: "Extracorporeal Membrane Oxygenation is an advanced life support technique that temporarily takes over the function of the heart, the lungs, or both. Blood is pumped out of the patient's body through large cannulas, passed through an artificial membrane oxygenator that adds oxygen and removes carbon dioxide, and then returned to the patient. In essence, ECMO does what the lungs and heart cannot â€” buying critical time for organs to recover.", callout: "Dr. Kothari's Perspective: ECMO is not the last resort people fear it to be. It is a bridge â€” to recovery, to a transplant, or to a decision. When initiated at the right time, in the right patient, by an experienced team, outcomes are genuinely impressive.", warning: "" },
                { heading: "Types of ECMO", content: "VV-ECMO (Veno-Venous): Supports lung function only. Blood is drawn from a vein, oxygenated, and returned to a vein. Used in severe ARDS and respiratory failure.\n\nVA-ECMO (Veno-Arterial): Supports both heart and lung function. Blood is drawn from a vein and returned to an artery. Used in cardiogenic shock and cardiac arrest.\n\nECCOâ‚‚R: A low-flow variant focused primarily on COâ‚‚ removal in less severe respiratory failure.", callout: "", warning: "" },
                { heading: "Who Needs ECMO?", content: "ECMO is not for every ICU patient. The ELSO 2021 guidelines recommend considering VV-ECMO when conventional mechanical ventilation fails to maintain life-compatible oxygenation in severe ARDS â€” specifically when PaOâ‚‚/FiOâ‚‚ ratio is below 50â€“80 mmHg despite optimal ventilator settings.", callout: "", warning: "ECMO is NOT appropriate for: Irreversible neurological injury | End-stage multi-organ failure with no reversible aetiology | Uncontrolled bleeding disorders | Advanced malignancy without transplant option" },
                { heading: "ECMO in Practice at Apollo ICU", content: "Once initiated, ECMO requires 24/7 expert management by a dedicated team. The ICU team monitors circuit pressures, membrane function, oxygenator performance, and anticoagulation levels continuously. Daily weaning assessments are performed â€” testing whether the patient's own lungs or heart can gradually take over the work of the ECMO circuit as function returns.", callout: "", warning: "" },
                { heading: "What Families Should Know â€” and Ask", content: "If a family member is being considered for ECMO, the most important questions to ask are: What is the reversible cause of organ failure? What is our recovery target? What defined endpoints will guide the decision to continue or discontinue? An experienced ECMO team will always have clear answers to these questions before initiation.", callout: "", warning: "" }
            ]
        }
    },
    crrt: {
        subtitle: "Understanding Continuous Renal Replacement Therapy â€” why it differs from conventional dialysis, and when it is used in the ICU.",
        structured: {
            stats: [
                { num: "50%", lbl: "of ICU patients develop AKI" },
                { num: "3Ã—", lbl: "increased mortality risk with AKI in ICU" },
                { num: "~60%", lbl: "of severe AKI patients recover if they survive the critical illness" }
            ],
            sections: [
                { heading: "Acute Kidney Injury (AKI) in the ICU", content: "Acute Kidney Injury affects 30â€“50% of all ICU patients and is independently associated with increased mortality. In the context of critical illness â€” whether sepsis, major surgery, cardiogenic shock, or rhabdomyolysis â€” the kidneys are often among the first organs to suffer. When they fail, toxins accumulate, fluid balance deteriorates, and acid-base disorders worsen.", callout: "", warning: "" },
                { heading: "CRRT vs. Conventional Haemodialysis â€” What's the Difference?", content: "Conventional intermittent haemodialysis (IHD) runs for 4 hours, dialysing large volumes of blood rapidly. This rapid fluid and solute shift is poorly tolerated by haemodynamically unstable ICU patients â€” it can cause dangerous drops in blood pressure. CRRT solves this by running continuously at a much slower rate â€” typically 20â€“25 mL/kg/hour â€” allowing gentle, gradual removal of fluid and toxins that the heart and circulation can tolerate.", callout: "Key Concept: The slower, gentler pace of CRRT is its defining advantage. It removes fluid and waste products at a rate the unstable ICU patient's haemodynamics can accommodate.", warning: "" },
                { heading: "When Does the ICU Use CRRT? (KDIGO 2022)", content: "Per KDIGO 2022 AKI Guidelines, the urgent indications for initiating CRRT are: Refractory metabolic acidosis (pH <7.2 not responding to bicarbonate) | Refractory hyperkalaemia (>6 mmol/L not responding to medical management) | Severe uraemic complications (encephalopathy, pericarditis, bleeding) | Fluid overload unresponsive to diuretics causing respiratory compromise | Drug or toxin removal requiring continuous clearance.", callout: "", warning: "" },
                { heading: "What Families Need to Know About CRRT", content: "CRRT requires close monitoring of the access site (typically a large central venous catheter), anticoagulation to prevent filter clotting, and frequent electrolyte checks. The machine runs continuously, appears technically complex, but causes the patient minimal discomfort. The goal is always supporting kidney function while the underlying critical illness is treated â€” with the hope that the kidneys will recover sufficiently to function independently again.", callout: "", warning: "" }
            ]
        }
    },
    ventilation: {
        subtitle: "A family-friendly explanation of mechanical ventilation in the ICU â€” settings, complications, and the weaning process.",
        structured: {
            stats: [
                { num: "6 mL", lbl: "per kg ideal body weight â€” the lung-protective tidal volume (ARMA Trial)" },
                { num: "22%", lbl: "reduction in ARDS mortality with lower tidal volumes" },
                { num: "16+hrs", lbl: "prone positioning daily reduces ARDS mortality (PROSEVA Trial)" }
            ],
            sections: [
                { heading: "Why Is My Family Member on a Ventilator?", content: "Mechanical ventilation is a life-support measure â€” not a sign of hopelessness. When a patient cannot breathe adequately on their own due to severe pneumonia, ARDS, sepsis, drug overdose, or post-operative complications, a ventilator breathes for them while the underlying cause is treated. The goal is always to support the patient through the crisis and safely remove ventilator support as soon as possible.", callout: "", warning: "" },
                { heading: "How Does a Ventilator Work?", content: "A ventilator delivers a precisely controlled breath â€” a mixture of oxygen and air â€” through a tube placed in the windpipe (endotracheal tube). The key settings include: tidal volume (how much air per breath), respiratory rate (how many breaths per minute), FiOâ‚‚ (fraction of oxygen), and PEEP (Positive End-Expiratory Pressure â€” keeping the air sacs from collapsing).", callout: "", warning: "" },
                { heading: "Lung-Protective Ventilation", content: "Traditional ventilation used high volumes to keep oxygen levels normal. The landmark ARMA trial (2000) showed this caused ventilator-induced lung injury (VILI). The modern standard is lung-protective ventilation: 6 mL/kg tidal volumes, higher PEEP to keep air sacs open, and limiting plateau pressures. This approach reduced 28-day ARDS mortality by 22%.", callout: "Evidence-Based: Prone positioning (lying face down) for â‰¥16 hours per day reduced 28-day ARDS mortality from 32.8% to 16% in the PROSEVA trial â€” now standard practice in Dr. Kothari's ICU.", warning: "" },
                { heading: "The Weaning Process â€” Getting Off the Ventilator", content: "Weaning is the careful, systematic process of reducing ventilator support as the patient's breathing improves. Every day, Dr. Kothari's team conducts a Spontaneous Breathing Trial (SBT) â€” reducing ventilator support to minimal levels and monitoring whether the patient can breathe safely on their own. If the SBT is passed and other criteria are met, the tube is removed (extubation).", callout: "", warning: "" },
                { heading: "What Families Should Understand", content: "Many patients on ventilators are conscious and aware â€” they may be able to hear you, so talk to them gently. Sedation is kept as light as possible â€” heavy sedation increases complications. Progress is measured daily â€” ask the team what today's SBT result was. There will be days of setback â€” weaning is rarely linear. Patience and trust in the process matter.", callout: "", warning: "" }
            ]
        }
    },
    mof: {
        subtitle: "Understanding MODS â€” what causes it, why it is so dangerous, and how critical care specialists orchestrate the complex support required.",
        structured: {
            stats: [
                { num: ">50%", lbl: "MODS cases precipitated by sepsis" },
                { num: "3â€“4", lbl: "organ failures â€” mortality approaches 70â€“80%" },
                { num: "SOFA", lbl: "daily scoring to track organ function trajectory" }
            ],
            sections: [
                { heading: "What Is Multi-Organ Dysfunction Syndrome (MODS)?", content: "MODS is the progressive dysfunction of two or more organ systems in a critically ill patient â€” such that homeostasis cannot be maintained without medical intervention. It is the most common cause of ICU death. When lungs, kidneys, liver, heart, and brain fail in sequence or simultaneously, the intensivist's role becomes that of a conductor â€” orchestrating simultaneous support for each failing system.", callout: "Key Concept: No single organ fails in isolation in MODS. Interventions to support one organ must be carefully chosen to avoid worsening another. This is the art of critical care medicine.", warning: "" },
                { heading: "Why Does Multi-Organ Failure Happen?", content: "The most common triggers are severe sepsis and septic shock (accounting for >50% of cases), major trauma, large burns, pancreatitis, and cardiogenic shock. The underlying mechanism involves excessive systemic inflammation, microvascular thrombosis, cellular energy failure, and apoptosis â€” a biological perfect storm that progressively impairs organ function despite adequate perfusion.", callout: "", warning: "" },
                { heading: "The SOFA Score â€” Tracking Organ Failure", content: "The Sequential Organ Failure Assessment (SOFA) score quantifies the degree of dysfunction across six organ systems: respiratory (P/F ratio), coagulation (platelets), liver (bilirubin), cardiovascular (vasopressor requirement), neurological (GCS), and renal (creatinine). A SOFA score of â‰¥2 indicates active organ dysfunction. Daily SOFA trajectory is one of the most important prognostic tools in MODS management.", callout: "", warning: "" },
                { heading: "How MODS Is Managed at Apollo ICU", content: "Management is fundamentally supportive â€” buying time for organ recovery while treating the precipitating cause. In practice: ECMO or high-flow nasal cannula for respiratory failure, CRRT for renal failure, vasopressors for cardiovascular support, enteral nutrition to protect the gut, heparin for coagulopathy, and strict glycaemic control. There is no single drug that reverses MODS.", callout: "", warning: "" }
            ]
        }
    },
    "family-guide": {
        subtitle: "Everything families need to know about the ICU â€” the machines, the monitors, how to communicate, and how to support recovery from outside the room.",
        structured: {
            stats: [],
            sections: [
                { heading: "Entering the ICU for the First Time", content: "Nothing fully prepares you for seeing a family member in the ICU for the first time. The noise of monitors, the tangle of lines, the hiss of ventilators â€” it is overwhelming. The most important thing to know is that every wire, tube, and machine has a purpose. You are not powerless. Your presence matters, your questions matter, and your support is part of the healing process.", callout: "Remember: Even if your loved one appears sedated, they may be able to hear you. Studies show that familiar voices, gentle touch, and spoken reassurance reduce anxiety and improve ICU outcomes. Talk to them. Tell them what day it is. Tell them you're there.", warning: "" },
                { heading: "Understanding the Machines Around the Bed", content: "Heart Rate Monitor: Shows how fast the heart is beating â€” normal is 60â€“100 bpm.\n\nBlood Pressure: Either via cuff cycling every few minutes, or an invasive arterial line in the wrist artery for continuous real-time reading.\n\nSpOâ‚‚ (Oxygen Saturation): The probe on the finger measuring how well the blood carries oxygen. Targets are typically 94â€“98%.\n\nVentilator: If intubated â€” breathes for your loved one. You will see a tube in the mouth and the ventilator cycling with each breath.\n\nIV Lines and Infusions: Medications, fluids, and nutrition delivered directly into the bloodstream.", callout: "", warning: "" },
                { heading: "How to Communicate With the ICU Team", content: "At Apollo ICU, the team aim to provide family briefings daily â€” usually in the morning after rounds. Prepare your questions in advance. The most useful questions to ask: What is the primary diagnosis? Is the patient improving, stable, or deteriorating? What is the next 24-hour plan? What are the things to watch for? Are there decisions being made that need our input?", callout: "", warning: "Things NOT to do in the ICU: Do not bring outside food, flowers, or plants (infection risk) | Do not discuss prognosis negatively near the patient â€” they may hear | Do not adjust or touch any tubes, lines, or equipment | Do not visit if you have fever, cough, or diarrhoea" },
                { heading: "Supporting Recovery from Outside the Room", content: "Your self-care is not a luxury â€” it is a medical necessity. ICU family members are at significant risk of anxiety, depression, and PTSD. Eat regularly. Sleep in shifts if you must stay at the hospital. Accept help from friends and family. Ask the social work team for support resources. And know that by staying informed, advocating for your loved one, and being present, you are genuinely contributing to their care.", callout: "", warning: "" }
            ]
        }
    },
    delirium: {
        subtitle: "Why ICU patients become confused or agitated, how delirium is clinically assessed and managed, and what the long-term cognitive implications are.",
        structured: {
            stats: [
                { num: "50â€“80%", lbl: "of mechanically ventilated patients develop delirium" },
                { num: "3Ã—", lbl: "higher risk of 6-month mortality in delirious ICU patients" },
                { num: "11 days", lbl: "longer hospital stay in patients with ICU delirium" }
            ],
            sections: [
                { heading: "What Is ICU Delirium?", content: "ICU delirium is an acute brain dysfunction characterised by disturbed attention, impaired thinking, and an altered level of consciousness â€” occurring in 50â€“80% of mechanically ventilated ICU patients. It is not just confusion. Delirium is an independent risk factor for increased mortality, prolonged ventilation, longer ICU and hospital stays, and lasting cognitive impairment after discharge.", callout: "", warning: "" },
                { heading: "Types of ICU Delirium", content: "Hyperactive: Agitation, pulling at tubes, attempting to get out of bed â€” the most visible type, and often the most frightening for families.\n\nHypoactive: Quiet, withdrawn, flat â€” often missed, but actually the most common and prognostically worse form.\n\nMixed: Fluctuating between both types â€” the most common overall pattern.", callout: "", warning: "" },
                { heading: "CAM-ICU â€” How We Assess ICU Delirium", content: "The Confusion Assessment Method for the ICU (CAM-ICU) is a validated bedside tool applicable even in intubated patients. It assesses: (1) Acute onset and fluctuating course, (2) Inattention, (3) Altered level of consciousness, (4) Disorganised thinking. A positive CAM-ICU result triggers immediate management review.", callout: "", warning: "" },
                { heading: "The ABCDEF Bundle â€” Prevention Is the Priority", content: "The ABCDEF bundle (2018) is the comprehensive, evidence-based framework for delirium prevention and management: A â€” Assess pain; B â€” Both Spontaneous Awakening and Breathing Trials; C â€” Choice of sedation (light sedation targets); D â€” Delirium assess/manage (CAM-ICU); E â€” Early Mobility and Exercise; F â€” Family engagement and empowerment. Implementing the full ABCDEF bundle reduces delirium incidence by up to 78% in some studies.", callout: "", warning: "" }
            ]
        }
    },
    nutrition: {
        subtitle: "Why nutrition is among the most underappreciated life-saving therapies in the ICU â€” optimal timing, routes, and evidence-based targets.",
        structured: {
            stats: [
                { num: "24â€“48h", lbl: "target window for enteral nutrition initiation" },
                { num: "1.2â€“2g", lbl: "protein per kg body weight per day â€” ICU target" },
                { num: "25â€“30", lbl: "kcal/kg/day â€” typical ICU caloric target" }
            ],
            sections: [
                { heading: "Why Nutrition Is a Life-Saving ICU Therapy", content: "ICU patients undergo a profound metabolic storm â€” hypermetabolism, accelerated protein catabolism, immune dysregulation, and gut barrier dysfunction. Without adequate nutritional support, lean muscle mass is lost at a rate of 1â€“2% per day. This ICU-acquired weakness prolongs ventilation, impairs immunity, delays wound healing, and worsens long-term outcomes. Early, optimal nutrition is not comfort â€” it is therapy.", callout: "Key Insight: An ICU patient who is not eating is not simply not hungry. They are burning through their body reserve at an alarming rate. Every hour of nutritional delay matters.", warning: "" },
                { heading: "Timing Is Everything: Early Enteral Nutrition", content: "ESPEN 2023 guidelines recommend initiating enteral nutrition within 24â€“48 hours of ICU admission in most patients. If the gut works, use it â€” is the guiding principle. Early enteral nutrition preserves gut mucosal integrity (preventing bacterial translocation), maintains gut-associated immunity, reduces infections and systemic inflammation, and improves clinical outcomes compared to parenteral routes.", callout: "", warning: "" },
                { heading: "Enteral vs. Parenteral Nutrition", content: "Enteral nutrition (EN) is delivered via a tube in the stomach (NGT) or small intestine (NJT) and is the preferred route when the gut is functional. Parenteral nutrition (PN) â€” delivered intravenously â€” is reserved for patients where EN is contraindicated or insufficient (e.g., gut ileus, short bowel). PN carries higher infection risk and is metabolically more complex to manage. Supplemental PN can be added after 3â€“7 days if EN alone cannot meet targets.", callout: "", warning: "" },
                { heading: "Caloric and Protein Targets", content: "Optimal caloric target in the early ICU phase (days 1â€“3): 70â€“80% of estimated requirements â€” hypocaloric feeding reduces metabolic complications while still being protective. From Day 3â€“4 onwards: full caloric target of 25â€“30 kcal/kg/day. Protein: 1.2â€“2.0 g/kg/day â€” the higher range for burns, trauma, and ECMO patients. Daily monitoring of blood glucose (target 7.8â€“10 mmol/L) with insulin infusion is mandatory in all ICU patients receiving nutrition.", callout: "", warning: "" }
            ]
        }
    },
    antimicrobial: {
        subtitle: "Preventing hospital-acquired infections, understanding antibiotic resistance in Indian ICUs, and how stewardship protects patients â€” and the future of medicine.",
        structured: {
            stats: [
                { num: "30%", lbl: "of ICU patients develop at least one HAI" },
                { num: "8â€“10 days", lbl: "additional ICU stay caused by VAP" },
                { num: "2â€“3Ã—", lbl: "higher mortality risk with HAI" }
            ],
            sections: [
                { heading: "Hospital-Acquired Infections in the ICU", content: "Hospital-acquired infections (HAIs) are among the most serious threats to ICU patients. The most common include Ventilator-Associated Pneumonia (VAP), Central Line-Associated Bloodstream Infections (CLABSI), and Catheter-Associated Urinary Tract Infections (CAUTI). These infections affect 30% of ICU patients, significantly increase mortality, prolong stay, and drive antibiotic use â€” fuelling resistance.", callout: "", warning: "" },
                { heading: "Prevention: ICU Bundles That Work", content: "Apollo ICU implements evidence-based prevention bundles. VAP bundle includes head-of-bed elevation at 30â€“45Â°, daily sedation holidays, oral care with chlorhexidine, and subglottic suction. CLABSI bundle includes maximum barrier precautions, chlorhexidine skin prep, and daily necessity reviews. Every unnecessary line, tube, and catheter is removed as soon as clinically safe.", callout: "", warning: "" },
                { heading: "Antimicrobial Stewardship â€” Using Antibiotics Wisely", content: "Antimicrobial stewardship (AMS) is the coordinated programme ensuring antibiotics are used only when needed, for the right duration, and then de-escalated or stopped based on culture results. This is not about being conservative with sick patients â€” it is about being precise. Giving the wrong antibiotic for too long selects for resistant organisms that will be untreatable in the next patient.", callout: "The Stewardship Principle: Start broad when sepsis is suspected. Culture early. De-escalate once organism and sensitivities are known. Stop when the course is complete. This protects the patient, the community, and the future of medicine.", warning: "" },
                { heading: "AMR and the Indian ICU Context", content: "India has one of the world's highest burdens of carbapenem-resistant Klebsiella, ESBL-producing E. coli, and MRSA. Hospital-acquired infections in Indian ICUs are far more likely to involve multi-drug resistant organisms than in Western centres. This makes stewardship even more critical â€” and makes culture-guided therapy the non-negotiable standard of care at responsible ICUs like Apollo Hospitals, Ahmedabad.", callout: "", warning: "" }
            ]
        }
    },
    pics: {
        subtitle: "Post-Intensive Care Syndrome affects up to 50% of ICU survivors â€” the physical, cognitive, and psychological challenges of life after critical illness.",
        structured: {
            stats: [
                { num: "30â€“50%", lbl: "of ICU survivors develop PICS" },
                { num: "25%", lbl: "of ICU survivors develop PTSD" },
                { num: "monthsâ€“years", lbl: "duration of PICS symptoms in many patients" }
            ],
            sections: [
                { heading: "What Is Post-Intensive Care Syndrome (PICS)?", content: "Discharge from the ICU is not the end of the critical illness journey â€” it is the beginning of a new, often underappreciated one. Post-Intensive Care Syndrome (PICS) is the term for new or worsening impairments in physical, cognitive, and mental health status arising after critical illness â€” persisting beyond the acute hospitalisation. SCCM estimates 30â€“50% of ICU survivors are affected.", callout: "PICS also affects families â€” PICS-F (Post-Intensive Care Syndrome â€” Family) refers to the psychological burden borne by caregivers of ICU survivors: anxiety, depression, complicated grief, and PTSD at very high rates.", warning: "" },
                { heading: "Physical Challenges of PICS", content: "ICU-acquired weakness (ICUAW) is the most common physical manifestation â€” significant muscle atrophy and weakness affecting 25â€“50% of mechanically ventilated patients. It causes difficulty walking, climbing stairs, and performing daily activities for months after discharge. Other physical issues include persistent fatigue, dysphagia (swallowing difficulties post-extubation), sleep disorders, sexual dysfunction, and chronic pain.", callout: "", warning: "" },
                { heading: "Cognitive and Mental Health Challenges", content: "Cognitive impairment after ICU is strikingly common â€” affecting attention, memory, processing speed, and executive function. Many survivors describe brain fog persisting for months. Depression affects up to 30% of survivors. Anxiety is highly prevalent. And 25% develop clinical PTSD, characterised by intrusive memories, nightmares, hyperarousal, and avoidance of hospital reminders.", callout: "", warning: "" },
                { heading: "Supporting Long-Term ICU Recovery", content: "Early mobilisation in the ICU (from day 2â€“3 if haemodynamically stable) is the single most evidence-based intervention for preventing ICUAW. Post-ICU rehabilitation should be multidisciplinary: physiotherapy to rebuild strength, neuropsychological support for cognitive and emotional recovery, and ICU follow-up clinics that screen for PTSD and unresolved physical complications. ICU diaries â€” daily logs kept by nurses for the patient â€” have been shown to reduce PTSD rates by up to 35%.", callout: "", warning: "" }
            ]
        }
    }
};



function editBuiltInArticle(id) {
    const a = BUILT_IN_ARTICLES.find(x => x.id === id);
    if (!a) return;
    const content = BUILT_IN_CONTENT[id] || {};
    document.getElementById('editor-id').value = 'art-' + id;
    document.getElementById('editor-title').value = a.title;
    document.getElementById('editor-pillar').value = a.pillar;
    document.getElementById('editor-subtitle').value = content.subtitle || '';
    document.getElementById('editor-author').value = 'Dr. Jay Kothari';
    document.getElementById('editor-status').value = a.status;
    document.getElementById('editor-overrides-id').value = id;
    if (content.structured) {
        loadStructured(content.structured);
    } else {
        clearSectionsOnly(); addSection();
    }
    switchPanel('editor');
}

async function deleteArticle(id) {
    if (!confirm('Delete this article?')) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) return;

    const updatedList = currentArticles.filter(a => a.id !== id);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'knowledge_articles', items: updatedList })
        });
        const result = await res.json();
        if (result.success) { toast('ðŸ—‘ï¸ Article deleted.', 'success'); renderKnowledge(); }
        else toast('Delete failed: ' + (result.error || 'Unknown error'), 'error');
    } catch (err) { toast('Error deleting: ' + err.message, 'error'); }
}

function clearEditor() {
    ['editor-id', 'editor-title', 'editor-subtitle', 'editor-body', 'editor-overrides-id'].forEach(f => document.getElementById(f).value = '');
    document.getElementById('editor-author').value = 'Dr. Jay Kothari';
    document.getElementById('editor-status').value = 'published';
    clearSectionsOnly();
    addSection();
}

// ============================================================
// REVIEWS
// ============================================================
async function renderReviews() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch('./api/content.php?type=peer_recognitions', {
            headers: { 'X-Admin-Token': pass }
        });
        const d = await res.json();
        const reviews = d.data || [];
        const grid = document.getElementById('reviews-grid');
        if (reviews.length === 0) {
            grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">No reviews yet.</div>';
            return;
        }
        grid.innerHTML = reviews.map((r, i) => `
        <div class="photo-review-item">
          <div class="photo-review-body">
            <div class="photo-review-name">${r.name || 'Anonymous'}</div>
            <div class="photo-review-date">${r.date || ''}</div>
            <div style="font-size:0.85rem;margin:8px 0;line-height:1.5;">${r.text}</div>
            <div style="margin-top:6px;"><span class="status-badge badge-${r.status}">${r.status}</span></div>
            <div class="photo-review-actions" style="margin-top:12px;">
              ${r.status === 'pending' ? `<button class="action-btn action-btn-approve" onclick="updateReviewStatus(${i},'approved')">✓ Approve</button>
              <button class="action-btn action-btn-reject" onclick="updateReviewStatus(${i},'rejected')">✕ Reject</button>` : ''}
              <button class="action-btn action-btn-delete" onclick="deleteReview(${i})">Delete</button>
            </div>
          </div>
        </div>`).join('');
    } catch (e) {
        console.error('Failed to fetch reviews:', e);
    }
}

async function updateReviewStatus(index, status) {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch('./api/content.php?type=peer_recognitions', { headers: { 'X-Admin-Token': pass } });
        const d = await res.json();
        let reviews = d.data || [];
        reviews[index].status = status;
        const res2 = await fetch('./api/content.php', {
            method: 'POST',
            body: JSON.stringify({ type: 'peer_recognitions', items: reviews, admin_token: pass }),
            headers: { 'Content-Type': 'application/json' }
        });
        if ((await res2.json()).success) {
            toast(`Review ${status}`, 'success');
            renderReviews();
        }
    } catch (e) { toast('Failed to update review', 'error'); }
}

async function deleteReview(index) {
    if (!confirm('Are you sure you want to delete this review?')) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch('./api/content.php?type=peer_recognitions', { headers: { 'X-Admin-Token': pass } });
        const d = await res.json();
        let reviews = d.data || [];
        reviews.splice(index, 1);
        const res2 = await fetch('./api/content.php', {
            method: 'POST',
            body: JSON.stringify({ type: 'peer_recognitions', items: reviews, admin_token: pass }),
            headers: { 'Content-Type': 'application/json' }
        });
        if ((await res2.json()).success) {
            toast('Review deleted', 'success');
            renderReviews();
        }
    } catch (e) { toast('Failed to delete review', 'error'); }
}

function submitTestReview() {
    const name = document.getElementById('rv-name').value.trim();
    const platform = document.getElementById('rv-platform').value;
    const text = document.getElementById('rv-text').value.trim();
    if (!name || !text) { alert('Name and review text are required.'); return; }
    const reviews = getPendingReviews();
    reviews.push({ id: Date.now(), name, platform, text, status: 'pending', submitted: new Date().toISOString() });
    savePendingReviews(reviews);
    document.getElementById('rv-name').value = '';
    document.getElementById('rv-text').value = '';
    renderReviews();
    alert('Test review submitted for approval!');
}

// ============================================================
// PHOTOS
// ============================================================
async function renderPhotos() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    try {
        const res = await fetch('./api/content.php?type=photo_wall', {
            headers: { 'X-Admin-Token': pass }
        });
        const d = await res.json();
        const photos = d.data || [];
        const grid = document.getElementById('photo-grid');

        let html = '';
        if (photos.length > 0) {
            html += `<div style="grid-column:1/-1;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--text-muted);padding:12px 0 8px;border-bottom:1px solid var(--border-subtle);margin-bottom:4px;">📸 Photo Gallery (${photos.length})</div>`;
            html += photos.map((p, i) => `<div class="photo-review-item">
          <div class="photo-review-img">${p.url ? `<img src="${p.url}" style="width:100%;height:100%;object-fit:cover;" />` : '📸'}</div>
          <div class="photo-review-body">
            <div class="photo-review-name">${p.caption || 'Untitled'}</div>
            <div class="photo-review-date">${p.label || 'General'} · ${p.status || 'Active'}</div>
            <div class="photo-review-actions" style="margin-top:8px;">
              <button class="action-btn action-btn-delete" onclick="deletePhoto(${i})">Delete</button>
            </div>
          </div>
        </div>`).join('');
        }

        if (!html) html = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted);">No photos yet. Use the form above.</div>';
        grid.innerHTML = html;
        updateBadges();
    } catch (e) { console.error('Failed to fetch photos:', e); }
}

async function addPhoto() {
    const url = document.getElementById('photo-url').value.trim();
    const caption = document.getElementById('photo-caption').value.trim();
    const label = document.getElementById('photo-label').value.trim() || 'General';
    if (!caption) { alert('Caption is required.'); return; }
    const photos = getAdminPhotos();
    photos.push({ id: Date.now(), url, caption, label, status: 'approved', added: new Date().toISOString() });
    saveAdminPhotos(photos);
    document.getElementById('photo-url').value = '';
    document.getElementById('photo-caption').value = '';
    document.getElementById('photo-label').value = '';
    renderPhotos();
}

function updatePhotoStatus(id, status) {
    saveAdminPhotos(getAdminPhotos().map(p => p.id === id ? { ...p, status } : p));
    renderPhotos();
}
function deletePhoto(id) {
    if (!confirm('Delete this photo?')) return;
    saveAdminPhotos(getAdminPhotos().filter(p => p.id !== id));
    renderPhotos();
}

// ============================================================
// MYTH BUSTER CRUD â€” Server side via ./api/content.php
// ============================================================
const DEFAULT_MYTHS = [
    { id: 1, statement: '"Patients on ventilators feel nothing."', fact: 'Many ventilated patients are conscious and aware. Studies confirm they can hear voices. Light sedation is now preferred to prevent delirium and ICU-acquired weakness. Talk to your loved one â€” it matters.', source: 'ABCDEF Bundle Â· SCCM 2018' },
    { id: 2, statement: '"ICU means the patient is going to die."', fact: 'The ICU is a place of intensive monitoring and life-saving intervention â€” not end of life. Survival rates in modern ICUs often exceed 80â€“85% for many diagnoses. The ICU is where the most reversible critical illnesses are treated.', source: 'ANZICS Report 2022' },
    { id: 3, statement: '"More IV fluids is always better in shock."', fact: 'Fluid overload in the ICU is independently associated with worse outcomes â€” including prolonged ventilation and renal failure. Modern practice targets dynamic fluid responsiveness, not volume.', source: 'ANDROMEDA-SHOCK Â· JAMA 2019' },
    { id: 4, statement: '"Antibiotics cure all infections in the ICU."', fact: 'ICU infections are increasingly caused by multidrug-resistant organisms (MRSA, ESBL, Carbapenem-resistant Klebsiella). The right antibiotic, chosen by culture, matters far more than speed alone.', source: 'Surviving Sepsis Campaign 2021' },
    { id: 5, statement: '"Prone positioning (face-down) is experimental."', fact: 'Prone positioning for â‰¥16 hours/day in severe ARDS is evidence-based standard of care â€” reducing 28-day mortality from 32.8% to 16% in the landmark PROSEVA trial (NEJM 2013).', source: 'PROSEVA Trial Â· NEJM 2013' },
    { id: 6, statement: '"ECMO is always the last resort."', fact: 'ECMO initiated earlier, before the patient deteriorates too far, is associated with better outcomes. The ELSO 2021 guidelines recommend ECMO consideration at a P/F ratio below 80 mmHg, not only when all else has failed.', source: 'ELSO Guidelines 2021' },
];

let currentMyths = [];

async function renderMyths() {
    try {
        const res = await fetch('./api/content.php?type=myth_busters');
        const serverData = await res.json();

        // If server is empty, fallback to DEFAULT_MYTHS
        currentMyths = (serverData && serverData.length) ? serverData : DEFAULT_MYTHS;

        document.getElementById('myth-count').textContent = currentMyths.length + ' cards';
        document.getElementById('myth-table-body').innerHTML = currentMyths.map(m => `
                  <div style="border-bottom:1px solid var(--border-subtle);padding:14px 16px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:start;">
                    <div>
                      <div style="font-size:0.84rem;font-weight:700;color:var(--text-primary);margin-bottom:4px;">⚠️ ${m.statement}</div>
                      <div style="font-size:0.78rem;color:var(--text-secondary);">âœ… ${m.fact.substring(0, 100)}...</div>
                      <div style="font-size:0.72rem;color:var(--accent-secondary);margin-top:4px;">â€” ${m.source}</div>
                    </div>
                    <div class="action-btns">
                      <button class="action-btn action-btn-edit myth-edit-btn" data-id="${m.id}">Edit</button>
                      <button class="action-btn action-btn-delete myth-delete-btn" data-id="${m.id}">Delete</button>
                    </div>
                  </div>`).join('');
    } catch (err) {
        console.error('Error loading myths:', err);
    }
}

// Delegate edit/delete clicks
document.getElementById('myth-table-body').addEventListener('click', e => {
    const id = e.target.getAttribute('data-id');
    if (!id) return;
    if (e.target.classList.contains('myth-edit-btn')) editMyth(parseInt(id));
    if (e.target.classList.contains('myth-delete-btn')) deleteMyth(parseInt(id));
});

function editMyth(id) {
    const m = currentMyths.find(x => x.id === id);
    if (!m) return;
    document.getElementById('myth-edit-id').value = id;
    document.getElementById('myth-statement').value = m.statement;
    document.getElementById('myth-fact').value = m.fact;
    document.getElementById('myth-source').value = m.source;
    document.querySelector('#panel-myths .editor-card h3').textContent = 'âœï¸ Edit Myth Card';
    // Scroll to top of panel
    document.getElementById('panel-myths').scrollTop = 0;
}

async function saveMythCard() {
    const stmt = document.getElementById('myth-statement').value.trim();
    const fact = document.getElementById('myth-fact').value.trim();
    const source = document.getElementById('myth-source').value.trim();
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';

    if (!stmt || !fact) { alert('Required fields missing.'); return; }
    if (!pass) return;

    const editId = parseInt(document.getElementById('myth-edit-id').value);
    let updatedList = [...currentMyths];

    if (editId) {
        const idx = updatedList.findIndex(m => m.id === editId);
        if (idx > -1) updatedList[idx] = { id: editId, statement: stmt, fact, source };
    } else {
        updatedList.push({ id: Date.now(), statement: stmt, fact, source });
    }

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'myth_busters', items: updatedList })
        });
        if ((await res.json()).success) { clearMythForm(); renderMyths(); }
    } catch (err) { alert('Error saving.'); }
}

async function deleteMyth(id) {
    if (!confirm(`Delete this card?`)) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    const updatedList = currentMyths.filter(x => x.id !== id);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'myth_busters', items: updatedList })
        });
        if ((await res.json()).success) { renderMyths(); }
    } catch (err) { alert('Error deleting.'); }
}

function clearMythForm() {
    ['myth-edit-id', 'myth-statement', 'myth-fact', 'myth-source'].forEach(id => document.getElementById(id).value = '');
    document.querySelector('#panel-myths .editor-card h3').textContent = 'âž• Add / Edit Myth Card';
}


// ============================================================
// QUIZ QUESTION CRUD
// ============================================================
// ============================================================
// QUIZ QUESTION CRUD â€” Server side
// ============================================================
let currentQuizBank = [];

async function renderQuizEditor() {
    try {
        const res = await fetch('./api/content.php?type=quiz_questions');
        const serverData = await res.json();

        const tbody = document.getElementById('quiz-q-table');
        const countEl = document.getElementById('quiz-q-count');

        if (!serverData || serverData.length === 0) {
            countEl.textContent = 'Built-in questions (not yet customised)';
            tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--text-muted);">Using the default questions. Add a question above or click "Reset to 50 Defaults" to start customising on the server.</td></tr>`;
            currentQuizBank = []; // or you could load QUESTION_BANK here
            return;
        }

        currentQuizBank = serverData;
        countEl.textContent = currentQuizBank.length + ' questions';
        tbody.innerHTML = currentQuizBank.map((q, i) => {
            const letters = ['A', 'B', 'C', 'D'];
            return `<tr>
                      <td style="width:40px;">${i + 1}</td>
                      <td style="font-size:0.84rem;">${q.q.substring(0, 80)}${q.q.length > 80 ? '...' : ''}</td>
                      <td><span class="status-badge badge-published">${letters[q.correct]}: ${q.options[q.correct]?.substring?.(0, 30) ?? ''}...</span></td>
                      <td><div class="action-btns">
                        <button class="action-btn action-btn-edit quiz-edit-btn" data-idx="${i}">Edit</button>
                        <button class="action-btn action-btn-delete quiz-delete-btn" data-idx="${i}">Delete</button>
                      </div></td>
                    </tr>`;
        }).join('');
    } catch (err) {
        console.error('Error loading quiz bank:', err);
    }
}

// Delegate quiz clicks
document.getElementById('quiz-q-table').addEventListener('click', e => {
    const idx = e.target.getAttribute('data-idx');
    if (idx === null) return;
    if (e.target.classList.contains('quiz-edit-btn')) editQuizQ(parseInt(idx));
    if (e.target.classList.contains('quiz-delete-btn')) deleteQuizQ(parseInt(idx));
});

function editQuizQ(idx) {
    const q = currentQuizBank[idx];
    if (!q) return;
    document.getElementById('q-edit-id').value = idx;
    document.getElementById('q-question').value = q.q;
    document.getElementById('q-a').value = q.options[0] || '';
    document.getElementById('q-b').value = q.options[1] || '';
    document.getElementById('q-c').value = q.options[2] || '';
    document.getElementById('q-d').value = q.options[3] || '';
    document.getElementById('q-correct').value = q.correct;
    document.getElementById('q-explanation').value = q.explanation || '';
    document.getElementById('panel-quizeditor').scrollTop = 0;
}

async function saveQuizQuestion() {
    const quest = document.getElementById('q-question').value.trim();
    const optA = document.getElementById('q-a').value.trim();
    const optB = document.getElementById('q-b').value.trim();
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';

    if (!quest || !optA || !optB) { alert('Question and at least options A & B are required.'); return; }
    if (!pass) return;

    const options = [optA, optB, document.getElementById('q-c').value.trim(), document.getElementById('q-d').value.trim()].filter(Boolean);
    const correct = parseInt(document.getElementById('q-correct').value);
    const explanation = document.getElementById('q-explanation').value.trim();
    const editIdx = document.getElementById('q-edit-id').value;

    let updatedBank = [...currentQuizBank];
    if (editIdx !== '') {
        updatedBank[parseInt(editIdx)] = { q: quest, options, correct, explanation };
    } else {
        updatedBank.push({ q: quest, options, correct, explanation });
    }

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'quiz_questions', items: updatedBank })
        });
        if ((await res.json()).success) { clearQuizForm(); renderQuizEditor(); }
    } catch (err) { alert('Error saving.'); }
}

async function deleteQuizQ(idx) {
    if (!confirm(`Delete this question?`)) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    let updatedBank = currentQuizBank.filter((_, i) => i !== idx);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'quiz_questions', items: updatedBank })
        });
        if ((await res.json()).success) { renderQuizEditor(); }
    } catch (err) { alert('Error deleting.'); }
}

function clearQuizForm() {
    ['q-edit-id', 'q-question', 'q-a', 'q-b', 'q-c', 'q-d', 'q-explanation'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('q-correct').value = '0';
}

async function resetQuizToDefault() {
    if (!confirm('This will load the 50 default questions to the server. Continue?')) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) return;

    const defaults = [
        { q: "What does ECMO stand for?", options: ["Extracorporeal Membrane Oxygenation", "Extended Cardiac Monitoring Operation", "Emergency Cardiac Muscle Oxygenation", "External Cardio-Metabolic Oxygenator"], correct: 0, explanation: "ECMO temporarily replaces heart/lung function by circulating blood outside the body through an artificial lung." },
        { q: "According to Sepsis-3, what defines septic shock?", options: ["Fever >38Â°C", "Vasopressor requirement AND lactate >2 mmol/L despite fluids", "WBC >12,000", "RR >20 breaths/min"], correct: 1, explanation: "Sepsis-3 defines septic shock as sepsis requiring vasopressors to maintain MAP â‰¥65 mmHg AND lactate >2 mmol/L." },
        { q: "What tidal volume is recommended for lung-protective ventilation in ARDS?", options: ["10â€“12 mL/kg IBW", "8â€“10 mL/kg IBW", "6 mL/kg IBW", "4 mL/kg IBW"], correct: 2, explanation: "ARMA trial showed 6 mL/kg IBW reduces 28-day ARDS mortality by 22%." }
    ];

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'quiz_questions', items: defaults })
        });
        if ((await res.json()).success) { renderQuizEditor(); alert('â†º Reset done.'); }
    } catch (err) { alert('Error resetting.'); }
}


// ============================================================
// RESEARCH PAPERS CRUD â€” Server side
// ============================================================
let currentResearchList = [];

async function renderResearch() {
    try {
        const res = await fetch('./api/content.php?type=research_papers');
        const serverData = await res.json();

        // Fallback to defaults if server is empty
        currentResearchList = (serverData && serverData.length) ? serverData : DEFAULT_RESEARCH_SEED;

        document.getElementById('rp-count').textContent = currentResearchList.length + ' papers';
        document.getElementById('rp-table').innerHTML = currentResearchList.map(p => `<tr>
                  <td style="font-weight:700;font-size:0.84rem;">${p.title}</td>
                  <td style="font-size:0.78rem;color:var(--text-muted);">${p.journal}</td>
                  <td>${p.year}</td>
                  <td><span class="status-badge badge-published" style="font-size:0.66rem;">${p.topic}</span></td>
                  <td><div class="action-btns">
                    ${p.doi ? `<a href="${p.doi}" target="_blank" class="action-btn action-btn-edit" style="text-decoration:none;">View â†—</a>` : ''}
                    <button class="action-btn action-btn-edit res-edit-btn" data-id="${p.id}">Edit</button>
                    <button class="action-btn action-btn-delete res-del-btn" data-id="${p.id}">Delete</button>
                  </div></td>
                </tr>`).join('');
    } catch (err) { console.error('Error loading research:', err); }
}

// Delegate research clicks
document.getElementById('rp-table').addEventListener('click', e => {
    const id = e.target.getAttribute('data-id');
    if (!id) return;
    if (e.target.classList.contains('res-edit-btn')) editResearch(parseInt(id));
    if (e.target.classList.contains('res-del-btn')) deleteResearch(parseInt(id));
});

const DEFAULT_RESEARCH_SEED = [
    { id: 1, title: 'Prone Positioning in Severe ARDS (PROSEVA)', journal: 'NEJM', year: 2013, topic: 'ARDS & Ventilation', doi: 'https://doi.org/10.1056/NEJMoa1214103', takeaway: 'Prone positioning â‰¥16h/day reduced 28-day mortality from 32.8% to 16% in severe ARDS patients.', practice: 'We implement prone positioning early for all severe ARDS patients at Apollo, maintaining it for at least 16 hours/day per PROSEVA protocol.' },
    { id: 2, title: 'VV-ECMO for Severe ARDS (EOLIA)', journal: 'NEJM', year: 2018, topic: 'ECMO', doi: 'https://doi.org/10.1056/NEJMoa1800385', takeaway: 'Early VV-ECMO reduced the composite primary endpoint; trial stopped early. Rescue ECMO crossover was 28%.', practice: 'EOLIA guides our ECMO initiation. We convene the ECMO team when P/F ratio drops below 50 mmHg despite optimal care.' }
];

function editResearch(id) {
    const p = currentResearchList.find(x => x.id === id);
    if (!p) return;
    document.getElementById('rp-edit-id').value = id;
    document.getElementById('rp-title').value = p.title;
    document.getElementById('rp-journal').value = p.journal;
    document.getElementById('rp-year').value = p.year;
    document.getElementById('rp-topic').value = p.topic;
    document.getElementById('rp-doi').value = p.doi || '';
    document.getElementById('rp-takeaway').value = p.takeaway || '';
    document.getElementById('rp-practice').value = p.practice || '';
    document.getElementById('panel-research').scrollTop = 0;
}

async function saveResearchPaper() {
    const title = document.getElementById('rp-title').value.trim();
    const journal = document.getElementById('rp-journal').value.trim();
    const year = parseInt(document.getElementById('rp-year').value);
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';

    if (!title || !journal || !year) { toast('Required fields missing.', 'error'); return; }
    if (!pass) return;

    const editId = parseInt(document.getElementById('rp-edit-id').value);
    const newPaper = {
        id: editId || Date.now(), title, journal, year,
        topic: document.getElementById('rp-topic').value,
        doi: document.getElementById('rp-doi').value.trim(),
        takeaway: document.getElementById('rp-takeaway').value.trim(),
        practice: document.getElementById('rp-practice').value.trim()
    };

    let updatedList = [...currentResearchList];
    if (editId) {
        const idx = updatedList.findIndex(p => p.id === editId);
        if (idx > -1) updatedList[idx] = newPaper;
    } else {
        updatedList.push(newPaper);
    }

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'research_papers', items: updatedList })
        });
        if ((await res.json()).success) { toast('Research paper saved!', 'success'); clearResearchForm(); renderResearch(); }
    } catch (err) { toast('Error saving research.', 'error'); }
}

async function deleteResearch(id) {
    if (!confirm(`Delete this research paper?`)) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    const updatedList = currentResearchList.filter(x => x.id !== id);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'research_papers', items: updatedList })
        });
        if ((await res.json()).success) { toast('Paper deleted.', 'success'); renderResearch(); }
    } catch (err) { toast('Error deleting research.', 'error'); }
}

function clearResearchForm() {
    ['rp-edit-id', 'rp-title', 'rp-journal', 'rp-year', 'rp-doi', 'rp-takeaway', 'rp-practice'].forEach(id => document.getElementById(id).value = '');
}

// ============================================================
// PEER RECOGNITION CRUD â€” Server side
// ============================================================
let currentPeerList = [];

async function renderPeerRecognitions() {
    const tbody = document.getElementById('peer-table-body');
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;">â³ Loading recognitions...</td></tr>';

    try {
        const res = await fetch('./api/content.php?type=peer_recognitions');
        const data = await res.json();
        currentPeerList = data || [];

        if (currentPeerList.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">No recognition blocks found. Add one above.</td></tr>';
            return;
        }

        tbody.innerHTML = currentPeerList.map((p, i) => `
                    <tr>
                        <td style="font-size:1.5rem;">${p.icon}</td>
                        <td style="font-weight:700;">${p.title}</td>
                        <td style="font-size:0.8rem;color:var(--accent-secondary);">${p.source}</td>
                        <td style="font-size:0.8rem;max-width:300px;">${p.text.substring(0, 100)}${p.text.length > 100 ? '...' : ''}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn action-btn-edit" onclick="editPeer(${i})">Edit</button>
                                <button class="action-btn action-btn-delete" onclick="deletePeer(${i})">Delete</button>
                            </div>
                        </td>
                    </tr>
                `).join('');
    } catch (err) {
        console.error('Error loading peer recognitions:', err);
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--ad-red);">Failed to load data.</td></tr>';
    }
}

function editPeer(idx) {
    const p = currentPeerList[idx];
    if (!p) return;
    document.getElementById('peer-edit-id').value = idx;
    document.getElementById('peer-title').value = p.title;
    document.getElementById('peer-icon').value = p.icon;
    document.getElementById('peer-source').value = p.source;
    document.getElementById('peer-text').value = p.text;
    document.getElementById('peer-editor-title').textContent = 'âœï¸ Edit Recognition Block';
    document.getElementById('panel-peer').scrollTop = 0;
}

async function savePeerRecognition() {
    const title = document.getElementById('peer-title').value.trim();
    const icon = document.getElementById('peer-icon').value.trim();
    const source = document.getElementById('peer-source').value.trim();
    const text = document.getElementById('peer-text').value.trim();
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';

    if (!title || !text) { toast('Title and Recognition Text are required.', 'error'); return; }
    if (!pass) { toast('Admin session error. Please log in again.', 'error'); return; }

    const editIdx = document.getElementById('peer-edit-id').value;
    const newBlock = { title, icon, source, text };

    let updatedList = [...currentPeerList];
    if (editIdx !== "") {
        updatedList[parseInt(editIdx)] = newBlock;
    } else {
        updatedList.push(newBlock);
    }

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'peer_recognitions', items: updatedList })
        });
        const result = await res.json();
        if (result.success) {
            toast('âœ… Recognition block saved!', 'success');
            clearPeerForm();
            renderPeerRecognitions();
        } else {
            toast('Save failed: ' + (result.error || 'Unknown error'), 'error');
        }
    } catch (err) {
        toast('Error saving recognition: ' + err.message, 'error');
    }
}

async function deletePeer(idx) {
    if (!confirm('Are you sure you want to delete this recognition block?')) return;
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) return;

    let updatedList = currentPeerList.filter((_, i) => i !== idx);

    try {
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type: 'peer_recognitions', items: updatedList })
        });
        if ((await res.json()).success) {
            toast('ðŸ—‘ï¸ Recognition block deleted.', 'success');
            renderPeerRecognitions();
        }
    } catch (err) {
        toast('Error deleting recognition.', 'error');
    }
}

function clearPeerForm() {
    ['peer-edit-id', 'peer-title', 'peer-icon', 'peer-source', 'peer-text'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('peer-editor-title').textContent = 'âž• Add / Edit Recognition Block';
}


// ============================================================
// INIT
// ============================================================
function loadAll() {
    renderDashboard();
    renderAdminCal();
    updateBadges();
    checkApiStatus();
    loadHeroContent();
}

// ============================================================
// SWITCH PANEL â€” extended for new panels
// ============================================================
const _origSwitchPanel = switchPanel;
switchPanel = function (name) {
    document.querySelectorAll('.admin-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.admin-nav-link').forEach(l => l.classList.remove('active'));
    const panel = document.getElementById('panel-' + name);
    if (!panel) return;
    panel.classList.add('active');
    document.querySelector(`[data-panel="${name}"]`)?.classList.add('active');
    if (name === 'requests') renderRequests();
    if (name === 'calendar') renderAdminCal();
    if (name === 'knowledge') renderKnowledge();
    if (name === 'settings') loadCurrentSettings();
    if (name === 'reviews') renderReviews();
    if (name === 'photos') renderPhotos();
    if (name === 'myths') renderMyths();
    if (name === 'quizeditor') renderQuizEditor();
    if (name === 'research') renderResearch();
    if (name === 'peer') renderPeerRecognitions();
    if (name === 'hero') loadHeroContent();
    if (name === 'apihealth') runAllChecks();
};

// ============================================================
// TOAST SYSTEM
// ============================================================
function toast(msg, type = 'info', duration = 3500) {
    const tc = document.getElementById('toast-container');
    const t = document.createElement('div');
    const icons = { success: 'âœ…', error: 'âŒ', info: 'â„¹ï¸' };
    t.className = `toast toast-${type}`;
    t.textContent = `${icons[type] || ''} ${msg}`;
    tc.appendChild(t);
    requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => t.remove(), 400);
    }, duration);
}

// Override doLogin to use new styling + toast
document.getElementById('admin-pass').addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });
document.getElementById('admin-user').addEventListener('keydown', e => { if (e.key === 'Enter') document.getElementById('admin-pass').focus(); });

const _origDoLogin = doLogin;
doLogin = async function () {
    const btn = document.getElementById('login-btn');
    const err = document.getElementById('login-error');
    const u = document.getElementById('admin-user').value.trim();
    const p = document.getElementById('admin-pass').value.trim();
    err.style.display = 'none';
    if (!u || !p) { err.textContent = 'Enter username and password'; err.style.display = 'block'; return; }
    btn.textContent = 'Signing in...'; btn.disabled = true;
    try {
        const r = await fetch('./api/settings.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_auth', admin_pass: p })
        });
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const d = await r.json();
        if (d.success) {
            localStorage.setItem('apollo_admin_logged', 'true');
            localStorage.setItem('apollo_admin_temp_pass', p);
            document.getElementById('login-screen').style.display = 'none';
            document.getElementById('admin-layout').style.display = 'flex';
            loadAll();
            toast('Welcome back, Dr. Kothari!', 'success');
        } else {
            err.textContent = 'Invalid credentials'; err.style.display = 'block';
        }
    } catch (e) {
        err.innerHTML = `Server error: ${e.message}<br><small>Check that <b>api/settings.php</b> is uploaded to Hostinger.</small>`;
        err.style.display = 'block';
    }
    btn.textContent = 'Sign In â†’'; btn.disabled = false;
};

// ============================================================
// API STATUS CHECK
// ============================================================
async function checkApiStatus() {
    const pill = document.getElementById('api-status-pill');
    try {
        const r = await fetch('./api/content.php?type=myth_busters');
        const d = await r.json();
        if (d.success !== undefined) {
            pill.textContent = 'API Live'; pill.className = 'api-status ok';
        } else { throw new Error('bad response'); }
    } catch {
        pill.textContent = 'API Error'; pill.className = 'api-status err';
    }
}

// ============================================================
// API DIAGNOSTICS PANEL
// ============================================================
const ENDPOINTS_TO_CHECK = [
    { label: 'content.php â€” myth_busters', url: './api/content.php?type=myth_busters' },
    { label: 'content.php â€” quiz_questions', url: './api/content.php?type=quiz_questions' },
    { label: 'content.php â€” research_papers', url: './api/content.php?type=research_papers' },
    { label: 'content.php â€” knowledge_articles', url: './api/content.php?type=knowledge_articles' },
    { label: 'settings.php â€” GET', url: './api/settings.php' },
];

async function runAllChecks() {
    const el = document.getElementById('api-check-results');
    el.innerHTML = '<div style="color:var(--ad-text-muted);padding:12px;">Running checks...</div>';
    const results = await Promise.all(ENDPOINTS_TO_CHECK.map(async ep => {
        const t0 = Date.now();
        try {
            const r = await fetch(ep.url);
            const d = await r.json();
            const ms = Date.now() - t0;
            const ok = r.ok && (d.success !== undefined || typeof d === 'object');
            return { label: ep.label, ok, status: r.status, ms, preview: JSON.stringify(d).substring(0, 120) };
        } catch (e) {
            return { label: ep.label, ok: false, status: 'ERR', ms: Date.now() - t0, preview: e.message };
        }
    }));
    el.innerHTML = results.map(r => `
                <div class="admin-stat" style="border-left-color:${r.ok ? 'var(--ad-green)' : 'var(--ad-red)'}">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <span style="font-weight:700;color:var(--ad-text);font-size:0.86rem;">${r.label}</span>
                        <span class="status-badge badge-${r.ok ? 'approved' : 'rejected'}">${r.ok ? '✓ OK' : 'âœ• FAIL'} Â· ${r.status} Â· ${r.ms}ms</span>
                    </div>
                    <div style="font-size:0.72rem;color:var(--ad-text-muted);font-family:monospace;background:rgba(0,0,0,0.3);padding:6px 10px;border-radius:6px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${r.preview}</div>
                </div>`).join('');
    checkApiStatus();
}

async function runManualTest() {
    const url = document.getElementById('test-endpoint').value;
    const method = document.getElementById('test-method').value;
    const body = document.getElementById('test-body').value.trim();
    const resultEl = document.getElementById('test-result');
    resultEl.style.display = 'block';
    resultEl.textContent = 'Loading...';
    try {
        const opts = { method };
        if (method === 'POST' && body) { opts.headers = { 'Content-Type': 'application/json' }; opts.body = body; }
        const r = await fetch(url, opts);
        const d = await r.json();
        resultEl.textContent = `HTTP ${r.status} ${r.statusText}\n\n${JSON.stringify(d, null, 2)}`;
    } catch (e) {
        resultEl.textContent = `Error: ${e.message}`;
    }
}

// ============================================================
// HERO & CONTACT â€” load/save
// ============================================================
async function loadHeroContent() {
    try {
        const r = await fetch('./api/settings.php');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const s = await r.json();
        g('hc-hero-tagline').value = s.hero_tagline || '';
        g('hc-hero-empathy').value = s.hero_empathy || '';
        g('hc-hero-badge').value = s.hero_badge || '';
        g('hc-wa-number').value = s.wa_number || '';
        g('hc-wa-msg').value = s.wa_message || '';
        g('hc-icu-phone').value = s.icu_phone || '';
        g('hc-opd-link').value = s.opd_link || '';
        g('hc-ticker').value = s.ticker_text || '';
        g('hc-ticker-on').checked = !!s.ticker_on;
        g('hc-stat1-num').value = s.stat1_num || '';
        g('hc-stat1-lbl').value = s.stat1_lbl || '';
        g('hc-stat2-num').value = s.stat2_num || '';
        g('hc-stat2-lbl').value = s.stat2_lbl || '';
        g('hc-stat3-num').value = s.stat3_num || '';
        g('hc-stat3-lbl').value = s.stat3_lbl || '';
        g('hc-stat4-num').value = s.stat4_num || '';
        g('hc-stat4-lbl').value = s.stat4_lbl || '';
    } catch { }
}

async function saveHeroContent() {
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) { toast('Not authenticated', 'error'); return; }
    const g = id => document.getElementById(id).value;
    const body = {
        admin_pass: pass,
        site_name: g('hc-site-name'), hero_title: g('hc-hero-title'),
        hero_tagline: g('hc-hero-tagline'), hero_empathy: g('hc-hero-empathy'),
        hero_badge: g('hc-hero-badge'), wa_number: g('hc-wa-number'),
        wa_message: g('hc-wa-msg'), icu_phone: g('hc-icu-phone'),
        opd_link: g('hc-opd-link'), ticker_text: g('hc-ticker'),
        ticker_on: document.getElementById('hc-ticker-on').checked,
        stat1_num: g('hc-stat1-num'), stat1_lbl: g('hc-stat1-lbl'),
        stat2_num: g('hc-stat2-num'), stat2_lbl: g('hc-stat2-lbl'),
        stat3_num: g('hc-stat3-num'), stat3_lbl: g('hc-stat3-lbl'),
        stat4_num: g('hc-stat4-num'), stat4_lbl: g('hc-stat4-lbl'),
    };
    try {
        const r = await fetch('./api/settings.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
        const d = await r.json();
        if (d.success) toast('Hero content saved!', 'success');
        else toast('Save failed: ' + (d.error || 'Unknown error'), 'error');
    } catch (e) { toast('Connection error: ' + e.message, 'error'); }
}

// ============================================================
// EXPORT / IMPORT
// ============================================================
async function exportSection(type) {
    try {
        const r = await fetch(`./api/content.php?type=${type}`);
        const d = await r.json();
        const data = d.data ?? d;
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `apollo_${type}_${new Date().toISOString().slice(0, 10)}.json`;
        a.click();
        toast(`Exported ${type}`, 'success');
    } catch (e) { toast('Export failed: ' + e.message, 'error'); }
}

async function exportAll() {
    try {
        const r = await fetch('./api/content.php');
        const d = await r.json();
        const data = d.data ?? d;
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `apollo_all_content_${new Date().toISOString().slice(0, 10)}.json`;
        a.click();
        toast('Full export downloaded!', 'success');
    } catch (e) { toast('Export failed: ' + e.message, 'error'); }
}

async function importContent() {
    const type = document.getElementById('import-type').value;
    const file = document.getElementById('import-file').files[0];
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    const status = document.getElementById('import-status');
    if (!file) { toast('Select a JSON file first', 'error'); return; }
    if (!pass) { toast('Not authenticated', 'error'); return; }
    if (!confirm(`⚠️ This will OVERWRITE all ${type} on the server. Continue?`)) return;
    try {
        const text = await file.text();
        const items = JSON.parse(text);
        if (!Array.isArray(items)) throw new Error('File must contain a JSON array');
        const r = await fetch('./api/content.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ admin_pass: pass, type, items })
        });
        const d = await r.json();
        if (d.success) {
            status.textContent = `âœ… Imported ${d.data?.count ?? items.length} items into ${type}`;
            status.className = 'status-ok'; status.style.display = 'block';
            toast('Import successful!', 'success');
        } else throw new Error(d.error);
    } catch (e) {
        status.textContent = 'âŒ ' + e.message; status.className = 'status-error'; status.style.display = 'block';
        toast('Import failed: ' + e.message, 'error');
    }
}

function exportBookingsCSV() {
    const bookings = getBookings();
    if (!bookings.length) { toast('No bookings to export', 'info'); return; }
    const headers = ['Name', 'Phone', 'Date', 'Slot', 'Reason', 'Status'];
    const rows = bookings.map(b => [b.name, b.phone, b.dateLabel, b.slot, b.reason, b.status].map(v => `"${(v || '').replace(/"/g, '""')}"`).join(','));
    const csv = [headers.join(','), ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = `bookings_${new Date().toISOString().slice(0, 10)}.csv`; a.click();
    toast('Bookings exported as CSV!', 'success');
}

function exportBookingsJSON() {
    const bookings = getBookings();
    const blob = new Blob([JSON.stringify(bookings, null, 2)], { type: 'application/json' });
    const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
    a.download = `bookings_${new Date().toISOString().slice(0, 10)}.json`; a.click();
    toast('Bookings exported as JSON!', 'success');
}

// Improve all save functions to use toasts instead of alert()
const _origSaveMyth = saveMythCard;
saveMythCard = async function () {
    const stmt = document.getElementById('myth-statement').value.trim();
    const fact = document.getElementById('myth-fact').value.trim();
    const source = document.getElementById('myth-source').value.trim();
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!stmt || !fact) { toast('Statement and Fact are required', 'error'); return; }
    if (!pass) { toast('Not authenticated', 'error'); return; }
    const editId = parseInt(document.getElementById('myth-edit-id').value);
    let updatedList = [...currentMyths];
    if (editId) {
        const idx = updatedList.findIndex(m => m.id === editId);
        if (idx > -1) updatedList[idx] = { id: editId, statement: stmt, fact, source };
    } else { updatedList.push({ id: Date.now(), statement: stmt, fact, source }); }
    try {
        const res = await fetch('./api/content.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_pass: pass, type: 'myth_busters', items: updatedList }) });
        const d = await res.json();
        if (d.success) { toast('Myth card saved!', 'success'); clearMythForm(); renderMyths(); }
        else toast('Save failed: ' + (d.error || '?'), 'error');
    } catch (e) { toast('Connection error', 'error'); }
};

const _origSaveQuiz = saveQuizQuestion;
saveQuizQuestion = async function () {
    const quest = document.getElementById('q-question').value.trim();
    const optA = document.getElementById('q-a').value.trim();
    const optB = document.getElementById('q-b').value.trim();
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!quest || !optA || !optB) { toast('Question and Options A & B are required', 'error'); return; }
    if (!pass) { toast('Not authenticated', 'error'); return; }
    const options = [optA, optB, document.getElementById('q-c').value.trim(), document.getElementById('q-d').value.trim()].filter(Boolean);
    const correct = parseInt(document.getElementById('q-correct').value);
    const explanation = document.getElementById('q-explanation').value.trim();
    const editIdx = document.getElementById('q-edit-id').value;
    let updatedBank = [...currentQuizBank];
    if (editIdx !== '') updatedBank[parseInt(editIdx)] = { q: quest, options, correct, explanation };
    else updatedBank.push({ q: quest, options, correct, explanation });
    try {
        const res = await fetch('./api/content.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_pass: pass, type: 'quiz_questions', items: updatedBank }) });
        const d = await res.json();
        if (d.success) { toast('Question saved!', 'success'); clearQuizForm(); renderQuizEditor(); }
        else toast('Save failed: ' + (d.error || '?'), 'error');
    } catch (e) { toast('Connection error', 'error'); }
};

const _origSaveResearch = saveResearchPaper;
saveResearchPaper = async function () {
    const title = document.getElementById('rp-title').value.trim();
    const journal = document.getElementById('rp-journal').value.trim();
    const year = parseInt(document.getElementById('rp-year').value);
    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!title || !journal || !year) { toast('Title, Journal and Year are required', 'error'); return; }
    if (!pass) { toast('Not authenticated', 'error'); return; }
    const editId = parseInt(document.getElementById('rp-edit-id').value);
    const newPaper = { id: editId || Date.now(), title, journal, year, topic: document.getElementById('rp-topic').value, doi: document.getElementById('rp-doi').value.trim(), takeaway: document.getElementById('rp-takeaway').value.trim(), practice: document.getElementById('rp-practice').value.trim() };
    let updatedList = [...currentResearchList];
    if (editId) { const idx = updatedList.findIndex(p => p.id === editId); if (idx > -1) updatedList[idx] = newPaper; } else updatedList.push(newPaper);
    try {
        const res = await fetch('./api/content.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ admin_pass: pass, type: 'research_papers', items: updatedList }) });
        const d = await res.json();
        if (d.success) { toast('Research paper saved!', 'success'); clearResearchForm(); renderResearch(); }
        else toast('Save failed: ' + (d.error || '?'), 'error');
    } catch (e) { toast('Connection error', 'error'); }
};

// Also wrap all renderX fetch calls to handle new { success, data } format
const _origRenderMyths = renderMyths;
renderMyths = async function () {
    try {
        const res = await fetch('./api/content.php?type=myth_busters');
        const json = await res.json();
        const serverData = json.data ?? json;
        currentMyths = (serverData && serverData.length) ? serverData : DEFAULT_MYTHS;
        document.getElementById('myth-count').textContent = currentMyths.length + ' cards';
        document.getElementById('myth-table-body').innerHTML = currentMyths.map(m => `
                  <div style="border-bottom:1px solid var(--ad-border);padding:14px 16px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:start;">
                    <div>
                      <div style="font-size:0.84rem;font-weight:700;color:var(--ad-text);margin-bottom:4px;">⚠️ ${m.statement}</div>
                      <div style="font-size:0.78rem;color:var(--ad-text-muted);">âœ… ${m.fact.substring(0, 100)}...</div>
                      <div style="font-size:0.72rem;color:var(--ad-indigo);margin-top:4px;">â€” ${m.source}</div>
                    </div>
                    <div class="action-btns">
                      <button class="action-btn action-btn-edit myth-edit-btn" data-id="${m.id}">Edit</button>
                      <button class="action-btn action-btn-delete myth-delete-btn" data-id="${m.id}">Delete</button>
                    </div>
                  </div>`).join('');
    } catch (err) { console.error('Error loading myths:', err); toast('Failed to load myth cards', 'error'); }
};

const _origRenderResearch = renderResearch;
renderResearch = async function () {
    try {
        const res = await fetch('./api/content.php?type=research_papers');
        const json = await res.json();
        const serverData = json.data ?? json;
        currentResearchList = (serverData && serverData.length) ? serverData : DEFAULT_RESEARCH_SEED;
        document.getElementById('rp-count').textContent = currentResearchList.length + ' papers';
        document.getElementById('rp-table').innerHTML = currentResearchList.map(p => `<tr>
                  <td style="font-weight:700;font-size:0.84rem;">${p.title}</td>
                  <td style="font-size:0.78rem;color:var(--ad-text-muted);">${p.journal}</td>
                  <td>${p.year}</td>
                  <td><span class="status-badge badge-published" style="font-size:0.66rem;">${p.topic}</span></td>
                  <td><div class="action-btns">
                    ${p.doi ? `<a href="${p.doi}" target="_blank" class="action-btn action-btn-edit" style="text-decoration:none;">View â†—</a>` : ''}
                    <button class="action-btn action-btn-edit res-edit-btn" data-id="${p.id}">Edit</button>
                    <button class="action-btn action-btn-delete res-del-btn" data-id="${p.id}">Delete</button>
                  </div></td>
                </tr>`).join('');
    } catch (err) { console.error('Error loading research:', err); toast('Failed to load research papers', 'error'); }
};

const _origRenderQuiz = renderQuizEditor;
renderQuizEditor = async function () {
    try {
        const res = await fetch('./api/content.php?type=quiz_questions');
        const json = await res.json();
        const serverData = json.data ?? json;
        const tbody = document.getElementById('quiz-q-table');
        const countEl = document.getElementById('quiz-q-count');
        if (!serverData || serverData.length === 0) {
            countEl.textContent = 'Using built-in defaults'; tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:20px;color:var(--ad-text-muted);">No custom questions yet. Add one or click Reset to Defaults.</td></tr>`; currentQuizBank = []; return;
        }
        currentQuizBank = serverData; countEl.textContent = currentQuizBank.length + ' questions';
        tbody.innerHTML = currentQuizBank.map((q, i) => {
            const letters = ['A', 'B', 'C', 'D'];
            return `<tr><td style="width:40px;">${i + 1}</td><td style="font-size:0.84rem;">${q.q.substring(0, 80)}${q.q.length > 80 ? '...' : ''}</td><td><span class="status-badge badge-published">${letters[q.correct]}: ${(q.options[q.correct] || '').substring(0, 25)}...</span></td><td><div class="action-btns"><button class="action-btn action-btn-edit quiz-edit-btn" data-idx="${i}">Edit</button><button class="action-btn action-btn-delete quiz-delete-btn" data-idx="${i}">Delete</button></div></td></tr>`;
        }).join('');
    } catch (err) { toast('Failed to load quiz bank', 'error'); }
};

// ============================================================
// SITE IMAGES
// ============================================================
window.previewImage = function (input, targetId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById(targetId).src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
};

window.uploadSiteImage = async function (type, inputId) {
    const fileInput = document.getElementById(inputId);
    if (!fileInput.files || !fileInput.files[0]) {
        toast('Please select a file first', 'error');
        return;
    }

    const pass = localStorage.getItem('apollo_admin_temp_pass') || '';
    if (!pass) {
        toast('Authentication required', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('image', fileInput.files[0]);
    formData.append('type', type);
    formData.append('admin_pass', pass);

    const btn = fileInput.closest('.editor-card').querySelector('button');
    const originalText = btn.textContent;
    btn.textContent = '⏱️ Uploading...';
    btn.disabled = true;

    try {
        const res = await fetch('./api/upload_image.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            toast('✅ Image uploaded successfully!', 'success');
            // Refresh preview with cache buster
            const prevId = 'prev-img-' + type;
            const img = document.getElementById(prevId);
            img.src = img.src.split('?')[0] + '?v=' + Date.now();
            fileInput.value = '';
        } else {
            toast('❌ Upload failed: ' + (data.error || 'Unknown error'), 'error');
        }
    } catch (err) {
        toast('❌ Connection error: ' + err.message, 'error');
    } finally {
        btn.textContent = originalText;
        btn.disabled = false;
    }
};
// ============================================================
// AUTH & SESSION
// ============================================================
window.doLogin = async function () {
    const u = document.getElementById('admin-user').value.trim();
    const p = document.getElementById('admin-pass').value.trim();
    const err = document.getElementById('login-error');
    if (!u || !p) { err.textContent = 'Enter username and password'; err.style.display = 'block'; return; }

    try {
        const r = await fetch('./api/settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'check_auth', admin_pass: p })
        });
        if (!r.ok) throw new Error(`HTTP ${r.status} ${r.statusText}`);
        const d = await r.json();
        if (d.success) {
            localStorage.setItem('apollo_admin_logged', 'true');
            localStorage.setItem('apollo_admin_temp_pass', p);
            document.getElementById('login-screen').style.display = 'none';
            document.getElementById('admin-layout').style.display = 'flex';
            loadAll();
        } else {
            err.textContent = 'Invalid credentials'; err.style.display = 'block';
        }
    } catch (e) {
        console.error('Auth Error:', e);
        err.innerHTML = `Server error: ${e.message}<br><small>Check if <b>api/settings.php</b> was uploaded to Hostinger.</small>`;
        err.style.display = 'block';
    }
};

window.doLogout = function () {
    localStorage.removeItem('apollo_admin_logged');
    localStorage.removeItem('apollo_admin_temp_pass');
    document.getElementById('login-screen').style.display = 'flex';
    document.getElementById('admin-layout').style.display = 'none';
    document.getElementById('admin-user').value = '';
    document.getElementById('admin-pass').value = '';
};

// ============================================================
// SITE SETTINGS
// ============================================================
window.loadCurrentSettings = async function () {
    try {
        const r = await fetch('./api/settings.php');
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        const s = await r.json();

        const map = {
            'st-wa-number': s.wa_number,
            'st-icu-phone': s.icu_phone,
            'st-wa-message': s.wa_message,
            'st-site-name': s.site_name,
            'st-hero-title': s.hero_title,
            'st-hero-tagline': s.hero_tagline,
            'st-hero-empathy': s.hero_empathy,
            'st-admin-user': s.admin_user
        };

        for (const [id, val] of Object.entries(map)) {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        }
    } catch (err) { console.error('Error loading settings:', err); }
};

// Auto-run on load if logged in
document.addEventListener('DOMContentLoaded', () => {
    if (localStorage.getItem('apollo_admin_logged') === 'true') {
        document.getElementById('login-screen').style.display = 'none';
        document.getElementById('admin-layout').style.display = 'flex';
        loadAll();
    }

    // Auth Enter Listeners
    const pField = document.getElementById('admin-pass');
    if (pField) pField.addEventListener('keydown', e => { if (e.key === 'Enter') doLogin(); });

    const uField = document.getElementById('admin-user');
    if (uField) uField.addEventListener('keydown', e => { if (e.key === 'Enter') pField.focus(); });
});
