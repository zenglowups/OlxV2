# Posting wizard and private drafts (M5)

## Working flow
Verified, active individual and business sellers start at /ro/sell (or /en/sell).
Administrators use separate administrative accounts and cannot create seller drafts.
Navigate the category tree, select a visible leaf, and create a private draft.
The wizard has category, title/description, inherited attributes, price, locality,
contact preference and private preview. All links and actions persist real data.
M6 inserts secure photos after category; M7 adds actual publication and lifecycle.
There is no inactive publish button or synthetic listing inventory.

Owners can revisit every step, save incomplete input, leave, and resume at
/account/drafts. The list uses twelve records per page with explicit fields and
one eager category query. Category selection uses a paginated 24-item window.
Changing category clears only the old structured attributes. Preview recomputes
missing requirements from current definitions rather than trusting a client flag.

## Persistence and access
listings uses an internal bigint and a separate public UUID. Owner, draft status,
moderation state, public identifier, version and lifecycle timestamps cannot be
mass-assigned. The foreign key to users restricts deletion until listing retention
is handled by the account-deletion workflow in M14.

Every draft read/write requires the owner, verified email and usable account.
Other owners get 404; guests sign in; suspended/banned sessions are rejected.
Drafts, including preview, are no-store/private and never published or indexed.
Contact preference is stored, but no actual email or phone is rendered in preview.
Phone selection requires a current profile number; the encrypted profile remains
the source. M9 implements buyer contact reveal.

POST /account/drafts uses an owner-scoped UUID creation token to make repeated
submissions idempotent. The user row is locked before checking the configurable
50-draft limit and inserting. There are no ordinary-startup or automatic demo seeds.

## Autosave and conflict handling
PATCH /account/drafts/{uuid}/steps/{step} accepts a version and validated step data.
Omitted fields preserve stored values; explicit empty values clear optional fields.
Unknown attributes and fields are discarded. Requiredness/minimum content lengths
apply when continuing; partial saves still validate type, length, ranges and options.
Successful saves increment the version and store the resume step.

Writes lock the fresh user and listing rows and compare the version inside the
transaction. A shared taxonomy advisory lock keeps structural validation consistent
with catalogue administration. Stale requests receive HTTP 409 without changing
the record. HTML conflict recovery keeps the submitted values available to copy.

Vanilla JavaScript debounces edits for 900ms and serializes saves, including input
typed during an in-flight request. It waits for an in-flight save before normal
form submission; versions cannot race within the tab. Navigation saves dirty data.
A 15-second timeout, visible validation/network errors, manual retry and unload
warning protect unsaved edits. Conflicting tabs require reload, never silent retry.
The browser test covers offline recovery, a second tab and normal forms without JS.
No contact data or drafts are copied to localStorage.

Limits: create 10/minute/user, step save 90/minute/user. CSRF applies to both methods.

## Prices and locations
Fixed/negotiable prices are positive integer minor units (bani/cents), max
999,999,999.99 RON/EUR. The parser accepts decimal comma or point, never thousands
separators or exponent notation. Free stores zero; exchange/contact store null.
Display formatting does not affect database money values.

countries stores ISO country codes and localized names. counties belongs to country;
the listing has a composite foreign key ensuring that its county belongs to that
country. LocationSeeder supplies Romania's 41 counties plus Bucharest and is
idempotent without overwriting administrator edits.
Locality is currently seller-entered, validated text (max 100 characters).
No street/house address or coordinates are requested. A verified locality dataset
and search normalization belong to M8/M14; this is not geocoding.

Run once on a fresh migrated environment:
~~~sh
docker compose exec -T app php artisan db:seed --class=CategorySeeder --force
docker compose exec -T app php artisan db:seed --class=LocationSeeder --force
~~~
These are reference records, not users/listings, and ordinary startup does not seed.

## Validation and local QA
DraftTest covers ownership for every step, guest/unverified/admin restrictions,
mass assignment, version conflict, partial/full validation, integer prices,
all pricing modes, attribute false/zero handling, category changes/visibility,
county constraints, phone availability, privacy, escaped text, malformed input,
idempotent creation, limits, pagination and resumption.

The Playwright draft flow runs on desktop/mobile, signs in with a generated local
QA seller, uses the actual UI, tests reload persistence, full progression,
no contact leakage, guest denial, two-tab conflicts, offline recovery,
save/close, language switching, and a no-JavaScript form submission.
It captures screenshots, runs axe and checks narrow-screen overflow.

~~~sh
docker compose exec -T app php scripts/listing-browser-fixture.php create
docker compose run --rm --no-deps browser npx playwright test tests/Browser/drafts.spec.js
docker compose exec -T app php scripts/listing-browser-fixture.php clean
~~~
The complete browser suite also needs the catalogue fixture from docs/catalog.md.
Fixture credentials remain in ignored .tools/listing-browser.json. Cleanup checks
the recorded ID, UUID, exact name/email and deletes only those sellers' drafts
before removing those accounts. It refuses other environments and does not reset
the database or flush Redis.

## Release limits / next
- Drafts have no automatic destructive retention yet. M7/M15 add a configurable,
  documented lifecycle; removed/expired evidence retention must respect M13/M14.
- M6: eight validated images, S3 variants, reorder/remove/primary image.
- M7: publish, public pages, expiry, reservation/sold/renewal/removal.
- M9: authenticated contact reveal.
- This local milestone is not a public launch or production-readiness claim.
