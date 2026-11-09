# Web and Android release versions

The web/PWA and Android APK are separate deployable products. The web release is recorded in `version.json`, `package.json`, `package-lock.json`, `public/manifest.json`, and the service-worker cache version. The Android `versionName` and monotonic `versionCode` live in `apps/android_app/app/build.gradle`. A web-only release does not change the Android APK version, and a changed web version does not mean a new APK is available.

## Publish a web release

1. Push the finished update to `main`. The Laravel CI workflow checks the commit.
2. After CI succeeds, **Automatic Application Versioning** increments the patch version, updates the web package and service-worker cache metadata, builds the release assets, checks that the tested commit is still the tip of `main`, then commits and tags the release. A failed CI run does not create a release. A commit that already changes `version.json` is treated as an explicitly versioned release and is not bumped again.
3. The hosting service must deploy the generated release commit. The displayed version changes only after that deployment is live; a code push by itself does not prove deployment succeeded.
4. After deployment, verify `/pwa/version` reports the new `latest_version`, `installed_version`, `build`, and service-worker version. On a test browser, confirm the update prompt appears once for a genuinely newer loaded release, **Later** leaves a quiet pill, and **Update Now** loads new HTML/assets before reporting success.

Run `php scripts/check-release-manifest.php` to validate the checked-out web metadata locally. `php scripts/version-bump.php --dry-run` previews the next version without changing files.

For an Android release, build and verify an APK separately, increment `versionCode` and `versionName` appropriately, then publish the actual signed APK. Do not copy the web version into Android unless that APK was built and distributed.

## Update-state regression matrix

| State | Expected behavior |
| --- | --- |
| Browser already loaded the current release | No update prompt; displayed version matches loaded HTML. |
| New tested web release is deployed | One prompt for that release, with an optional quiet reminder pill after **Later**. |
| Server unavailable or version check fails | No false “up to date” claim or install attempt; retry on reconnect/focus. |
| Two tabs are open | Applying in one tab prompts the other to recheck; it must not claim success from a broadcast alone. |
| Service-worker activation or reload fails | No success toast until a new document is loaded; keep a quiet retry path. |
| Teacher has an active attendance session or assistant displays a live QR | Do not interrupt with an automatic modal or reload. |
| Student scanner is open | Do not reload until the scan is closed. |
| Teacher has pending or failed offline attendance records | Do not reload; explain that records must sync first. |
| Android APK version differs from web version | Show each product's own version; a web release must not imply a new APK. |

These checks should be repeated on desktop and Android mobile browsers after deployment. Server-side unit/feature tests cover metadata consistency and version response behavior; browser checks cover the service-worker lifecycle and interaction states.
