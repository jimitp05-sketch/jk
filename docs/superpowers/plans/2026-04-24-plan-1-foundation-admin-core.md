# Plan 1: Foundation + Admin Core — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create all new DB tables, fix the hero image distortion, rebuild the admin dashboard with live analytics, add bulk moderation to all community panels, and add the newsletter signup strip + subscribers admin panel.

**Architecture:** New DB tables are created via a one-time migration script (`api/migrate.php`). Analytics are served by a new `api/dashboard_stats.php` endpoint. Newsletter subscribers are managed by a new `api/subscribers.php` endpoint. Admin JS and panel HTML are extended in-place following existing patterns (`switchPanel`, `admin-nav-link`, `admin-panel` divs).

**Tech Stack:** PHP 7+ / PDO / MySQL, Vanilla JS (no framework), HTML/CSS. No build tools. No npm.

---

## File Map

| Action | File | Purpose |
|---|---|---|
| Create | `api/migrate.php` | One-time DB migration script |
| Create | `api/dashboard_stats.php` | Live analytics endpoint |
| Create | `api/subscribers.php` | Newsletter subscriber CRUD |
| Create | `admin/panels/subscribers.php` | Subscribers admin panel HTML |
| Modify | `styles.css` | Hero image fix + newsletter strip styles + bulk moderation styles |
| Modify | `index.html` | Hero img `height: auto` attribute + newsletter strip HTML |
| Modify | `admin.php` | Add Subscribers nav link + include subscribers panel |
| Modify | `admin/panels/dashboard.php` | Analytics widgets |
| Modify | `admin/panels/reviews.php` | Bulk action checkboxes + toolbar |
| Modify | `admin/panels/diya.php` | Bulk action checkboxes + toolbar |
| Modify | `admin/panels/memories.php` | Bulk action checkboxes + toolbar |
| Modify | `admin.js` | Dashboard analytics loader + bulk moderation logic + subscribers panel JS |

---

## Task 1: DB Migrations

**Files:**
- Create: `api/migrate.php`

- [ ] **Step 1: Create migration script**

Create `api/migrate.php`:

```php
<?php
/**
 * One-time migration — run once via browser: /api/migrate.php?secret=apollo_migrate_2026
 * After running, delete or rename this file.
 */
require_once __DIR__ . '/db.php';

$secret = $_GET['secret'] ?? '';
if ($secret !== 'apollo_migrate_2026') {
    http_response_code(403);
    die(json_encode(['error' => 'Forbidden']));
}

header('Content-Type: application/json');
$pdo = get_db_connection();
$results = [];

$migrations = [
    'subscribers' => "
        CREATE TABLE IF NOT EXISTS subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(100) DEFAULT '',
            source VARCHAR(50) DEFAULT 'homepage',
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'wiki_entries' => "
        CREATE TABLE IF NOT EXISTS wiki_entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            term VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            category ENUM('procedure','equipment','condition','medication','acronym') NOT NULL,
            definition_plain TEXT NOT NULL,
            definition_clinical TEXT,
            related_pillar VARCHAR(100) DEFAULT '',
            meta_title VARCHAR(60) DEFAULT '',
            meta_description VARCHAR(160) DEFAULT '',
            status ENUM('published','draft') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'wiki_related_terms' => "
        CREATE TABLE IF NOT EXISTS wiki_related_terms (
            wiki_id INT NOT NULL,
            related_wiki_id INT NOT NULL,
            PRIMARY KEY (wiki_id, related_wiki_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'courses' => "
        CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT,
            audience ENUM('families','clinicians','survivors','all') DEFAULT 'all',
            article_ids TEXT DEFAULT '',
            status ENUM('published','draft') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'course_completions' => "
        CREATE TABLE IF NOT EXISTS course_completions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

foreach ($migrations as $name => $sql) {
    try {
        $pdo->exec($sql);
        $results[$name] = 'OK';
    } catch (PDOException $e) {
        $results[$name] = 'ERROR: ' . $e->getMessage();
    }
}

echo json_encode(['success' => true, 'results' => $results], JSON_PRETTY_PRINT);
```

- [ ] **Step 2: Run the migration**

Open in browser: `http://localhost/api/migrate.php?secret=apollo_migrate_2026`

Expected response:
```json
{
  "success": true,
  "results": {
    "subscribers": "OK",
    "wiki_entries": "OK",
    "wiki_related_terms": "OK",
    "courses": "OK",
    "course_completions": "OK"
  }
}
```

- [ ] **Step 3: Verify tables exist**

Run in MySQL (via phpMyAdmin or CLI):
```sql
SHOW TABLES LIKE 'subscribers';
SHOW TABLES LIKE 'wiki_entries';
SHOW TABLES LIKE 'courses';
```

Each should return one row.

- [ ] **Step 4: Commit**

```bash
git add api/migrate.php
git commit -m "feat: add DB migration for subscribers, wiki, courses tables"
```

---

## Task 2: Hero Image Distortion Fix

**Files:**
- Modify: `styles.css` (lines ~1004–1008 and ~4949–4952)

- [ ] **Step 1: Locate and inspect current rules**

In `styles.css`, find these two rules:
```css
/* Around line 1004 */
.hero-image-wrapper img {
    width: 100%;
    max-width: 420px;
    border-radius: var(--radius-xl);
    display: block;
}

/* Around line 4949 */
.hero-image-wrapper img {
    max-width: 100% !important;
    width: 100%;
}
```

- [ ] **Step 2: Fix the primary rule (line ~1004)**

Replace the first rule with:
```css
.hero-image-wrapper img {
    width: 100%;
    height: auto;
    max-width: 420px;
    border-radius: var(--radius-xl);
    display: block;
    object-fit: contain;
}
```

- [ ] **Step 3: Fix the responsive override (line ~4949)**

Replace the responsive rule with:
```css
.hero-image-wrapper img {
    max-width: 100% !important;
    width: 100%;
    height: auto !important;
    object-fit: contain !important;
}
```

- [ ] **Step 4: Fix the hero-doctor-img class (line ~5276)**

Find:
```css
.hero-doctor-img {
    max-width: 420px;
    border-radius: 30px;
}
```

Replace with:
```css
.hero-doctor-img {
    max-width: 420px;
    border-radius: 30px;
    height: auto;
    object-fit: contain;
}
```

- [ ] **Step 5: Add aspect-ratio to wrapper to prevent collapse**

Find `.hero-image-wrapper {` (the main rule around line 995) and add `aspect-ratio: 6 / 7;`:

```css
.hero-image-wrapper {
    position: relative;
    flex-shrink: 0;
    border-radius: var(--radius-xl);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .12);
    box-shadow: 0 40px 100px rgba(0, 0, 0, .55), 0 0 0 1px rgba(0, 180, 216, .1);
    aspect-ratio: 6 / 7;
    max-width: 420px;
}
```

- [ ] **Step 6: Verify visually**

Open `index.html` in browser. Check:
- Hero doctor image renders at natural portrait proportions on desktop
- No stretching or squishing at 1440px, 1024px, 768px viewport widths
- On mobile (<768px) the wrapper is hidden — confirm `display: none` still applies

- [ ] **Step 7: Commit**

```bash
git add styles.css
git commit -m "fix: hero doctor image distortion — enforce height:auto and aspect-ratio"
```

---

## Task 3: Dashboard Analytics Endpoint

**Files:**
- Create: `api/dashboard_stats.php`

- [ ] **Step 1: Create the endpoint**

Create `api/dashboard_stats.php`:

```php
<?php
/**
 * GET /api/dashboard_stats.php
 * Returns live counts for the admin dashboard analytics widgets.
 * Requires admin auth (X-Admin-Token header).
 */
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
requireAdmin();

$pdo = get_db_connection();

// ── Total bookings ───────────────────────────────────────────────────────
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pendingBookings = (int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn();

// ── Articles (stored as JSON in content table) ────────────────────────────
$articlesRow = $pdo->query("SELECT data FROM content WHERE content_key = 'knowledge_articles' LIMIT 1")->fetch();
$articles = $articlesRow ? json_decode($articlesRow['data'], true) : [];
$publishedArticles = array_filter($articles, fn($a) => ($a['status'] ?? '') === 'published');
$totalArticles = count($publishedArticles);

// Top 5 articles by view_count field in JSON
usort($articles, fn($a, $b) => ($b['view_count'] ?? 0) - ($a['view_count'] ?? 0));
$topArticles = array_slice($articles, 0, 5);
$topArticles = array_map(fn($a) => [
    'title'      => $a['title'] ?? 'Untitled',
    'pillar'     => $a['pillar'] ?? '',
    'view_count' => (int)($a['view_count'] ?? 0),
], $topArticles);

// ── Community activity ─────────────────────────────────────────────────────
$diyaRow = $pdo->query("SELECT data FROM content WHERE content_key = 'diyas' LIMIT 1")->fetch();
$diyas = $diyaRow ? json_decode($diyaRow['data'], true) : [];
$pendingDiyas = count(array_filter($diyas, fn($d) => ($d['status'] ?? 'pending') === 'pending'));

$reviewsRow = $pdo->query("SELECT data FROM content WHERE content_key = 'peer_recognitions' LIMIT 1")->fetch();
$reviews = $reviewsRow ? json_decode($reviewsRow['data'], true) : [];
$pendingReviews = count(array_filter($reviews, fn($r) => ($r['status'] ?? 'pending') === 'pending'));

$memoriesRow = $pdo->query("SELECT data FROM content WHERE content_key = 'memories' LIMIT 1")->fetch();
$memories = $memoriesRow ? json_decode($memoriesRow['data'], true) : [];
$pendingMemories = count(array_filter($memories, fn($m) => ($m['status'] ?? 'pending') === 'pending'));

// ── Subscribers ────────────────────────────────────────────────────────────
try {
    $totalSubscribers = (int)$pdo->query("SELECT COUNT(*) FROM subscribers")->fetchColumn();
    $newSubscribersWeek = (int)$pdo->query("SELECT COUNT(*) FROM subscribers WHERE subscribed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
} catch (Exception $e) {
    $totalSubscribers = 0;
    $newSubscribersWeek = 0;
}

respond([
    'success' => true,
    'data' => [
        'bookings'          => $totalBookings,
        'pending_bookings'  => $pendingBookings,
        'articles'          => $totalArticles,
        'pending_reviews'   => $pendingReviews,
        'pending_diyas'     => $pendingDiyas,
        'pending_memories'  => $pendingMemories,
        'top_articles'      => $topArticles,
        'subscribers'       => $totalSubscribers,
        'new_subscribers'   => $newSubscribersWeek,
    ],
    'error' => null
]);
```

- [ ] **Step 2: Verify endpoint**

With admin session active, open browser console on admin page and run:
```javascript
fetch('./api/dashboard_stats.php', {
    headers: { 'X-Admin-Token': getSessionToken() }
}).then(r => r.json()).then(console.log)
```

Expected: `{ success: true, data: { bookings: N, pending_bookings: N, ... } }`

- [ ] **Step 3: Commit**

```bash
git add api/dashboard_stats.php
git commit -m "feat: add dashboard_stats API endpoint with live analytics"
```

---

## Task 4: Dashboard Panel Rebuild

**Files:**
- Modify: `admin/panels/dashboard.php`
- Modify: `admin.js`

- [ ] **Step 1: Replace dashboard.php**

Replace the entire contents of `admin/panels/dashboard.php`:

```html
<!-- DASHBOARD -->
<div class="admin-panel active" id="panel-dashboard">
    <div class="panel-header">
        <h1>Dashboard</h1>
        <p>Live overview of consultations, content, and community.</p>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-bookings">—</div>
            <div class="admin-stat-label">Total Bookings</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-books">—</div>
            <div class="admin-stat-label">Pending Requests</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-articles">—</div>
            <div class="admin-stat-label">Published Articles</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-pending-reviews">—</div>
            <div class="admin-stat-label">Pending Approvals</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="stat-subscribers">—</div>
            <div class="admin-stat-label">Subscribers</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">Quick Actions</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;padding:16px;">
            <button class="action-btn action-btn-edit" onclick="bulkApproveAll('peer_recognitions')">✅ Approve All Reviews</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('subscribers')">📧 View Subscribers</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('seohealth')">🔍 SEO Health Check</button>
            <button class="action-btn action-btn-edit" onclick="switchPanel('requests')">📋 View Bookings</button>
        </div>
    </div>

    <!-- SEO Health Summary -->
    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">SEO Health Summary</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('seohealth')">Full Report →</button>
        </div>
        <div id="dash-seo-summary" style="padding:16px;color:var(--text-muted);font-size:0.85rem;">Loading…</div>
    </div>

    <!-- Top Articles -->
    <div class="admin-table-wrap" style="margin-bottom:20px;">
        <div class="table-header">
            <div class="table-title">Top Articles by Views</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('knowledge')">View All →</button>
        </div>
        <table>
            <thead>
                <tr><th>Article</th><th>Pillar</th><th>Views</th></tr>
            </thead>
            <tbody id="dash-top-articles">
                <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px;">Loading…</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Recent Bookings -->
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Recent Booking Requests</div>
            <button class="action-btn action-btn-edit" onclick="switchPanel('requests')">View All</button>
        </div>
        <table>
            <thead>
                <tr><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr>
            </thead>
            <tbody id="dashboard-recent-bookings">
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No booking requests yet.</td></tr>
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 2: Add dashboard analytics loader to admin.js**

Find the `loadAll` function in `admin.js`. After its opening brace, add a call to the new loader. Search for `function loadAll` and add `loadDashboardStats();` inside it.

Then add this new function anywhere in `admin.js` (before `loadAll` is fine):

```javascript
async function loadDashboardStats() {
    try {
        const r = await fetch('./api/dashboard_stats.php', {
            headers: { 'X-Admin-Token': getSessionToken() }
        });
        const d = await r.json();
        if (!d.success) return;
        const s = d.data;

        const set = (id, val) => { const el = document.getElementById(id); if (el) el.textContent = val; };
        set('stat-bookings', s.bookings);
        set('stat-pending-books', s.pending_bookings);
        set('stat-articles', s.articles);
        set('stat-pending-reviews', s.pending_reviews + s.pending_diyas + s.pending_memories);
        set('stat-subscribers', s.subscribers);

        // Top articles table
        const tbody = document.getElementById('dash-top-articles');
        if (tbody) {
            tbody.innerHTML = s.top_articles.length
                ? s.top_articles.map(a => `
                    <tr>
                        <td>${escH(a.title)}</td>
                        <td><span style="font-size:0.75rem;color:var(--text-muted)">${escH(a.pillar)}</span></td>
                        <td>${a.view_count}</td>
                    </tr>`).join('')
                : '<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px;">No article views tracked yet.</td></tr>';
        }

        // SEO health summary
        loadSEOHealthSummary(tbody);
    } catch (e) {
        console.error('Dashboard stats error:', e);
    }
}

async function loadSEOHealthSummary() {
    const el = document.getElementById('dash-seo-summary');
    if (!el) return;
    try {
        const r = await fetch('./api/content.php?type=knowledge_articles');
        const d = await r.json();
        const articles = d.data || [];
        const missingMeta = articles.filter(a => !a.meta_description).length;
        const shortArticles = articles.filter(a => {
            const wc = ((a.structured?.sections || []).map(s => (s.content || '')).join(' ').split(/\s+/).length);
            return wc < 500;
        }).length;
        const noKeyword = articles.filter(a => !a.focus_keyword).length;
        el.innerHTML = `
            <span style="margin-right:20px;">📄 Missing meta: <strong>${missingMeta}</strong></span>
            <span style="margin-right:20px;">📝 Under 500 words: <strong>${shortArticles}</strong></span>
            <span>🔑 No keyword: <strong>${noKeyword}</strong></span>
        `;
    } catch (e) {
        el.textContent = 'Could not load SEO summary.';
    }
}
```

- [ ] **Step 3: Verify dashboard loads analytics**

Log in to admin → Dashboard panel should show live numbers in all 5 stat boxes and top articles table.

- [ ] **Step 4: Commit**

```bash
git add admin/panels/dashboard.php admin.js
git commit -m "feat: rebuild admin dashboard with live analytics and quick actions"
```

---

## Task 5: Bulk Moderation — Reviews Panel

**Files:**
- Modify: `admin/panels/reviews.php`
- Modify: `admin.js`

- [ ] **Step 1: Read current reviews.php**

Open `admin/panels/reviews.php` and locate the `<table>` element and its `<thead>`.

- [ ] **Step 2: Add bulk toolbar and checkbox column to reviews panel**

In `admin/panels/reviews.php`, add a bulk toolbar above the table and a checkbox `<th>` as the first column. Replace the panel's table section with:

```html
<!-- REVIEW APPROVALS -->
<div class="admin-panel" id="panel-reviews">
    <div class="panel-header">
        <h1>Review Approvals</h1>
        <p>Approve or reject community-submitted reviews.</p>
    </div>

    <!-- Bulk toolbar (shown when items selected) -->
    <div id="reviews-bulk-bar" style="display:none;background:var(--bg-section);border:1px solid var(--border);border-radius:8px;padding:10px 16px;margin-bottom:12px;display:none;align-items:center;gap:12px;">
        <span id="reviews-bulk-count" style="font-weight:600;color:var(--text);">0 selected</span>
        <button class="action-btn action-btn-edit" onclick="bulkModerate('reviews','approved')">✅ Approve Selected</button>
        <button class="action-btn action-btn-reject" onclick="bulkModerate('reviews','rejected')">❌ Reject Selected</button>
        <button class="action-btn" style="margin-left:auto;color:var(--text-muted);background:none;border:none;cursor:pointer;" onclick="clearBulkSelection('reviews')">Clear</button>
    </div>

    <!-- Filter -->
    <div style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;">
        <button class="action-btn action-btn-edit" onclick="filterReviews('all')">All</button>
        <button class="action-btn" onclick="filterReviews('pending')" style="background:var(--bg-section);">Pending</button>
        <button class="action-btn" onclick="filterReviews('approved')" style="background:var(--bg-section);">Approved</button>
    </div>

    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">Submitted Reviews <span class="admin-badge" id="badge-reviews">0</span></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="reviews-select-all" onchange="toggleSelectAll('reviews',this.checked)" /></th>
                    <th>Name / Platform</th>
                    <th>Review</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="reviews-table-body">
                <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:28px;">No reviews yet.</td></tr>
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 3: Add bulk moderation JS to admin.js**

Add this block to `admin.js`:

```javascript
// ============================================================
// BULK MODERATION
// ============================================================

function toggleSelectAll(type, checked) {
    document.querySelectorAll(`.bulk-check-${type}`).forEach(cb => cb.checked = checked);
    updateBulkBar(type);
}

function updateBulkBar(type) {
    const selected = document.querySelectorAll(`.bulk-check-${type}:checked`);
    const bar = document.getElementById(`${type}-bulk-bar`);
    const countEl = document.getElementById(`${type}-bulk-count`);
    if (!bar) return;
    if (selected.length > 0) {
        bar.style.display = 'flex';
        if (countEl) countEl.textContent = `${selected.length} selected`;
    } else {
        bar.style.display = 'none';
    }
}

function clearBulkSelection(type) {
    document.querySelectorAll(`.bulk-check-${type}`).forEach(cb => cb.checked = false);
    const selectAll = document.getElementById(`${type}-select-all`);
    if (selectAll) selectAll.checked = false;
    updateBulkBar(type);
}

async function bulkModerate(type, newStatus) {
    const checkboxes = document.querySelectorAll(`.bulk-check-${type}:checked`);
    if (!checkboxes.length) return;
    const ids = Array.from(checkboxes).map(cb => cb.dataset.id);
    const confirmed = newStatus === 'deleted'
        ? confirm(`Delete ${ids.length} item(s)? This cannot be undone.`)
        : true;
    if (!confirmed) return;

    // Map UI type to content key
    const keyMap = { reviews: 'peer_recognitions', diya: 'diyas', memories: 'memories' };
    const contentKey = keyMap[type] || type;

    try {
        const r = await fetch('./api/content.php?type=' + contentKey);
        const d = await r.json();
        let items = d.data || [];

        items = items.map(item => {
            if (ids.includes(String(item.id))) {
                return newStatus === 'deleted' ? null : { ...item, status: newStatus };
            }
            return item;
        }).filter(Boolean);

        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Admin-Token': getSessionToken() },
            body: JSON.stringify({ type: contentKey, items, session_token: getSessionToken() })
        });
        const result = await res.json();
        if (result.success) {
            toast(`✅ ${ids.length} item(s) ${newStatus === 'deleted' ? 'deleted' : newStatus}`, 'success');
            clearBulkSelection(type);
            // Reload the relevant panel
            if (type === 'reviews') renderReviews();
            if (type === 'diya') renderDiyas();
            if (type === 'memories') renderMemories();
        } else {
            toast('Error: ' + (result.error || 'Unknown'), 'error');
        }
    } catch (e) {
        toast('Connection error: ' + e.message, 'error');
    }
}

async function bulkApproveAll(contentKey) {
    if (!confirm('Approve all pending items?')) return;
    try {
        const r = await fetch('./api/content.php?type=' + contentKey);
        const d = await r.json();
        let items = (d.data || []).map(item =>
            item.status === 'pending' ? { ...item, status: 'approved' } : item
        );
        const res = await fetch('./api/content.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Admin-Token': getSessionToken() },
            body: JSON.stringify({ type: contentKey, items, session_token: getSessionToken() })
        });
        const result = await res.json();
        if (result.success) {
            toast('✅ All pending items approved', 'success');
            loadDashboardStats();
        } else {
            toast('Error: ' + (result.error || 'Unknown'), 'error');
        }
    } catch (e) {
        toast('Error: ' + e.message, 'error');
    }
}
```

- [ ] **Step 4: Update renderReviews() to include checkboxes**

Find the existing `renderReviews` function in `admin.js`. Find where it builds `<tr>` rows for each review. Add a checkbox cell as the first `<td>` in each row:

```javascript
// At the start of each review row, add:
<td><input type="checkbox" class="bulk-check-reviews" data-id="${escH(String(r.id))}" onchange="updateBulkBar('reviews')" /></td>
```

Also add hover title showing full review text to the review text cell:
```javascript
<td title="${escH(r.text || '')}">${escH((r.text || '').substring(0, 80))}${(r.text||'').length > 80 ? '…' : ''}</td>
```

- [ ] **Step 5: Apply same pattern to diya.php and memories.php**

Repeat Step 2 structure (bulk toolbar + checkbox column) for:
- `admin/panels/diya.php` → type `diya`, bar ID `diya-bulk-bar`, select-all ID `diya-select-all`
- `admin/panels/memories.php` → type `memories`, bar ID `memories-bulk-bar`, select-all ID `memories-select-all`

Update `renderDiyas()` and `renderMemories()` in `admin.js` the same way as Step 4.

- [ ] **Step 6: Add bulk bar CSS to styles.css or admin.css**

```css
/* Bulk action bar */
[id$="-bulk-bar"] {
    animation: fadeIn 0.15s ease;
}
```

- [ ] **Step 7: Verify bulk moderation**

Log in → Reviews → Check 2 items → "Approve Selected" → both should show as approved and the count badge should update.

- [ ] **Step 8: Commit**

```bash
git add admin/panels/reviews.php admin/panels/diya.php admin/panels/memories.php admin.js styles.css
git commit -m "feat: add bulk moderation with select-all, approve/reject to reviews, diya, memories"
```

---

## Task 6: Newsletter API + Subscribers Admin Panel

**Files:**
- Create: `api/subscribers.php`
- Create: `admin/panels/subscribers.php`
- Modify: `index.html`
- Modify: `admin.php`
- Modify: `admin.js`
- Modify: `styles.css`

- [ ] **Step 1: Create subscribers API**

Create `api/subscribers.php`:

```php
<?php
/**
 * POST { email, name?, source? }          → subscribe (public, no auth)
 * GET  ?action=list  + X-Admin-Token      → list subscribers (admin only)
 * POST { action: 'delete', id }           → delete subscriber (admin only)
 */
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
setCORSHeaders();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$pdo = get_db_connection();

// ── GET: List subscribers (admin) ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAdmin();
    $action = $_GET['action'] ?? 'list';
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, email, name, source, subscribed_at FROM subscribers ORDER BY subscribed_at DESC");
        $rows = $stmt->fetchAll();
        respond(['success' => true, 'data' => $rows, 'error' => null]);
    }
    respond(['success' => false, 'data' => null, 'error' => 'Unknown action'], 400);
}

// ── POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? 'subscribe';

    // Delete (admin only)
    if ($action === 'delete') {
        requireAdmin();
        $id = (int)($input['id'] ?? 0);
        if (!$id) respond(['success' => false, 'data' => null, 'error' => 'Invalid ID'], 400);
        $pdo->prepare("DELETE FROM subscribers WHERE id = ?")->execute([$id]);
        respond(['success' => true, 'data' => null, 'error' => null]);
    }

    // Subscribe (public)
    $email = trim($input['email'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(['success' => false, 'data' => null, 'error' => 'Valid email required'], 400);
    }

    // Rate limit: max 3 subscribe attempts per IP per hour
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!checkRateLimit($ip, 3, 3600, 'subscribe')) {
        respond(['success' => false, 'data' => null, 'error' => 'Too many attempts'], 429);
    }

    $name   = trim($input['name'] ?? '');
    $source = trim($input['source'] ?? 'homepage');

    try {
        $stmt = $pdo->prepare("INSERT INTO subscribers (email, name, source) VALUES (?, ?, ?)");
        $stmt->execute([$email, $name, $source]);
        respond(['success' => true, 'data' => ['message' => 'Subscribed!'], 'error' => null]);
    } catch (PDOException $e) {
        // Duplicate entry = already subscribed
        if ($e->getCode() === '23000') {
            respond(['success' => true, 'data' => ['message' => 'Already subscribed!'], 'error' => null]);
        }
        error_log('Subscriber insert failed: ' . $e->getMessage());
        respond(['success' => false, 'data' => null, 'error' => 'Failed to subscribe'], 500);
    }
}

respond(['success' => false, 'data' => null, 'error' => 'Method not allowed'], 405);
```

- [ ] **Step 2: Create subscribers admin panel**

Create `admin/panels/subscribers.php`:

```html
<!-- SUBSCRIBERS -->
<div class="admin-panel" id="panel-subscribers">
    <div class="panel-header">
        <h1>Newsletter Subscribers</h1>
        <p>Manage email subscribers who signed up via the ICU Family Guide.</p>
    </div>
    <div class="stats-row" style="margin-bottom:20px;">
        <div class="admin-stat">
            <div class="admin-stat-num" id="sub-total">—</div>
            <div class="admin-stat-label">Total Subscribers</div>
        </div>
        <div class="admin-stat">
            <div class="admin-stat-num" id="sub-new-week">—</div>
            <div class="admin-stat-label">New This Week</div>
        </div>
    </div>
    <div class="admin-table-wrap">
        <div class="table-header">
            <div class="table-title">All Subscribers</div>
            <div style="display:flex;gap:8px;">
                <input type="text" id="sub-search" placeholder="Search email…"
                    oninput="filterSubscribers()" style="padding:6px 10px;border-radius:6px;border:1px solid var(--border);background:var(--bg-section);color:var(--text);font-size:0.85rem;" />
                <button class="action-btn action-btn-edit" onclick="exportSubscribersCSV()">📥 Export CSV</button>
            </div>
        </div>
        <table>
            <thead>
                <tr><th>Email</th><th>Name</th><th>Source</th><th>Subscribed</th><th>Action</th></tr>
            </thead>
            <tbody id="sub-table-body">
                <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>
```

- [ ] **Step 3: Add subscribers JS to admin.js**

```javascript
// ============================================================
// SUBSCRIBERS
// ============================================================
let _allSubscribers = [];

async function loadSubscribers() {
    try {
        const r = await fetch('./api/subscribers.php?action=list', {
            headers: { 'X-Admin-Token': getSessionToken() }
        });
        const d = await r.json();
        _allSubscribers = d.data || [];
        renderSubscribers(_allSubscribers);
        const total = document.getElementById('sub-total');
        const week = document.getElementById('sub-new-week');
        if (total) total.textContent = _allSubscribers.length;
        const oneWeekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000;
        if (week) week.textContent = _allSubscribers.filter(s =>
            new Date(s.subscribed_at).getTime() > oneWeekAgo
        ).length;
    } catch (e) {
        console.error('Subscribers load error:', e);
    }
}

function renderSubscribers(subs) {
    const tbody = document.getElementById('sub-table-body');
    if (!tbody) return;
    tbody.innerHTML = subs.length
        ? subs.map(s => `
            <tr>
                <td>${escH(s.email)}</td>
                <td>${escH(s.name || '—')}</td>
                <td>${escH(s.source || 'homepage')}</td>
                <td style="font-size:0.8rem;color:var(--text-muted)">${new Date(s.subscribed_at).toLocaleDateString()}</td>
                <td><button class="action-btn action-btn-reject" onclick="deleteSubscriber(${s.id})">Delete</button></td>
            </tr>`).join('')
        : '<tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:28px;">No subscribers yet.</td></tr>';
}

function filterSubscribers() {
    const q = (document.getElementById('sub-search')?.value || '').toLowerCase();
    renderSubscribers(_allSubscribers.filter(s => s.email.toLowerCase().includes(q)));
}

async function deleteSubscriber(id) {
    if (!confirm('Remove this subscriber?')) return;
    try {
        const res = await fetch('./api/subscribers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Admin-Token': getSessionToken() },
            body: JSON.stringify({ action: 'delete', id, session_token: getSessionToken() })
        });
        const d = await res.json();
        if (d.success) { toast('Subscriber removed', 'success'); loadSubscribers(); }
        else toast('Error: ' + d.error, 'error');
    } catch (e) { toast('Error: ' + e.message, 'error'); }
}

function exportSubscribersCSV() {
    if (!_allSubscribers.length) { toast('No subscribers to export', 'error'); return; }
    const rows = [['Email', 'Name', 'Source', 'Subscribed At']];
    _allSubscribers.forEach(s => rows.push([s.email, s.name || '', s.source, s.subscribed_at]));
    const csv = rows.map(r => r.map(v => `"${(v||'').replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `subscribers-${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
}
```

- [ ] **Step 4: Add switchPanel hook for subscribers**

Find the `switchPanel` override near line 1518 in `admin.js` and add:
```javascript
if (name === 'subscribers') loadSubscribers();
```

- [ ] **Step 5: Add newsletter strip to index.html**

Before the `</main>` closing tag in `index.html`, add:

```html
<!-- Newsletter Strip -->
<section class="newsletter-strip">
    <div class="container">
        <div class="newsletter-inner">
            <div class="newsletter-text">
                <h3>Free: The ICU Family Survival Guide</h3>
                <p>What every family needs to know during a loved one's critical illness. Written by Dr. Jay Kothari.</p>
            </div>
            <form class="newsletter-form" onsubmit="subscribeNewsletter(event)">
                <input type="email" id="nl-email" placeholder="Your email address" required />
                <button type="submit" class="btn-primary">Send me the guide →</button>
            </form>
            <p id="nl-msg" style="display:none;margin-top:10px;font-weight:600;color:var(--color-teal)"></p>
        </div>
    </div>
</section>
```

- [ ] **Step 6: Add subscribeNewsletter() to script.js**

Open `script.js` and add at the bottom:

```javascript
async function subscribeNewsletter(e) {
    e.preventDefault();
    const email = document.getElementById('nl-email')?.value.trim();
    const msg = document.getElementById('nl-msg');
    if (!email) return;
    try {
        const r = await fetch('./api/subscribers.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, source: 'homepage' })
        });
        const d = await r.json();
        if (msg) {
            msg.style.display = 'block';
            msg.textContent = d.success
                ? '✅ Check your email! The guide is on its way.'
                : (d.error || 'Something went wrong. Please try again.');
            msg.style.color = d.success ? 'var(--color-teal)' : 'var(--color-red)';
        }
        if (d.success) document.getElementById('nl-email').value = '';
    } catch (err) {
        if (msg) { msg.style.display = 'block'; msg.textContent = 'Connection error. Please try again.'; }
    }
}
```

- [ ] **Step 7: Add newsletter strip CSS to styles.css**

```css
/* Newsletter Strip */
.newsletter-strip {
    background: linear-gradient(135deg, var(--bg-dark) 0%, var(--bg-midnight) 100%);
    padding: 60px 0;
    border-top: 1px solid rgba(255,255,255,0.07);
}

.newsletter-inner {
    display: flex;
    align-items: center;
    gap: 40px;
    flex-wrap: wrap;
}

.newsletter-text {
    flex: 1;
    min-width: 260px;
}

.newsletter-text h3 {
    font-family: var(--font-serif);
    font-size: 1.6rem;
    color: var(--text);
    margin-bottom: 8px;
}

.newsletter-text p {
    color: var(--text-muted);
    font-size: 0.95rem;
}

.newsletter-form {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.newsletter-form input[type="email"] {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.07);
    color: var(--text);
    font-size: 0.95rem;
    width: 280px;
    outline: none;
}

.newsletter-form input[type="email"]:focus {
    border-color: var(--color-teal);
}

@media (max-width: 600px) {
    .newsletter-inner { flex-direction: column; align-items: flex-start; }
    .newsletter-form { flex-direction: column; width: 100%; }
    .newsletter-form input[type="email"] { width: 100%; }
}
```

- [ ] **Step 8: Add Subscribers to admin sidebar in admin.php**

Find the "Site Controls" nav group in `admin.php` and add before it:

```html
<div class="admin-nav-group">
    <div class="admin-nav-label">Growth</div>
    <div class="admin-nav-link" data-panel="subscribers" onclick="switchPanel('subscribers')">
        <span class="nav-icon">📧</span>Subscribers <span class="admin-badge" id="badge-subscribers">0</span>
    </div>
</div>
```

- [ ] **Step 9: Include subscribers panel in admin.php**

Find the line that includes the last panel in `admin.php` (something like `<?php include 'admin/panels/export.php'; ?>`) and add after it:

```php
<?php include 'admin/panels/subscribers.php'; ?>
```

- [ ] **Step 10: Verify full flow**

1. Open `index.html` → newsletter strip visible above footer
2. Enter email → submit → success message appears
3. Open admin → Subscribers panel → new email appears in table
4. Click "Export CSV" → CSV downloads with correct data
5. Click "Delete" on a subscriber → removed from table

- [ ] **Step 11: Commit**

```bash
git add api/subscribers.php admin/panels/subscribers.php index.html admin.php admin.js script.js styles.css
git commit -m "feat: newsletter subscriber signup, admin subscribers panel, CSV export"
```

---

**Plan 1 Complete.** After these tasks:
- All DB tables created
- Hero image renders without distortion
- Dashboard shows live analytics
- Bulk moderation works on all 3 community panels
- Newsletter strip captures emails, admin can view and export subscribers
