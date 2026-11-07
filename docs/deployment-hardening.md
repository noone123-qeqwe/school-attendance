# Safe Render deployment notes

## Before deploying

1. Revoke any GitHub personal access token pasted into chat or another untrusted place. Do not add replacement tokens to source files, shell history, logs, or deployment manifests.
2. Review the Render service's existing environment values in the dashboard. The Blueprint now marks SMTP login/from addresses and Semaphore credentials as manually configured values instead of embedding an address in `render.yaml`.
3. Keep `SMS_ENABLED=false` unless the school has configured and tested its Semaphore account. To enable absence SMS later, configure `SEMAPHORE_API_KEY`, optionally set `SEMAPHORE_SENDER_NAME`, set `SMS_ENABLED=true`, then enable SMS notifications in the admin settings.
4. Confirm production `APP_DEBUG=false`, `APP_KEY`, database, SMTP/Brevo, Reverb, VAPID, Cloudinary, and other provider settings are present in Render. Rotate the providers listed in [the credential rotation checklist](security-credential-rotation.md).

## Deploy

Push the reviewed commit to the configured branch and let the existing Render Docker service build it. The Dockerfile runs `npm run build`; the Vite build plugin reads `version.json` and synchronizes `public/sw.js` application and cache versions. The worker keeps its CacheStorage cleanup scoped to this application's static/runtime caches; offline attendance records remain in IndexedDB.

This change adds no database migration. The current container entrypoint may run the repository's existing migrations on startup (`RUN_MIGRATIONS` defaults to true in the all-in-one role); no new schema work is needed for this release. Feature flags and face verification policy use the existing `settings` table.

After Render reports the deployment healthy, verify `/up`, administrator settings, one teacher QR session, one student attendance flow using the enabled method, and the PWA update prompt on an installed test device. Keep SMS disabled during verification unless the provider has been explicitly configured.

## Rollback

Redeploy the previous healthy Render commit/image. No database rollback is required for these changes. The prior service worker continues to use its prior application cache names, and the new worker removes only old caches named with the attendance static/runtime prefixes; IndexedDB attendance queues are not deleted.
