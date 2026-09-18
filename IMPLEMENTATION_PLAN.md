# Implementation plan

Last updated: 2026-09-18. Repository: zenglowups/OlxV2, branch main.
The owner explicitly requested continued implementation of the full document on 2026-09-15.
Complete and validate one milestone before starting the next; do not rebuild finished work.

## Execution state

**M1 complete in commit 03eb66b. M2 complete in commit 89e0e2c. M3 complete in commit 327bcbb. M4 complete in commit c068fc4. M5 drafts complete (current checkpoint).**

### M1 completed
- Laravel 13 / PHP 8.4 modular monolith; official scaffold and locked dependencies.
- Docker Engine/Compose in existing Ubuntu WSL 2; Windows START/STOP entry points.
- PHP-FPM/Nginx, PostgreSQL 17, Redis, Mailpit, private S3-compatible local MinIO.
- Queue worker, scheduler heartbeat, Vite build tooling, optional Chromium/axe runner.
- Named persistent volumes; random untracked credentials; no automatic demo data.
- Localized RO/EN Blade shell, original configurable Aproape identity, responsive layout.
- Liveness/readiness, local infrastructure smoke and explicit restart persistence probe.
- PostgreSQL PHPUnit, frontend lint/build, browser tests and Docker-based CI configuration.
- Requirements/design sources and architecture/testing/deployment documentation.

### M1 validation — actual results
- Three starter migrations applied; subsequent startup reports nothing to migrate.
- PHPUnit: **13 passed, 205 assertions**, 2.14 seconds.
- Playwright: **10 passed**, 5.7 seconds; additional screenshot run 2 passed.
- Pint: 40 files formatted; Composer strict validation passed; frontend lint/build passed.
- Live smoke passed: database write/rollback, S3 round-trip, Redis queue, scheduler heartbeat,
  Mailpit SMTP. Probe email d1aa057e-f56b-43a6-aae6-2368b3926170 received locally.
- Full Windows STOP/START passed. PostgreSQL, Redis and S3 markers preserved and cleaned;
  existing Mailpit email preserved; .env SHA-256 unchanged.
- Windows browser displays Aproape at http://localhost:8088/ro.
- Desktop 1440px and mobile 390px screenshots inspected; overflow checks 320–1440px passed.
  Keyboard navigation, mobile menu, language switching, error recovery and axe A/AA passed.
- Windows localhost remained accessible after launching shells exited and multiple minutes elapsed.

### Startup issues resolved
- Port 8080 belongs to another Windows project (Dr. Pencu). This project uses **8088**.
- Startup checks application identity and rejects an unrelated HTTP service.
- WSL lifetime is held by a hidden, project-owned process; STOP releases only that process.
- Nginx resolves the PHP container dynamically after recreation.
- Startup clears compiled code only; it does not flush Redis data, sessions or rate limits.

### Exact next task
Implement M6: secure listing photos, maximum eight, actual format/dimension/size validation,
private S3 storage, queued 320/640/1280 WebP variants, orientation/metadata handling,
owner-authorized reorder/remove/primary selection, failed-upload recovery and durable cleanup.
Insert photos after category in the wizard; show real images in private preview and draft cards.
Validate and commit M6 before M7 publishing/lifecycle. No fake publish button or synthetic inventory.
Continue the navy/teal responsive interface and integrate profile listings/reviews in M7/M11.
Owner requested faster visible progress and continued again on 2026-09-18.

### M2 completed and validated — 2026-09-16
- Real RO/EN registration/login/logout, individual/business types, password/session security.
- Queued verification and password recovery, single-use reset, revocation of all sessions.
- Email normalization plus database uniqueness; authenticated verification ownership and expiry.
- Suspension/ban enforcement; rate limits; CSRF; no-store sensitive pages; malformed-input handling.
- Stateful Google/Facebook Socialite boundary, no email-based automatic account linking or stored tokens.
- Localized responsive forms and email templates; 403/419/429 recovery pages.
- UTC application/database clock; Europe/Bucharest presentation; query strings removed from access logs.
- Additive account migration applied; no development reset or .env overwrite.
- PHPUnit: **34 passed, 628 assertions**, 6.75 seconds. Pint formatted 61 files.
- Playwright: **16 passed**, 19.7 seconds. After queue refactor, all six authentication browser tests
  passed again (17.9 seconds), including actual Mailpit delivery, verification, password reset,
  another device's session rejection, relogin and all-session revocation on desktop/mobile.
- CSRF rejection, keyboard navigation, RO/EN form switching, overflow and axe checks passed.
- Desktop/mobile registration screenshots inspected; Windows app-browser navigation verified.
- Composer strict validation and security audit passed; frontend lint/build passed.
- Live infrastructure smoke passed again. Only UUID-labelled browser QA accounts were cleaned.
- CSS 11.65 kB / 3.43 kB gzip, JS 0.23 kB / 0.17 kB gzip; no SPA/runtime framework added.
- Google/Facebook live provider configuration remains external and untested; see docs/authentication.md.

### M3 completed and validated — 2026-09-17
- Owner-managed individual/business profiles, unique normalized usernames, biography and locality.
- Explicit public visibility; private owner preview; banned/unverified/suspended profiles hidden.
- Encrypted phone/CUI, opt-in CUI display, phone/email preference, no fabricated business verification.
- Real private S3 avatar uploads, actual image decoding, EXIF orientation, 320px WebP, durable retirement.
- New homepage editorial photo, navy/teal visual system, desktop header and mobile bottom navigation.
- Redesigned account dashboard, profile editor and public profile; real data only.
- Additive profile/deletion migrations applied during successful full Windows START; PHP EXIF installed.
- PHPUnit: **46 passed, 989 assertions**, 8.11 seconds, no warnings.
- Playwright: **16 passed**, 29.2 seconds, including real S3 upload, private/public transitions,
  business profile, no leaked contact/CUI, real email verification/reset and revoked sessions.
- Desktop/mobile homepage/account/profile screenshots inspected. Axe and 320–1440px overflow checks passed.
- Fixed error-page locale links, text contrast and an unclosed account template found in verification.
- Pint: 74 files; frontend lint/build passed. CSS 26.64 kB / 6.51 kB gzip; JS 0.52 / 0.30 kB gzip.
- Live infrastructure smoke passed (probe 04cae467-b89b-42c5-b508-d7492c8099a4).
- See docs/profiles.md and docs/design-assets.md. Listings/reviews integrate in M7/M11.
### M4 completed and validated — 2026-09-17
- Nine real major categories, 23 subcategories and 12 initial typed attributes, seeded explicitly.
- Original category cards on the homepage, category/child pages and localized breadcrumbs.
- Database adjacency tree with recursive ancestry, inherited visibility and active dynamic fields.
- Required/type/range/option validation service for future drafts and publishing.
- Protected administrator category/attribute CRUD, parent search, password confirmation and rate limits.
- Individual admin access command, session revocation, unassignable role and durable audit records.
- Serialized structural changes reject cycles, duplicate slugs and inherited key conflicts.
- Additive catalogue migration applied; initial seeder is idempotent and preserves administrator edits.
- PHPUnit: **57 passed, 1,366 assertions**, 16.21 seconds.
- Playwright: **20 passed**, 41.9 seconds; complete desktop/mobile registration/profile and catalogue/admin flows.
- Four dedicated catalogue checks also passed (14.6 seconds). Screenshots and axe inspected.
- Fixed numeric form defaults and category language URLs found during real-browser verification.
- Browser workers bounded to four; long admin scenario has a 90-second limit with 15-second action timeouts.
- Pint: 89 files; frontend lint/build passed. CSS 30.10 kB / 7.14 kB gzip, JS 0.52 / 0.30 kB gzip.
- Live DB/S3/queue/scheduler/Mailpit smoke passed (probe 7ef67d19-bb80-435b-a06c-737adc68da6f).
- Only exact UUID-labelled browser QA accounts/categories were cleaned. No user data or volumes reset.
- Admin 2FA/full admin modules remain M14; no default production admin or external exposure.
- See docs/catalog.md for data model, commands and QA setup.

### M5 completed and validated — 2026-09-18
- Seven-step server-rendered wizard, owner-only draft dashboard and private preview in RO/EN.
- Real PostgreSQL persistence, incomplete saves, 900ms autosave, normal non-JS forms and resume links.
- Locked version checks reject stale-tab writes with recoverable HTTP 409; visible offline retry.
- Live inherited attribute validation, exact integer money, five pricing modes, RON/EUR.
- Romania reference country/42 counties and validated locality; no precise address requested.
- Protected contact preference only; actual phone/email never appears in the preview.
- Idempotent draft creation, per-user limits, ownership/state checks and no mass-assigned lifecycle.
- Added responsive category picker, editor, price options, checklist and draft cards to navigation.
- Additive migration applied; explicit idempotent LocationSeeder, no development resets.
- Full PHPUnit: **70 passed, 1,950 assertions**, 21.44 seconds (2026-09-17).
- Full Playwright: **22 passed**, 56.9 seconds, including the complete wizard on desktop/mobile.
- Dedicated draft browser run: 2 passed, 27.7 seconds. Axe and 320–1440px wizard overflow passed.
- Tested reload/resume, two-tab conflict, offline/retry, language switching, no-JS submission and guest denial.
- Screenshots inspected; fixed native form submission, price-field alignment and hidden skip-link capture.
- Pint: 104 files. Frontend lint/build passed; CSS 42.06 kB / 9.29 gzip, JS 3.28 / 1.47 gzip.
- Infrastructure smoke passed (probe b6943a1c-d041-4e8c-a1d3-b5178e982f58).
- Full Windows START succeeded on 2026-09-18; readiness OK, all services healthy.
- Resume verification: 13 draft tests / 170 assertions passed again (6.07 seconds).
- Prior approval-service usage limit interrupted only QA cleanup/commit. On resume, only the
  recorded QA users, categories, drafts and avatars were cleaned. No real user data removed.
- See docs/listing-drafts.md. Photos M6, public publication/lifecycle M7, protected reveal M9.

## Milestones

| # | Milestone | State |
|---|---|---|
| 1 | Engineering foundation | Complete |
| 2 | Authentication and OAuth boundaries | Complete |
| 3 | Individual/business/public profiles and privacy | Complete |
| 4 | Hierarchical categories and dynamic attributes/admin | Complete |
| 5 | Posting wizard, private drafts, preview | Complete |
| 6 | Secure image pipeline and S3 variants | Pending |
| 7 | Publishing, lifecycle, editing, expiration, renewals | Pending |
| 8 | Search abstraction, filters, indexes, pagination | Pending |
| 9 | Protected contact reveal and anti-abuse | Pending |
| 10 | Favorites, recent searches, saved searches | Pending |
| 11 | Eligible reviews, replies, reputation | Pending |
| 12 | Private business verification workflow | Pending |
| 13 | Reporting, moderation and sanctions | Pending |
| 14 | Secure functional admin system and 2FA | Pending |
| 15 | Queued notifications and scheduled lifecycle jobs | Pending |
| 16 | Real seller analytics and dashboard | Pending |
| 17 | SEO metadata, sitemap, schemas and index controls | Pending |
| 18 | Query plans, N+1/payload/cache/image performance | Pending |
| 19 | Explicit large synthetic dataset generation | Pending |
| 20 | Local/staging k6 load tests and measurements | Pending |
| 21 | Security and authorization review | Pending |
| 22 | Full mobile/accessibility QA | Pending |
| 23 | Production infrastructure, monitoring and restore rehearsal | Pending |

## Architectural decisions

- Modular Laravel monolith, Blade, vanilla JS; no SPA, chat, videos or fake marketplace features.
- PHP 8.4, Laravel 13, PostgreSQL 17, Redis 7.4, Vite 8/Node 24.
- APP_NAME controls the provisional Aproape identity; OLXv2 remains internal.
- PostgreSQL is durable truth, S3 the media boundary, Redis cache/queues/sessions.
- Test DB olxv2_testing; boot guard prevents test resets of development data.
- Ports bind to loopback; PostgreSQL/Redis are private to Compose.
- Browser container is an optional test profile, not an ordinary startup dependency.
- MinIO official Quay release pinned by digest; archived upstream is a local-only dependency.
  Production must use a maintained S3 provider; see architecture.md.
- No default users or automatic demo/synthetic seed data.
- No public deployment or Git push requested; local logical commits only.

## Known issues and release limits

- M1 is a working foundation; marketplace functionality is still being implemented.
- MinIO community upstream is archived; reassess emulator maintenance before external exposure.
- Docker Desktop/macOS paths are structurally supported but tested environment is Windows/Ubuntu WSL.
- Chrome connector unavailable; Windows app browser and automated Chromium used.
- In-app viewport override did not produce requested dimensions; exact visual QA used test screenshots.
- Full screen-reader audit, Safari/Firefox and production security review remain.
- Hosted CI has not run; local checks passed.
- Never use port 8080 here: it belongs to another project.

## External configuration pending

Google/Facebook OAuth, Turnstile, production S3/R2, transactional email,
Cloudflare/domain/TLS, off-server backups and monitoring require owner/provider setup.
Implement and test boundaries locally; do not claim these external services are active.
Payments deferred until real requirements and provider configuration exist.

## Performance

M1 shell: 7,700-byte HTML, built CSS 9.40 kB (2.83 kB gzip), JS 0.23 kB (0.17 kB gzip).
Windows PowerShell sequential warm requests (2026-09-15):
- localhost: 20 requests, median 2,110.3 ms, p95 2,249.6 ms.
- 127.0.0.1: 10 requests, median 90.1 ms, p95 325.9 ms.
The difference is consistent with Windows localhost IPv6-to-IPv4 fallback; use 127.0.0.1 for
server benchmarks. These are small local diagnostics, not load tests or scalability evidence.
M18–M20 require query plans, explicit 10k/100k/1m staging generators and measured concurrency.
Production backup restoration also remains a release gate.
