# Listing publication and lifecycle (M7)

## User flow

A completed draft can be published from its private preview. The server revalidates the current
category, inherited attributes, all required fields, selected contact method and photo readiness.
Publishing requires a verified usable seller account with an explicitly public seller profile.
Publishing never changes the user's profile privacy setting.

A newly published listing is immediately visible as active and unreviewed, with a 30-day lifetime.
Its public URL uses the stable UUID. Cards appear in the paginated listing feed, its category and
ancestors, the public seller profile and the latest-listings section when there is real inventory.
No sample marketplace inventory or fabricated activity is seeded.

My listings shows real per-owner state counts, filters and twelve-row pages. It provides native
forms with explicit review screens for reserve, available again, sold, edit, repost and withdrawal.
The existing draft dashboard and wizard remain usable. Editing withdraws the listing into a private
draft before incomplete changes can be saved. Republish uses the same URL and retains an unexpired
deadline. This intentionally prioritizes draft privacy over keeping the previous revision public;
a separate live revision workflow is not implemented.

## States and allowed owner transitions

| Current effective state | Actions |
| --- | --- |
| Draft | Publish after validation; withdraw |
| Active | Edit into private draft; reserve; sold; withdraw |
| Reserved | Edit; available again; sold; withdraw |
| Sold / expired | Edit; repost for 30 days after full validation; withdraw |
| Suspended / withdrawn | Read-only owner history |

Only unreviewed/reviewed moderation states permit these actions. Moderation holds cannot be bypassed
through editing or reposting. Reposts return to unreviewed. Full moderator tools remain M13/M14.
Withdrawal records removed_at and hides all public routes. It retains the record, lifecycle history
and private variants; there is no automatic destructive retention or legal-evidence purge.

Every mutation locks a fresh owner then listing, authorizes ownership and validates the submitted
version. Conflicts return 409 with a recovery link. Mutations are rate-limited and CSRF-protected.
listing_events records actor, action, previous/new state, version and time in the same transaction.
The event journal does not copy contact data or freeform listing contents.

## Public eligibility and privacy

A shared PostgreSQL query requires active/reserved state, unexpired published lifetime, permissible
moderation, a verified usable non-admin seller, an explicitly public profile, and an active category
ancestry. Hiding a profile or parent category, banning/unverifying a seller, moderation, editing,
sale, withdrawal or expiry immediately removes the listing and its images from public eligibility.
Expired temporary account suspensions are treated consistently with authentication.

Public pages load only the required seller identity fields; encrypted phone, email, private tax
data and source object paths are not rendered. Contact reveal remains M9. The UI explains that
contacting sellers is not available yet and does not expose an inert reveal button.

S3 remains private. Public WebP variants are streamed through the eligibility-checking application
controller, using private/no-store headers for prompt access revocation. Owners can still inspect
their own retained variants, including sold/withdrawn items. Original uploads never have a route.
Public CDN caching is deferred until an explicit revocation policy exists.

## Expiration and queries

Public eligibility checks expires_at on each query, so a delayed scheduler cannot extend visibility.
listings:expire runs each minute and processes at most 100 due records ordered by deadline/id.
It rechecks each row under the owner/listing lock and records each expiration once. Expired records
remain editable/repostable by their usable owner. Reminder emails are implemented separately in M15.

Partial indexes support public publication order, category/publication order and due expiration.
Feeds select needed columns and one ready image per listing; category and county are eager-loaded.
Owner counts are SQL aggregates and pagination loads only one window. Public profile and category
pages use the same eligibility query, including recursive category ancestry.
Local query-plan observations are not evidence of performance at production scale; M18–M20 remain.

## Verification and local QA

ListingLifecycleTest uses PostgreSQL olxv2_testing. It covers publication validation, deadline and
stable-URL semantics, stale versions, all transitions, audit persistence, immediate expiry, repeated
scheduler execution, privacy revocation, moderation/account/taxonomy restrictions and owner pagination.
Browser QA uses exact UUID-marked seller fixtures and real MinIO/Redis photos, then exercises native
publication, guest viewing, profile/category cards, reserve, edit, sale, repost and withdrawal on
desktop/mobile. It checks axe, responsive widths, localization, no contact disclosure and media
revocation. Scoped cleanup journals image retirement and removes only the recorded QA event rows.
Actual validation results are recorded in IMPLEMENTATION_PLAN.md.
