# Student Dashboard Page Overrides

> **PROJECT:** Smart Attendance (Osmeña Colleges)
> **Scope:** `/home` (Desktop + Mobile) and `/mobile/home`
> **Stylesheet:** `public/css/student-dashboard.css`

---

## Page-Specific Rules

### Layout Architecture
- **Desktop (`>= 1024px`)**:
  1. Compact Command & Identity Bar (Avatar + editorial greeting/clock metadata + crisp Alabaster primary Scan QR CTA & subtle secondary 6-Digit PIN CTA).
  2. Asymmetric 12-column Hero Bento (`7fr` Live/Next Class Focus Stage with subtle oxblood atmospheric edge + `5fr` Circular Standing Gauge & 7-Day Interactive Attendance Pulse).
  3. 4-column Quick Workspace Strip (`Weekly Schedule`, `My Classes`, `Attendance Log`, `Submit Excuse`) — flat hairline-bordered utility row.
  4. 4-column Interactive Attendance Overview KPI Row (`Present`, `Late`, `Absent`, `Subjects`) — tabular figures with thin proportional bar and click-to-inspect drilldown.
  5. Asymmetric 2-column Workspace (`1.45fr` Today's Schedule Timeline + Recent Activity hairline feed | `1fr` Subject Attendance Standing list + Campus Announcements) — zero nested card-in-card clutter.
- **Mobile (`< 768px`)**:
  - Compact Identity Ribbon with full-width 2-column thumb-reachable primary Check-In dock above the fold (stacks cleanly at `<= 359px`).
  - Live/Next Class Focus Card followed by Standing Gauge + 7-Day Interactive Pulse.
  - Swipeable single-row Quick Strip (`/home`) or 2-column Quick Tools grid (`/mobile/home`).
  - 2×2 KPI tiles and native-feel bottom-sheet modals with drag handles, `>= 44×44px` stepper/close touch targets, and safe-area bottom inset.

### Real-Time & Accessibility Guarantees
- Clock and spotlight countdown pause automatically when `document.hidden === true` and sync immediately on tab focus.
- Schedule filter (`All`, `Remaining`, `Done`) and attendance records search announce contextual counts via polite `aria-live` status regions without stealing focus.
- Day Summary Inspector supports `ArrowLeft` / `ArrowRight` keyboard navigation between days and `Escape` dismissal.
- Async modals (`#daySummaryModal`, `#attendanceRecordsModal`) render skeleton shimmer placeholders matching card geometry (`CLS < 0.1`).
- Command Palette (`#cmdPaletteOverlay`) is unified across all student pages via `student-portal-master.css`.
