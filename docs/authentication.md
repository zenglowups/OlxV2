# Authentication (M2)

## Available locally
- RO/EN registration for individual and business accounts.
- Email/password login, remember-me cookie, logout and password-protected revocation of all sessions.
- Queued, localized verification and password-recovery email through Mailpit.
- Signed verification links expire after 60 minutes and require the matching authenticated user.
- Reset tokens expire after 60 minutes, are stored hashed, and are rechecked/consumed under a
  PostgreSQL row lock. Resetting a password increments the session version and rotates remember tokens.
- Suspended/banned accounts cannot sign in; existing sessions are rejected on their next request.
  A suspension with an elapsed end time stops blocking access. Administration arrives in M13/M14.

Start using /ro/register or /en/register. Mailpit: http://localhost:8025.
There are no seeded accounts or default credentials.

## Security decisions
- Passwords are 12–128 characters and hashed by Laravel; no client-supplied status/verification/role.
- Email addresses are normalized; a PostgreSQL lower(email) unique index also protects concurrent writes.
- Session IDs rotate at login and registration. CSRF protects all forms; sensitive responses are no-store.
- Redis rate limits cover login (identity/IP), registration, recovery, verification, OAuth and session actions.
- Password recovery responds generically and resolves account existence in a queue job, avoiding a
  synchronous password-token hashing delay that would distinguish existing accounts.
- Verification signs the complete path and query (including locale, user id, email hash and expiry).
  It deliberately excludes the hostname, using Laravel signed:relative, allowing local proxy aliases.
  The email's clickable URL uses APP_URL. All query parameters remain covered by the signature.
- HTTP access logs exclude query strings so reset tokens, OAuth codes and private email addresses
  are not recorded there. Production access-log retention and application auditing remain M21/M23.
- Application/database instants use UTC. DISPLAY_TIMEZONE defaults to Europe/Bucharest for presentation.
  The old APP_TIMEZONE setting is no longer read; existing .env files were preserved.

## Google / Facebook configuration — not active yet
Laravel Socialite provides stateful authorization-code redirects and callbacks. Required environment values:

| Provider | Variables | Local callback |
|---|---|---|
| Google | GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI | http://localhost:8088/oauth/google/callback |
| Facebook | FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET, FACEBOOK_REDIRECT_URI | http://localhost:8088/oauth/facebook/callback |

Create applications with the providers, register exact permitted callbacks, configure credentials privately,
then clear Laravel's configuration cache and restart the queue. A provider may require an HTTPS test domain.
Use dedicated staging credentials and provider test users; do not expose local development as production.
Buttons remain absent and routes return 404 until all three settings for that provider exist.

OAuth identities are identified by the unique provider/id pair. Access/refresh tokens are not persisted.
An existing account is never automatically linked using a matching email address. That user must sign in
using their existing password or recover access through their email. Manual provider linking is not implemented.
A new Google identity counts as email-verified only with the provider's explicit verified-email signal.
Facebook registrations require this application's own verification email. New OAuth accounts are individual;
profile/business editing follows in M3. OAuth-only users can use email recovery to choose a password.

Provider flows have contract tests for invalid state, duplicate identity, email takeover prevention and bans.
No live Google/Facebook authorization has been claimed without configured provider credentials.

## Validation and sources
Backend tests use olxv2_testing. Browser tests create UUID-labelled qa-auth accounts in the local development
database and receive real queued mail in Mailpit. Run the scoped cleanup after testing:

```sh
docker compose exec -T app php scripts/cleanup-browser-users.php
```

It only removes accounts recorded by that test, whose email UUID and display name both match;
it never resets the database. Mailpit retains the QA emails locally.

Implementation follows official [Laravel authentication](https://laravel.com/docs/13.x/authentication),
[email verification](https://laravel.com/docs/13.x/verification),
[password recovery](https://laravel.com/docs/13.x/passwords),
[signed URLs](https://laravel.com/docs/13.x/urls#signed-urls) and
[Socialite](https://laravel.com/docs/13.x/socialite) documentation.
See IMPLEMENTATION_PLAN.md for dated execution results and remaining milestones.
