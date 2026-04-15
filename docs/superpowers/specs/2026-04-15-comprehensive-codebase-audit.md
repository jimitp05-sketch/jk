# Comprehensive Codebase Audit Report
**Project:** AI Apollo - Dr. Jay Kothari Medical Website  
**Date:** 2026-04-15  
**Scope:** Full source code analysis across HTML, CSS, JavaScript, Python, JSON, and PHP  

---

## Executive Summary

The AI Apollo website is a multi-page medical/healthcare site (~12,000 lines across 17 source files) for Dr. Jay Kothari, an ICU specialist. The codebase has **strong medical content quality** and several **good security fundamentals** (prepared SQL statements, password hashing, rate limiting), but suffers from **critical XSS vulnerabilities**, **massive CSS duplication (~30% of styles.css)**, **inline code bloat**, and **patient data privacy concerns**.

### Issue Counts by Severity

| Severity | Count | Categories |
|----------|-------|------------|
| **CRITICAL** | 7 | XSS (4), CSS duplication (2), inline code monoliths (1) |
| **HIGH** | 22 | Security (6), Accessibility (5), SEO (3), Code quality (4), Performance (4) |
| **MEDIUM** | 25+ | Across all categories |
| **LOW** | 15+ | Minor quality and consistency issues |

---

## 1. SECURITY FINDINGS

### CRITICAL

1. **Stored XSS in admin reviews panel** (`admin.js:872-885`) -- `r.name`, `r.text`, `r.status` injected into innerHTML without `escH()`. Attacker-submitted review executes JS in admin context, enabling session hijack.

2. **Stored XSS in public knowledge hub** (`knowledge.html:654`) -- Article `content` field from API injected into innerHTML without sanitization. Combined with admin compromise, enables persistent XSS on public pages.

3. **XSS in photo rendering** (`admin.js:961-964`) -- `p.url`, `p.caption`, `p.label` unescaped in innerHTML. URL like `" onerror="alert(1)` breaks out of img src attribute.

4. **XSS in buildArticleHTML** (`admin.js:568`) -- Section headings, content, callouts, warnings all injected raw. Content saved and served to all visitors.

### HIGH

5. **Hardcoded default admin password** (`api/auth.php:29`) -- `apollo2024` in source code. Anyone with repo access knows the password.

6. **CSRF not enforced** (`api/settings.php:210-212`) -- Token generation exists but validation is explicitly bypassed with a TODO comment.

7. **Patient data stored unencrypted** (`api/booking.php:205-206`) -- Name, email, phone, medical reason stored in plaintext MySQL. No encryption at rest, no audit logging, no consent mechanism. HIPAA/data privacy risk.

8. **Data directory potentially web-accessible** (`api/auth.php:20-21`) -- Session tokens, CSRF tokens stored as JSON files in `data/`. If no `.htaccess` blocks access, tokens can be downloaded.

9. **Booking status changes via GET** (`api/get_bookings.php:83-94`, `admin.js:235`) -- State-changing operations triggered by GET parameters, exploitable via `<img>` tag CSRF.

10. **No Content-Security-Policy on HTML pages** -- Only `settings.php` has CSP. HTML pages allow unrestricted inline script execution.

### MEDIUM

11. **Session token in sessionStorage** (`admin.js:9,15`) -- Accessible to any XSS exploit.
12. **No SRI on external resources** -- Google Fonts loaded without integrity verification.
13. **Error messages leak hosting provider** (`admin.js:56`) -- Reveals "Hostinger" and file paths.

### Positive Security Findings
- Prepared SQL statements throughout (no SQL injection)
- `password_hash()`/`password_verify()` for auth
- Brute force protection (5 attempts, 15-min lockout)
- Per-IP rate limiting on API endpoints
- CORS whitelist (not wildcard `*`)
- IP addresses hashed with SHA-256 for diya submissions
- Image uploads validated via `getimagesize()`
- Database credentials in `.env`, not hardcoded

---

## 2. HTML FINDINGS

### CRITICAL

1. **Duplicate IDs** in `quiz.html:140,202` -- `id="quiz-panel" id="quiz"` and `id="facts-panel" id="facts"`. Breaks HTML validity and anchor navigation.

2. **Placeholder WhatsApp number** -- `919999999999` used in `index.html:71,321,683,700` and `data/legacy/settings.json`. Messages route to wrong contact.

### HIGH

3. **Keyboard-inaccessible calendars** -- `booking.html` and `diya.html` calendar grids use `<div onclick>` without `role`, `tabindex`, or keyboard handlers.

4. **SEO: JS-rendered content invisible to crawlers** -- Knowledge articles (`knowledge.html:249-532`), reviews (`reviews.html`), quiz content (`quiz.html`) all rendered via JavaScript template literals. Search engines see empty containers.

5. **FAQ buttons lack `aria-controls`** (`index.html:608-665`) -- No linkage between toggle buttons and answer panels.

6. **Photo modal lacks focus trapping** (`reviews.html:150-247`) -- Missing `aria-modal="true"`, no focus trap, form uses `onclick` instead of native submit.

7. **Nav toggle inconsistency** -- `booking.html` nav toggle lacks `aria-expanded` present in `index.html`.

### MEDIUM

8. Missing `<main>` element in 6/8 pages
9. Missing canonical URLs, Open Graph tags, and structured data on most sub-pages
10. Emoji-based icons not accessible or consistent across platforms
11. Font loading approach differs between pages (preload vs blocking stylesheet)
12. Navigation links inconsistent across pages ("Reviews" vs "Pulse", varying link sets)

---

## 3. JAVASCRIPT FINDINGS

### CRITICAL

1. **Multiple XSS vectors** -- See Security section above. `renderReviews()`, `renderPhotos()`, `renderPeerRecognitions()`, `buildArticleHTML()` all skip `escH()`.

### HIGH

2. **Function redefinition chaos** (`admin.js`) -- `renderMyths`, `renderResearch`, `renderQuizEditor`, `saveMythCard`, `saveQuizQuestion`, `saveResearchPaper` are each defined TWICE. Original stored in `_orig*` variables that are **never called**. The second definition silently overwrites the first with different logic.

3. **switchPanel defined twice** (`admin.js:188-205` and `1491-1513`) -- Different panel handling logic, second overwrites first.

4. **deletePhoto bug** (`admin.js:996-999`) -- Called with array index but filters by `p.id !== id`. Since index rarely matches a timestamp-based ID, **photos can never be deleted**.

5. **Null reference risk** (`script.js:67`) -- `document.getElementById('navbar')` may be null on pages without `#navbar`, crashing scroll handler.

6. **Mixed localStorage/server storage** -- Photos and test reviews save to localStorage, but render functions fetch from server API. **Data never syncs**.

7. **Global variable pollution** (`admin.js`) -- All functions and variables in global scope. No IIFE or module encapsulation. ~20 global variables, ~50 global functions.

### MEDIUM

8. **Race condition in review operations** (`admin.js:891-907`) -- Fetch, modify by index, POST back. Concurrent operations modify wrong items.
9. **Implicit global `event`** (`admin.js:2205`) -- Non-standard, fails in Firefox strict mode.
10. **Memory leak** (`script.js:371`) -- `setInterval` for ticker never cleared.
11. **No API response validation** -- Most fetch calls do `res.json()` without checking `res.ok`.
12. **Base64 photo uploads** (`admin.js:2474`) -- No file size validation. 5MB photo becomes 6.7MB JSON payload.
13. **Inconsistent error handling** -- Mix of `toast()`, `alert()`, and silent `catch{}`.
14. **No debounce on mousemove** (`script.js:130,144,211`) -- Three handlers fire on every pixel of movement.
15. **DOM query inside mousemove** (`script.js:149`) -- `querySelectorAll('.hero-float-badge')` re-queries DOM on every mouse move.
16. **ReferenceError in loadHeroContent** (`admin.js:1614`) -- Calls `g()` which is only defined inside `saveSettings` scope.

---

## 4. CSS FINDINGS

### CRITICAL

1. **~2000 lines of duplicate rules** in `styles.css` (~30% of file). Full booking system, quiz components, myth cards, reviews/pulse components, FAB, reveal animations all defined twice with slightly different values. Later definitions silently override earlier ones.

2. **`pulse.css` is 100% redundant** -- Every single rule already exists in `styles.css:3032-3449`. The file adds an unnecessary HTTP request.

### HIGH

3. **25+ non-accessibility `!important` declarations** -- Cascade warfare instead of proper specificity management. `.nav-cta` alone uses 9 `!important`.

4. **No print styles** -- Medical website has no `@media print` for appointment confirmations, articles, or contact info.

5. **Footer text fails WCAG AA contrast** -- `rgba(255,255,255,.28)` on `#0A1628` background = ~1.9:1 ratio (needs 4.5:1).

6. **`body::before` noise overlay** (`styles.css:139-149`) -- Fixed full-viewport SVG texture at `z-index:10000` with `mix-blend-mode:overlay`. Forces compositing on every page.

7. **`box-shadow` animations** (`styles.css:899,5318,5581`) -- Trigger expensive paint on every frame. Should use `transform`/`opacity`.

### MEDIUM

8. **10+ scattered `@media (max-width:768px)` blocks** throughout file
9. **23 compatibility aliases** in `:root` mapping old to new token names -- incomplete migration
10. **`@keyframes emergencyPulse` defined twice** with different animations -- silent override
11. **Conflicting property values** between duplicate blocks (e.g., `body line-height: 1.68` vs `1.78`, `form-field margin-bottom: 18px` vs `0px`)
12. **Fragile inline-style attribute selector** (`styles.css:1740`) -- `[style*="display:flex"][style*="justify-content:space-between"]`
13. **Missing tablet breakpoint** between 768px-1024px

---

## 5. DATA & UTILITY FILES

### HIGH

1. **Placeholder WhatsApp number** in `data/legacy/settings.json` -- `919999999999` breaks contact functionality if referenced.

2. **Destructive in-place write** in `extract_css.py` -- Overwrites `reviews.html` with no backup. Failure mid-write truncates production file.

### MEDIUM

3. **Hardcoded absolute paths** in `extract_css.py` -- Only works on one developer's machine.
4. **`os` imported but never used** in `extract_css.py`.

### LOW

5. **`knowledge_articles: []`** in `content.json` -- Empty placeholder, technical debt.
6. **Redundant `doi`/`url` fields** in research papers data.
7. **All quote status fields identical** (`"active"`) -- field is redundant.
8. **HTML entities for icons** in `peer_recognitions` data -- couples data to HTML rendering.

### Positive Data Findings
- Medical content is accurate and well-sourced (real trials: PROSEVA, EOLIA, ARMA, NICE-SUGAR)
- JSON files are valid and consistently structured
- `starter_quotes.json` is clean with no duplicates

---

## 6. TOP 10 PRIORITY REMEDIATIONS

| # | Severity | Issue | Fix |
|---|----------|-------|-----|
| 1 | CRITICAL | XSS in 4+ render functions | Apply `escH()` to ALL server data in innerHTML. Use DOMPurify for article content. |
| 2 | CRITICAL | ~2000 lines CSS duplication | Deduplicate styles.css, delete pulse.css entirely |
| 3 | HIGH | Default admin password in source | Force password change on first login, remove from code |
| 4 | HIGH | CSRF not enforced | Remove the "backward compatibility" bypass, enforce tokens |
| 5 | HIGH | Patient data unencrypted | Encrypt PII at rest (AES-256), add consent checkbox, privacy policy |
| 6 | HIGH | Keyboard-inaccessible calendars | Add `role="gridcell"`, `tabindex`, keyboard event handlers |
| 7 | HIGH | JS-rendered content invisible to SEO | Server-side render knowledge articles and reviews, or use prerendering |
| 8 | HIGH | deletePhoto bug | Fix to pass `p.id` instead of array index in onclick |
| 9 | HIGH | Function redefinition pattern | Remove duplicate definitions, consolidate into single versions |
| 10 | HIGH | Footer contrast fails WCAG AA | Increase footer text opacity to meet 4.5:1 ratio |

---

## 7. ARCHITECTURE RECOMMENDATIONS

1. **Split monolithic files** -- `styles.css` (6895 lines), `admin.js` (2523 lines), `diya.html` (2909 lines), `memories.html` (2671 lines) are all too large to maintain. Extract page-specific CSS/JS into separate files.

2. **Adopt a build system** -- Even a simple tool like `esbuild` or `parcel` would enable CSS/JS concatenation, minification, and dead code elimination.

3. **Implement CSP** -- Add `Content-Security-Policy` header via server config to mitigate XSS impact.

4. **Add automated testing** -- No tests exist currently. At minimum, add accessibility testing (axe-core) and security scanning (eslint-plugin-security).

5. **Template the shared HTML** -- Navigation, footer, and head boilerplate are copy-pasted across 8 HTML files with inconsistencies. Use a templating system or server-side includes.
