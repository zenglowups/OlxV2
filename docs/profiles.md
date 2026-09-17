# Profiles and privacy (M3)

Individual and business profiles are edited at /ro/account/profile (also /en).
A verified, usable account is required. The owner chooses a unique lowercase username,
description, locality, account type, optional photo/logo and either phone or email contact.
The public page is /ro/sellers/{username}. Profiles start private; the owner has a private preview.

## Public and private data

- Explicit visibility publishes display name/business name, username, photo, description,
  locality, account age and a genuine email-verification badge.
- No public telephone/email in HTML, model serialization or image metadata.
- Phone and CUI/CIF are encrypted with Laravel encrypted casts. Back up APP_KEY securely;
  losing it loses access to these values. Key rotation requires Laravel's previous-key procedure.
- Romanian numbers normalize to +40. Stored phone is not claimed verified.
- Business CUI is private by default and may be disclosed explicitly. It is seller-declared,
  never an ANAF/ONRC verification claim. Switching to individual clears the business fields.
- Private, banned, unverified and actively suspended profiles return 404 to the public.
- Contact reveal, listings and reputation will integrate through M9, M7 and M11 respectively.
- Account deletion/export request handling remains part of the later privacy/admin work.

## Image boundary

JPEG/PNG/WebP only, actual MIME/type and decoder checks, maximum 5 MiB / 12 MP,
minimum 64px per side, maximum 6000px per side. SVG/GIF/HEIC are rejected.
GD decodes and re-encodes to a 320px WebP with new random UUID storage name.
JPEG EXIF orientation is normalized. Individuals use a centered crop; business logos fit on white.
Source bytes and metadata are not retained. The private S3 object is served through a
policy-controlled endpoint; no public bucket ACL or original filenames are exposed.
Avatar responses are private/no-store so withdrawing profile visibility takes effect.
The owner can replace/remove a photo. Previous objects enter a durable deletion queue in the same
transaction as the profile update. Immediate deletion is attempted, with failures retried by
media:cleanup every ten minutes in batches of fifty.

## Authorization and validation

Owner identity comes from the authenticated session; a locked fresh user and policy guard the write.
Request fields are allowlisted. Another user ID, stored object path or account status cannot be assigned.
Username uniqueness is enforced in PostgreSQL, including a lowercase index. Concurrent collisions
produce a form error. Writes are rate-limited to ten per minute per user, with normal CSRF protection.
Storage unavailability gives a recoverable photo validation error.

## Verification

ProfileTest covers ownership, private defaults, status/verification visibility, encrypted data,
CUI consent, input abuse, actual image conversion, invalid files, replacement cleanup and storage failure.
The real browser flow uploads to MinIO, confirms owner access and guest denial, publishes a business
profile, verifies no private contact/CUI leaked, then returns it to private. Desktop/mobile axe checks
and screenshots cover the homepage, account, profile editor and public profile.

References:
- https://laravel.com/docs/13.x/filesystem
- https://laravel.com/docs/13.x/eloquent-mutators#encrypted-casting
- https://www.php.net/manual/en/function.imagecreatefromstring.php
- https://www.php.net/manual/en/function.imagewebp.php
