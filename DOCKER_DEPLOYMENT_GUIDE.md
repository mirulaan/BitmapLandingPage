# Docker Deployment Guide for BitmapLandingPage

A comprehensive, production-ready guide for building, running, and deploying the **BitmapLandingPage** (Staff Hub) application inside Docker containers with PostgreSQL (`db_LandingPage`).

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Architecture Overview](#2-architecture-overview)
3. [Quick Start: Docker Compose (Recommended)](#3-quick-start-docker-compose-recommended)
   - [Step 1: Prepare the PostgreSQL Database](#step-1-prepare-the-postgresql-database)
   - [Step 2: Environment Configuration](#step-2-environment-configuration)
   - [Step 3: Generate Application Key](#step-3-generate-application-key)
   - [Step 4: Build & Launch Services](#step-4-build--launch-services)
   - [Step 5: Verify Deployment & Health Check](#step-5-verify-deployment--health-check)
4. [Database & Artisan Management](#4-database--artisan-management)
5. [Standalone Single-Container Deployment](#5-standalone-single-container-deployment)
   - [Building the Image](#building-the-image)
   - [Running with External PostgreSQL](#running-with-external-postgresql)
   - [Running Standalone with SQLite](#running-standalone-with-sqlite)
   - [Deploying to Cloud Providers](#deploying-to-cloud-providers)
6. [Local Development with Live Reload](#6-local-development-with-live-reload)
7. [Environment Variables Reference](#7-environment-variables-reference)
8. [Maintenance, Logs, and Day-2 Operations](#8-maintenance-logs-and-day-2-operations)
9. [Troubleshooting & FAQs](#9-troubleshooting--faqs)

---

## 1. Prerequisites

Before beginning, ensure you have the following installed on your host machine or deployment server:

- **Docker Engine**: version 24.0+ ([Install Docker](https://docs.docker.com/engine/install/))
- **Docker Compose**: version 2.20+ (included with Docker Desktop and standard Docker Engine packages)
- **Git** to clone and pull project updates
- **PostgreSQL** server accessible from the Docker host with database `db_LandingPage` created

Verify your Docker installation:
```bash
docker --version
docker compose version
```

---

## 2. Architecture Overview

BitmapLandingPage uses an optimized **multi-stage build** designed for minimal attack surface, high performance, and rapid startup:

```
┌─────────────────────────────────────────────────────────────┐
│ Stage 1: assets-builder (node:22-alpine)                    │
│ - Compiles Tailwind CSS v4 & JavaScript via Vite            │
│ - Outputs production bundles to /public/build               │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│ Stage 2: composer-builder (composer:2)                      │
│ - Installs production PHP dependencies (--no-dev)           │
│ - Generates optimized ClassLoader                           │
└──────────────────────────────┬──────────────────────────────┘
                               │
┌──────────────────────────────▼──────────────────────────────┐
│ Stage 3: Production Runtime (php:8.4-fpm-alpine)            │
│ ├─ Nginx (HTTP reverse proxy, gzip, security headers)       │
│ ├─ PHP 8.4 FPM (OPcache enabled for high throughput)        │
│ ├─ Supervisord (manages Nginx + PHP-FPM process lifecycle)  │
│ └─ Custom entrypoint.sh (auto-migration, config cache)      │
└─────────────────────────────────────────────────────────────┘
```

### Services Defined in `docker-compose.yml`:

| Service | Container Name | Description | Port |
| :--- | :--- | :--- | :--- |
| **`app`** | `bitmap_landing_page_app` | Main web server (Nginx + PHP-FPM + Supervisor) | `8624:80` |
| **`redis`** | `bitmap_landing_page_redis` | Redis 7 for caching, sessions, and queue backend | `6379:6379` |
| **`queue`** | `bitmap_landing_page_queue` | Background worker processing async jobs (`artisan queue:work`) | Internal |
| **`scheduler`** | `bitmap_landing_page_scheduler` | Task scheduler executing cron jobs (`artisan schedule:work`) | Internal |

---

## 3. Quick Start: Docker Compose (Recommended)

### Step 1: Prepare the PostgreSQL Database

Ensure the target PostgreSQL database `db_LandingPage` exists on your database server before launching the containers:

```sql
CREATE DATABASE "db_LandingPage";
```

### Step 2: Environment Configuration

Copy the pre-configured Docker environment file:

```bash
cp .env.docker.example .env
```

Review and adjust `.env` parameters if needed:
- `APP_URL`: Set to your domain or server IP (e.g., `http://your-server-ip:8624`).
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: Default connection uses PostgreSQL with `DB_DATABASE=db_LandingPage`.
- `APP_PORT`: Default is `8624` (change if port `8624` is occupied).

### Step 3: Generate Application Key

If you do not already have an `APP_KEY`, generate one:

```bash
# Using openssl (any machine):
echo "APP_KEY=base64:$(openssl rand -base64 32)"

# Or using PHP on host (if installed):
php artisan key:generate --show
```

Set the generated key in your `.env` file:
```dotenv
APP_KEY=base64:YourGeneratedKeyGoesHere...
```

> **Note:** `docker/entrypoint.sh` will also auto-generate an ephemeral `APP_KEY` via `php artisan key:generate --show` if `APP_KEY` is empty, but setting a persistent `APP_KEY` in `.env` is essential in production so sessions and encrypted cookies stay consistent across restarts and across `app`, `queue`, and `scheduler` containers.

### Step 4: Build & Launch Services

Run the build and start containers in detached mode:

```bash
docker compose up -d --build
```

Docker will:
1. Compile front-end assets with Vite.
2. Install optimized PHP vendor libraries (`--no-dev`).
3. Build the Alpine runtime image with `pdo_pgsql` and `redis`.
4. Start Redis and verify its healthcheck.
5. Launch `app`, automatically run pending PostgreSQL migrations (`RUN_MIGRATIONS=true`), cache configs/routes/views, and start Nginx + PHP-FPM.
6. Launch `queue` and `scheduler` containers.

For first-time setup on a fresh `db_LandingPage` database, seed the default admin account (`admin@nordvik.local` / `password`), portal settings, services, notices, and bookmarks (including Guest Wifi Pass):

```bash
docker compose exec app php artisan db:seed --force
```

### Step 5: Verify Deployment & Health Check

Check the status of running containers:
```bash
docker compose ps
```

All services (`app`, `redis`, `queue`, `scheduler`) should show `Up` or `healthy`.

Access the application in your browser:
- **Staff Portal:** [http://localhost:8624](http://localhost:8624)
- **Wi-Fi Guest Pass QR Page:** [http://localhost:8624/wifi](http://localhost:8624/wifi)
- **Admin Panel:** [http://localhost:8624/admin](http://localhost:8624/admin)
- **Health Check Endpoint:** [http://localhost:8624/up](http://localhost:8624/up)

---

## 4. Database & Artisan Management

All Laravel Artisan commands can be executed against the running `app` container:

### Run Migrations & Seeders
```bash
# Run migrations manually
docker compose exec app php artisan migrate --force

# Seed default admin user & Staff Hub data
docker compose exec app php artisan db:seed --force
```

### Cache Optimization (Production)
```bash
# Clear all caches (routes, config, views, events)
docker compose exec app php artisan optimize:clear

# Re-cache for production speed
docker compose exec app php artisan optimize
```

### Interactive Shell & Tinker
```bash
# Open interactive Laravel Tinker shell
docker compose exec app php artisan tinker

# Open bash shell inside app container
docker compose exec app bash
```

---

## 5. Standalone Single-Container Deployment

Because the image bundles both Nginx and PHP-FPM under Supervisord, you can run BitmapLandingPage as a standalone container on any Docker host without Compose.

### Building the Image

```bash
docker build -t bitmap-landing-page:latest .
```

### Running with External PostgreSQL

```bash
docker run -d \
  --name bitmap-landing-page \
  --restart unless-stopped \
  -p 8624:80 \
  -e APP_NAME=BitmapLandingPage \
  -e APP_ENV=production \
  -e APP_DEBUG=false \
  -e APP_KEY=base64:YOUR_32_BYTE_BASE64_KEY \
  -e APP_URL=http://your-server-ip:8624 \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=192.168.100.4 \
  -e DB_PORT=5432 \
  -e DB_DATABASE=db_LandingPage \
  -e DB_USERNAME=postgres \
  -e DB_PASSWORD=your_password \
  -e SESSION_DRIVER=database \
  -e CACHE_STORE=database \
  -e QUEUE_CONNECTION=database \
  -e RUN_MIGRATIONS=true \
  -v bitmap_landing_storage:/var/www/html/storage \
  bitmap-landing-page:latest
```

> **Note:** When running standalone without Redis, set `SESSION_DRIVER=database`, `CACHE_STORE=database`, and `QUEUE_CONNECTION=database`.

### Running Standalone with SQLite

For demo or lightweight standalone deployments without an external PostgreSQL database:

```bash
docker run -d \
  --name bitmap-landing-page-sqlite \
  --restart unless-stopped \
  -p 8624:80 \
  -e APP_NAME=BitmapLandingPage \
  -e APP_ENV=production \
  -e APP_KEY=base64:YOUR_32_BYTE_BASE64_KEY \
  -e DB_CONNECTION=sqlite \
  -e SESSION_DRIVER=database \
  -e CACHE_STORE=database \
  -e QUEUE_CONNECTION=sync \
  -e RUN_MIGRATIONS=true \
  -e SEED_DATABASE=true \
  -v bitmap_landing_storage:/var/www/html/storage \
  -v bitmap_landing_database:/var/www/html/database \
  bitmap-landing-page:latest
```

### Deploying to Cloud Providers

The container image is engineered for cloud platforms that inject a `$PORT` environment variable:

- **Google Cloud Run:**
  - Automatically adjusts Nginx listen port from `$PORT`.
  - Stateless execution; connect Cloud SQL or external PostgreSQL (`db_LandingPage`).
- **Railway / Fly.io / Heroku:**
  - Build directly from `Dockerfile`; port forwarding dynamically adapts.
- **Coolify / CapRover / Portainer:**
  - Import the repository and select `docker-compose.yml`.

---

## 6. Local Development with Live Reload

For active feature development with host file syncing and Vite HMR:

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up
```

Features in development mode:
- Live code syncing from host to `/var/www/html`.
- Port `5173` mapped for Vite live asset reloading (`docker compose exec app npm run dev -- --host`).
- Laravel optimization caching skipped (`SKIP_OPTIMIZATION=true`).
- `APP_ENV=local` and `APP_DEBUG=true`.

---

## 7. Environment Variables Reference

| Variable | Default | Description |
| :--- | :--- | :--- |
| `APP_NAME` | `BitmapLandingPage` | Application display name |
| `APP_ENV` | `production` | Environment (`production`, `local`, `testing`) |
| `APP_KEY` | *(empty)* | 32-byte base64 application encryption key |
| `APP_DEBUG` | `false` | Enable detailed stack traces (set `false` in production) |
| `APP_URL` | `http://localhost:8624` | Canonical application base URL |
| `DB_CONNECTION` | `pgsql` | Database driver (`pgsql`, `sqlite`, `mysql`) |
| `DB_HOST` | `192.168.100.4` | PostgreSQL server address |
| `DB_PORT` | `5432` | PostgreSQL server port |
| `DB_DATABASE` | `db_LandingPage` | Database name |
| `DB_USERNAME` | `postgres` | Database username |
| `DB_PASSWORD` | `Abitabit9898` | Database user password |
| `REDIS_HOST` | `redis` | Redis hostname or IP |
| `REDIS_PORT` | `6379` | Redis port |
| `CACHE_STORE` | `redis` | Cache backend driver |
| `SESSION_DRIVER` | `redis` | Session storage driver |
| `QUEUE_CONNECTION` | `redis` | Asynchronous queue driver |
| `RUN_MIGRATIONS` | `true` | Automatically run `migrate --force` upon container start |
| `SEED_DATABASE` | `false` | Automatically seed database on container start |
| `SKIP_OPTIMIZATION` | `false` | Skip `config:cache` / `route:cache` / `view:cache` / `event:cache` |
| `PORT` | `80` | Internal Nginx listening port (auto-set by Cloud Run / Railway) |

---

## 8. Maintenance, Logs, and Day-2 Operations

### Monitoring Logs in Real-Time

```bash
# View combined logs across all containers
docker compose logs -f

# View web application logs (Nginx + PHP-FPM)
docker compose logs -f app

# View queue worker processing logs
docker compose logs -f queue
```

### Updating to the Latest Version

```bash
# 1. Pull latest code from repository
git pull origin main

# 2. Rebuild and launch updated containers
docker compose build --pull
docker compose up -d

# 3. Clean up dangling images
docker image prune -f
```

### Stopping and Starting Services

```bash
# Stop containers (preserves volumes and database data)
docker compose down

# Start existing containers
docker compose up -d

# Completely wipe containers and volumes (CAUTION: deletes local Redis/storage volumes)
docker compose down -v
```

---

## 9. Troubleshooting & FAQs

### Q1: `curl: (7) Failed to connect to localhost port 8624`
- Check container status: `docker compose ps`.
- Check app startup logs: `docker compose logs app`.
- Ensure host port `8624` is not in use by another service: `lsof -i :8624` or `netstat -tlpn`.

### Q2: Database connection errors (`SQLSTATE[08006] Connection refused`) or missing database (`SQLSTATE[08006] database "db_LandingPage" does not exist`)
- Confirm the database `db_LandingPage` has been created on PostgreSQL (`CREATE DATABASE "db_LandingPage";`).
- Ensure PostgreSQL is listening on all network interfaces (`listen_addresses = '*'`) in `postgresql.conf`.
- Verify host authentication permissions in `pg_hba.conf` allow connections from the Docker subnet (`172.x.x.x`).
- Test database connectivity from inside the container:
  ```bash
  docker compose exec app php -r "new PDO('pgsql:host=192.168.100.4;port=5432;dbname=db_LandingPage', 'postgres', 'Abitabit9898'); echo 'Connected successfully';"
  ```

### Q3: Storage or Cache Permission Errors
The Dockerfile sets permissions for `www-data:www-data` on `/storage` and `/bootstrap/cache`. If volume mounts override permissions, reset them inside the container:
```bash
docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### Q4: Resetting Application Cache
```bash
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```
