# Critical Care Platform — Design Spec
**Date:** 2026-04-24  
**Project:** Dr. Jay Kothari — Apollo Hospitals, Ahmedabad  
**Scope:** Admin Overhaul + ICU Wiki + Emergency Assessment Tool + ICU Knowledge Academy

---

## 1. Context & Problem Statement

The current website is a passive brochure sustained by offline word-of-mouth and physician referrals. The admin panel is slow and manual — every task requires multiple clicks with no analytics, no bulk actions, no content scheduling. The Knowledge Hub has no structure for learning paths, no SEO depth, and no urgency mechanism for crisis visitors who land terrified and need immediate guidance.

**Primary user types:**
- Crisis families — a loved one is in ICU right now, searching desperately at 2am
- Grateful survivors — recovered patients who want to give back and refer others

**Three core problems to solve:**
1. Trust gap — visitors don't convert fast enough
2. Admin chaos — all five admin workflows are slow and painful
3. Discovery gap — the site earns no organic traffic of its own

---

## 2. Scope — What We Are Building

### 2A. Admin Overhaul
- Article editor rebuild (templates, autosave, scheduled publish, SEO sidebar, preview, duplicate)
- Content Calendar panel (new)
- Admin Dashboard analytics rebuild
- Bulk moderation across reviews, diyas, memories
- SEO Health panel (new)
- Newsletter subscribers panel (new)
- Hero image distortion fix

### 2B. Feature 9 — ICU Wiki
500+ keyword-targeted wiki pages with schema markup, auto-linked to Knowledge Hub pillars, managed from a new admin Wiki panel.

### 2C. Feature 2 — Emergency ICU Assessment Tool
5-question interactive triage widget on the homepage. Branching logic → severity result → immediate CTA.

### 2D. Feature 7 — ICU Knowledge Academy
Knowledge Hub upgraded to structured learning paths. Progress tracking, completion badges, shareable certificates.

### Out of Scope (Phase 2)
WhatsApp automation, Family Companion Mode, Recovery Tracker, Referring Doctor Portal, Second Opinion Form, AI Article Assistant.

---

## 3. Admin Overhaul — Detailed Design

### 3.1 Article Editor Rebuild

**Template Picker (new)**
When "+ New Article" is clicked, a modal appears before the editor opens with 5 templates:
- Clinical Deep Dive — pre-populates: Overview, Pathophysiology, Treatment Approach, What Families Should Know, Key Statistics
- Family Guide — pre-populates: What This Means, What Happens in the ICU, What You Can Do, Questions to Ask, Hope & Recovery
- Myth Buster — pre-populates: Common Myth, The Truth, Why This Matters, What Research Says
- Research Summary — pre-populates: Study Overview, Key Findings, Clinical Implications, Limitations
- Quick Facts — pre-populates: 3 Stat blocks + 5 bullet facts + 1 Key Takeaway

Each template pre-fills section headings only — Dr. Kothari's team fills the content.

**Draft Autosave**
- Saves to localStorage every 60 seconds
- Status indicator top-right of editor: "Autosaved 2 mins ago"
- On page load, if a local draft exists: banner "You have an unsaved draft — Continue editing? [Yes] [Discard]"
- Server-side draft save button remains separate (explicit save to DB)

**Scheduled Publish**
- Next to the Publish/Draft toggle: a date-time picker (appears only when status = Published)
- If a future date is set: status shows as "Scheduled" in all tables
- A PHP endpoint (`/api/publish_scheduled.php`) checks for due articles and publishes them — called automatically by `admin.js` every time the admin dashboard panel is loaded, requiring no external cron setup
- Admin tables show a clock icon for scheduled articles

**SEO Sidebar**
- Collapsible right panel in the editor (toggle button: "SEO ▶")
- Fields:
  - Meta Title (60 char max, live counter, auto-populated from article title)
  - Meta Description (160 char max, live counter)
  - Focus Keyword (text input)
  - OG Image (upload or inherit from article image)
  - Estimated Read Time (auto-calculated: word count ÷ 200, rounded up, display: "~6 min read")
  - Canonical URL (auto-generated as `/knowledge/{article-slug}`, editable)
- All SEO fields stored in the articles DB table (new columns)

**Preview Button**
- Opens `/knowledge/preview?id={draft_id}&token={session_token}` in a new tab
- Renders the article exactly as it would appear live, including meta tags in `<head>`
- Banner at top: "PREVIEW MODE — This article is not yet published"

**Duplicate Article**
- Action button on every row in the Knowledge Hub panel: "Duplicate"
- Creates a new draft with title "Copy of [Original Title]", same pillar, same sections
- Opens directly in the editor

### 3.2 Content Calendar Panel

**Location:** New panel in admin sidebar between "Knowledge Hub" and "Editor"

**Layout:**
- Month/year header with ← → navigation
- 7-column calendar grid (Mon–Sun)
- Each day cell shows coloured dots: green = published, blue = scheduled, grey = draft edited that day
- Clicking a dot shows a popover: article title + status + [Edit] [View Live] buttons
- Clicking an empty future date: opens new article editor with that date pre-set as scheduled publish date

**Status Lanes (below calendar):**
- Horizontal swim lanes showing all articles by status: Draft | Scheduled | Published | Archived
- Each article shown as a pill with title + pillar colour
- Drag pill from Draft lane to Scheduled lane → opens date picker
- Drag scheduled pill to a different date → updates scheduled date

### 3.3 Admin Dashboard Analytics Rebuild

**Current state:** 4 static counters (Total Bookings, Pending Requests, Published Articles, Pending Approvals)

**Rebuilt dashboard — 6 widgets:**

**Widget 1: Content Performance**
- Table of top 5 most-viewed articles this month
- Columns: Title, Views, Avg Read Time, Pillar
- Views tracked via a `view_count` integer column on the `articles` table, incremented server-side on each public article page load (no external analytics needed)
- "View all →" links to Knowledge Hub panel sorted by views

**Widget 2: Booking Funnel**
- 4-step funnel: Visitors (approximated from article views) → Booking page hits → Forms started → Forms submitted
- Displayed as horizontal bar with percentages
- Conversion rate shown: "X% of visitors book a consultation"

**Widget 3: Community Activity**
- Side-by-side: This week vs Last week
- Rows: New Diyas | New Memories | New Reviews | New Subscribers
- Green/red arrows showing trend

**Widget 4: Quick Actions (one-click)**
- "Approve all pending reviews" → bulk approves all pending, shows count
- "Publish scheduled articles now" → manually triggers scheduled publish check
- "Export subscriber list" → downloads CSV
- "Check SEO health" → jumps to SEO Health panel

**Widget 5: SEO Health Summary**
- 3 numbers: Articles missing meta | Articles under 500 words | Articles with no focus keyword
- Each number is clickable → jumps to SEO Health panel filtered to that issue

**Widget 6: Recent Bookings**
- Existing table, kept as-is, moved to bottom of dashboard

### 3.4 Bulk Moderation

Applied to: Reviews panel, Diya panel, Memories panel (all 3 sub-tabs)

**Changes per moderation table:**
- Checkbox column added as first column (header checkbox = select all visible)
- Bulk action bar appears above table when ≥1 item selected: "[X] selected — [Approve All] [Reject All] [Delete All]"
- Confirmation modal before bulk delete: "Delete X items? This cannot be undone. [Cancel] [Delete]"
- Content preview on row hover: tooltip showing full text of review/prayer/story (truncated at 300 chars)
- Smart filter added to each table: "Show: All | This week | Pending only | Approved only"

### 3.5 SEO Health Panel

**Location:** New panel in admin sidebar, near bottom, labelled "SEO Health"

**Layout:**
- Summary bar at top: X articles healthy | Y need attention | Z critical
- Filterable table of all articles with columns:
  - Title
  - Word Count (colour-coded: red <300, amber 300–500, green >500)
  - Meta Title (✓ or ✗)
  - Meta Description (✓ or ✗)
  - Focus Keyword (✓ or ✗)
  - Internal Links count
  - Action: [Fix in Editor →]
- Filter tabs: All | Needs Attention | Critical
- Sortable columns

**Attention thresholds:**
- Critical: word count <300 OR missing meta title AND meta description
- Needs attention: word count 300–500 OR missing any one SEO field
- Healthy: word count >500 AND all 3 SEO fields present

### 3.6 Newsletter Subscribers Panel

**Location:** New panel in admin sidebar labelled "Subscribers"

**Layout:**
- Summary: Total subscribers | New this week | New this month
- Table: Email | Name (optional) | Subscribed date | Source (homepage strip / article page)
- Bulk actions: Export selected as CSV | Delete selected
- Search bar: filter by email

**Public-facing signup:**
- Strip above footer on homepage (and optionally on article pages)
- Fields: Email address only (name optional, shown as placeholder)
- Lead magnet: "Get the free ICU Family Survival Guide" (a PDF Dr. Kothari creates — stored in `/assets/guides/icu-family-guide.pdf`)
- On submit: email stored in `subscribers` table, confirmation shown, PDF download link triggered
- No external email service required in Phase 1

### 3.7 Hero Image Distortion Fix

**Root cause:** `.hero-image` container has a fixed height forcing the `600×700px` portrait to stretch or crop on certain screen sizes.

**Fix in `styles.css`:**
```css
.hero-image {
  aspect-ratio: 6 / 7;       /* locks container to portrait ratio */
  max-height: 600px;
  overflow: hidden;
}

.hero-image img,
.hero-image source {
  width: 100%;
  height: auto;               /* never forces height */
  object-fit: contain;        /* never crops or stretches */
  display: block;
}
```

**Also add to `index.html`:** explicit `width="600" height="700"` attributes on the `<img>` tag if not already present (prevents layout shift).

---

## 4. Feature 9 — ICU Wiki

### 4.1 Concept
A structured encyclopedia of ICU terms, procedures, medications, and conditions. Each entry is its own URL-addressable page targeting a specific long-tail search query. Example pages:
- `/wiki/what-is-a-ventilator`
- `/wiki/what-is-ecmo`
- `/wiki/what-is-sepsis`
- `/wiki/what-does-peep-mean`
- `/wiki/crrt-kidney-failure-icu`

Target: 500+ entries over time. Admin creates entries one at a time. Each entry auto-links to its related Knowledge Hub pillar.

### 4.2 Wiki Entry Structure
Each wiki entry contains:
- **Term** (e.g. "PEEP — Positive End-Expiratory Pressure")
- **Plain English definition** (1–2 sentences, written for families)
- **Clinical explanation** (2–3 paragraphs, for referring doctors)
- **Related terms** (linked to other wiki entries)
- **Related Knowledge Hub article** (linked by pillar)
- **Related research papers** (linked from Research Library)
- **Schema markup:** `MedicalEntity` + `DefinedTerm`
- **Meta title:** "[Term] — ICU Guide by Dr. Jay Kothari, Apollo Hospitals"
- **Meta description:** auto-generated from plain English definition

### 4.3 Public Wiki Page (`/wiki.html`)
- Search bar at top: live search across all wiki terms as you type
- Alphabetical index: A–Z letter tabs
- Category filter: Procedures | Equipment | Conditions | Medications | Acronyms
- Each entry shows as a card with term + plain English definition + "Read more →"
- Clicking a card opens the full wiki entry page (`/wiki/{slug}.html` or dynamic `/wiki.php?slug=`)

### 4.4 Admin Wiki Panel (New)
**Location:** New panel in sidebar labelled "ICU Wiki"

**Features:**
- Table of all wiki entries: Term | Category | Related Pillar | Status | Actions
- "+ New Entry" button opens entry editor:
  - Term name
  - Slug (auto-generated, editable)
  - Category selector
  - Plain English definition (textarea, 1–2 sentence guidance)
  - Clinical explanation (textarea)
  - Related terms (multi-select from existing wiki entries)
  - Related Knowledge Hub pillar (dropdown)
  - Related research papers (multi-select)
  - Status: Published / Draft
- Search/filter by category or status
- Bulk publish / bulk delete

### 4.5 Database Table
```sql
CREATE TABLE wiki_entries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  term VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  category ENUM('procedure','equipment','condition','medication','acronym') NOT NULL,
  definition_plain TEXT NOT NULL,
  definition_clinical TEXT,
  related_pillar VARCHAR(100),
  meta_title VARCHAR(60),
  meta_description VARCHAR(160),
  status ENUM('published','draft') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE wiki_related_terms (
  wiki_id INT,
  related_wiki_id INT,
  PRIMARY KEY (wiki_id, related_wiki_id)
);

CREATE TABLE wiki_related_research (
  wiki_id INT,
  research_id INT,
  PRIMARY KEY (wiki_id, research_id)
);
```

### 4.6 SEO Per Wiki Entry
Each wiki page gets in `<head>`:
```html
<title>{meta_title}</title>
<meta name="description" content="{meta_description}">
<link rel="canonical" href="https://drjaykothari.com/wiki/{slug}">
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "MedicalWebPage",
  "name": "{term}",
  "description": "{definition_plain}",
  "author": {
    "@type": "Person",
    "name": "Dr. Jay Kothari",
    "jobTitle": "Critical Care Specialist",
    "worksFor": "Apollo Hospitals, Ahmedabad"
  },
  "medicalAudience": "Patient"
}
</script>
```

---

## 5. Feature 2 — Emergency ICU Assessment Tool

### 5.1 Concept
A 5-question branching triage widget embedded on the homepage, designed for crisis visitors who land terrified and need immediate guidance. Outputs one of 3 severity levels with a contextual CTA.

### 5.2 Question Flow
```
Q1: Is the patient currently unconscious or unresponsive?
    [Yes → Q_CRITICAL path]  [No → Q2]

Q2: Is breathing severely laboured, absent, or on a machine?
    [Yes → Q_CRITICAL path]  [No → Q3]

Q3: Has there been a sudden collapse, seizure, or extreme confusion?
    [Yes → Q_URGENT path]  [No → Q4]

Q4: Is the patient currently admitted to an ICU and you have questions about their care?
    [Yes → Q_CONSULT path]  [No → Q5]

Q5: Are you researching ICU care for a family member who may need it soon?
    [Yes → Q_RESEARCH path]  [No → Q_GENERAL]
```

### 5.3 Result States

**CRITICAL (Q1 or Q2 = Yes):**
```
┌─────────────────────────────────────────────────┐
│ 🔴 This may be a medical emergency              │
│ Call emergency services immediately, then       │
│ contact Dr. Kothari's ICU team directly.        │
│                                                 │
│ [Call ICU Now: 1860-500-1066]                   │
│ [WhatsApp Emergency Team]                       │
└─────────────────────────────────────────────────┘
```

**URGENT (Q3 = Yes):**
```
┌─────────────────────────────────────────────────┐
│ 🟡 This needs urgent specialist attention       │
│ Book a priority consultation with Dr. Kothari  │
│ or call his team now.                          │
│                                                 │
│ [Book Priority Consultation]                    │
│ [Call Team: 1860-500-1066]                      │
└─────────────────────────────────────────────────┘
```

**CONSULT (Q4 = Yes):**
```
┌─────────────────────────────────────────────────┐
│ 🔵 You have a loved one in ICU right now        │
│ Dr. Kothari can provide a second opinion        │
│ or take over complex cases.                    │
│                                                 │
│ [Request ICU Consultation]                      │
│ [Read: Family Guide to the ICU →]               │
└─────────────────────────────────────────────────┘
```

**RESEARCH (Q5 = Yes):**
```
┌─────────────────────────────────────────────────┐
│ 🟢 You're preparing — that's incredibly smart   │
│ Start with Dr. Kothari's free ICU Family Guide  │
│                                                 │
│ [Get the Free Family Guide]                     │
│ [Browse the Knowledge Hub →]                   │
└─────────────────────────────────────────────────┘
```

**GENERAL:**
```
┌─────────────────────────────────────────────────┐
│ Welcome — how can we help?                      │
│                                                 │
│ [Book a Consultation]                           │
│ [Explore the Knowledge Hub →]                  │
└─────────────────────────────────────────────────┘
```

### 5.4 Placement & Design
- Positioned in the homepage hero section, below the headline and above the existing CTA buttons
- Collapsed by default: a single button "Not sure where to start? Answer 5 quick questions →"
- Expands inline (no modal, no page navigation) when clicked
- Progress indicator: 5 dots at top, filled as questions are answered
- Back button on every question
- Restart link on result screen
- Mobile-first: full-width card, large tap targets (min 48px)
- Colours match existing design tokens (red = `--color-red`, amber = `--color-gold`, blue = `--color-teal`, green = `--color-teal-light`)

### 5.5 Admin Controls (in Hero panel)
The existing "Hero & Content" admin panel gets a new section:
- Toggle: Show/hide assessment tool on homepage
- Edit each question text
- Edit each result title, body text, and CTA button labels
- Edit phone numbers and WhatsApp links in result CTAs
- All stored in `site_settings` table as JSON under key `assessment_tool`

### 5.6 Implementation Notes
- Pure vanilla JS — no library needed
- State stored in a simple JS object during the session
- No server calls required (all question logic is client-side)
- Results with CTAs deep-link to booking page or trigger WhatsApp link

---

## 6. Feature 7 — ICU Knowledge Academy

### 6.1 Concept
Upgrade the Knowledge Hub from a flat article list into structured learning paths called "Courses." Each course is a curated sequence of 3–7 existing articles grouped around a learning goal. Users track their progress and earn a completion badge they can share.

### 6.2 Courses (Initial Set)
| Course | Articles | Audience |
|---|---|---|
| ICU Survival Guide for Families | 5 articles | Crisis families |
| Understanding ECMO | 3 articles | Patients & families |
| Life After the ICU (Post-ICU Recovery) | 4 articles | Survivors |
| Critical Care Basics for Referring Doctors | 4 articles | Clinicians |
| Preventing ICU Complications | 3 articles | Families & clinicians |

### 6.3 Progress Tracking
- Tracked in `localStorage` — no login required
- Key: `apollo_academy_{course_id}` → array of completed article IDs
- Progress bar on each course card: "3 of 5 articles read"
- Article marked as read: automatically when user scrolls past 80% of the article content (IntersectionObserver on the article footer)
- Manual "Mark as read" button also shown at article bottom

### 6.4 Completion Badge & Certificate
On completing all articles in a course:
- A modal appears: "You've completed [Course Name]"
- A downloadable certificate card is generated client-side using Canvas API:
  - Certificate text: "This certifies that [name] has completed [Course Name] — Dr. Jay Kothari, Apollo Hospitals"
  - Name field: user enters their name in the modal before downloading
  - Format: PNG, 1200×800px, branded with site colours and Dr. Kothari's signature line
- WhatsApp share button: pre-filled message "I just completed the ICU Survival Guide for Families by Dr. Jay Kothari. Incredibly helpful for anyone with a loved one in critical care. [link]"

### 6.5 Public Academy Page (`/academy.html`)
- Hero: "Learn from India's Leading Critical Care Specialist"
- Grid of course cards, each showing:
  - Course title and description
  - Number of articles + estimated total read time
  - Progress bar (from localStorage)
  - Tags: Beginner / Intermediate / For Clinicians
  - "Start Course →" or "Continue →" button
- Completed courses show a green badge "Completed ✓" and "Download Certificate" button
- Link from main navigation: "Academy" (replaces or sits beside "Knowledge Hub")

### 6.6 Admin Academy Panel (New)
**Location:** New panel in sidebar labelled "Academy"

**Features:**
- Table of all courses: Title | Articles | Completions (tracked count) | Status
- "+ New Course" button:
  - Course title
  - Description (2–3 sentences)
  - Audience tag (Families / Clinicians / Survivors / All)
  - Article selector: drag-and-drop ordering of articles from Knowledge Hub
  - Thumbnail image upload
  - Status: Published / Draft
- Edit / Delete / Duplicate actions per course
- Completion counter: how many users have completed each course (tracked via a `course_completions` table, incremented on certificate download)

### 6.7 Database Addition
```sql
CREATE TABLE courses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  audience ENUM('families','clinicians','survivors','all') DEFAULT 'all',
  status ENUM('published','draft') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE course_articles (
  course_id INT,
  article_id INT,
  order_index INT,
  PRIMARY KEY (course_id, article_id)
);

CREATE TABLE course_completions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 6.8 Navigation Update
- Main nav updated: "Knowledge Hub" becomes a dropdown with two items:
  - "Articles" → knowledge.html
  - "Academy" → academy.html
- Or: "Academy" added as a standalone nav item depending on final nav space

---

## 7. SEO Infrastructure (Applied Across All Features)

### 7.1 Schema Markup — Per Page Type
| Page | Schema |
|---|---|
| `index.html` | `MedicalOrganization` + `Person` + `FAQPage` + `BreadcrumbList` |
| Knowledge Hub articles | `MedicalWebPage` + `Article` |
| Wiki entries | `MedicalWebPage` + `DefinedTerm` |
| `booking.html` | `MedicalClinic` + `OpeningHoursSpecification` |
| `research.html` | `ScholarlyArticle` per paper |
| `academy.html` | `Course` per learning path |
| `quiz.html` | `Quiz` |

### 7.2 Dynamic Meta Tags
All article, wiki, and academy pages pull meta title + description + OG image dynamically from the database. Static pages (homepage, booking, etc.) get hardcoded but well-crafted meta tags.

### 7.3 Open Graph Tags (WhatsApp / LinkedIn sharing)
Every article and wiki page gets:
```html
<meta property="og:title" content="{meta_title}">
<meta property="og:description" content="{meta_description}">
<meta property="og:image" content="{og_image_url}">
<meta property="og:type" content="article">
<meta property="og:site_name" content="Dr. Jay Kothari — Critical Care">
```

### 7.4 Auto-Updating Sitemap
`sitemap.xml` regenerated via `/api/sitemap.php` on:
- Article published or archived
- Wiki entry published or archived
- Course published or archived

Sitemap includes all public pages + all published articles + all wiki entries + all courses.

### 7.5 Share Buttons on All Content Pages
- WhatsApp (primary — pre-filled message with article title + URL)
- Copy link (clipboard API)
- LinkedIn
- Floating on left side (desktop) / sticky bottom bar (mobile)

---

## 8. Homepage Additions

### 8.1 Latest from Knowledge Hub Strip
- 3-card strip between the Research section and FAQ
- Auto-populated from 3 most recently published articles
- Card shows: article title, pillar tag, estimated read time, "Read Article →"
- No manual admin updates needed — auto-refreshes on each publish

### 8.2 Newsletter Signup Strip
- Above footer: "Free: The ICU Family Survival Guide"
- Email input + CTA button
- On submit: stored in `subscribers` table + PDF download triggered
- PDF stored at `/assets/guides/icu-family-guide.pdf` (Dr. Kothari to provide)

---

## 9. Database Changes Summary

### New Tables
- `wiki_entries` (full schema in Section 4.5)
- `wiki_related_terms` (join table)
- `wiki_related_research` (join table)
- `courses` (full schema in Section 6.7)
- `course_articles` (join table with order_index)
- `course_completions` (course_id, completed_at)
- `subscribers` (email, name, source, subscribed_at)

### Modified Tables
- `articles`: add columns — `meta_title`, `meta_description`, `focus_keyword`, `og_image`, `scheduled_at`, `view_count`, `slug`
- `site_settings`: add key `assessment_tool` (JSON blob for Emergency Tool config)

---

## 10. New Admin Panels Summary

| Panel | Sidebar Label | Position |
|---|---|---|
| Content Calendar | "Content Calendar" | After Knowledge Hub |
| ICU Wiki | "ICU Wiki" | After Research |
| Academy | "Academy" | After ICU Wiki |
| SEO Health | "SEO Health" | Near bottom |
| Subscribers | "Subscribers" | Near bottom |

### Modified Panels
- Dashboard: full analytics rebuild
- Editor: template picker, autosave, schedule, SEO sidebar, preview, duplicate
- Requests: bulk actions added
- Reviews: bulk actions + hover preview added
- Diya: bulk actions + smart filter added
- Memories: bulk actions + smart filter added (all 3 sub-tabs)
- Hero & Content: Emergency Tool toggle + question editor added

---

## 11. Implementation Order (Recommended)

1. Database migrations (new tables + column additions)
2. Hero image distortion fix (10-minute CSS fix, immediate visible win)
3. Admin Dashboard analytics rebuild
4. Bulk moderation across all panels
5. Article editor rebuild (template picker, autosave, schedule, SEO sidebar, preview, duplicate)
6. Content Calendar panel
7. SEO Health panel
8. Newsletter signup (public strip + admin Subscribers panel)
9. Schema markup on all existing pages
10. Dynamic meta tags + OG tags
11. Auto-updating sitemap
12. Emergency ICU Assessment Tool (homepage widget + admin controls)
13. Homepage: Latest Articles strip
14. Share buttons on content pages
15. ICU Wiki (admin panel + public wiki page + per-entry pages)
16. ICU Knowledge Academy (admin panel + public academy page + progress tracking + certificates)

---

## 12. Success Criteria

- Admin can create and publish a fully SEO-optimised article in under 5 minutes (vs. 20+ today)
- Bulk moderation reduces review/diya/memory approval time by 70%
- Emergency Assessment Tool is live on homepage and tested across mobile + desktop
- At least 20 wiki entries published within first month post-launch
- At least 3 courses live in the Academy at launch
- All pages score 90+ on Google PageSpeed SEO audit
- Hero image renders perfectly on mobile, tablet, and desktop with no distortion
