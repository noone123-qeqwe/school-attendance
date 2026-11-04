# Horizontal scaling

The hosting platform's service load balancer should route HTTP traffic to identical web replicas. Each replica runs the same image and connects to the same PostgreSQL database, cache/lock store, and public file store. Do not use per-instance file sessions or cache when more than one replica is active.

## Process roles

`APP_PROCESS_ROLE=all` remains the single-container default. For a scaled deployment, run the image as separate processes:

| Process | Setting | Purpose |
| --- | --- | --- |
| Web replicas | `APP_PROCESS_ROLE=web`, `RUN_MIGRATIONS=false` | PHP-FPM and Nginx only; scale this service behind the platform load balancer. |
| Release migration | `APP_PROCESS_ROLE=migrate` | Run once against the shared database before routing traffic to the new release. The process exits after migrations finish. |
| Queue workers | `APP_PROCESS_ROLE=worker`, `QUEUE_CONNECTION=database` (or another asynchronous driver) | Drain the shared queue. Add worker instances as queue depth grows. |
| Scheduler | `APP_PROCESS_ROLE=scheduler` | Run Laravel's scheduler separately. Scheduled events also use shared-cache locks so multiple scheduler instances cannot execute the same occurrence. |

The web role does not start scheduler or queue processes. The all-in-one role skips the queue worker when `QUEUE_CONNECTION=sync`, since synchronous jobs execute during the request. Database queue tables are included in the migrations. Keep `SESSION_DRIVER=database` and use the shared database cache or a shared Redis cache for cross-replica locks and cache entries.

PHP-FPM starts with a dynamic pool and conservative defaults. Tune capacity to the memory available to each container with `PHP_FPM_MAX_CHILDREN`, `PHP_FPM_START_SERVERS`, `PHP_FPM_MIN_SPARE_SERVERS`, `PHP_FPM_MAX_SPARE_SERVERS`, and `PHP_FPM_MAX_REQUESTS`. For example, an instance with more memory can raise the child limit; avoid multiplying the limit without checking resident memory under face-verification and report workloads. `PHP_FPM_PM` accepts `dynamic`, `ondemand`, or `static`.

## Shared uploads

The local `public` disk remains the default for development and single-instance deployments. Local container files are not shared between replicas. For horizontal scaling, configure the existing Cloudinary disk as the public disk by setting `PUBLIC_FILESYSTEM_DRIVER=cloudinary` and storing `CLOUDINARY_URL` as a deployment secret. New uploads then use Cloudinary, and profile, QR, and attachment links resolve through the selected disk. Existing local uploads must be copied to the shared provider before switching the setting; this change does not migrate files already on a running instance.

Keep `APP_KEY`, database settings, cache settings, queue settings, and file-store credentials consistent across all process roles. Keep uploaded user files out of the static asset CDN cache unless the file provider is configured to serve them with the intended access policy.
