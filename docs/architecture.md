# Architecture

## Current foundation

Laravel 13 / PHP 8.4 modular monolith, Blade server rendering, plain CSS and a small progressive
JavaScript enhancement. Vite 8 builds versioned assets. No SPA, chat or artificial marketplace data.
Public routes: /, /ro, /en. Localization uses lang/ro/ui.php and lang/en/ui.php.
Brand text comes from APP_NAME. The symbol and illustration are original SVG assets.

The source was initialized from the official laravel/laravel 13.x skeleton in an empty repository.
Scaffold-specific automation and host-install instructions were replaced with this project's
Docker workflow. Upstream framework files retain their Laravel license.

## Service boundaries

Browser → Nginx → PHP-FPM / Laravel → PostgreSQL, Redis and S3-compatible filesystem.
Queue and scheduler run the same application image. PostgreSQL stores durable business data.
Redis stores encrypted sessions, cache and queues, with append-only persistence and noeviction.
Mailpit captures development email. Heavy work belongs in jobs; even the smoke email is sent by
a real Redis worker, not by an HTTP request.

Named volumes retain database, Redis, storage, email and dependency data. Source is bind-mounted
for Windows editing. vendor and node_modules stay on Linux volumes for speed.
Application storage/framework and logs are writable by www-data. User uploads will go to S3,
not these framework directories. The current data model contains only the Laravel foundation
tables: users, password resets, sessions, cache locks, jobs, job batches and failed jobs.
There are no shared accounts or automatic seeders.

## Startup and health

Compose uses healthy dependencies and successful one-shot setup gates:
PostgreSQL/Redis/MinIO → storage-init → setup → app → queue/scheduler/web.
Assets build successfully before Nginx starts.
[Compose documents these dependency conditions](https://docs.docker.com/compose/how-tos/startup-order/).

- /up: framework boot/liveness.
- /health/ready: actual PostgreSQL query, Redis write/read/delete, S3 HeadBucket.
  Returns only application identity and ok/unavailable; no credentials, hosts or exceptions.
  It intentionally has no web/session middleware so Redis failure can return 503.
- app:ready: operator CLI prints individual dependency states.
- app:smoke: local-only transactional database write/rollback; S3 write/read/delete;
  queued email through Redis to Mailpit; waits up to 25 seconds for job confirmation.
- scheduler:heartbeat: Redis timestamp, updated by the scheduler every minute, TTL 180 seconds.
- Windows startup additionally checks the HTTP application identity at APP_URL.
  A healthy WSL container alone does not prove that Windows localhost reaches the same app.

Ordinary start/stop never resets data, rotates an existing key, seeds listings or deletes volumes.
Test database reset is guarded by tests/TestCase.php and restricted to olxv2_testing.

## Security foundations

Laravel CSRF/session infrastructure remains intact. All mutations in later milestones need policies
and request validation. No credentials or personal contact details are rendered by the shell.
Security headers include CSP, frame restrictions, MIME sniff protection, referrer and permissions
policies. Local Vite allowances only apply in APP_ENV=local.
No third-party fonts, tracking or scripts are requested. The development shell is noindex and
robots disallows crawling; revisit during the SEO milestone before launch.

## Storage decision and dependency caveat

The local emulator is MinIO, using the official Quay registry and a digest-pinned release because
Docker Hub's MinIO image was unavailable during setup. [The upstream repository is archived and
community distribution is source-only](https://github.com/minio/minio).
This is **local development infrastructure**, not a production storage recommendation.
Before public deployment, configure a maintained S3 provider (R2 or another compatible provider)
with least-privilege application keys and appropriate access/lifecycle policies.
The legacy local image must not be exposed publicly. Track upstream security and replace the local
emulator if needed; the Flysystem S3 boundary keeps application code portable.
Legal evidence and original uploads remain private; any public image variants need a distinct policy.

## Performance and future modules

No business records are loaded by the M1 shell. The illustration is a small SVG; assets are local
and hashed. Session/cache use Redis. M1 measures only infrastructure and shell behavior.
No scalability claims are supported yet.

Future application services should live behind clear domain boundaries (Listings, Search, Contact,
Trust, Moderation) inside this monolith. ListingSearchService will isolate PostgreSQL search.
Design indexes with real query plans and bounded pagination when those schemas arrive.
Views/contact events need batched counters rather than updating one listing row on each view.
Image variants, lifecycle jobs, moderation retention and account deletion are later milestones.

## Sources

Runtime decisions follow [Laravel's supported release requirements](https://laravel.com/docs/13.x/releases).
Composer and npm exact versions are in their lockfiles. Container release tags are bounded;
MinIO additionally pins the image digest. Refresh dependencies through reviewed updates and rerun
the complete suite, rather than resolving new packages on ordinary startup.
