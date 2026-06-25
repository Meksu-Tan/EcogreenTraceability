# Ecogreen Oleochemicals — Design System

## Company Overview

**Ecogreen Oleochemicals** is one of the world's leading producers of Natural Fatty Alcohols, derived from sustainable palm-based and natural raw materials. Founded in 1991 on Batam Island, Indonesia, the company has grown into a global B2B chemical manufacturer serving personal care, cosmetics, pharmaceuticals, food, home care, and industrial lubricant sectors.

**Headquarters:** Singapore (Harbourfront Centre)  
**Employees:** ~1,200–1,300 globally  
**Website:** https://www.ecogreenoleo.com

### Manufacturing Entities
| Entity | Location | Key Products |
|--------|----------|-------------|
| PT Ecogreen Oleochemicals | Batam, Indonesia | Saturated Fatty Alcohols (C8–C18), Oleic Acid, Refined Glycerin, MCT |
| Ethoxylates Manufacturing Pte Ltd (EMPL) | Singapore | Fatty Alcohol Ethoxylates |
| Deutsche Hydrierwerke GmbH (DHW) Rodleben | Germany (est. 1916) | Unsaturated Fatty Alcohols, Fatty Amines, Specialty Esters, Sorbitol |
| E&S Chimie | France | Fatty Alcohol Ethoxylates, Sulfates, Specialty Esters |

### Product Lines
- **Ecorol®** — Saturated Fatty Alcohols (C8–C18)
- **Rofanol®** — Unsaturated Fatty Alcohols (Oleyl Alcohols)
- **Ecoric®** — Fatty Acids (Oleic Acid, etc.)
- **Refined Glycerin** — Food/pharma grade
- **MCT (Medium Chain Triglycerides)** — Food, cosmetics, pharma, lubricant
- **Fatty Alcohol Ethoxylates** — Surfactant downstream

### Certifications
ISO 9001, ISO 14001, FSSC 22000 (Food Safety), GMP+B2, ISO 17025, HAS 23000 (Halal, LPPOM MUI), Kosher (Orthodox Union), RSPO SCCS (since 2013), ECOCERT, Cosmos Cosmetic Standard.

---

## Sources
- Company website: https://www.ecogreenoleo.com (web research, April 2026; direct fetch unavailable)
- Codebase: Not attached — website uses PHP/Yii framework, Bootstrap, Font Awesome
- Figma: Not provided
- No slides or branded templates were provided

> **Note:** This design system was built from web research only. No codebase, Figma file, or branded assets were provided. Visual decisions are inferred from available brand information and industry context. Please provide source assets to refine.

---

## CONTENT FUNDAMENTALS

### Voice & Tone
- **Professional and authoritative** — speaks as a trusted global industrial supplier
- **Third person for company** ("Ecogreen Oleochemicals is…") on formal pages; **"we"/"our"** in narrative/commitment copy
- **Formal but accessible** — avoids jargon overload; explains chemistry terms when needed
- **Customer-centric** — "To serve our customers globally…"; "We are committed to supply high quality products and to build strong customer relationships"
- **Sustainability-conscious but factual** — leads with certifications and data, not just claims
- **No emoji** — purely professional; emoji are never used
- **Sentence case** for headings, **Title Case** for product names and certifications
- **Numbers are precise** — C8 to C18 (not "various"); "since 2013" (not "for years")

### Copy Patterns
- Short declarative sentences for facts: "We produce various cuts of Saturated Fatty Alcohols (from C8 to C18)."
- Lists of certifications with parenthetical issuing body: "ISO 9001 (Quality) by TUV Rheinland"
- Product names always accompanied by generic name: "Ecorol® Fatty Alcohols are…"
- Registered trademark ® always used for product names
- Applications listed as comma-separated runs: "personal care, oral care, laundry detergents, dish washing liquid…"

---

## VISUAL FOUNDATIONS

### Colors

> ⚠ **Catatan untuk AI Agent:** Warna di bawah ini diinferensikan dari **website publik** Ecogreen.
> Untuk **aplikasi internal A&D**, gunakan token dari `DESIGN-SYSTEM.md` yang sudah dikalibrasi
> ke Vuetify theme: primary = `#42B240`, background = `#F7F4EF`.

- **Primary Green:** Deep forest green (`#1A6B3C`) — logo color di website publik
- **Secondary Green:** Leaf green (`#4C9A5A`) — hover states di website publik
- **Light Green:** (`#E8F5EC`) — backgrounds, tinted panels
- **Dark:** (`#0D3B22`) — dark backgrounds, footers
- **Accent Warm:** (`#C8873A`) — highlight, callouts (rare use)
- **Neutral Warm:** (`#F7F4EF`) — page background, card backgrounds
- **Text Primary:** (`#1C2420`) — body text
- **Text Secondary:** (`#5A6860`) — captions, metadata
- **White:** (`#FFFFFF`) — card surfaces, reversed text
- **Border:** (`#D4DDD8`) — separators, card outlines

### Typography
- **Display / Headings:** Montserrat — geometric sans, conveys modernity and precision
- **Body:** Source Sans 3 — highly readable; clean workhorse for long-form product descriptions
- **Mono / Technical:** JetBrains Mono — for chemical formulas, spec tables, grade codes
- Google Fonts substitution (no original font files provided)

### Spacing & Layout
- **Base unit:** 8px
- **Container max-width:** 1200px
- **Section padding:** 80px vertical (desktop), 48px (mobile)
- **Card padding:** 24px
- **Grid:** 12-column Bootstrap-style; primary layout is 3-col product grid, 2-col feature splits

### Backgrounds
- White (#FFFFFF) — primary page surface
- Warm off-white (#F7F4EF) — alternating sections
- Deep green (#0D3B22) — footer, hero overlays
- No gradient backgrounds; no textures or patterns
- Photography: facility/factory imagery, green landscapes; warm, slightly desaturated; never grainy

### Corner Radii
- Cards: 6px
- Buttons: 4px
- Badges/pills: 100px (fully rounded)
- Inputs: 4px

### Shadows
- Card: `0 2px 8px rgba(0,0,0,0.08)` — subtle lift
- Dropdown/modal: `0 8px 24px rgba(0,0,0,0.14)`
- No heavy drop shadows; prefer border + subtle shadow combo

### Borders
- 1px solid `#D4DDD8` — default card/table border
- Left accent border: not used (not a brand motif)

### Animation
- Subtle; no bounces or spring physics
- Fade + translateY(8px): standard entrance
- Transition: 200ms ease — hover states; 300ms ease — panel open/close
- No auto-playing animations; scroll-triggered fade-in for section entries

### Hover States
- Buttons: darken 10% (`#155832`)
- Cards: `box-shadow` intensifies; `translateY(-2px)`
- Nav links: green underline slides in from left

### Iconography
- Font Awesome 6 (Free tier) — confirmed in tech stack
- Style: solid (`fas`) primary; regular (`far`) for secondary/outline
- No emoji as icons
- No custom icon font; no SVG sprite

### Cards
- White background, 6px radius, 1px border, subtle shadow
- Image top → content body → footer with CTA
- Product cards: image + grade + application tags + download PDF link

### Imagery
- Industrial plant photos, close-ups of chemical products, green landscapes
- Color grading: warm, slightly desaturated — not stark/clinical
- Never stock-photo generic; always facility-authentic

---

## ICONOGRAPHY

> ⚠ **Catatan untuk AI Agent:** Dokumen ini mendeskripsikan **website publik Ecogreen** (ecogreenoleo.com)
> yang menggunakan Font Awesome 6. **Aplikasi internal A&D** menggunakan **Remix Icon (`ri-*`)**
> sesuai tech stack (`ARCHITECTURE.md §3`) dan Materio Vuetify template.
> Gunakan Remix Icon — bukan Font Awesome — saat membangun komponen app.

Font Awesome 6 Free is used on the **public website**. No custom icon font exists.  
CDN: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css`

**Common icon usage:**
- `fa-leaf` — sustainability, natural sourcing
- `fa-industry` — manufacturing / facilities
- `fa-flask` — products / R&D
- `fa-certificate` — certifications
- `fa-globe` — global presence
- `fa-file-pdf` — product data sheet downloads
- `fa-phone`, `fa-envelope`, `fa-map-marker-alt` — contact info
- `fa-chevron-right` — nav arrows, breadcrumbs

No SVG illustrations are used. No emoji. No PNG icon set was identified.

---

## FILE INDEX

```
README.md                    ← This file
SKILL.md                     ← Agent skill descriptor
colors_and_type.css          ← CSS design tokens
assets/
  logo.svg                   ← Ecogreen logo (constructed)
  logo-white.svg             ← White reversed logo
preview/
  colors-primary.html        ← Primary color swatches
  colors-neutral.html        ← Neutral palette
  colors-semantic.html       ← Semantic color tokens
  type-display.html          ← Display / heading type scale
  type-body.html             ← Body type specimens
  type-mono.html             ← Monospace / technical type
  spacing-tokens.html        ← Spacing scale tokens
  spacing-radius-shadow.html ← Radius + shadow system
  components-buttons.html    ← Button states
  components-badges.html     ← Badges, pills, tags
  components-cards.html      ← Product card component
  components-forms.html      ← Form inputs
  components-nav.html        ← Navigation header
  brand-logo.html            ← Logo usage
  brand-certifications.html  ← Certification badge system
ui_kits/
  website/
    README.md
    index.html               ← Marketing website UI kit
    Header.jsx
    Footer.jsx
    ProductCard.jsx
    HeroSection.jsx
    CertBadge.jsx
```
