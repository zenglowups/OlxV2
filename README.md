# OLXv2 · Aproape

A Romanian classifieds marketplace, built as a portable Laravel monolith.
**Completed: M1 foundation, M2 authentication, M3 profiles and responsive visual interface, M4 categories and administration. Next: M5 posting wizard.**
Registration, login, email verification, password recovery, session revocation and individual/business profiles with private S3 avatars work locally. Live category navigation and secure category/attribute administration also work. Listings, search and contact reveal are still pending. See docs/authentication.md for setup and security decisions.
The provisional public name **Aproape** is configured with `APP_NAME`; OLXv2 is an internal codename.

## Open the application

- Application: **http://localhost:8088/ro**
- English: **http://localhost:8088/en**
- Create an account: **http://localhost:8088/ro/register**
- Sign in: **http://localhost:8088/ro/login**
- Mailpit (local email inbox): **http://localhost:8025**
- MinIO console: **http://localhost:9001**
- Liveness: **http://localhost:8088/up**
- Readiness: **http://localhost:8088/health/ready**

Port 8080 is already used by another Windows project on this machine.
This application uses 8088. Startup verifies the application identity through Windows HTTP,
not only container health, to catch Windows/WSL port collisions.

## Windows: start and stop

From this directory, double-click or run:

```powershell
.\START-OLXv2.bat
.\STOP-OLXv2.bat
```

The start script detects Docker Desktop or Docker Engine in Ubuntu WSL 2, creates `.env`
with random local credentials if absent, builds the PHP image, installs **locked**
Composer/npm dependencies, applies pending migrations, builds frontend assets, starts services,
waits for readiness and verifies the application at its Windows URL. On WSL it also keeps a hidden
WSL process alive until STOP, because systemd services alone do not keep the distribution running.
The process id/start time and diagnostic logs live in ignored .tools/. STOP terminates only that process.

**An existing .env is never replaced. Stop preserves every named volume.**
The script never resets/seeds a database and never runs `down -v`.
Initial image/dependency downloads need internet access and take longer than subsequent starts.

### Prerequisites

Use one of:
1. Docker Desktop with Linux containers and Compose v2; launch Docker Desktop before START.
2. Ubuntu WSL 2 with Docker Engine, Compose v2, Buildx and systemd.

This machine uses option 2 (Ubuntu 26.04). Docker was installed inside the existing WSL
distribution; PHP, Composer, Node, PostgreSQL and Redis for the project run in containers.
On a fresh Ubuntu system, an administrator can install `docker.io docker-compose-v2 docker-buildx`
from Ubuntu packages, enable the Docker service, and grant the chosen local developer Docker access.
Docker access confers host-level privileges; configure it only for trusted developers.
Set `OLXV2_WSL_DISTRO` if your WSL distribution is not named Ubuntu.

Recommended local resources: 4 CPU cores, 8 GB RAM and 15 GB free disk including optional browser images.

## Linux / WSL / macOS with Docker

Run once from the repository root:

```sh
docker run --rm -v "$PWD:/work" -w /work php:8.4-cli-bookworm php scripts/setup-env.php
docker compose up -d --build --wait --wait-timeout 300
```

Ordinary sessions after initialization:

```sh
docker compose up -d
docker compose stop
```

Use START on Windows for the extra host-URL identity check. If you change ports,
update `APP_PORT` and `APP_URL` together, then rerun START.
MinIO/SMTP ports are configurable separately in `.env`.

## Services and data

| Service | Role | Persistent storage |
| --- | --- | --- |
| web / app | Nginx / PHP 8.4 FPM + Laravel 13 | Source mount; no uploaded media on the VPS |
| postgres | PostgreSQL 17; application and isolated test databases | postgres_data |
| redis | Cache, encrypted sessions, queue; AOF enabled, no eviction | redis_data |
| queue | Laravel queue worker; retries, failure recording | Redis + failed_jobs |
| scheduler | Laravel scheduler; Redis heartbeat once per minute | Redis |
| minio | Private, S3-compatible local development storage | minio_data |
| mailpit | Local SMTP capture; sends no external email | mailpit_data |
| setup / storage-init | One-shot dependency/migration/bucket setup | vendor |
| assets | One-shot Vite production build | node_modules |
| vite | Optional hot reload, profile dev | node_modules |
| browser | Optional reproducible Chromium/axe tests, profile test | browser_node_modules |

All published ports bind to loopback. PostgreSQL and Redis have no host ports.
MinIO credentials are in your private `.env` under `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY`.
The local bucket is private. Do not enable anonymous access to future legal evidence.

## Common commands

Run in WSL from `/mnt/c/Users/Admin/Desktop/OlxV2`, or use Docker Desktop's terminal:

```sh
docker compose ps --all
docker compose logs --tail 100 app queue scheduler
docker compose exec app php artisan migrate:status
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
docker compose exec app composer validate --strict
docker compose exec app php artisan app:ready
docker compose exec app php artisan app:smoke
docker compose run --rm --no-deps assets sh -c 'npm run lint && npm run build'
docker compose run --rm --no-deps browser
```

Equivalent PowerShell wrapper (pass arguments explicitly as an array):

```powershell
.\scripts\dev.ps1 -Action Compose -DockerArguments @('ps', '--all')
.\scripts\dev.ps1 -Action Compose -DockerArguments @('exec', '-T', 'app', 'php', 'artisan', 'test')
```

Optional frontend hot reload:

```sh
docker compose --profile dev up -d vite
# After stopping Vite, return to normal built assets:
docker compose stop vite
docker compose run --rm --no-deps assets npm run build
```

Vite listens on http://localhost:5173; view the application on 8088.
START removes a stale hot-file and defaults to built assets. Don't run multiple asset installers concurrently.

## Documentation and continuation

- [Execution state and next milestone](IMPLEMENTATION_PLAN.md)
- [Permanent engineering constraints](AGENTS.md)
- [Original product requirements](docs/product-requirements.md)
- [Architecture and decisions](docs/architecture.md)
- [Tests and verification](docs/testing.md)
- [Deployment and backup preparation](docs/deployment.md)
- [Design references](docs/design-references/README.md)

No production providers, accounts or credentials are configured.
Milestone 2 begins only when the owner asks to continue.
