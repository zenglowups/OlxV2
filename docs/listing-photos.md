# Listing photos (M6)

The posting wizard has a photo step immediately after category selection. Photos are optional,
private while the listing is a draft, and limited to eight including queued or failed entries.
The first position is the primary image. Owners can move images, choose the primary image and
delete them using ordinary server-rendered forms. Selected but unsubmitted files trigger a leave
warning; processing status refreshes without discarding a new selection. No videos or HEIC support.

## Input and processing

The server decodes actual JPEG, PNG or WebP bytes with GD. Neither the client MIME type nor the
original filename determines format or storage paths. Each file is at most 10 MiB, each dimension
64–6,000 pixels, and total area at most 12 megapixels. JPEG EXIF rotation/mirroring is normalized.
Sources use random UUID paths in private S3; original names are never persisted.

Redis jobs create private WebP variants bounded by 320, 640 and 1,280 pixels on either axis,
preserving aspect ratio and alpha without upscaling. Re-encoding removes metadata. Source hashes
are checked again in the worker. Original sources are retired after successful processing.
Responsive preview images use actual-width srcset descriptors; small images do not duplicate
width descriptors. Draft cards eager-load only one ready image per paginated listing.

PHP accepts more multipart entries than the application limit so a ninth file reaches application
validation instead of silently truncating to eight. PHP/Nginx request limits are 85 MiB.

## Ownership, concurrency and recovery

Every write checks a fresh verified, usable owner and draft status under a user/listing row lock.
A version conflict returns 409 and cannot replace newer edits. Photo IDs are scoped to the listing.
Queue processing and deletion lock the same photo row; duplicate or late jobs cannot resurrect a
deleted photo. Database constraints bound positions and states, and ownership locks enforce count.

Database reservations commit before source upload, so every possible object has a durable owner
and known path. Source upload failure produces a visible failed entry. The queue retries processing
three times with 10/30-second backoff (80-second job timeout); terminal failure retires originals
and any partial variants. The owner can delete the failed entry and upload again.

The scheduler runs media:recover-listings once per minute. It marks up to 25 uploads older than
15 minutes as failed and redispatches up to 25 stale queued records whose dispatch was lost.
Durable pending_file_deletions entries cover original, partial and completed objects; media:cleanup
retries storage outages every ten minutes in bounded batches. There is no public original URL.

The /media/listings/{uuid}/{variant} controller authorizes the current draft owner and emits
image/webp with private, no-store and nosniff headers. Anonymous users and other owners receive 404.
All S3 objects and the bucket stay private. Public listing image eligibility is added in M7;
production S3 credentials and CDN/private access policy remain a separate deployment gate.

## Verification

ListingPhotoTest runs against PostgreSQL olxv2_testing and isolated Linux temporary storage so
Unix permissions are testable even when the repository is on a Windows bind mount. It exercises
real image conversion, orientation, metadata stripping, all supported formats, invalid files,
eight-photo limits, stale versions, ownership, late/duplicate jobs, recovery and cleanup outages.

The real browser suite uses only UUID-labelled QA sellers. It exercises actual MinIO and Redis
processing on desktop/mobile, primary/reorder/delete, private gallery, protected routes, the ninth
multipart file, axe checks and overflow at 320–1,440 px.

```sh
docker compose exec -T app php scripts/listing-browser-fixture.php create
docker compose run --rm --no-deps browser npx playwright test tests/Browser/photos.spec.js
docker compose exec -T app php scripts/listing-browser-fixture.php audit-photos
docker compose exec -T app php scripts/listing-browser-fixture.php clean
```

The audit verifies real private ACLs, anonymous MinIO HTTP 403 responses and removed originals.
Cleanup targets only the exact recorded QA identities and journals their image paths before
deleting records. It never resets development data. See IMPLEMENTATION_PLAN.md for actual results.
