# Update announcements

Routine deployments and cache refreshes are quiet. Fresh server-rendered pages
and versioned assets load during normal navigation or the next app visit. Open
pages are never automatically reloaded by a deployment or a worker activation.

Super administrators can use **System Maintenance → Publish a release**:

- **Quiet** (default): advance the release without a popup or device push.
- **Announcement**: show an optional, nonblocking banner once per published
  version. Later dismisses that version across navigations and future builds.
- **Critical**: require an explicit update when the app is safe to refresh.
  This also establishes a minimum supported version, preserved by later quiet
  releases so clients that missed the critical release still receive the prompt.

The release choice is recorded in `version.json`:

```json
"update": {
    "version": "9.1.6",
    "mode": "silent",
    "minimum_supported_version": null
}
```

Keep the policy version equal to the release being announced. A missing, invalid
or stale announcement defaults to quiet. Code revisions and cache identifiers
still detect fresh assets for a manual **Settings → Check for updates**, but do
not reset announcement dismissal. A minimum supported version must be a valid
numeric release at or below the deployed version.

For a durable Render deployment, commit the intended release metadata alongside
the release. Changes published through the admin screen affect the running
installation's filesystem; redeployments on ephemeral storage replace them with
the committed manifest. Automated version bumps are quiet unless the release's
policy is explicitly updated to match the new version.

Active cameras, live attendance work, changed POST forms, and queued offline
attendance block an explicit refresh. Save changes and navigate normally before
updating. Failed/offline version checks do not claim an update or a successful
installation. An installation is confirmed only after the intended document
actually loads.
