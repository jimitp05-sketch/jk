# Diya Calendar Filter & Quotes — Design Spec

**Date:** 2026-04-15
**Status:** Approved
**Scope:** Add calendar-based diya browsing and inspirational quotes to the public diya page, plus admin enhancements

---

## Problem

The diya prayer wall is a live snapshot — users light diyas but cannot revisit past prayers. Someone who lit a diya for a loved one months ago has no way to find that moment again. The page is a one-time action, not an ongoing emotional experience.

## Solution

Add a collapsible calendar filter to the diya page that lets anyone browse prayers by date, paired with random inspirational quotes. This transforms the diya wall into a living memory book that people return to.

---

## 1. Calendar Icon Button (Trigger)

- A gold-themed calendar icon button labeled "Diya Memories" positioned directly above the prayer wall grid
- Matches existing gold accent color (`#D4A84B`)
- When collapsed, the page looks exactly as it does today — zero visual change
- Tapping the icon expands the calendar below it with a smooth slide-down animation
- Tapping again (or an "x" button) collapses it back

## 2. Full Month Calendar (Expanded State)

- Full month grid layout (Sun–Sat columns, date cells)
- Current month displayed by default (e.g., "April 2026")
- Previous/Next month arrow buttons for navigation to any past month
- **Gold-glowing dots** on days that have diyas lit:
  - Brighter intensity = more diyas that day
  - Days without diyas are dim and non-clickable
  - Only active (gold) dates are clickable
- Selected date is highlighted with a stronger gold ring/border
- Smooth slide-down animation on open, slide-up on close

## 3. Date Filtering & Wall Behavior

### When a date is clicked:
- The prayer wall grid transitions (fade out -> fade in) to show only diyas lit on that date
- A soft label appears above the grid: "**X diyas were lit on [Month Day, Year]**"
- The existing mini-diya design stays identical — same animated flames, same tooltips on hover
- A random inspirational quote appears between the calendar and the filtered grid

### Resetting:
- An "All Diyas" button appears when filtering is active (near the date label)
- Clicking it or collapsing the calendar resets the wall to show all diyas
- The label and quote disappear, returning to the default state

### Data flow:
- No new API endpoint needed for filtering
- The existing `GET /api/diya.php` already returns all approved diyas with `lit_at` timestamps
- Filtering happens client-side in JavaScript — parse `lit_at` and match against selected date
- The calendar's gold-dot rendering uses this same data to determine which dates have diyas

## 4. Quotes System

### Display:
- When a date is selected, a random quote from the pool appears between the calendar and the filtered diya grid
- Styled as centered italic text with subtle gold line dividers above and below
- Each time a different date is picked, a new random quote is pulled
- When calendar is collapsed or in "All Diyas" mode, no quote is shown

### Storage:
- Quotes stored in the `content` table (same pattern as diyas/memories)
- `content_key = 'diya_quotes'`
- JSON array of objects:
  ```json
  {
    "id": "quote_<16-char-hex>",
    "text": "Even a single flame can hold back the darkness.",
    "author": "Unknown",
    "status": "active"
  }
  ```
- Ships with 30–50 pre-loaded healing/prayer/hope quotes

### API:
- `GET /api/diya.php?action=get_quotes` — returns all active quotes (public)
- `POST /api/diya.php` with `action=add_quote` — add new quote (admin)
- `POST /api/diya.php` with `action=edit_quote` — edit quote (admin)
- `POST /api/diya.php` with `action=delete_quote` — delete quote (admin)

## 5. Admin Panel Enhancements

### Date filter on diya table:
- A date picker input at the top of the existing diya management table (`admin/panels/diya.php`)
- A "Show All" button next to it to reset
- When a date is selected, the table filters to show only diyas from that date
- Client-side filtering — no new API needed
- Everything else stays the same: delete, approve, stats, add form

### Diya Quotes management:
- New sub-section under the existing "Diya Prayer Wall" admin panel
- Table with columns: Quote Text | Author | Actions (Edit / Delete)
- Add new quote form: text input + author input + add button
- No approval workflow — admin-added means active
- Ships with 30–50 starter quotes pre-loaded

## 6. What Stays Untouched

- Hero section, light-a-diya form, prayer wall grid design, recent prayers ticker
- All existing diya tooltips, flame CSS animations, sparkle effects
- All existing API endpoints and their behavior
- Rate limiting, CORS, admin authentication
- Database schema (uses existing `content` table pattern)
- Mobile navigation, page routing, global styles

## 7. Tech Summary

| Component | Approach |
|-----------|----------|
| Calendar UI | Vanilla JS + CSS, built from scratch matching dark/gold theme |
| Date filtering | Client-side JS, parsing existing `lit_at` field |
| Quotes storage | `content` table, `content_key = 'diya_quotes'`, JSON array |
| Quotes API | New actions added to existing `api/diya.php` |
| Admin date filter | Client-side JS filter on existing admin table |
| Admin quotes panel | New sub-section in `admin/panels/diya.php` |
| Starter quotes | 30–50 quotes pre-loaded via API or migration |

## 8. Files to Modify

| File | Changes |
|------|---------|
| `diya.html` | Add calendar icon button, expandable calendar, quote display, date label, filtering JS |
| `api/diya.php` | Add quote CRUD actions (get_quotes, add_quote, edit_quote, delete_quote) |
| `admin/panels/diya.php` | Add date picker filter, quotes management sub-section |
| `admin.js` | Add admin quote CRUD functions, date filter logic |
| `admin.css` | Styles for quotes table and date picker (if needed) |
