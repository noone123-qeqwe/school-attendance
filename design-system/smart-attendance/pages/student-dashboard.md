# Student Dashboard Page Overrides

> **PROJECT:** Smart Attendance (Osmeña Colleges)
> **Scope:** `/home` on desktop and mobile. `/mobile/home` redirects to `/home`.
> **Stylesheet:** `public/css/student-dashboard.css` (v4.0 — Academic Command Canvas)

---

## Page-Specific Rules

### Layout Architecture
- **Shared:** Show the student identity, relevant alerts, live or upcoming class, today's schedule, and available announcements. The featured class offers **Check in now** during an active session and **Check in for a classmate** when Peer-Snap is available. Schedule controls offer `All`, `Remaining`, and `Done` filters and a link to the full timetable.
- **Tablet and desktop (`>= 768px`):** The masthead includes **Scan QR** and **Enter PIN**. The attendance panel includes **Attendance Standing** and the **7-Day Pulse**. **Subject Standing** appears in the dashboard workspace. The hero becomes two columns at `1024px`; the workspace becomes two columns at `1120px`.
- **Mobile (`< 768px`):** Use a single column for the shared content and the bottom **Scan** tab for general check-in. The masthead check-in buttons, attendance panel, and subject standing are hidden at this width.

### Real-Time & Accessibility Guarantees
- Clock and spotlight countdown pause automatically when `document.hidden === true` and sync immediately on tab focus.
- Schedule filter (`All`, `Remaining`, `Done`) announces visible class counts via `#sdScheduleLiveRegion` (`aria-live="polite" aria-atomic="true"`) without stealing focus.
- On tablet and desktop, the 7-Day Pulse opens `#daySummaryModal` with `ArrowLeft` / `ArrowRight` stepping, a loading indicator, and retry on error.
- Use actual attendance records for statistics, escape dynamic content, and apply CSP nonces to inline script blocks.
