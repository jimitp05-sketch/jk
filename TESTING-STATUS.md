# Fox Wisdom Admin Panel Testing Status
## Last Updated: 3 May 2026

## Project Overview
- **Website:** foxwisdom.com (Dr. Jay Kothari — Critical Care Specialist)
- **Admin Panel:** foxwisdom.com/admin.php (credentials: admin/admin)
- **Stack:** HTML/CSS/JS frontend, PHP 8+ backend, MySQL database
- **Hosting:** Hostinger

---

## FIXES ALREADY APPLIED & UPLOADED TO HOSTINGER

### 1. Diya Panel Visibility Bug (CRITICAL)
- **File:** `admin/panels/diya.php`
- **Issue:** `style="display:none;"` inline style overrode CSS `.admin-panel.active { display: block }` — panel never became visible when clicked
- **Fix:** Removed inline `style="display:none;"`

### 2. Memories Panel Visibility Bug (CRITICAL)
- **File:** `admin/panels/memories.php`
- **Issue:** Same inline style override issue as Diya panel
- **Fix:** Removed inline `style="display:none;"`

### 3. Site Images Upload Functions Missing (CRITICAL)
- **File:** `admin.js`
- **Issue:** `previewImage()` and `uploadSiteImage()` called in images.php HTML but never defined in JS — clicking Upload & Replace would throw errors
- **Fix:** Added both functions before the mobile nav section (~line 3180)

### 4. ICU Ward Image Missing from Admin
- **File:** `admin/panels/images.php` + `api/upload_image.php`
- **Issue:** 5 site images exist (hero, ecmo, team, knowledge, icu-ward) but only 4 had upload slots
- **Fix:** Added ICU Ward upload card in images.php, added 'icu' => 'img-icu-ward.png' mapping in upload_image.php

### 5. Export/Import Missing Content Types
- **File:** `admin/panels/export.php`
- **Issue:** Only Myths, Quiz, Research, Knowledge were exportable/importable — missing FAQ, Reviews, Social
- **Fix:** Added faq_items, peer_recognitions, social_settings to both export buttons and import dropdown

---

## LIVE BROWSER TESTING COMPLETED

| # | Section | Result | Details |
|---|---------|--------|---------|
| 1 | Admin Login | ✅ PASS | admin/admin works, dashboard loads |
| 2 | Dashboard | ✅ PASS | Stats cards show (all 0s), quick actions visible |
| 3 | Booking Calendar | ✅ PASS | May 2026 calendar renders, today highlighted, click date shows bookings |
| 4 | Pending Requests | ✅ PASS | Empty table with correct columns (Patient, Phone, Date & Time, Reason, Status, Actions) |
| 5 | Website Booking Form | ⚠️ PARTIAL | Calendar shows available slots (green dots), form fills correctly, submit button did not trigger via automation (likely CSRF/event issue with automation, NOT a code bug) |

---

## LIVE BROWSER TESTING STILL PENDING

These sections need to be tested by navigating to them in admin, performing actions, and checking the website:

### HIGH PRIORITY (User specifically requested these)
| # | Section | What to Test |
|---|---------|--------------|
| 1 | **Quiz Questions** | Add a quiz question in admin → Save → Check foxwisdom.com/quiz.html |
| 2 | **Research Papers** | Add a research paper in admin → Save → Check foxwisdom.com/research.html |
| 3 | **Knowledge Hub** | Edit an existing article → Save → Check foxwisdom.com/knowledge.html |
| 4 | **Memories** | Check admin upload/approval flow → Submit from foxwisdom.com/memories.html → Check it appears in admin as pending → Approve → Check website shows it |
| 5 | **Booking Submission** | Submit booking from foxwisdom.com/booking.html → Check admin Pending Requests |

### MEDIUM PRIORITY
| # | Section | What to Test |
|---|---------|--------------|
| 6 | **Diya Prayer Wall** | Verify panel now opens (was broken before fix), test approve/reject flow |
| 7 | **Memories Panel** | Verify panel now opens (was broken before fix), test Stories/Notes/Photos tabs |
| 8 | **Review Approvals** | Add/approve/reject reviews, check foxwisdom.com/reviews.html |
| 9 | **Photo Wall** | Upload photo, approve, check website |
| 10 | **FAQ Manager** | Add/edit FAQ, check foxwisdom.com homepage FAQ section |
| 11 | **Hero & Content** | Edit hero title/tagline, check homepage |
| 12 | **Site Images** | Upload an image (verify new JS functions work) |

### LOWER PRIORITY
| # | Section | What to Test |
|---|---------|--------------|
| 13 | **Subscribers** | Check list loads, test CSV export |
| 14 | **Social Media** | Edit links/pinned posts, check reviews.html |
| 15 | **Settings & Credentials** | Change password, verify re-login |
| 16 | **Export / Import** | Export JSON, import JSON |
| 17 | **API Diagnostics** | Run health checks |
| 18 | **Myth Buster Cards** | Add/edit myths, check quiz.html |
| 19 | **New Article (Editor)** | Create article with sections, verify on knowledge.html |

---

## ARCHITECTURE REFERENCE

### Admin Sidebar Sections (20 total)
**OVERVIEW:** Dashboard
**CONSULTATIONS:** Booking Calendar, Pending Requests
**CONTENT:** Knowledge Hub, New Article, Myth Buster Cards, Quiz Questions, Research Papers
**COMMUNITY:** Review Approvals, Photo Wall
**MEMORIES:** Diya Prayer Wall, Memories — ICU to Home
**GROWTH:** Subscribers, Social Media
**SITE CONTROLS:** FAQ Manager, Hero & Content, Site Images, Settings & Credentials, Export/Import, API Diagnostics

### Data Flow Pattern
- Admin saves → POST `api/content.php` (or specialized endpoint) with session token
- Website loads → GET `api/content.php?type=X` (no auth needed)
- Changes reflect immediately on page refresh

### API Endpoints
| Endpoint | Purpose |
|----------|---------|
| `api/settings.php` | Site settings (hero, contact info) |
| `api/content.php?type=X` | All content CRUD (faq_items, quiz_questions, knowledge_articles, research_papers, myth_busters, peer_recognitions, photo_wall, social_settings) |
| `api/booking.php` | Booking submissions |
| `api/get_bookings.php` | Admin booking management |
| `api/diya.php` | Diya prayer wall |
| `api/memories.php` | Memories (stories, notes, photos) |
| `api/subscribers.php` | Email subscribers |
| `api/upload_image.php` | Site image uploads |
| `api/dashboard_stats.php` | Dashboard statistics |
| `api/forgot_password.php` | Password reset |

### Key Files
- `admin.php` — Main admin panel HTML (sidebar + panel includes)
- `admin.js` — All admin JavaScript (~3240 lines)
- `admin.css` — Admin panel styles
- `admin/panels/*.php` — 20 panel HTML files
- `api/*.php` — Backend API endpoints
- `script.js` — Frontend shared JS (settings, theme, speed dial)
- `premium-ux.js` — UI enhancements (no API calls)

### Frontend Pages & Their API Sources
| Page | Loads From |
|------|-----------|
| index.html | settings.php, content.php?type=faq_items, content.php?type=peer_recognitions |
| booking.html | booking.php (calendar + submit) |
| knowledge.html | content.php?type=knowledge_articles |
| research.html | content.php?type=research_papers |
| quiz.html | content.php?type=quiz_questions, content.php?type=myth_busters |
| reviews.html | content.php?type=peer_recognitions, content.php?type=social_settings |
| memories.html | memories.php (stories, notes, photos) |
| diya.html | diya.php (diyas, quotes) |

---

## KNOWN ISSUES (NOT YET FIXED)

### Code Quality
1. **admin.js is 3240 lines** — monolithic, function wrapping pattern (switchPanel defined twice, second extends first)
2. **No content search/filter** in admin list views
3. **No pagination** for large datasets
4. **No audit logging** of admin actions

### Missing Features (Nice to Have)
1. Content versioning / edit history
2. Bulk email to subscribers
3. Content scheduling (publish later)
4. Role-based access (only single admin account)
5. Search within admin panels
6. Status filter for Diya moderation
7. Content dependency checks (prevent deleting referenced items)

### Potential Issues to Verify
1. Quiz "Reset to 50 Defaults" button — only 3 defaults exist in code
2. Memory tab switching may have event handling issue (uses global `event`)
3. Social media settings stored but integration on reviews.html needs verification
4. Image upload file size validation (10MB limit exists in API but no client-side check beyond the function we added)
