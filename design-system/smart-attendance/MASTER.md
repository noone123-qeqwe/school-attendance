# Design System Master File — Smart Attendance (Osmeña Colleges)

> **LOGIC:** When building a specific page, first check `design-system/smart-attendance/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.

---

**Project:** Smart Attendance (Osmeña Colleges)
**Methodology:** UI UX Pro Max v2.15.0
**Pattern:** Real-Time Operations Dashboard (Glassmorphism + Bento Hierarchy)
**Design Dials:** Variance 7/10 (Balanced / Modern) | Motion 4/10 (Standard, Purposeful) | Density 8/10 (Dense / Dashboard)

---

## Global Rules

### Brand Color Palette (Matched to `public/images/logo.png`)

| Role | Hex | CSS Variable | Contrast Target |
|------|-----|--------------|-----------------|
| Canvas Background | `#0F0407` | `--sdc-canvas` / `--ds-seal-bg` | Base |
| Surface (Primary Card) | `#1B090F` | `--sdc-surface` | Elevated layer |
| Surface Raised | `#270E17` | `--sdc-surface-2` | Hover / nested |
| Surface Interactive | `#361320` | `--sdc-surface-3` | Active / hover |
| Seal Burgundy Core | `#381018` | `--ds-seal-core` | Logo medallion |
| Seal Burgundy Mid | `#5A1827` | `--ds-seal-mid` | Active nav / accents |
| Seal Crimson Bright | `#8A2439` | `--ds-seal-bright` | Ambient glow |
| Primary CTA (Embossed Pearl) | `#FFFFFF → #E6DCE0` | `--sdc-silver` | `15.8:1` vs `#300810` |
| On Primary CTA | `#300810` | `--ds-seal-ink` | WCAG AAA |
| Primary Text | `#FCF9FA` | `--sdc-text` | `> 16:1` vs `#1B090F` |
| Secondary Text | `#DFD1D5` | `--sdc-text-soft` | `> 11:1` vs `#1B090F` |
| Muted Text | `#B8A0A8` | `--sdc-muted` | `> 6.5:1` vs `#1B090F` |
| Rose-Mauve Accent | `#E48FA0` | `--sdc-rose-accent` | `> 7.4:1` vs `#1B090F` |
| Present / Live | `#34D399` | `--sdc-emerald` | `> 9.5:1` vs `#1B090F` |
| Late / Warning | `#FBBF24` | `--sdc-amber` | `> 10.5:1` vs `#1B090F` |
| Absent / Destructive | `#FF6B7A` | `--sdc-rose` | `> 6.8:1` vs `#1B090F` |
| Info / Excused | `#7DD3FC` | `--sdc-sky` | `> 11.0:1` vs `#1B090F` |
| Focus Ring | `#FFFFFF` | `--ds-focus-ring` | WCAG 2.2 AA/AAA |

### Typography
- **Display & UI Font:** `Plus Jakarta Sans`, `Inter`, system-ui, sans-serif
- **Monospace / Tabular Data Font:** `JetBrains Mono`, ui-monospace, monospace
- **Numeric Discipline:** Always apply `font-variant-numeric: tabular-nums` to KPIs, clocks, countdowns, badges, and data columns.
- **Heading Balance:** Apply `text-wrap: balance` to `h1–h3`; `overflow-wrap: anywhere` to shrinkable flex/grid text children.
- **Mobile Readability:** Minimum `16px` on form inputs to prevent iOS auto-zoom; minimum `12px` for helper/badge labels.

### Spacing & Layout (4px/8px Grid — Density 8/10)
- Breakpoints: `375px` (small phone), `768px` (tablet), `1024px` (desktop), `1440px` (wide desktop).
- Touch targets: `>= 44×44px` on all mobile controls with `>= 8px` gap between adjacent targets.
- Fixed chrome offset: `scroll-padding-top` and `scroll-padding-bottom` MUST compensate for sticky topbar and mobile bottom nav (`focus-not-obscured`, WCAG 2.2 AA).
- Zero horizontal viewport overflow at `375px` and landscape orientations.

### Motion Tokens (Purposeful, Interruptible, GPU-Only)
- `--ds-dur-fast: 120ms` (press / micro-feedback)
- `--ds-dur-base: 180ms` (hover / border / color transitions)
- `--ds-dur-enter: 240ms` (`cubic-bezier(0.16, 1, 0.3, 1)` deceleration on arrival)
- `--ds-dur-exit: 145ms` (`cubic-bezier(0.4, 0, 1, 1)` acceleration on departure, ~60% of enter)
- Animate `transform` and `opacity` only. Never animate `width`/`height`/`top`/`left`.
- Respect `@media (prefers-reduced-motion: reduce)` unconditionally.
- Pause live clocks/countdown timers when `document.hidden === true` and refresh immediately on `visibilitychange`.

### Accessibility & UX Mandates (WCAG 2.2 AA)
- **No Emoji as Structural Icons:** Vector icons (`Bootstrap Icons` / inline SVG) only. Decorative icons beside text MUST carry `aria-hidden="true"`.
- **Single Live Region Discipline:** Static badges MUST NOT use `role="status"`. Use one contextual `aria-live="polite" aria-atomic="true"` status announcer per filter/search surface.
- **Color Not Only Indicator:** Pair status colors with semantic text and icons.
- **Focusable Error Summaries:** Multi-error forms use `<x-error-summary />` (`role="alert" tabindex="-1"`) linked to invalid fields while keeping inline `aria-describedby` errors.
- **Progressive Loading:** Use layout-reserved skeleton shimmer cards instead of empty spinners inside async modals (`CLS < 0.1`).
- **Tap & Keyboard Parity:** `touch-action: manipulation`, `cursor: pointer`, visible `:focus-visible` rings, Escape/Arrow keyboard controls for dialogs and day steppers.

### Student Portal Implementation Architecture
- **Master Stylesheet (`public/css/student-portal-master.css`):** Loaded on **every student route** across `layouts/app.blade.php`, `layouts/mobile-app.blade.php`, and `layouts/biometric-flow.blade.php`, wrapped in `@media screen` (preserving clean black-on-white `@media print` / PDF output) and strictly scoped to `body.student-portal`, `body.student-mobile-portal`, and `body.biometric-flow-page` so Admin, Teacher, and Parent portals remain untouched.
- **Covered Student Surfaces (Desktop & Mobile):**
  - Shell: Desktop sidebar, settings sidebar, top header, notification & account dropdowns, Command Palette (`Ctrl+K`), mobile header, slide-over drawer, floating bottom nav + pearl-silver Scan jewel, More sheet, modals, toasts.
  - Pages: Dashboard (`/home`, `/mobile/home`), Classes & COR Sheet (`/classes`), Weekly Schedule (`/schedule`), Attendance & School Calendar + Records (`/calendar`, `/attendance-calendar`, `/attendance/records`, `/mobile/history`), QR Check-In Studio & Peer Snap (`/mobile/scan`, `/peer-snap`), Excuse Submissions & Forms (`/excuses`, `/excuses/create*`), Notifications (`/notifications`), Profile (`/profile`, `/mobile/profile`), Settings & Biometrics (`/settings`, `/settings/biometrics/*`), and Student Assistant (`/student-assistant*`).
- **Biometric Studio (`public/css/biometric-flow.css` + `public/css/camera-lighting.css` + `resources/views/qr/verify.blade.php`):**
  - Unified Osmeña Colleges burgundy-velvet & pearl-silver studio across **Face Registration** (`/settings/biometrics/face`), **Fingerprint Registration** (`/settings/biometrics/fingerprint`), **Attendance Face & Fingerprint Verification** (`/qr/verify`), and **Login Biometric Verification** (`#biometricModal`).
  - Features 2-column desktop studio layouts, mobile-first stacked ergonomics, optical viewfinder corners + rose-to-pearl scan sweep, concentric animated fingerprint sensor pad, hardware credentials vault, semantic consent/withdraw controls, and reduced-motion compliance.
