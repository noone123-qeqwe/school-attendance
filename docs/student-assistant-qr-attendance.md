# Student Assistant QR attendance integration

## Architecture

The class is the existing `Subject` model. Existing `User` student accounts,
`enrollments`/class roster rules, `AttendanceSession`, `Attendance`, teacher QR
page, student scanner, Breeze authentication, Reverb/Echo channels, notification
table, queue, Spatie activity log, and bundled QR renderer are reused. No new
login, attendance table, class table, per-meeting assistant assignment, or OTP
flow is introduced.

`class_student_assistants` stores one assignment per class/student. Two unique
nullable slots serialize the maximum of two occupied assignments. Assignment
changes lock the class row, validate teacher ownership, active student status,
and class enrollment, and release expired slots. Revoking an assignment changes
only the class-level permission; the student's account is unchanged.

`attendance_qr_tokens` stores signed-QR metadata. Its unique nullable
`active_session_id` permits at most one current token per session. Issuance
locks the attendance-session row, rechecks authorization and session timing,
invalidates the prior token, and signs the new payload with a key derived from
`APP_KEY`. Ordinary display requests return the current token; automatic
rotation and manual replacement are separate operations. Manual replacements
have a separate quota. The original teacher QR/code remains available until a
signed QR takes over; it cannot become valid again during that session.

The existing scan routes accept both legacy and signed QR values. Signed scans
check the HMAC, expiry, invalidation, session, enrollment, device binding,
geofence, and duplicate attendance. The final attendance write locks the
session and rechecks the token so replacement cannot race a scan. The teacher's
captured laptop coordinates remain the geofence center; this feature does not
substitute a school address or assistant location.

## Operating flow

1. On the existing teacher class page, assign up to two enrolled students with
   start and end dates. They continue signing in as students.
2. The teacher starts the existing attendance session with the laptop's current
   location. Assistants can open that class only during its active window.
3. Either assistant displays the same active signed QR. The browser rotates an
   expired QR automatically, while the server decides validity. Manual
   replacement immediately invalidates the prior QR.
4. An enrolled student scans using the existing QR attendance flow. The teacher
   sees live attendance and QR changes. Only the teacher can extend, close, or
   generate an emergency QR.
5. Session closure invalidates the current signed QR. Session, QR, and scan
   activity appears in the teacher-only Session Activity panel. Significant
   scan anomalies queue an in-app teacher alert.

## Deployment

Run the normal Laravel migrations. Keep `APP_KEY` stable after issuing QR
tokens. A working queue worker is needed to deliver in-app teacher
notifications; attendance recording does not wait for those jobs. Configure and
run the application's existing Reverb/Pusher setup for immediate updates.
Without WebSockets, the assistant QR screen still polls current server state.

Optional `.env` settings (defaults shown):

```text
ATTENDANCE_START_GRACE_MINUTES=10
QR_TOKEN_TTL=60
QR_MAX_MANUAL_REGENERATIONS=3
```

The two-assistant maximum is intentionally fixed in the slot allocator rather
than exposed as an environment option that could disagree with the database
constraint.

No secrets are exposed in event payloads or audit properties. The QR value
itself is a short-lived bearer credential: serve the app over HTTPS and avoid
logging QR URLs at the web-server or proxy layer where possible.

## Verification status

Phases 0–6: architecture audit, assignment, authorization, session integration,
signed QR issuance/rotation, and scan integration are implemented and covered
by focused feature tests. Phases 7–9: live events, reconnect/state refresh,
audit timeline, and queued in-app notifications are implemented; real-device
WebSocket and queue-worker delivery still require deployment testing. Phase 10:
route, Blade, PHP, clean SQLite migration, and focused regression checks pass.
Phase 11 remains partly operational: migration against a copy of the deployed
schema, true parallel multi-process QR generation, Android/laptop geolocation,
and live Reverb interruption/recovery must be checked in the target environment.

The full project suite currently has eight unrelated existing failures in
biometric-login view assertions and offline-sync tests. They should be resolved
before claiming a fully green release.
