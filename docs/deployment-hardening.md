# Safe Render deployment notes

## Before deploying

1. Revoke any GitHub personal access token pasted into chat or another untrusted place. Do not add replacement tokens to source files, shell history, logs, or deployment manifests.
2. Review the Render service's existing environment values in the dashboard. The Blueprint now marks SMTP login/from addresses and Semaphore credentials as manually configured values instead of embedding an address in `render.yaml`.
3. Keep `SMS_ENABLED=false` unless the school has configured and tested its Semaphore account. To enable absence SMS later, configure `SEMAPHORE_API_KEY`, optionally set `SEMAPHORE_SENDER_NAME`, set `SMS_ENABLED=true`, then enable SMS notifications in the admin settings.
4. Confirm production `APP_DEBUG=false`, `APP_KEY`, database, SMTP/Brevo, Reverb, VAPID, Cloudinary, and other provider settings are present in Render. Rotate the providers listed in [the credential rotation checklist](security-credential-rotation.md).
5. Keep `CLIENT_IP_HEADER=CF-Connecting-IP` on Render when using the administrator IP allowlist. The middleware trusts this only because Render's Cloudflare edge overwrites it; for another host, configure only a header that the trusted edge overwrites, and do not use caller-controlled `X-Forwarded-For` as the allowlist identity.

## Deploy

Push the reviewed commit to the configured branch and let the existing Render Docker service build it. The Dockerfile runs `npm run build`; the Vite build plugin reads `version.json` and synchronizes `public/sw.js` application and cache versions. The worker keeps its CacheStorage cleanup scoped to this application's static/runtime caches; offline attendance records remain in IndexedDB.

This change adds no schema migration. Three historical data-provisioning migrations have been replaced with no-op migrations to prevent future installations from importing embedded account data. Existing migration records and any accounts already created by earlier deployments are not reversed; review those accounts separately. The current container entrypoint may run migrations on startup (`RUN_MIGRATIONS` is enabled for the Render web service). Feature flags and face verification policy use the existing `settings` table.

## Data durability and database checks

The Render cron service runs the scheduled database backup command, but Render cron jobs cannot attach persistent disks. Backup files created by that service are temporary, and its backup log rows can point to files unavailable from the web service. Do not rely on these files as recoverable backups. Configure backups to a durable external object store and test a restore, or rely on a separately verified managed PostgreSQL backup plan, before treating production data as protected.

The Blueprint uses Render PostgreSQL. The repository test suite covers SQLite and MySQL paths; it does not establish PostgreSQL migration or backup compatibility. Validate migrations, attendance workflows, and a restore against a disposable PostgreSQL staging database before production use.

## Offline attendance and queue recovery

Pending attendance stays in the teacher's browser IndexedDB until the server acknowledges it. The PWA now resumes sync when reopened online, retries transient failures with capped exponential backoff, and keeps permanent validation or enrollment failures on-device for manual review. The browser is asked to preserve this site's storage where supported, but that request can be denied; unsynced data still exists only on that device and cannot be recovered if the device or browser storage is lost.

The Render queue worker uses a 90-second job timeout and a 120-second database queue visibility timeout. Keep `DB_QUEUE_RETRY_AFTER` at least several seconds above `QUEUE_TIMEOUT`; the container refuses to start the worker with an unsafe pair. For another queue backend, configure its visibility or retry timeout to satisfy the same relationship.

After Render reports the deployment healthy, verify `/up`, administrator settings, one teacher QR session, one student attendance flow using the enabled method, and the PWA update prompt on an installed test device. Keep SMS disabled during verification unless the provider has been explicitly configured.

## Rollback

Redeploy the previous healthy Render commit/image. No database rollback is required for these changes. The prior service worker continues to use its prior application cache names, and the new worker removes only old caches named with the attendance static/runtime prefixes; IndexedDB attendance queues are not deleted.
