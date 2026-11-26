# Design System Master File — Smart Attendance (Osmeña Colleges)

> **LOGIC:** When building a specific page, first check `design-system/smart-attendance/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.

---

**Project:** Smart Attendance (Osmeña Colleges)
**System Version:** v4.0 — Login-Matched Smoked Charcoal Glass, Warm Gold & Subtle Maroon
**Pattern:** Commercial Operational SaaS + Native-Grade Mobile App
**Quality Benchmarks:** Linear (surface clarity & hairline discipline), Apple (mobile ergonomics & typography), Stripe (information density & contrast)

---

## Global Rules

### Brand Color Palette (Matched to Login UI & Osmeña Seal)

| Role | Hex / Value | CSS Variable | Contrast Target |
|------|-------------|--------------|-----------------|
| Canvas Background | `#141010` | `--sdc-canvas` / `--ds-bg` | Base |
| Surface (Primary Glass Card) | `#1E1515` (`rgba(28, 18, 18, 0.88)`) | `--sdc-surface` / `--ds-surface` | Elevated layer |
| Surface Raised | `#261B1B` | `--sdc-surface-2` | Hover / input / well |
| Surface Interactive | `#2F2222` | `--sdc-surface-3` | Active / selected |
| Seal Maroon Deep | `#561313` | `--sdc-seal-core` | Institutional badge |
| Seal Maroon Accent | `#7A1A1A` | `--sdc-seal-mid` | Active nav wash / subtle border |
| Osmeña Primary Maroon | `#800000` | `--sdc-seal-bright` / `--ds-maroon` | Interactive emphasis / button ink |
| Primary CTA (Crisp Ivory Glass) | `#FFFFFF` -> `#F5F5F5` | `--sdc-cta-grad` | `10.95:1` vs `#800000` |
| On Primary CTA | `#800000` | `--sdc-cta-text` | WCAG AAA |
| Primary Text | `#FAF8F5` | `--sdc-text` | `> 16.5:1` vs `#1E1515` |
| Secondary Text | `#D6CCC6` | `--sdc-text-soft` | `> 11.2:1` vs `#1E1515` |
| Muted Text | `#A49890` | `--sdc-muted` | `> 5.8:1` vs `#1E1515` |
| Warm Gold Accent | `#D4AF37` | `--sdc-rose-accent` / `--ds-gold` | `> 7.8:1` vs `#1E1515` |
| Present / Live | `#34D399` | `--sdc-emerald` | `> 9.4:1` vs `#1E1515` |
| Late / Warning | `#FBBF24` | `--sdc-amber` | `> 10.6:1` vs `#1E1515` |
| Absent / Destructive | `#FB7185` | `--sdc-rose` | `> 6.9:1` vs `#1E1515` |
| Info / Excused | `#38BDF8` | `--sdc-sky` | `> 8.8:1` vs `#1E1515` |
| Hairline Border | `rgba(212, 175, 55, 0.16)` | `--sdc-border` | Structural separation |
| Hairline Subtle | `rgba(255, 255, 255, 0.08)` | `--sdc-border-subtle` | Row dividers |
| Focus Ring | `#D4AF37` / `#FAF8F5` | `--ds-focus-ring` | WCAG 2.2 AA/AAA |

### Surface & Anti-Clutter Discipline
- **Elevation via Luminance & Smoked Glass Hairlines:** Prefer translucent smoked charcoal surfaces (`#141010` -> `#1E1515` -> `#261B1B` / `rgba(28, 18, 18, 0.88)`) and `1px solid` warm gold/neutral hairlines over heavy saturated maroon walls or deep fog shadows.
- **No Cards Inside Cards:** Inside surface panels, separate items with clean `1px solid var(--sdc-border-subtle)` hairline dividers or compact tonal rows instead of nested boxed cards.
- **Restrained Blur:** Reserve `backdrop-filter: blur(16px)` strictly for floating chrome (top header, mobile bottom navigation bar, modals, command palette) to protect mobile GPU frame rates.
- **No `background-attachment: fixed`:** Avoid full-viewport scroll repaints for 60fps mobile and desktop scrolling.
- **Disciplined Radii Scale:** `8px` (`--sdc-radius-xs`), `10px` (`--sdc-radius-sm`), `14px` (`--sdc-radius-md`), `16px` (`--sdc-radius-lg`), `20px` (`--sdc-radius-xl` modals/sheets).

### Typography
- **Display & UI Font:** `Plus Jakarta Sans`, `Inter`, system-ui, sans-serif
- **Monospace / Tabular Data Font:** `JetBrains Mono`, ui-monospace, monospace
- **Numeric Discipline:** Apply `font-variant-numeric: tabular-nums` to KPIs, clocks, countdowns, badges, and data columns.
- **Heading Balance:** Apply `text-wrap: balance` to `h1–h3`; `overflow-wrap: anywhere` to shrinkable flex/grid text children.
- **Mobile Readability:** Minimum `16px` on form inputs to prevent iOS auto-zoom; minimum `12px` for helper/badge labels.

### Spacing, Layout & Responsive Targets
- Representative tested viewports: `320px`, `375px`, `390px`, `430px`, `768px`, `1024px`, `1280px`, `1440px`, `1920px` plus landscape.
- Touch targets: `>= 44×44px` on all mobile interactive controls with `>= 8px` spacing between adjacent controls.
- Fixed chrome offset: `scroll-padding-top` and `scroll-padding-bottom` compensate for sticky header and mobile bottom navigation (`focus-not-obscured`, WCAG 2.2 AA).
- Zero horizontal viewport overflow across all breakpoints.

### Motion Tokens (Purposeful, Interruptible, GPU-Only)
- `--ds-dur-fast: 120ms` (press / micro-feedback)
- `--ds-dur-base: 180ms` (hover / border / color transitions)
- `--ds-dur-enter: 240ms` (`cubic-bezier(0.16, 1, 0.3, 1)`)
- `--ds-dur-exit: 145ms` (`cubic-bezier(0.4, 0, 1, 1)`)
- Respect `@media (prefers-reduced-motion: reduce)` unconditionally.
- Pause live clocks/countdown timers when `document.hidden === true` and refresh immediately on `visibilitychange`.

### Accessibility & UX Mandates (WCAG 2.2 AA)
- **Vector Icons Only:** Decorative icons beside text MUST carry `aria-hidden="true"`.
- **Single Live Region Discipline:** One contextual `aria-live="polite" aria-atomic="true"` status announcer per filter/search surface.
- **Color Not Only Indicator:** Pair semantic status colors with clear labels and icons.
- **Focusable Error Summaries:** Multi-error forms use `<x-error-summary />` (`role="alert" tabindex="-1"`).
- **Progressive Loading:** Layout-reserved skeleton shimmer cards inside async modals (`CLS < 0.1`).

### Student Portal Implementation Architecture
- **Master Stylesheet (`public/css/student-portal-master.css`):** Loaded on student routes across `layouts/app.blade.php`, `layouts/mobile-app.blade.php`, and `layouts/biometric-flow.blade.php`, wrapped in `@media screen` (preserving clean black-on-white `@media print` / PDF output) and strictly scoped to `body.student-portal`, `body.student-mobile-portal`, and `body.biometric-flow-page` so Admin, Teacher, and Parent portals remain untouched.
- **Covered Student Surfaces (Desktop & Mobile):**
  - Shell: Desktop sidebar, settings sidebar, top header, notification & account dropdowns, Command Palette (`Ctrl+K`), mobile header, slide-over drawer, docked mobile bottom navigation bar, More bottom sheet, modals, toasts.
  - Pages: Dashboard (`/home`, `/mobile/home`), Classes & COR (`/my-classes`), Weekly Schedule (`/schedule`), Attendance & School Calendar + Records (`/student/calendar`, `/student/attendance-calendar`, `/attendance/records`, `/mobile/history`), QR Check-In & Results (`/mobile/scan`, `/qr/result`, `/qr/login`, `/peer-snap`), Excuse Submissions & Forms (`/excuses*`), Notifications (`/notifications`), Profile (`/profile`, `/mobile/profile`), Settings (`/settings`), Face Registration Studio (`/settings/biometrics/face`), Student Assistant (`/student-assistant*`), and student status/auth pages (`/student/pending-approval`, `/password/change`, `/offline`).
