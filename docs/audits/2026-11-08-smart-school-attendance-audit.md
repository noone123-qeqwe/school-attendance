# Smart School Attendance System Audit

**Audit date:** 2026-11-08
**Repository:** `school_attendance`
**Scope:** Role-based workflows, attendance integrity, notifications, schedule management, authorization, responsive page coverage, and focused code quality checks.

## Executive summary

The audit found and fixed defects in absence notifications, schedule integrity, teacher data scoping, parent alert visibility, and inline script execution under Content Security Policy. The QR attendance flow has an end-to-end regression test that follows one attendance record through student, teacher, parent, and administrator views. All automated application tests pass.

This is a source and automated-test audit. It does not certify production readiness: every UI control was not manually clicked at each viewport, production database migrations and backup restoration were not exercised, and real camera, biometric verifier, mail, and push-service behavior was not tested against live services.

## Verification results

| Check | Result |
| --- | --- |
| Laravel feature and unit suite (`php artisan test`) | **906 passed, 4,726 assertions**, 343.17 seconds |
| JavaScript tests (`node --test tests/js/*.test.cjs`) | **14 passed, 0 failed** |
| PHP syntax on changed application files | **Passed** |
| PHPStan level 5 on the changed schedule controller, lock and roster services, subject requests, attendance and notification models | **No errors**; the report service and other existing controllers retain type findings |
| PHPStan level 5 across the full project | **558 findings remain**; full-project analysis is not clean (down from 574 after relation typing) |
| `git diff --check` | **Passed** |
| Role page render smoke coverage from the continuation run | **219 pages** across student, teacher, parent, admin, data-entry, and auditor role contexts; notification polling is now a shared authenticated route with separate role tests |
| Route inventory from the audit run | **403 registered Laravel routes** |

The automated suite runs with `APP_ENV=testing` and an in-memory SQLite database. It does not replace a production-like database migration, restore, or concurrency test.

## Fixes made

| Priority | Finding | Change and regression coverage |
| --- | --- | --- |
| High | Class schedule changes could leave stale attendance schedule entries; deleting one class could remove a slot still shared by another assignment. The mirror key also needed to include the end time. | Class schedule create/update/delete and attendance schedule synchronization now run in a database transaction. Updates clean up the exact old slot only when it is no longer assigned; deletes preserve shared slots. The new schedule tests cover rescheduling, deletion, shared slots, and full-slot matching. |
| High | Overlap checks did not prevent two schedules in the same section, while the UI and controller allowed an unassigned teacher even though class schedule storage requires one. | Same-section overlap validation is enforced alongside teacher and room conflicts. Teacher selection is required and validates that the selected user has the teacher role. Adjacent, non-overlapping times remain valid. |
| Medium | Newly created `Absent` rows did not reliably trigger the model’s absence alert flow, so linked parents could miss an alert. The command also wrote a second student notification directly. | The attendance model now handles newly created absent rows and status transitions. The command no longer duplicates the student notification write. A regression test verifies one student alert and one linked parent alert. |
| Medium | A QR attendance scan needed cross-role consistency verification beyond the scan response. | Added an end-to-end test that confirms the resulting `Present` record is visible to the student workflow, teacher live monitor, linked parent dashboard, and admin attendance records. |
| Low | Schedule model relation types were missing or too loose for focused static analysis. | Added typed relation return declarations and corrected the class schedule controller helper PHPDoc. The changed application files now pass the focused PHPStan run. |

## Continuation findings and verification

| Priority | Confirmed root cause | Action taken | Evidence |
| --- | --- | --- | --- |
| Critical | `AttendanceReportService` treated an empty teacher subject list as unrestricted; the direct attendance CSV export did the same. A teacher with no assigned subjects could obtain school-wide records. The percentage report also listed unrelated students. | An empty non-null scope now yields no attendance or students. Direct CSV always filters by assigned subject codes and rejects a requested foreign subject. Percentage and roster outputs use the subject's explicit and profile-based roster. | `TeacherDataScopeTest`: 4 passed, including daily/summary/percentage/CSV and cross-section isolation; full suite passed. |
| High | Class schedule create/update overwrote `subjects.instructor_id`, transferring all teacher routes and QR ownership when a second teacher was assigned to the same subject code. Subject edits could also delete class schedule mirror rows. | Enforced one instructor and matching course/year/section per subject; kept admin-managed schedule rows when subject details are edited; blocked subject code changes with attendance. Schedule writes and subject edits acquire a shared database row lock before validation and mutation. | `ClassScheduleConflictTest`: 12 passed; full suite passed. PostgreSQL concurrency has not been exercised locally. |
| High | Parent absence alerts were addressed to the parent, but the parent list and poll read only child recipient IDs. The shared poll route was limited to students. | Parent views and polling include parent-owned alerts; the poll route now supports authenticated roles, respects admin IP rules and requires verified admin 2FA. New parent alerts carry a related student ID for safe child filtering. | `ParentNotificationAccessTest`: 2 passed; `AttendanceLifecycleTest`: 6 passed; full suite passed. Existing older alerts are not retroactively linked to a student. |
| Medium | The CSP contains a script nonce, which blocks inline `<script>` blocks lacking that nonce in modern browsers. Many role views had affected inline blocks. | Added the existing `@cspNonce` directive to inline blocks across role views without changing colors; updated parent alert and subject edit controls where needed. | Source scan found **zero** inline script openings without a nonce; `CspInlineScriptTest` rendered admin, teacher, student, and parent pages and passed with 60 assertions; full suite passed. |
| Medium | Subject creation and editing accepted any user ID as an instructor. | Both subject request validators now require a user with the teacher role. | `ClassScheduleConflictTest` includes an invalid-instructor regression; full suite passed. |
| High | A linked guardian marking a child's notification read changed the shared notification row, so every other guardian lost the unread state. | Added a per-guardian `parent_notification_reads` table. Parent list, poll, and badge counts now use the signed-in guardian's read state, while the child's notification remains unread. | `ParentNotificationAccessTest` verifies two linked guardians see independent read state; parent notification and attendance lifecycle tests: 8 passed, 42 assertions. |

### Feature verification matrix

| User role | Module / feature | Status | Issue found | Action taken | Test result |
| --- | --- | --- | --- | --- | --- |
| Admin | Dashboard and attendance records | Verified working in automated tests | No new defect in exercised paths | Cross-role QR check retained | Full suite passed |
| Admin | User and subject management | Partially verified | Non-teacher account could be assigned as instructor | Role validation added | Targeted and full suite passed; every CRUD control not manually clicked |
| Admin | Class schedules and timetable | Fixed and retested | Ownership drift, mismatched cohort, stale mirror slots, overlap race | Owner/cohort checks and row-locked writes | 12 schedule tests passed; production concurrency untested |
| Admin | Reports and analytics | Verified in automated tests | No new defect in exercised paths | None in this continuation | Full suite passed; production data accuracy not sampled |
| Admin | Backups and system update | Partially verified | Durable restore remains an operational gap | None in this continuation | Local feature tests passed; staging restore not tested |
| Teacher | Dashboard, QR sessions, and class ownership | Verified in automated tests | Subject ownership could drift after admin scheduling | One-owner invariant added | QR/ownership suites passed |
| Teacher | Student roster, PDF preview, and CSV | Fixed and retested | List omitted explicit enrollees and exposed other sections | Shared roster scope applied | Teacher data scope test passed |
| Teacher | Attendance reports and exports | Fixed and retested | Empty subject scope exposed all records | Empty scope restricted; foreign code rejected | Teacher data scope test passed |
| Teacher | Subject editing | Fixed and retested | Could erase admin class schedule mirror | Preserve mirrored rows and lock code/cohort | Schedule test and full suite passed |
| Teacher | Live notifications | Fixed and retested | Student-only poll route returned forbidden | Shared authenticated poll with admin checks | Parent notification access test passed |
| Student | QR scanning and attendance history | Verified in automated tests | No new defect in exercised paths | Cross-role regression retained | Full suite passed |
| Student | Profile, biometrics, and device binding | Verified in automated tests | No new defect in exercised paths | None in this continuation | Full suite passed; real-device matching not tested |
| Student | Offline attendance recovery | Verified in JavaScript tests | No new defect in exercised paths | None in this continuation | 14 JavaScript tests passed in prior audit; no JS changed here |
| Parent | Linked-child attendance monitoring | Verified in automated tests | No new defect in exercised paths | Cross-role attendance regression retained | Full suite passed |
| Parent | Absence alerts, filters, and polling | Fixed and retested | Parent-owned alerts missing from list/poll | Include own records and relate new alerts to child | Parent access and attendance lifecycle tests passed |
| Parent | Child linking and profile | Partially verified | No new defect in exercised paths | None in this continuation | Automated tests passed; every control not manually clicked |
| All roles | Inline script interactions | Fixed and retested | CSP nonce blocked unnonced inline blocks | Added nonce directive across affected Blade views | Four rendered role pages and static scan passed |

The matrix describes executed evidence only. It does not claim a manual click-through of every route, button, dialog, or breakpoint. No feature was marked broken in the final test run; the unresolved items below remain partially verified or not tested.

## Role-based coverage

| Role | Verified in the automated audit | Coverage boundary |
| --- | --- | --- |
| Administrator | Role page render smoke coverage; schedule create/update/delete conflict cases; admin attendance record visibility after a student QR scan; system update and backup feature tests in the full suite. | A production backup restore and every admin CRUD control were not manually exercised against a production-like database. |
| Teacher / Instructor | Dashboard and attendance page tests; assigned-class ownership and QR session authorization; live clock-in monitor; attendance corrections and excuse review; student assistant authorization tests. | Live camera/projector operation and real-time provider behavior were not tested against deployed services. |
| Student | Dashboard and attendance history rendering; QR success, duplicate, invalid, expired, schedule and location rejection paths; device binding, biometric setup/login, profile photo quality and outage handling; offline queue JavaScript tests. | Real-device camera quality, unreliable mobile networks in the field, and biometric spoof resistance need physical-device testing. |
| Parent / Guardian | Child linking and access boundary tests; parent dashboard attendance visibility; parent absence-alert delivery in the model/queue test. | External email/web-push delivery and production account recovery were not exercised against live providers. |

Role page smoke tests establish that representative routes render for the tested role contexts; they do not prove that every visible button, modal, table action, or responsive state was manually operated.

## Security, reliability, and data-integrity notes

- The full automated suite includes authorization, security-header, device-binding, biometric/WebAuthn, QR-session, parent-child isolation, offline-sync, and upload validation coverage. These checks are useful evidence, not a penetration test.
- Class schedule writes now acquire a singleton row lock inside the transaction before validation, which serializes cooperating admin edits on PostgreSQL and MySQL. The schema still has no range-overlap constraint, and a concurrent PostgreSQL run was not available locally. Verify locking under load against the production database engine.
- The full-project PHPStan run has **558** remaining findings, down from 574 after typing notification relations. The focused analysis still reports legacy findings in the large home and parent controllers; fix these in small groups, prioritizing authorization, attendance, and data-write paths.
- Earlier dependency checks in this audit found no reported Composer or production npm dependency vulnerabilities. `composer validate` did flag an unbounded `*` version constraint for `spatie/laravel-activitylog`; replace it with a supported bounded constraint and validate lockfile/update behavior.
- The deployment backup path on Render was identified as ephemeral in the deployment audit. Production backups should be copied to durable external storage and periodically restored in a staging environment.
- Face matching does not have independently verified presentation-attack/liveness detection. The Peer-Snap verifier is disabled by default; enabling it sends multiple image frames to an external verifier. Before enabling face verification for attendance, document retention and consent, review the verifier’s processing and data residency, and test spoof resistance with real devices and representative users.
- The GitHub personal access token was pasted into the conversation earlier. Revoke it and issue a replacement; do not store the replacement in source, logs, or chat.

## Recommended follow-up

1. Run the new migrations and backup/restore workflow against the same database engine used in production, with a disposable staging dataset.
2. Run two concurrent administrators creating conflicting schedules against PostgreSQL and confirm that the second request is rejected after the first commits.
3. Reduce the 558 full-project PHPStan findings, prioritizing authorization, attendance, and data-write paths.
4. Validate backup durability and perform a timed restore drill.
5. Test each role at desktop and mobile breakpoints in a browser, operating the actual forms, dialogs, navigation, and report actions; preserve the current color system.
6. Complete privacy and presentation-attack review before enabling external face verification, then run real-device registration and matching trials.
7. If different teachers must teach different sections of the same subject code, redesign QR sessions, rosters, attendance keys, and teacher authorization around a section-specific class identity. The current safe invariant is one teacher per subject code.
8. Backfill older parent alerts with a related-student field only where the child association can be established reliably; per-guardian read state is now implemented and regression-tested.

## Change set

The previous audit fixes are already on `origin/main`. This continuation includes per-guardian notification read state and changes the following files in the verified commit:

- Backend: `app/Http/Controllers/Admin/ClassScheduleController.php`, `AdminController.php`, `HomeController.php`, `ParentController.php`, `TeacherController.php`; `app/Http/Requests/StoreSubjectRequest.php`, `UpdateSubjectRequest.php`; `app/Models/Attendance.php`, `Notification.php`, `ParentNotificationRead.php`; `app/Services/AttendanceReportService.php`, `ClassScheduleLock.php`, `SubjectRosterService.php`; `routes/web.php`.
- Database: `database/migrations/2026_11_08_000001_create_class_schedule_locks_table.php`, `2026_11_08_000002_add_related_student_to_notifications_table.php`, `2026_11_09_000001_create_parent_notification_reads_table.php`.
- Tests: `tests/Feature/AttendanceLifecycleTest.php`, `ClassScheduleConflictTest.php`, `CspInlineScriptTest.php`, `ParentNotificationAccessTest.php`, `TeacherDataScopeTest.php`.
- Views: `resources/views/admin/{absent,activity_logs,calendar,dashboard,gps-config,location-finder,profile,settings,student}.blade.php`; admin subdirectories `academic_years`, `attendance`, `backups`, `corrections`, `courses`, `departments`, `excuses`, `roles`, `sections`, `students`, `subjects`, `teachers`; `resources/views/attendance/records.blade.php`, `auth/forgot-password.blade.php`, `components/modal.blade.php`, `excuses/{create,create_general}.blade.php`, `instructor/calendar.blade.php`, `layouts/app.blade.php`, `layouts/mbn/parent.blade.php`, `mobile/history.blade.php`, `offline.blade.php`, `parent/{attendance-calendar,dashboard,excuse-form-general,excuse-form,link-child,notifications}.blade.php`, `partials/scripts/{admin,teacher}.blade.php`, `qr/verify.blade.php`, `student/profile.blade.php`, `teacher/{absent-report,attendance,calendar,excuse-reviews,student-detail}.blade.php`, and teacher subdirectories `classroom`, `my_excuses`, `notifications`, `students`, `subjects`.

Existing local edits to `apps/android_app/app/build.gradle` and `version.json` were preserved and are excluded from this audit commit.
