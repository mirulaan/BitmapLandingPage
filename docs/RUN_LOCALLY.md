# Staff Hub — Local Development Guide

This guide explains how to set up, run, and manage **Staff Hub** on your local machine.

---

## Prerequisites

Ensure you have the following installed on your machine:
* **PHP 8.3+** (PHP 8.5/8.6 supported) with SQLite and cURL extensions
* **Composer 2.x**
* **Node.js 20+** & **npm**

---

## 1. First-Time Setup (New Machine / Clone)

If you are cloning this repository fresh or setting it up on a new machine:

```bash
# 1. Enter the Laravel application folder
cd staffhub

# 2. Install PHP backend dependencies
composer install

# 3. Install frontend JavaScript & Tailwind CSS v4 dependencies
npm install

# 4. Copy environment file & generate encryption key
cp .env.example .env
php artisan key:generate

# 5. Run database migrations and seed default data
php artisan migrate:fresh --seed

# 6. Build production frontend assets
npm run build
```

---

## 2. Running Locally

### Quick Start (Server Only)
If you already ran `npm run build`, you only need to run the Laravel server:

```bash
cd staffhub
php artisan serve --port=8624
```

Access the portal in your browser:
* **Staff Portal**: [http://localhost:8624](http://localhost:8624)
* **Admin Panel**: [http://localhost:8624/admin](http://localhost:8624/admin)

---

### Active Development Mode (With Hot Reload)

When you are editing Blade views, CSS, or JavaScript assets and want instant Hot Module Replacement (HMR):

#### Option A — Single Command (Recommended)
Laravel 13 comes with a concurrent runner that starts both the backend PHP server and the Vite dev server together:

```bash
cd staffhub
composer run dev
```

#### Option B — Separate Terminals
* **Terminal 1** (Laravel Web Server):
  ```bash
  cd staffhub
  php artisan serve --port=8624
  ```

* **Terminal 2** (Vite Hot Reload):
  ```bash
  cd staffhub
  npm run dev
  ```

---

## 3. Admin Access & Default Credentials

The admin panel is protected by Laravel session authentication.

* **URL**: [http://localhost:8624/login](http://localhost:8624/login) (or click the **Admin** switch button on the portal header)
* **Default Admin Email**: `admin@nordvik.local`
* **Default Password**: `password`

---

## 4. Useful Development Commands

| Command | Description |
|---|---|
| `php artisan serve --port=8624` | Starts the local HTTP development server |
| `npm run dev` | Starts the Vite asset server with hot module replacement |
| `npm run build` | Compiles optimized CSS and JS assets to `public/build/` |
| `php artisan test` | Runs the full automated Pest / PHPUnit test suite |
| `php artisan migrate:fresh --seed` | Wipes the SQLite database and reseeds all default cards, notices, and settings |
| `php artisan route:list` | Lists all registered HTTP and API routes |

---

## 5. Verification & Testing

To verify that all features, authentication rules, and API endpoints are working properly:

```bash
cd staffhub
php artisan test
```

Expected result:
```text
Pass: 15, Assertions: 59
```

---

## 6. Architecture & File Structure

* **Main Layout**: `resources/views/layouts/app.blade.php`
* **Staff Portal View**: `resources/views/portal.blade.php`
* **Admin Control Panel**: `resources/views/admin.blade.php`
* **Login View**: `resources/views/login.blade.php`
* **Notice Modal Component**: `resources/views/components/notice-modal.blade.php`
* **Tailwind & Custom CSS**: `resources/css/app.css`
* **Client JS Engine**: `resources/js/app.js` (Clock, Theme toggle, Dual-layer wallpaper crossfade, Search filter, Notice modal)
* **Controllers**: `app/Http/Controllers/` (`PortalController`, `AdminController`, `AuthController`)
* **Database**: `database/database.sqlite`
