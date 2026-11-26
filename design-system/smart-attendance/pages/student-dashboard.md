# Student Dashboard Page Overrides

> **PROJECT:** Smart Attendance (Osmeña Colleges)
> **Scope:** `/home` (Desktop + Mobile) and `/mobile/home`
> **Stylesheet:** `public/css/student-dashboard.css` (v4.0 — Academic Command Canvas)

---

## Page-Specific Rules

### Layout Architecture
- **Desktop (`>= 1120px`) & Large Screens (`>= 1024px`)**:
  1. **Open Editorial Masthead (`.sd-masthead`):** Borderless circular avatar (`.sd-avatar-ring`), typographic greeting & live clock kicker, display student name (`h1`), quiet mid-dot metadata ribbon, plus Login-matched primary `Scan QR` & secondary `Enter PIN` CTAs directly on canvas (no nested outer box).
  2. **Contextual Alert Rail:** Dismissible missing-section notice banner (`#missingSectionNotice`), session status/error banners, and active attendance warning panel when warnings exist.
  3. **Signature Asymmetric Hero Grid (`.sd-hero-grid` — `1.38fr : 1fr`):** Left **Live / Next Class Stage (`#sdHeroSpotlight`)** with countdown timer (`#sdSpotlightTimer`) and session progress meter (`#sdSpotlightProgressFill`); Right **Attendance Telemetry Panel (`.sd-telemetry`)** unifying the SVG radial standing gauge, 3-segment proportional distribution bar (`Present` / `Late` / `Absent`), streak & pending-excuse pills, and interactive **7-Day Pulse** strip.
  4. **Contextual Utility Rail (`.sd-utility-rail`):** Sleek hairline shortcuts (`Weekly Schedule`, `My Classes`, `Attendance Log`, `Submit Excuse`, conditional `Student Assistant` & `Peer Check-In`) with live counts.
  5. **Asymmetric Operational Workspace (`.sd-workspace` — `1.42fr : 1fr`):** Primary column hosts interactive **Today's Schedule** timeline (with segmented `All` / `Remaining` / `Done` filters + polite `#sdScheduleLiveRegion`) and **Recent Check-Ins** ledger; secondary column surfaces sorted **Subject Standing** rows (showing calm `New` for zero-record subjects) and **Campus & Class Announcements**.
- **Tablet (`768px–1023px`)**:
  - Open masthead with side-by-side actions, stacked hero panels for generous readability beside the sidebar, full-width schedule time-rail timeline, and wrapping utility shortcuts.
- **Mobile (`< 768px`, down to `320px`)**:
  - Compact identity header with full-width 2-button thumb-reachable `Scan QR` / `Enter PIN` strip above the fold (single-column at `<= 359px`).
  - Horizontal swipeable utility rail (`>= 44px` tap targets), inline time-pill schedule rows, and native-feeling bottom-sheet `#daySummaryModal` (`max-height: 88dvh`, drag handle, safe-area bottom inset).

### Real-Time & Accessibility Guarantees
- Clock and spotlight countdown pause automatically when `document.hidden === true` and sync immediately on tab focus.
- Schedule filter (`All`, `Remaining`, `Done`) announces visible class counts via `#sdScheduleLiveRegion` (`aria-live="polite" aria-atomic="true"`) without stealing focus.
- Interactive 7-Day Pulse opens `#daySummaryModal` with `ArrowLeft` / `ArrowRight` stepping, `.ds-skeleton-stack` shimmer loading (`CLS < 0.1`), strict HTML escaping, and safe error retry.
- Zero fabricated stats, zero inline `onclick=""` handlers, and CSP nonces applied on every `<script>` block.
