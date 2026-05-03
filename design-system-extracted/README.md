# Dr. Jay Kothari — Design System

A brand & UI system for **Dr. Jay Kothari**, Consultant in Critical Care Medicine at Apollo Hospitals, Ahmedabad. The product is a single-doctor authority website with marketing, education, booking, and remembrance surfaces — premium editorial in tone, restrained in color, but emotionally ambitious in copy.

## Sources

- **Codebase:** `ai apollo/website 2/` (mounted via File System Access API)
  - Primary stylesheet: `styles.css` (~6800 lines, the source of truth for tokens)
  - Page-level overrides: `improvements.css`, `pulse.css`, `memories.css`, `diya.css`
  - Page templates: `index.html` (homepage), `booking.html`, `knowledge.html`, `quiz.html`, `reviews.html` (Pulse), `memories.html`, `diya.html`, `research.html`
  - Hero & content imagery: `img-hero-doctor.png/.webp`, `img-ecmo`, `img-icu-ward`, `img-team`, `img-knowledge`
- **Audit & docs:** `ai apollo/audit/`, `ai apollo/docs/` — production audits and a UI/UX audit HTML
- **Awesome-design-md:** `ai apollo/awesome-design-md/` — reference index, not part of this brand

## Product Context

There is **one product**: a marketing & utility website (`foxwisdom.com`) for Dr. Jay Kothari. It is not a SaaS app or a hospital portal — it's a **personal-brand site for a critical-care physician**, with extra surfaces that deepen the relationship:

| Surface | Purpose |
|---|---|
| **Home (`index.html`)** | Hero, credentials, expertise, why-Dr.-Kothari, journey timeline, knowledge hub, FAQ |
| **Knowledge Hub** | Long-form patient/family education on Sepsis, ECMO, ICU survival, etc. |
| **Research** | Publications & clinical interests |
| **ICU Intel (Quiz)** | Lightweight self-assessment quiz |
| **Pulse (Reviews)** | Compliance-aware feedback / reviews surface |
| **Memories** | Family-submitted remembrance wall (lost loved ones) |
| **Light a Diya** | A dark, gold, temple-vibe interactive page for lighting a virtual lamp in remembrance |
| **Booking** | OPD appointment booking (Mon–Sat, 2–4pm) |
| **Admin** | PHP back-office for content (FAQ items, quotes, memories) — out of scope visually |

The system serves a **bilingual / trilingual audience** (English · Hindi · Gujarati) and is constrained by **NMC Professional Conduct Regulations 2023** — no testimonials as marketing, no outcome guarantees, no misleading claims. This shapes the copy.

## Index — Files in This System

```
README.md                  ← you are here
SKILL.md                   ← Claude Code / Agent Skills entrypoint
colors_and_type.css        ← canonical tokens + semantic type styles
assets/                    ← hero & content imagery, logo SVG
preview/                   ← Design System tab cards (one HTML per concept)
ui_kits/
  website/                 ← marketing-site UI kit (homepage recreation)
slides/                    ← (none — no decks were provided)
```

> Slides are intentionally absent — no slide template was attached. If you'd like a deck system, ping the user.

---

## CONTENT FUNDAMENTALS

The voice is **editorial, empathetic, and quietly authoritative**. It is the voice of an excellent doctor speaking to a frightened family — never the voice of a marketing brochure. Copy is the most distinctive part of the brand.

### Tone
- **Empathetic-first.** Every page leads with an emotional hook before any credential. The hero literally says: *"We know you're terrified right now. Take a breath. You've found Dr. Jay Kothari — and he's ready."*
- **Quietly authoritative.** Numbers do the bragging — "30+ Years," "10,000+ Lives Touched," "<10 ECMO Intensivists in Gujarat." Never adjectives like "world-class" or "best-in-class."
- **Reassuring, never salesy.** CTAs are framed as care, not transaction: "Book OPD → Mon–Sat, 2PM" not "Get Started Today!"
- **Indian-English register.** "OPD," "consultation," "kindly," British spellings: *organisation, programme, behaviour, specialisation*. Use these consistently.

### Person & Address
- Speaks **to the family** ("your family deserves," "your loved one"), not to the patient directly. The audience is a worried relative, not the patient on a vent.
- "Dr. Kothari" — never "Jay," never "the doctor." On second mention.
- "We" = the ICU team. "I" appears only inside Dr. Kothari's direct quotes.
- "You" = the reader/family.

### Casing
- **Title case** for navigation labels, CTA buttons, section headers. ("Knowledge Hub," "Book Consultation," "Light a Diya.")
- **Sentence case** inside body paragraphs.
- **Sentence-case-with-em-dash** for headlines that pivot mid-line: *"Where Most Physicians Stop, — Dr. Kothari Begins."* (Em-dash + italicised second clause is a recurring rhythmic move.)
- Eyebrow labels are **ALL CAPS, 3.5px tracked**, in teal.

### Punctuation rhythm
- **Em-dashes everywhere** — they pace the prose and create the "reflective doctor" cadence.
- **Italics** for emotional emphasis (Playfair italic): *"Dr. Kothari Begins."*, *"Reach Out Now."*
- **Two-line headlines with `<br />`** are the default — never one long line.
- **Three-clause stat triples**: "30+ Years · 10,000+ Lives · <10 ECMO Docs."

### Examples
- Hero badge: `Apollo Hospitals · Gujarat's #1 Critical Care Unit`
- Hero empathy: *"We know you're terrified right now. Take a breath. You've found Dr. Jay Kothari — and he's ready."*
- Section label: `THE DOCTOR BEHIND THE DIAGNOSIS`
- Section title: *Quiet Confidence in the Loudest Moments*
- CTA: `Book OPD → Mon–Sat, 2PM`
- Promise: *"My promise to you: I will explain everything clearly, I will be available when you need me, and I will fight for your loved one with everything modern medicine has to offer."*
- Disclaimer (NMC): *"In compliance with NMC Professional Conduct Regulations 2023, this website does not display patient testimonials as marketing material."*

### Emoji
- **Never.** Emoji are not part of this brand. Stroked SVG icons (Lucide-family) carry every glyph slot. Unicode arrows (`→`) are used sparingly inside CTA labels.

### Bilingual moments
- "English · Hindi · Gujarati" is repeated as a trust line — always with middle dots.
- Hindi/Gujarati script sometimes appears on the Diya page in gold inscriptions. Do not invent translated body copy unless asked.

### Vibe
- Like reading a thoughtful op-ed in *The Hindu* or *Mint Lounge*, written by a doctor who chose every word.
- Premium, restrained, human. The opposite of pop-up SaaS energy.

---

## VISUAL FOUNDATIONS

### Color
- **Anchor palette is dark navy + bright teal**, not blue gradients. The hero gradient runs `#0A1628 → #162447 → #1B365C` (165deg) — almost-black to deep navy. Foreground accents are teal (`#00B4D8` / `#48CAE4`).
- **Gold (`#D4A84B`)** appears as a *temple* / *premium* accent — used heavily on `diya.html` (dark+gold), sparingly elsewhere (anniversary badges, diya nav link).
- **Pulse-red (`#E84545`)** is reserved for **emergency CTAs only** (the call-now button) and is animated with a slow `emergencyPulse`.
- **WhatsApp-green (`#25D366 → #128C7E`)** is its own gradient on the WhatsApp CTA — borrowed from the platform, intentional.
- **Light surfaces dominate the body** (`#FAFBFE` bg, `#FFFFFF` cards, `#F0F2F8` alternating sections). Dark mode is fully supported.

### Type
- **Display: Playfair Display** (700, occasionally italic 400) — H1, H2, hero, big numerics.
- **Body: Manrope** (300–800) — paragraphs, labels, UI.
- Tight letter-spacing (`-0.025em` on H2, `-0.03em` on H1) — counterbalances Playfair's open width.
- Eyebrow labels are 0.70rem, 3.5px tracked, ALL CAPS, in teal. Always paired with H2 below.
- Stat numbers use Playfair 800 weight, often with the teal `--grad-text` clipped onto them and a glow drop-shadow.

### Spacing / Layout
- **Section vertical padding: 120px** on desktop (`--sec-pad`). Generous, editorial.
- **Container max-width: 1200px**, side padding 28px.
- **Bento grids** (uneven-cell card layouts) for About; **3- and 4-column equal grids** for Why/Expertise.
- **Diagonal section dividers** — sections sometimes meet on a `clip-path: polygon(0 50%, 100% 0, ...)` angle.
- A **trust strip** (logos + stats with dot-dividers) sits immediately under the hero.

### Backgrounds
- Hero is a 165° **navy gradient** with two radial-glow ellipses (indigo at 65/50, teal at 10/80) and a **700px pulsing orb** in the top-right.
- A **subtle SVG noise texture** is applied to `body::before` at `opacity: 0.018, mix-blend-mode: overlay` — gives the whole site a soft grain.
- Faint **70px-grid lines** appear in the hero at 0.045 opacity.
- Section backgrounds alternate: `--bg-primary` and `--bg-section`. No "rainbow" backgrounds — restraint is the rule.
- **Hand-drawn illustrations: none.** **Repeating patterns: none** other than grain + grid. **Full-bleed photographs: yes** for hero (doctor portrait) and Knowledge Hub article cards.

### Imagery vibe
- **Color of imagery: cool, slightly desaturated, clinical-warm.** Mostly portraits and ICU scenes. No grain on photos themselves — only on the page chrome.
- Images sit inside cards with `--radius-xl` (36px), heavy shadow (`0 40px 100px rgba(0,0,0,.55)`), and a 1px teal-tinted ring.

### Animation
- **Easing:** `cubic-bezier(0.22, 1, 0.36, 1)` (`--ease-out`) — quiet decel. Spring `cubic-bezier(0.34, 1.56, 0.64, 1)` for a few playful badges.
- **Durations:** 0.2s fast, 0.4s base, 0.7s slow. Never longer.
- **Scroll-reveal pattern:** `.reveal { opacity:0; transform: translateY(36px); }` → adds `.visible` via IntersectionObserver. Stagger via `.reveal-delay-1..4` (0.1s steps).
- **Hero word-by-word entrance** (`@keyframes wordUp`) for headline.
- **Persistent infinite ambient anims:** `orbPulse` (8s), `floatBadge` (7s), `dotBlink` (2s), `ecgDraw` (4s). Always slow.
- **`prefers-reduced-motion` honored fully** — all infinite anims disabled.
- **No bounces, no lottie, no springy modal pops** anywhere except a few hover scales.

### Hover & Press
- **Cards:** lift `translateY(-6px)`, swap to `--card-hover` bg (`#F6F8FF`), border to `--border-accent`, glow shadow. A 0.7s **light-sweep shine** (`::after` skew-X gradient sliding `left:-100% → 150%`) is the brand's signature hover.
- **Primary buttons:** lift `translateY(-3px)`, deepen the teal glow, brief alpha-white overlay.
- **Emergency button:** scales 1.02 + lifts on hover, plus its always-on pulse.
- **Press states:** subtle `translateY(-1px)` (less lift than hover) — never a shrink/scale-down. No haptic-style scale-0.95.
- **Links:** underline grows from `width:0 → 100%` with `--grad-primary`, 0.2s.

### Borders, radii, cards
- **Radii ladder:** 6 / 10 / 16 / 24 / 36 / 9999. Most cards use **16px** (`--radius-md`).
- **Card recipe:** white bg, 1px `rgba(0,0,0,.07)` border, `--shadow-sm`, 16px radius. On hover → `--shadow-glow` + accent border + lift. Top edge gets a 1px **teal shimmer line** that fades in (`::before`).
- **Glass cards** (`.glass-card`) are *not* truly translucent on light theme — they're white with the shimmer treatment. On dark hero overlays, real glass (`backdrop-filter: blur(24px)` over `rgba(10,22,40,.75)`) is used for floating badges.

### Shadows (the elevation system)
- **Sm** — base resting card
- **Md** — section feature
- **Lg** — modal / focused card
- **Glow** — hover state (`0 0 0 1px var(--border-accent), 0 12px 40px var(--glow-indigo)`)
- All shadows mix two layers: a tight near shadow + a wide soft one. Color-tinted toward indigo, never pure black.

### Transparency & blur
- **Nav** — `rgba(245,247,252,.8)` + `backdrop-filter: blur(24px) saturate(180%)` always; on scroll → bumps to `rgba(255,255,255,.96)` and adds a bottom border.
- **Floating hero badges** — real glass, `blur(24px)` over `rgba(10,22,40,.75)`.
- **Mobile nav drawer** — `rgba(255,255,255,.99)`, almost solid.

### Capsules vs protection gradients
- **Capsules (pill-shaped chips)** are the dominant micro-component: `credential-pill`, `inst-badge`, `hero-badge`, `scenario-tag`, `insight-tag`. Always rounded-full, 0.7rem text, tracked +1.5px, often ALL CAPS.
- **Protection gradients** (legibility scrim) are used on the dark hero only — the radial overlays are themselves the scrim.

### Layout rules
- Nav is `position: fixed`, full-width, blurred. Adds a subtle shadow once `.scrolled`.
- Floating utilities: a `.back-to-top` 44px circle bottom-left, only after scroll.
- All sections use `<section class="section">` with the standard 120px padding.

### Iconography color & treatment
- **Icons inherit `currentColor`** — usually `--text-muted` in body, white in hero, teal when active.
- Icon containers (`expertise-icon`, `why-icon`, `contact-icon`) are typically a **soft tinted square** — `rgba(0,180,216,.14)` bg, 1px `rgba(0,180,216,.25)` border, 9px radius.

---

## ICONOGRAPHY

The codebase ships with **inline Lucide-family stroked SVGs** — no icon font, no sprite sheet, no PNG icons. Every glyph in the markup is a hand-pasted `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">…</svg>`.

The set used in production is recognisable Lucide:

- `phone`, `message-square`, `calendar`, `map-pin`, `building`, `shield`, `award`
- `heart-pulse`, `crosshair`, `sun`, `flask-conical`, `airplay`, `microscope`, `pill`
- `flame` (for the diya page), `lamp` (mobile nav diya)
- `lightning` / `zap` for the AI-Assisted badge
- `mic` / `messages-square` for trilingual

Stroke is **always 2px**, caps round, joins round. Icons are sized inline at **18×18** (sometimes 14×14 for inline badges).

### Recommended approach for this design system

- **Use Lucide via CDN** for new work — it matches the existing visual exactly. The runtime swap is one line:
  ```html
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>lucide.createIcons();</script>
  ```
  Then `<i data-lucide="heart-pulse"></i>` substitutes the inline SVGs.

- **Or** copy the existing inline SVGs verbatim from `index.html` — they're already Lucide-shaped. The `assets/icons/` folder in this design system contains the most-used set extracted as standalone SVGs.

### Logo / wordmark

There is **no separate logo file** in the codebase. The brand mark is a **typographic wordmark** in the nav:

```
Dr. Jay  Kothari
         ^^^^^^^   ← teal gradient, glow filter
```

Implemented as `<a class="nav-logo">Dr. Jay <span class="glow-text">Kothari</span></a>`.

The favicon is an inline SVG: dark navy rounded square `#0A1628` + teal `JK` monogram `#00d4aa`. We've extracted that into `assets/logo-jk-monogram.svg` and added a wordmark variant `assets/logo-wordmark.svg`.

### Emoji & Unicode
- **No emoji.** Anywhere.
- Unicode arrows `→` and middle-dot `·` are used as separators and inside CTA labels. That's it.

### Imagery & illustrations
The codebase ships these photographic assets only — copied to `assets/`:

| File | Use |
|---|---|
| `img-hero-doctor.{png,webp}` | Hero portrait of Dr. Kothari (6:7 aspect) |
| `img-ecmo.{webp}` | ECMO machine, Knowledge Hub article cover |
| `img-icu-ward.{webp}` | ICU room, contextual photo |
| `img-team.{webp}` | Critical care team |
| `img-knowledge.{webp}` | Generic medical-knowledge cover |

There are **no hand-drawn illustrations, no brand mascots, no patterns.** If new illustrative work is needed, ask first — it would be a brand expansion.

---

## CAVEATS

- **No logo file existed in the repo.** The wordmark in `assets/logo-wordmark.svg` and monogram in `assets/logo-jk-monogram.svg` are reconstructions of what's rendered live in the nav (matching the inline-favicon SVG in `index.html`). If there's an official mark, please share.
- **Fonts are Google-Fonts-served, not bundled.** No `.woff/.ttf` files were provided in the repo — the site uses the CDN. Same here. If you need offline fonts, ask and I'll bundle.
- **No slide deck template** was provided, so the `slides/` folder is omitted.
- **Imagery is photographic and Apollo-specific** — treat as licensed to the brand. Do not reuse beyond Dr. Kothari's surfaces.
