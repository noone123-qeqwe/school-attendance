# Design System Master File — Smart Attendance (Osmeña Colleges)

> **LOGIC:** When building a specific page, first check `design-system/smart-attendance/pages/[page-name].md`.
> If that file exists, its rules **override** this Master file.
> If not, strictly follow the rules below.

---

**Project:** Smart Attendance (Osmeña Colleges)
**System Version:** v3.0 — Obsidian Oxblood & Crisp Alabaster
**Pattern:** Commercial Operational SaaS + Native-Grade Mobile App
**Quality Benchmarks:** Linear (surface clarity & hairline discipline), Apple (mobile ergonomics & typography), Stripe (information density & contrast)

---

## Global Rules

### Brand Color Palette (Matched to `public/images/logo.png`)

| Role | Hex / Value | CSS Variable | Contrast Target |
|------|-------------|--------------|-----------------|
| Canvas Background | `#0B0406` | `--sdc-canvas` | Base |
| Surface (Primary) | `#14080C` | `--sdc-surface` | Elevated layer |
| Surface Raised | `#1C0C11` | `--sdc-surface-2` | Hover / input / well |
| Surface Interactive | `#261118` | `--sdc-surface-3` | Active / selected |
| Seal Oxblood Core | `#3D0F1B` | `--sdc-seal-core` | Institutional badge |
| Seal Oxblood Mid | `#6B192B` | `--sdc-seal-mid` | Active nav / indicators |
| Seal Crimson Bright | `#9E2A42` | `--sdc-seal-bright` | Interactive emphasis |
| Primary CTA (Crisp Alabaster) | `#FAF7F8` -> `#EBE4E6` | `--sdc-cta-grad` | `16.2:1` vs `#24060D` |
| On Primary CTA | `#24060D` | `--sdc-cta-text` | WCAG AAA |
| Primary Text | `#FAF7F8` | `--sdc-text` | `> 17:1` vs `#14080C` |
| Secondary Text | `#D4C6CA` | `--sdc-text-soft` | `> 11.5:1` vs `#14080C` |
| Muted Text | `#9E8990` | `--sdc-muted` | `> 5.8:1` vs `#14080C` |
| Rose Quartz Accent | `#E898A8` | `--sdc-rose-accent` | `> 7.8:1` vs `#14080C` |
| Present / Live | `#34D399` | `--sdc-emerald` | `> 9.8:1` vs `#14080C` |
| Late / Warning | `#FBBF24` | `--sdc-amber` | `> 11.0:1` vs `#14080C` |
| Absent / Destructive | `#FB7185` | `--sdc-rose` | `> 7.2:1` vs `#14080C` |
| Info / Excused | `#38BDF8` | `--sdc-sky` | `> 9.2:1` vs `#14080C` |
| Hairline Border | `rgba(250, 244, 246, 0.11)` | `--sdc-border` | Structural separation |
| Hairline Subtle | `rgba(250, 244, 246, 0.07)` | `--sdc-border-subtle` | Row dividers |
| Focus Ring | `#FAF7F8` | `--ds-focus-ring` | WCAG 2.2 AA/AAA |

### Surface & Anti-Clutter Discipline
- **Elevation via Luminance & Hairlines:** Prefer solid elevated surfaces (`#14080C` -> `#1C0C11` -> `#261118`) and `1px solid` hairlines over heavy blurred fog shadows.
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
