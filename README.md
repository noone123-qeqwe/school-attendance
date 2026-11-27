# Smart Classroom Attendance System

Laravel 12 attendance application for school administrators, teachers, students, and parents. The web application supports class schedules, QR attendance, location and device checks, offline teacher capture, absence warnings, reports, and a PWA. Android and desktop clients live under `apps/`.

## Local setup

Requires PHP 8.2 with the extensions listed in `Dockerfile`, Composer 2, Node.js, and a supported database. Configure `.env` with your database and environment settings, then run:

```sh
composer install
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan serve
```

For background work, run `php artisan queue:work` and `php artisan schedule:work` in separate terminals. Never use a temporary `APP_KEY` for a persistent installation. Configure the same stable key for every instance and restart.

For automatic face capture outside Docker, copy `vision_wasm*` from `node_modules/@mediapipe/tasks-vision/wasm/` to `public/face-guidance/v1/`. Put `blaze-face-short-range.tflite` in the same directory using the model URL and SHA-256 pinned in `Dockerfile`.

## Attendance rule

The current data model stores **one attendance result per student, subject, and local calendar date**. A QR session identifies the capture window and verification evidence; it does not create a second attendance result for a repeat meeting of that subject on the same date. The unique database index and `Attendance::updateOrCreateRecord()` enforce this rule. Confirm a policy change with the school before changing the index, reports, and auto-absence logic together.

The scheduler closes expired QR sessions each minute and checks scheduled classes for absences every 30 minutes. It runs chronic absence checks at 19:00, disciplinary warning processing at 19:15, and overall attendance-rate checks at 20:00 in the configured timezone. See `routes/console.php` for the current schedule.

## Build validation

```sh
php scripts/check-release-manifest.php
npm ci && npm run build
```

CI installs production PHP dependencies, builds the web assets, and checks release metadata. It no longer runs an automated test suite. See `.github/workflows/ci.yml`. The production Docker image can be checked with `docker build --tag attendance-check .`.

## Deployment and recovery

`render.yaml` and `Dockerfile` describe the current Docker deployment. `render-blueprint.yaml` is an older alternative with different database and scheduler settings; do not mix the two configurations. Set a stable Laravel `APP_KEY` (for example, from `php artisan key:generate --show`) and database credentials in the hosting environment. For mail delivery on Render Free, configure an HTTP provider key such as `BREVO_API_KEY`; Render Free blocks outbound SMTP ports 25, 465, and 587. The committed mail credential was removed; it must be revoked at the mail provider because it remains in earlier Git history. Keep all secrets out of commits and build arguments.

See the [Render deployment notes](docs/deployment-hardening.md) for environment checks, backup and restore, offline attendance recovery, and rollback.

The container defaults to a single-instance `all` process role. After deployment, verify `/up`, log in as each role, record and reconcile a sample attendance session, and check queue failures and scheduler logs. See the [web and Android release guide](docs/web-release-process.md) for version handling and update policies.

The scheduler runs `app:backup-database` daily with 14-day pruning. Store a copy outside the application host and periodically restore it into an isolated test database. A backup is only proven usable after a restore and a sample attendance/report check. Preserve the stable `APP_KEY` along with the recovery procedure; encrypted data may depend on it.

### QR live face verification

QR camera attendance uses a guided capture and server verification against an encrypted camera enrollment. It is disabled by default and requires documented consent when enabled. See the [face verification limits and privacy review](docs/biometric-verification-limitations.md) for enrollment, model checks, and rollout requirements. Classmate check-in has a separate [Peer-Snap FaceMatch architecture and rollout guide](docs/peer-snap-facematch.md).

For local development outside Docker, install Python 3 with OpenCV and set `FACE_MATCH_PYTHON`, `FACE_MATCH_DETECTOR_MODEL`, and `FACE_MATCH_RECOGNIZER_MODEL` to the executable and model paths. Guided attendance also needs the classifier at `FACE_ATTENDANCE_PAD_MODEL`. Docker provides these dependencies. The default `FACE_MATCH_THRESHOLD` of `0.363` is an OpenCV benchmark reference. Validate false acceptance and rejection rates under representative camera conditions before choosing a deployment threshold.

### Cache and CDN

The Render blueprint uses the database cache store so all application processes share cache entries and scheduler locks. For higher traffic, a shared Redis service can be enabled with `CACHE_STORE=redis` and `REDIS_URL`; keep a distinct `CACHE_PREFIX` when environments share one backend. Set `ASSET_URL` to an HTTPS CDN origin to serve Laravel and Vite assets from that host. The CDN should honor origin cache and CORS headers: only content-hashed `/build/assets/` files are immutable for a year, while mutable static files expire quickly. Bypass shared caching for local `/storage/`, `/private-storage/`, `/api/`, HTML pages, `/sw.js`, and `/manifest.json` so user uploads and app updates are not served stale or shared between users. For horizontal scaling, configure process roles and a shared public file provider as described in [docs/horizontal-scaling.md](docs/horizontal-scaling.md).

### Face sign-in and enrollment

Face ID and Windows Hello sign-in use a platform WebAuthn passkey. For this method, the server verifies a signed challenge using the registered credential's public key. The device may offer its PIN as an alternative user-verification method, so the application cannot determine which local method completed the prompt. Users can register a device passkey under Settings.
