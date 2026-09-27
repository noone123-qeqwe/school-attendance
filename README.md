# Smart Classroom Attendance System

Laravel 12 attendance application for school administrators, teachers, students, and parents. The web application supports class schedules, QR attendance, location and device checks, offline teacher capture, absence warnings, reports, and a PWA. Android and desktop clients live under `apps/`.

## Local setup

Requires PHP 8.2 with the extensions listed in `Dockerfile`, Composer 2, Node.js, and a supported database. Copy `.env.example` to `.env`, configure the database, then run:

```sh
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan serve
```

For background work, run `php artisan queue:work` and `php artisan schedule:work` in separate terminals. Never use a temporary `APP_KEY` for a persistent installation. Configure the same stable key for every instance and restart.

## Attendance rule

The current data model stores **one attendance result per student, subject, and local calendar date**. A QR session identifies the capture window and verification evidence; it does not create a second attendance result for a repeat meeting of that subject on the same date. The unique database index and `Attendance::updateOrCreateRecord()` enforce this rule. Confirm a policy change with the school before changing the index, reports, auto-absence logic, and tests together.

The scheduler closes expired QR sessions each minute and checks scheduled classes for absences every 30 minutes. It runs chronic absence checks at 19:00, disciplinary warning processing at 19:15, and overall attendance-rate checks at 20:00 in the configured timezone. See `routes/console.php` for the current schedule.

## Checks

```sh
php artisan test
php scripts/check-release-manifest.php
npm ci && npm run build
```

CI runs the test suite against SQLite and MySQL. See `.github/workflows/ci.yml`. Release metadata and the production Docker image can be checked with the commands above and `docker build --tag attendance-check .`.

## Deployment and recovery

`render.yaml` and `Dockerfile` describe the current Docker deployment. `render-blueprint.yaml` is an older alternative with different database and scheduler settings; do not mix the two configurations. Set `APP_KEY`, database credentials, and a **new** `MAIL_PASSWORD` in the hosting environment. The committed mail credential was removed; it must be revoked at the mail provider because it remains in earlier Git history. Keep all secrets out of commits and build arguments.

The container entrypoint runs migrations and starts the web server, queue worker, and scheduler. After deployment, verify `/up`, log in as each role, record and reconcile a sample attendance session, and check queue failures and scheduler logs. Review `docs/web-release-process.md` for web and Android version handling.

The scheduler runs `app:backup-database` daily with 14-day pruning. Store a copy outside the application host and periodically restore it into an isolated test database. A backup is only proven usable after a restore and a sample attendance/report check. Preserve the stable `APP_KEY` along with the recovery procedure; encrypted data may depend on it.
