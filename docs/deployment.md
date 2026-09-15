# Deployment preparation — not a production release

M1 is a local engineering foundation. The development Compose file is not a hardened
production deployment. Authentication, authorization flows and marketplace functionality
still need their scheduled milestones.

## Portable target

Ubuntu LTS, Nginx, PHP 8.4-FPM, PostgreSQL, Redis, queue workers under systemd/Supervisor,
Laravel Scheduler, maintained S3-compatible object storage, transactional email, TLS and
optional Cloudflare. A single VPS can host application/database/Redis initially.
Later, externalize PostgreSQL/Redis and add stateless app nodes without moving user uploads.

Deploy built assets and Composer dependencies from lockfiles. Disable APP_DEBUG,
set APP_ENV=production and APP_URL to the HTTPS origin, enable SESSION_SECURE_COOKIE,
retain the encryption key, configure trusted proxy addresses explicitly, and set restrictive
ownership. Serve only public/. Nginx must reject hidden files and arbitrary PHP paths.
Do not copy the permissive local Vite CSP allowances into production.

Database/Redis/storage management ports must remain private. The local MinIO root key and
Mailpit must not be used in production. Use a supported storage provider and restricted S3 keys.
Separate cache and critical queues at scale; noeviction avoids silently losing queues but requires
memory/backlog monitoring. Configure request/SMTP/storage timeouts and workers appropriately.

## Deployment outline (implement and rehearse in M23)

1. CI must pass on the deployment revision; produce immutable application and asset artifacts.
2. Take an off-server backup and confirm the restore point.
3. Install a release directory with vendor and built assets, then inject secrets outside Git.
4. Run safe forward migrations with --force, avoiding destructive schema changes.
5. Cache configuration/routes/views; switch the release symlink.
6. Gracefully restart queue workers and PHP-FPM; run readiness and functional smoke checks.
7. Monitor errors/backlogs; revert the application release if needed. Data rollback is a separate
   reviewed restore procedure, not an automatic migrate:rollback.

Run schedule:run once per minute in production, or a supervised scheduler process, not both.
Queue retry_after must exceed the worker timeout. Alert on stale scheduler/worker heartbeats,
failed jobs, database availability, HTTP error rates, storage errors and disk/RAM pressure.

## Backups and restore policy (production gate)

Proposed operational starting policy: encrypted off-server PostgreSQL backups daily, retained
30 days, with weekly copies for 12 weeks. Review recovery-time/recovery-point objectives before
launch; use WAL archiving/PITR if the agreed recovery point requires it.
Store backup credentials separately from the application. Back up encryption keys/configuration
securely and document who can restore them. S3 objects need independent versioning/retention and
a provider-appropriate recovery strategy.

Never keep the only backup on the production VPS. Regularly test restoring into a separate
non-production database and verify representative records, migrations and application behavior.
Record date, backup identifier, integrity result and elapsed restore time. A backup is unverified
until that exercise succeeds.

Local command pattern (no binary dump redirection through Windows PowerShell 5):

```sh
docker compose exec -T postgres pg_dump -U olxv2 -Fc -f /tmp/olxv2.dump olxv2
docker compose cp postgres:/tmp/olxv2.dump ./olxv2.dump
# Transfer/encrypt this artifact using the approved backup destination; never commit it.
# Restore ONLY into a new disposable database, never the active database:
docker compose exec -T postgres createdb -U olxv2 olxv2_restore_check
docker compose exec -T postgres pg_restore -U olxv2 --no-owner --dbname=olxv2_restore_check /tmp/olxv2.dump
```

These are instructions, not evidence that restoration has been performed in M1.
Before changing retention rules, define handling for operational/legal evidence, removed listings,
drafts, images, audit events and deletion requests. Do not implement automatic deletion of evidence
without the approved policy.

## External configuration still needed

Google/Facebook OAuth, Turnstile/Cloudflare, production domain/TLS, S3/R2, transactional email,
monitoring and off-server backups. None are active in this foundation.
Payments and promoted listings are deferred; no fake payment integration exists.
The OAuth implementation boundary belongs to M2; provider setup documentation will be added there.
