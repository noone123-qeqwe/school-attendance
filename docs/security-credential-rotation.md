# Credential rotation checklist

## Audit findings

The current working tree ignores local `.env*` files, and `.env.example` was also excluded by the ignore rule. The example file has been restored as a tracked, placeholder-only template. The local `.env` contains duplicate Reverb definitions; Laravel and Vite resolve the later definitions, which were retained. The application configuration reads the Reverb app ID, key, secret, host, port, and scheme from those environment variables, and the frontend uses the corresponding `VITE_REVERB_*` values.

A read-only history scan found credential-shaped values in committed environment files, including `APP_KEY`, `DB_PASSWORD`, `MAIL_PASSWORD`, `REVERB_APP_SECRET`, and `SEMAPHORE_API_KEY`. Values were not copied into this report. Confirm whether any deployed credentials match those historical values and rotate matching credentials. Ordinary commits do not remove values from old Git commits.

Tracked student and teacher roster CSVs also contained personal account data and shared starter passwords. They have been removed from the current source tree and preserved only in the ignored `storage/app/private-imports/` directory for local seeding. Historical account-provisioning migrations are now no-ops. The earlier CSVs and migration contents remain in Git history; this change does not rewrite shared history or restore any account records those migrations may already have changed.

## Rotate in the provider before any history cleanup

- **Laravel application key (`APP_KEY`)**: inspect whether it protects persisted encrypted values. Plan the rotation because it invalidates encrypted cookies and may make data encrypted with the old key unreadable.
- **Database (`DB_PASSWORD`)**: rotate the TiDB Cloud or other database user password, then update the deployment secret and test connectivity.
- **Email**: rotate Gmail or other SMTP app passwords, and rotate Brevo, Postmark, Resend, or other mail API keys that match historical values.
- **Semaphore SMS**: rotate the API key if it is valid or was ever enabled in a deployed environment.
- **Cloudinary**: rotate the API secret and update the provider URL if the historical value included account credentials.
- **Ngrok**: revoke and replace any matching authentication token.
- **Reverb**: rotate the app key and secret together, then update the server and `VITE_REVERB_*` deployment variables together.
- **Web Push**: rotate the VAPID private key and public key as a pair if exposed.
- **Other configured integrations**: check AWS, Pusher, Ably, Slack, peer-face verifier, and any other provider credentials in the deployment dashboard. Rotate any value that appears in history or local deployment files.
- **Roster accounts**: check whether the old account-sync migrations ran in production and review any accounts they may have created or updated. The application forces known starter-password accounts through password recovery, but the school should verify affected accounts and follow its privacy incident process if roster data was exposed.

Use provider-generated credentials and update the deployment secret store directly. Do not put replacement values in this repository, tickets, logs, or chat. Confirm production connections after each provider change. Keep SMS disabled until a valid Semaphore key and sender name are configured.

## Optional shared-history cleanup

Do not rewrite or force-push shared history as part of routine hardening. If the repository owner later approves history cleanup, first rotate affected credentials, notify collaborators, make a protected backup, and run a vetted history rewrite (for example, `git filter-repo`) in a separate clone. Remove the affected environment files or replace them with the safe template, scan every rewritten ref, coordinate a maintenance window, and obtain explicit approval before force-pushing. Require collaborators to re-clone or reset to the rewritten history. A history rewrite does not replace credential rotation.

## Deployment environment variables

`SMS_ENABLED` defaults to `false`. Set it to `true` only after configuring `SEMAPHORE_API_KEY` and `SEMAPHORE_SENDER_NAME` in the deployment secret store. Keep `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, and `REVERB_SCHEME` consistent with the matching `VITE_REVERB_*` client settings. The tracked `.env.example` contains safe local defaults and placeholders, not production values.
