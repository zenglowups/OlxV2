# Testing the foundation

## Automated backend checks

```sh
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan test
docker compose exec -T app vendor/bin/pint --test
docker compose exec -T app composer validate --strict
```

PHPUnit uses **PostgreSQL**, not SQLite, with a separate olxv2_testing database.
The test application refuses to boot against any other database before RefreshDatabase can run.
The database is created by docker/postgres/init-testing.sql only when the PostgreSQL volume is first
initialized. If adopting an existing volume, create this test database explicitly with createdb;
do not delete or reinitialize the development volume.

Tests cover:
- Random first-run credentials and idempotent .env preservation.
- Romanian/English translation completeness, routing, language isolation and rebranding.
- Truthful server-rendered shell, security headers and localized 404 recovery.
- Health status 200/503, minimal responses and session-independent readiness.
- Real PostgreSQL migrations, persistence, unique email constraint, hashed/hidden password fields.

Authentication tests cover privilege-field rejection, sessions, bans/suspensions, rate limits, malformed input, signed email ownership/expiry, reset expiry/reuse, and OAuth state/email takeover. Add listing/profile policy and IDOR tests alongside those features.

## Frontend and browser

```sh
docker compose run --rm --no-deps assets sh -c 'npm run lint && npm run build'
docker compose run --rm --no-deps browser
```

The optional browser container pins Playwright 1.63.0 and includes Chromium.
Its version must match package-lock.json when upgrading Playwright.
Two projects exercise desktop and a mobile viewport. Tests cover language switching,
loaded illustration, anchors, keyboard skip navigation, accessible mobile menu, 404 recovery,
JavaScript errors, overflow at 320/360/390/768/1024/1440 pixels, and axe WCAG A/AA checks.
Axe is an automated check, not a full accessibility certification or screen-reader audit.
Reports/traces are written to ignored playwright-report/ and test-results/.

Manual verification uses the app browser against Windows localhost:8088, because Docker-only tests
would not catch a conflicting Windows process on the same port. Chrome should use the same URL.
Chrome's automation connector was unavailable in this session; automated checks use Chromium.

## Real infrastructure check

```sh
docker compose exec -T app php artisan app:ready
docker compose exec -T app php artisan app:smoke
docker compose exec -T app php artisan schedule:list
```

app:smoke is restricted to APP_ENV=local, Redis queues and MAIL_HOST=mailpit.
It dispatches a real job, confirms the worker's completion, and generates an email whose subject
contains a unique probe id. Confirm it in http://localhost:8025. It does not email real recipients.
Its temporary database write is rolled back and its S3 object is deleted.

app:smoke also checks the scheduler heartbeat; allow one minute after startup before running it.
For an explicit persistence test, run php scripts/persistence-probe.php create inside the app container, STOP then START, then run the probe with verify and clean. Run app:smoke again after a stop/start. Verify migrations, previous Mailpit messages and existing
object/database marker values persist. Do not use destructive resets as a testing shortcut.

## CI

.github/workflows/tests.yml runs the same Docker environment on pull requests and main pushes:
startup, backend tests, Pint, Composer validation, frontend lint/build, infrastructure smoke,
and Playwright. It has read-only repository permissions and no deployment step.
A workflow existing locally does not mean a hosted run has passed; see IMPLEMENTATION_PLAN.md.

## Recorded results

The dated execution state in IMPLEMENTATION_PLAN.md records actual counts, measurements,
browser coverage and outstanding limitations. Performance at scale, full security review,
backup restoration rehearsal and production deployment are separate release gates.

## M2 real account workflow
The browser suite creates only UUID-labelled QA accounts, checks real queued Mailpit messages,
opens verification/reset links, confirms another session is revoked, checks all RO/EN forms with axe,
and confirms CSRF failures. Clean only those recorded accounts after the run:

```sh
docker compose exec -T app php scripts/cleanup-browser-users.php
```
Do not flush Redis rate limits between retries; wait for the normal one-minute window if needed.
External Google/Facebook tests use provider-contract doubles until real credentials are configured.
## M3 profile and design coverage
ProfileTest validates ownership, visibility, privacy, business disclosure, actual image conversion
and cleanup retries. The desktop/mobile authentication workflow also edits the profile, uploads a
real avatar to MinIO, publishes a business profile, checks protected data is absent from public HTML,
and withdraws visibility. QA cleanup records avatar retirement before removing its own test accounts.
The homepage and all profile/account screens receive axe and responsive screenshot checks.

## M4 catalogue browser preparation
The complete browser suite now needs explicit local admin QA fixtures and the initial taxonomy.
See docs/catalog.md for create/test/clean commands. These never run during ordinary startup.
The CI workflow performs the same setup and cleans only its UUID-labelled fixture data.

## M5 draft browser preparation
The full suite also needs the two isolated seller accounts from
scripts/listing-browser-fixture.php create. Run its clean action after QA.
See docs/listing-drafts.md for setup, coverage, privacy and conflict guarantees.
The reference LocationSeeder must be run once on a fresh migrated environment.

## M6 private listing photos
ListingPhotoTest covers actual GD conversion, orientation, size/count limits, ownership,
version conflicts, processing failures, retries and durable cleanup. The browser suite adds real
MinIO/Redis uploads, primary/reorder/delete, responsive gallery and draft cards. Run the fixture
audit-photos action after browser checks to verify private S3 ACLs, anonymous 403 and source removal.
See docs/listing-photos.md for the full flow and scoped cleanup commands.
