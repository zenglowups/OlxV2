# Categories and dynamic fields (M4)

## Public catalogue
The homepage and /ro/categories (/en/categories) query live categories from PostgreSQL.
Each category has a stable unique slug, localized names/descriptions, parent, icon, order and visibility.
Public category pages navigate children and show ancestor breadcrumbs. Hidden ancestors hide the entire
branch. There are no simulated listings or counts. Listing results will join these pages in M7/M8.

Root and child windows use SQL pagination (24 cards); the homepage reads at most 12 roots.
Cards select only id, slug, localized names and icon. Ancestors use a PostgreSQL recursive query,
without a fixed schema depth. Admin lists are paginated (30) and intentionally eager-load parent labels.
The parent picker searches a bounded window of 50 and includes the current parent.

## Initial taxonomy
Run explicitly, never on ordinary startup:
    docker compose exec -T app php artisan db:seed --class=CategorySeeder --force

The idempotent seeder adds missing categories and definitions without overwriting administrator edits.
It creates nine requested major categories and 23 subcategories, plus twelve useful initial fields
for cars, real estate and electronics. It adds no sellers or advertisements.

## Dynamic attributes
Types: text, number, select, boolean. Definitions include RO/EN labels, stable key, optional unit/range,
localized options, required/filterable/active flags and display order. Descendants inherit active fields.
The same key cannot occur twice along an active lineage, including after a parent move.
AttributeValidator validates and normalizes submitted values against the selected category:
unknown fields are discarded, required fields enforced at completion, missing fields allowed for
partial drafts. Numbers and booleans become typed JSON values. Select values come from allowed options.
Keys/types are immutable after creation to preserve stored data meaning; disable and replace instead.
Future listing edits must revalidate against current definitions, and M8 must honor filterable flags.

## Administration
/ro/admin/categories is an actual protected editing interface. Administrators can create/edit/hide
categories, choose parents, add children, and manage inherited field definitions.
Each write requires the administrator's current password, CSRF and a ten-per-minute write limit.
A PostgreSQL transaction/advisory lock serializes tree edits and definition changes. Cycles,
duplicate slugs and conflicting inherited keys produce validation errors with transaction rollback.
Database checks/indexes also protect parent identity, unique slugs/keys, type and numeric range.

No administrator is created by default. A deployment operator may grant an existing verified,
active individual account by its exact numeric ID:
    docker compose exec -T app php artisan admin:access USER_ID
    docker compose exec -T app php artisan admin:access USER_ID --revoke

Both changes revoke existing sessions. Admin role is neither fillable nor serialized.
Registration and profile updates cannot grant it; an administrator cannot convert the account to business.
Important taxonomy/privilege writes create durable admin_audits with actor, subject, action and changes.
Passwords are not included in audits. Admin 2FA, a full audit viewer and additional admin modules remain M14;
this local foundation is not authorization for external exposure.

## Validation and local QA
CatalogTest covers idempotence, translations, 21-level ancestry, hidden branches, SQL pagination,
access control, password confirmation, audits, cycles, inherited types/ranges/options, immutable keys,
scope mismatch, conflicting moves and CLI grant/revocation.
Browser tests cover public navigation/language switching and real category/child/field creation,
hiding, guest denial, responsive screenshots and axe.

Prepare explicit disposable QA fixtures before browser tests:
    docker compose exec -T app php scripts/catalog-browser-fixture.php create
    docker compose run --rm --no-deps browser
    docker compose exec -T app php scripts/catalog-browser-fixture.php clean
    docker compose exec -T app php scripts/cleanup-browser-users.php

The fixture creates two UUID-labelled local-only administrator accounts, with random credentials in ignored
.tools/catalog-browser.json. Cleanup validates exact IDs, UUIDs, names and email before deleting only its
test accounts, recorded test category slugs, their attributes and audits. It never clears user categories.
CI runs the same preparation and cleanup. Recreate the fixture after a failed admin mutation flow
before retrying, to avoid duplicate test slugs. The catalogue seeder is required on a fresh test environment.
