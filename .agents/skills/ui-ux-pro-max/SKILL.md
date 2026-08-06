---
name: ui-ux-pro-max
description: >
  Elite UI/UX design skill for building world-class, premium enterprise interfaces.
  Triggers on any UI, UX, frontend, design, styling, layout, dashboard, component,
  responsive, mobile, animation, or visual polish task.
---

# UI/UX Pro Max — Enterprise Design System Mastery

You are an elite-tier UI/UX architect. Every interface you touch must feel like a
**million-dollar SaaS product**. Mediocre designs are unacceptable.

---

## Core Design Philosophy

### 1. Visual Hierarchy Is Law
- Every screen must have a **clear focal point** (hero metric, CTA, or key data)
- Use size, weight, color, and spacing to guide the eye naturally
- Never let two elements compete for attention at the same hierarchy level

### 2. Dark Mode First, Premium Always
- Default to rich dark interfaces (`#0a0a0a` → `#1a1a2e` gradients)
- Use **glassmorphism** surfaces: `backdrop-filter: blur(20px)`, translucent backgrounds
- Borders should be subtle: `rgba(255,255,255,0.06)` — never harsh solid lines
- Shadows should be deep and layered: `0 8px 32px rgba(0,0,0,0.4)`

### 3. Color Palette Rules
- **Never use raw CSS colors** (`red`, `blue`, `green`)
- Always use HSL or curated palettes through CSS custom properties
- Accent colors must be **warm gold** (`#cfa46f`, `#d4a574`) for this project
- Status colors: Success `#4ade80`, Warning `#fbbf24`, Danger `#f87171`, Info `#60a5fa`
- Text hierarchy: Primary `#f3e7cd`, Secondary `#b39b82`, Muted `#6b5c4d`

### 4. Typography
- Use **Inter** or system font stack — never browser defaults
- Font weights: 400 (body), 600 (labels), 700 (headings), 800 (hero numbers)
- Line heights: 1.6 for body, 1.2 for headings
- Letter spacing: `0.5px` for uppercase labels, `-0.02em` for large headings

### 5. Spacing & Layout
- Use an **8px grid system** (multiples of 8: 8, 16, 24, 32, 48, 64)
- Card padding: minimum `24px`, hero sections `32px–48px`
- Gap between cards: `16px–24px`
- Border radius: `12px` for cards, `8px` for buttons, `16px–24px` for hero sections
- **Never use Bootstrap's default spacing** — always override with premium values

---

## Component Standards

### KPI / Stat Cards
```css
/* Structure */
.ent-kpi-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 16px;
  padding: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.ent-kpi-card:hover {
  transform: translateY(-2px);
  border-color: rgba(207,164,111,0.3);
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
}
```
- Always include: icon, label, value, and optional trend indicator
- Values should be large and bold (1.75rem+, weight 800)
- Labels should be muted and uppercase (0.75rem, weight 600, letter-spacing 0.5px)

### Section Cards
```css
.ent-section {
  background: rgba(255,255,255,0.02);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 16px;
  overflow: hidden;
}
.ent-section-header {
  padding: 20px 24px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
```

### Buttons
- Primary: Gold gradient background, dark text, `border-radius: 10px`
- Secondary: Transparent with gold border, gold text
- Ghost: No border, subtle hover background
- All buttons: `padding: 10px 20px`, `font-weight: 600`, `transition: all 0.2s`
- **Always add hover states** — buttons without hover feel dead

### Tables
- No visible outer borders — use subtle row dividers only
- Header: uppercase, muted color, smaller font
- Rows: hover effect with subtle background shift
- Status cells: use colored badges, never raw text

### Forms
- Input backgrounds: `rgba(0,0,0,0.3)` with `border: 1px solid rgba(255,255,255,0.1)`
- Focus state: gold border glow (`box-shadow: 0 0 0 3px rgba(207,164,111,0.2)`)
- Labels: above inputs, muted color, small caps
- Error states: red border + red helper text below
- Group related fields in glass panels

### Badges / Status Indicators
```css
.ent-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.ent-badge.success { background: rgba(74,222,128,0.15); color: #4ade80; }
.ent-badge.warning { background: rgba(251,191,36,0.15); color: #fbbf24; }
.ent-badge.danger  { background: rgba(248,113,113,0.15); color: #f87171; }
```

### Empty States
- Always show an icon (3rem, muted opacity)
- Descriptive text below (not just "No data")
- Optional CTA button to guide the user
- Center-aligned with generous padding (60px+)

---

## Animation & Motion

### Entry Animations
```css
.ent-fade-up {
  opacity: 0;
  transform: translateY(20px);
  animation: entFadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes entFadeUp {
  to { opacity: 1; transform: translateY(0); }
}
```
- Stagger child elements with delay classes: `ent-delay-1` (0.1s), `ent-delay-2` (0.2s), etc.
- Keep animations under 0.6s — anything longer feels sluggish
- Use `cubic-bezier(0.16, 1, 0.3, 1)` (spring easing) for natural motion

### Hover Micro-interactions
- Cards: `translateY(-2px)` + enhanced shadow
- Buttons: slight scale `scale(1.02)` or background shift
- Links: color transition with underline reveal
- Icons: subtle rotation or scale on parent hover

### Loading States
- Use skeleton screens, not spinners, for content loading
- Skeleton: `background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.08) 50%, rgba(255,255,255,0.04) 75%)`
- Animate with `background-size: 200% 100%` and horizontal sweep

---

## Responsive Design Rules

### Breakpoints
- Mobile: `< 768px` — single column, stacked layout
- Tablet: `768px – 1024px` — 2-column grid
- Desktop: `> 1024px` — full multi-column layout

### Mobile-First Mandates
- Touch targets: minimum `44px × 44px`
- Font sizes: minimum `14px` for body, `12px` for labels
- Horizontal scroll: **NEVER** — if content overflows, redesign the layout
- Hide non-essential UI on mobile (e.g., decorative elements, secondary stats)
- Use `position: sticky` for key navigation elements
- Cards should go full-width on mobile with reduced padding (16px)

### Grid System
```css
.ent-grid { display: grid; gap: 16px; }
.ent-grid-2 { grid-template-columns: repeat(2, 1fr); }
.ent-grid-3 { grid-template-columns: repeat(3, 1fr); }
.ent-grid-4 { grid-template-columns: repeat(4, 1fr); }

@media (max-width: 1024px) {
  .ent-grid-3, .ent-grid-4 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
  .ent-grid-2, .ent-grid-3, .ent-grid-4 { grid-template-columns: 1fr; }
}
```

---

## Anti-Patterns (NEVER Do These)

1. **No inline styles for layout** — use CSS classes from the design system
2. **No default Bootstrap colors** — always override with project palette
3. **No unstyled scrollbars** — customize with `::-webkit-scrollbar`
4. **No text-only buttons** — always pair with an icon
5. **No walls of text** — break into scannable sections with icons and spacing
6. **No raw `<table>` without styling** — always wrap in enterprise table classes
7. **No placeholder images** — generate real assets or use meaningful icons
8. **No `!important`** — fix specificity issues properly
9. **No hard-coded pixel values for responsive widths** — use %, vw, or grid
10. **No unstyled form elements** — every input, select, and checkbox must be themed

---

## Quality Checklist (Before Every UI Delivery)

- [ ] Does the page have a clear visual hierarchy?
- [ ] Are all interactive elements (buttons, links, inputs) styled with hover/focus states?
- [ ] Is the layout responsive across mobile, tablet, and desktop?
- [ ] Are animations smooth and purposeful (not distracting)?
- [ ] Does the color palette stay within the defined design tokens?
- [ ] Are empty states handled gracefully?
- [ ] Is text readable with proper contrast ratios?
- [ ] Do all cards and sections have consistent border-radius and spacing?
- [ ] Are icons used meaningfully (not decoratively)?
- [ ] Would a CEO look at this and think "this looks expensive"?
