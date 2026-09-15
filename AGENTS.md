# OLXv2 engineering constraints

Read IMPLEMENTATION_PLAN.md, docs/product-requirements.md, and recent commits before continuing.
The original user request takes precedence over upstream scaffold recommendations.
This repository uses Docker; PHP, Composer, databases and Node do not need host installations.
The current task is Milestone 1 ONLY. Do not begin authentication until the owner says Continue.

## Architecture and product rules
- Modular Laravel monolith; PHP, PostgreSQL, Redis, Blade, CSS, Vite, optional Alpine.
- No React/SPA, microservices, Kubernetes, internal chat, videos, fabricated metrics or fake payments.
- UI language: Romanian first, English second; use Laravel lang files.
- Internal codename OLXv2; public provisional identity comes from APP_NAME (Aproape).
- Reference images communicate visual direction, not functionality. Never reproduce OLX branding.
- A working responsive shell is enough for M1; no inert marketplace controls or invented listings.
- PostgreSQL is durable truth. S3-compatible storage for uploads, Redis for cache/queues/sessions.
- Protect ownership on every mutation. Contact data must only come from authenticated reveal endpoints.
- Drafts and legal documents are private; eight listing images maximum. Verify actual formats.
- Trust badges must be supported by real verification or computed evidence.
- Query only required paginated windows; eager-load intentionally and inspect critical query plans.

## Safety and continuity
- Never commit .env, secrets, generated credentials, vendor, node_modules, or generated assets.
- Do not reset databases, run migrate:fresh on development data, delete volumes, or overwrite .env.
- Startup may run pending migrations only. Test database MUST be olxv2_testing.
- Keep services loopback-only locally. Production needs separate hardening and credentials.
- No external deployment or public launch as part of M1.
- Keep provider integrations honestly documented as pending until configured and tested.
- Make logical local Git commits, update IMPLEMENTATION_PLAN.md at checkpoints, preserve exact next task.
- Do not mark a milestone complete if migrations, tests, build, or required local services fail.
- On Continue: inspect state, run relevant tests, resume next unfinished milestone. Do not rebuild.
- Only delegate if explicitly requested by the user; work locally otherwise.

## Validation
Use scripts/dev.ps1 Compose (or docker compose inside WSL).
Run migrations, PHPUnit against PostgreSQL, Pint, frontend lint/build, service smoke checks,
and real browser QA at mobile/desktop sizes. Document actual results and limitations.
Never claim production readiness or scalability from this foundation.
