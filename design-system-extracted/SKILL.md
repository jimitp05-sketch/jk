---
name: jay-kothari-design
description: Use this skill to generate well-branded interfaces and assets for Dr. Jay Kothari Critical Care (Apollo Hospitals, Ahmedabad), either for production or throwaway prototypes/mocks/etc. Contains essential design guidelines, colors, type, fonts, assets, and UI kit components for prototyping. The brand is editorial-medical: navy → teal gradients, Playfair Display + Manrope, Lucide icons, glass cards with teal-shimmer top edge, warm/cool dual-surface system, and quiet-confident copy that respects the gravity of ICU contexts.
user-invocable: true
---

Read the README.md file within this skill, and explore the other available files.

If creating visual artifacts (slides, mocks, throwaway prototypes, etc), copy assets out and create static HTML files for the user to view. If working on production code, you can copy assets and read the rules here to become an expert in designing with this brand.

If the user invokes this skill without any other guidance, ask them what they want to build or design, ask some questions, and act as an expert designer who outputs HTML artifacts _or_ production code, depending on the need.

## Quick Map
- `README.md` — full overview, content fundamentals, visual foundations, iconography
- `colors_and_type.css` — design tokens (CSS variables): colors, gradients, type scale, shadows, radii. Import this into any new artifact.
- `assets/` — logos, hero portrait, ICU/team photos, Lucide-stroke icons
- `ui_kits/website/` — React (Babel) component recreation of the homepage; `index.html` runs the full clickable demo
- `preview/` — design-system specimen cards (typography, color, spacing, components, brand)

## Non-negotiables
1. **Tone is reverent.** This is a critical-care doctor's site. No emoji, no exclamation marks, no marketing-bro copy. Empathy first, then credentials, then CTA.
2. **Always use the navy → teal gradient** (`var(--jk-grad-hero)`) for dark sections; warm white (`#FFFBF7`) for emotional/quote sections; cool white (`#F0F2F8`) for clinical/analytical sections.
3. **Playfair Display for serif headlines** (italic for empathy, regular for confidence); **Manrope for everything else**.
4. **Lucide-family icons only**, 2px stroke, 18–22px inline. Never invent SVG glyphs.
5. **Glass cards** have a teal shimmer top edge that intensifies on hover, plus an indigo glow shadow. Don't use plain white cards.
6. **Stat numbers** are always Playfair 800 with teal text-clip + drop-shadow glow.
