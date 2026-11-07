# Safe Render deployment notes

## Before deploying

1. Revoke any GitHub personal access token pasted into chat or another untrusted place. Do not add replacement tokens to source files, shell history, logs, or deployment manifests.
2. Review the Render service's existing environment values in the dashboard. The Blueprint now marks SMTP login/from addresses and Semaphore credentials as manually configured values instead of embedding an address in `render.yaml`.
3. Keep `SMS_ENABLED=false` unless the school has configured and tested its Semaphore account. To enable absence SMS later, configure `SEMAPHORE_API_KEY`, optionally set `SEMAPHORE_SENDER_NAME`, set `SMS_ENABLED=true`, then enable SMS notifications in the admin settings.
4. Confirm production `APP_DEBUG=false`, `APP_KEY`, database, SMTP/Brevo, Reverb, VAPID, Cloudinary, and other provider settings are present in Render. Rotate the providers listed in [the credential rotation checklist](security-credential-rotation.md).

## Deploy

Push the reviewed commit to the configured branch and let the existing Render Docker service build it. The Dockerfile runs `npm run build`; the Vite build plugin reads `version.json` and synchronizes `public/sw.js` application and cache versions. The worker keeps its CacheStorage cleanup scoped to this application's static/runtime caches; offline attendance records remain in IndexedDB.

This change adds no schema migration. Three historical data-provisioning migrations have been replaced with no-op migrations to prevent future installations from importing embedded account data. Existing migration records and any accounts already created by earlier deployments are not reversed; review those accounts separately. The current container entrypoint may run migrations on startup (`RUN_MIGRATIONS` is enabled for the Render web service). Feature flags and face verification policy use the existing `settings` table.

## Data durability and database checks

The Render cron service runs the scheduled database backup command, but Render cron jobs cannot attach persistent disks. Backup files created by that service are temporary, and its backup log rows can point to files unavailable from the web service. Do not rely on these files as recoverable backups. Configure backups to a durable external object store and test a restore, or rely on a separately verified managed PostgreSQL backup plan, before treating production data as protected.

The Blueprint uses Render PostgreSQL. The repository test suite covers SQLite and MySQL paths; it does not establish PostgreSQL migration or backup compatibility. Validate migrations, attendance workflows, and a restore against a disposable PostgreSQL staging database before production use.

After Render reports the deployment healthy, verify `/up`, administrator settings, one teacher QR session, one student attendance flow using the enabled method, and the PWA update prompt on an installed test device. Keep SMS disabled during verification unless the provider has been explicitly configured.

## Rollback

Redeploy the previous healthy Render commit/image. No database rollback is required for these changes. The prior service worker continues to use its prior application cache names, and the new worker removes only old caches named with the attendance static/runtime prefixes; IndexedDB attendance queues are not deleted.
