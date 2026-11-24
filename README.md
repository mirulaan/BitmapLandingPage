# Staff Hub (Bitmap Landing Page)

> A modern, cozy office intranet portal and service launcher designed for workplace teams. Built with **Laravel 13 + Tailwind CSS v4** and SQLite, while preserving the exact "Warm Linen" daylight and "Cozy Espresso" night aesthetic.

---

## 🌟 Overview

**Staff Hub** provides a centralized, calm starting point for an office workday. Employees can launch internal tools, read categorized announcements, reach the IT helpdesk, and access daily bookmarks—accompanied by an ambient drifting wallpaper engine and seamless theme switching.

The repository includes:
1. **Full-Stack Web Application (Root)**: Full-featured **Laravel 13 + Tailwind CSS v4** system backed by SQLite, Eloquent models, session-based authentication for the admin control panel, and public JSON API endpoints.
2. **Single-File Standalone Edition (`index.html`)**: 100% self-contained HTML/CSS/JS prototype with zero build steps or server dependencies. Runs directly in any browser with client-side `localStorage`.

---

## 🎨 Visual Design & Theming

Designed around an editorial aesthetic combining serif headlines (*Fraunces*) with clear sans-serif interface text (*Plus Jakarta Sans*):

| Theme | Aesthetic | Background | Accent | Primary Use |
|---|---|---|---|---|
| **Day Mode** | **"Warm Linen"** | `#F9F6F0` | `#C86D44` (Terracotta) | Daylight productivity |
| **Night Mode** | **"Cozy Espresso"** | `#1B1512` | `#D98E5C` (Warm Amber) | Low-light / late shifts |

### Design Highlights
* **Ambient Wallpaper Engine**: Dual-layer crossfade transitions with subtle drifting keyframe animations, live blur & veil opacity controls, and warm multi-radial bokeh gradient fallback.
* **Live Dynamic Clock**: Tabular British English clock, full date formatting, and time-aware greetings (`🌙 Late shift`, `☀️ Good morning`, `🌤 Good afternoon`, `🌙 Good evening`).
* **Micro-interactions**: Smooth 0.4s theme transitions, card elevation lifts, toast alerts, and modal dialogs.
* **Instant Global Search**: Filter services and bookmarks in real time with the `/` shortcut key.

---

## 🚀 Features Breakdown

### 🏠 Staff Portal
* **Service Launcher**: Interactive card grid for internal services (e.g. Nextcloud, ONLYOFFICE, Office NAS, Project Tracker, Web Mail) with real-time status badges (`Online`, `Maintenance`, `Offline`).
* **Notice Board**: Categorized office announcements (`IT Notice`, `General`, `Reminder`) with priority chips (`High`, `Normal`), pin flags, and relative timestamps.
* **Public Notice Submission**: Built-in modal allowing any employee to publish quick notices.
* **Quick Bookmarks**: Fast chip links for guest Wi-Fi passes, desk booking, printer queues, leave requests, and expenses.
* **IT Helpdesk**: Instant one-click clipboard copy for IT support email and phone extension.

### ⚙️ Admin Control Panel (Auth-Protected)
* **Portal Identity**: Update office name, tagline, and IT helpdesk contact info.
* **Service Management**: Full CRUD for launcher cards (icon, title, URL, description, status) with reordering (↑ / ↓).
* **Ambient Wallpaper Settings**: Toggle wallpaper, choose image source (Lorem Picsum or custom URLs), and adjust blur & frosted veil opacity.
* **Announcements Admin**: Pin/unpin and delete notices.
* **Bookmarks Admin**: Add, edit, and delete bookmark chips.
* **Backup & Restore**: Export full portal database to JSON, import from backup, or reset to factory defaults.

---

## 🏗️ Tech Stack Comparison

| Component | Standalone (`index.html`) | Full-Stack Platform (Root) |
|---|---|---|
| **Runtime** | Web Browser | PHP 8.3+ (PHP 8.6 ready) |
| **Backend Framework** | None | Laravel 13.x |
| **Styling** | Embedded CSS Custom Properties | Tailwind CSS v4 + Custom Theme Tokens |
| **Build System** | None | Vite 8 + `@tailwindcss/vite` |
| **Database** | Browser `localStorage` | SQLite (`database/database.sqlite`) |
| **Authentication** | Open / Client Hash Routing | Laravel Session Auth + Sanctum API |
| **API Endpoints** | None | RESTful JSON API (`/api/services`, `/api/notices`, etc.) |
| **Testing** | Manual Verification | Automated Feature Tests (15 tests, 59 assertions) |

---

## 📂 Project Structure

```
BitmapLandingPage/
├── app/                         # Laravel Controllers, Models & Providers
│   ├── Http/Controllers/        # PortalController, AdminController, AuthController
│   └── Models/                  # PortalSetting, Service, Notice, Bookmark, User
├── database/
│   ├── migrations/              # Schema migrations for all entities
│   ├── seeders/                 # StaffHubSeeder with default office data
│   └── database.sqlite          # SQLite database
├── resources/
│   ├── css/app.css              # Tailwind CSS v4 + Warm Linen design system
│   ├── js/app.js                # Wallpaper engine, clock, search & AJAX modal
│   └── views/                   # Blade templates (layouts, portal, admin, login)
├── routes/                      # web.php and api.php
├── tests/Feature/               # Automated test suite (15 passed tests)
├── public/                      # Compiled Vite assets and entrypoint (index.php)
├── docs/
│   └── RUN_LOCALLY.md           # Comprehensive local development and testing guide
├── index.html                   # Original standalone single-file prototype (60 KB)
├── project-analysis.md          # Architectural analysis of the original prototype
├── staffhub-migration-plan.md   # Full-stack migration specifications
├── composer.json                # PHP dependencies
├── package.json                 # Node dependencies
└── vite.config.js               # Vite build configuration
```

---

## ⚡ Quick Start (Run Locally)

```bash
# 1. Start server directly from root
php artisan serve --port=8624
```

* **Staff Portal**: [http://localhost:8624](http://localhost:8624)
* **Admin Login**: [http://localhost:8624/admin](http://localhost:8624/admin)
  * **Email**: `admin@nordvik.local`
  * **Password**: `password`

### Active Frontend Development (Hot Reload)
```bash
composer run dev
```

For full setup instructions, asset compilation with Vite, and running tests, refer to **[docs/RUN_LOCALLY.md](docs/RUN_LOCALLY.md)**.
