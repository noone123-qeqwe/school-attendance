# Student Dashboard Page Overrides

> **PROJECT:** Smart Attendance (Osmeña Colleges)
> **Scope:** `/home` (Desktop + Mobile) and `/mobile/home`
> **Stylesheet:** `public/css/student-dashboard.css`

---

## Page-Specific Rules

### Layout Architecture
- **Desktop (`>= 1024px`)**:
  1. Top Command & Identity Ribbon (Avatar + greeting + live clock + student metadata + dual Check-In CTAs).
  2. Asymmetric 12-column Hero Bento (`7fr` Live/Next Class Focus Stage + `5fr` Circular Standing Gauge & 7-Day Interactive Attendance Pulse).
  3. 4-column Quick Workspace Dock (`Weekly Schedule`, `My Classes`, `Attendance Log`, `Submit Excuse`).
  4. 4-column Interactive Attendance Overview KPI Bento (`Present`, `Late`, `Absent`, `Subjects`).
  5. Asymmetric 2-column Workspace (`1.44fr` Today's Schedule Timeline + Recent Activity | `1fr` Subject Attendance Standing + Campus Announcements).
- **Mobile (`< 768px`)**:
  - Compact Identity Ribbon with full-width 2-column primary Check-In dock above the fold.
  - Full-width Live/Next Class Pass followed by Standing Gauge + 7-Day Pulse card.
  - Swipeable single-row Quick Dock (`/home`) or 2-column Quick Tools grid (`/mobile/home`).
  - 2×2 KPI Bento tiles and bottom-sheet modals with drag handles and safe-area bottom inset.

### Real-Time & Accessibility Guarantees
- Clock and spotlight countdown pause automatically when `document.hidden === true` and sync immediately on tab focus.
- Schedule filter (`All`, `Remaining`, `Done`) and attendance records search announce contextual counts via polite `aria-live` status regions without stealing focus.
- Day Summary Inspector supports `ArrowLeft` / `ArrowRight` keyboard navigation between days and `Escape` dismissal.
- Async modals (`#daySummaryModal`, `#attendanceRecordsModal`) render skeleton shimmer placeholders matching card geometry to prevent layout shift.
- Command Palette (`#cmdPaletteOverlay`) inherits the Osmeña Colleges Burgundy & Pearl-Silver theme on the student dashboard.
