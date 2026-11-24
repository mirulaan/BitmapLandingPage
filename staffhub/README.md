# Staff Hub — Laravel 13 + Tailwind CSS v4

A modern, responsive intranet portal for office teams featuring a "Warm Linen" daylight aesthetic and "Cozy Espresso" dark mode, ambient drifting wallpaper engine, service launcher, interactive announcements notice board, quick bookmarks, and an authenticated administration panel.

---

## Quick Start (Run Locally)

```bash
# 1. Start the web server
php artisan serve --port=8624
```

Then visit:
* **Staff Portal**: [http://localhost:8624](http://localhost:8624)
* **Admin Panel**: [http://localhost:8624/admin](http://localhost:8624/admin)

> [!TIP]
> For complete instructions, including first-time installation, Hot Module Replacement (HMR) with Vite, test execution, and default credentials, see **[Local Development Guide](../docs/RUN_LOCALLY.md)**.

---

## Default Admin Credentials

* **Email**: `admin@nordvik.local`
* **Password**: `password`

---

## Tech Stack

* **Runtime**: PHP 8.3+ (PHP 8.6 supported)
* **Framework**: Laravel 13
* **Database**: SQLite (`database/database.sqlite`)
* **Styling**: Tailwind CSS v4 + Custom "Warm Linen" CSS tokens
* **Frontend Build**: Vite 8 (`@tailwindcss/vite` + `laravel-vite-plugin`)
* **Interactive Engine**: Custom client JS (`resources/js/app.js`)
* **Testing**: PHPUnit / Pest PHP Feature tests

---

## Key Features

1. **Service Launcher**: Fast-access grid for internal web tools with live status indicators (`online`, `maintenance`, `offline`).
2. **Ambient Wallpaper**: Dual-layer crossfade background with subtle drift animations, live blur/veil sliders, and bokeh gradient fallback.
3. **Office Notice Board**: Categorized announcements (`IT Notice`, `General`, `Reminder`) with priority badges, pin-to-top flags, and public modal posting.
4. **Live Clock**: Tabular real-time British English clock and dynamic time-of-day greeting.
5. **Theme Engine**: Instant toggle between Warm Linen daylight mode and Cozy Espresso night mode.
6. **Quick Bookmarks**: Chip-styled direct links for daily bookmarks.
7. **Instant Search**: Real-time client-side filter with `/` keyboard shortcut.
8. **Admin Control Panel**: Auth-protected management of services, notices, bookmarks, portal identity, wallpaper config, and full JSON export/import/reset capabilities.
