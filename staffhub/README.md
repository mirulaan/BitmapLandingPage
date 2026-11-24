# Staff Hub — Laravel 13 + Tailwind CSS v4

> Full-stack intranet portal and service launcher for workplace teams. Rebuilt from the ground up on Laravel 13, Tailwind CSS v4, and SQLite while preserving the exact "Warm Linen" daylight and "Cozy Espresso" night aesthetic.

---

## 🌟 Key Features

* **Service Launcher**: Grid of company services (Nextcloud, ONLYOFFICE, Office NAS, etc.) with real-time status indicators (`Online`, `Maintenance`, `Offline`).
* **Ambient Wallpaper Engine**: Dual-layer crossfade transitions with drift animations, live blur and frosted veil sliders, and multi-radial bokeh gradient fallback.
* **Notice Board**: Filterable announcements with categories (`IT Notice`, `General`, `Reminder`), priority chips, pin flags, and interactive modal publishing.
* **Live Clock & Greeting**: Dynamic tabular British English clock with time-sensitive greeting (`☀️ Good morning`, `🌤 Good afternoon`, `🌙 Late shift`).
* **Instant Global Search**: Filter cards and bookmarks in real time with `/` keyboard navigation.
* **Quick Bookmarks & IT Helpdesk**: One-click chip links and copy-to-clipboard for internal support.
* **Admin Control Panel**: Auth-protected management of services, announcements, bookmarks, wallpaper settings, and JSON backup/restore.

---

## 🚀 Running Locally

```bash
# 1. Start the Laravel web server
php artisan serve --port=8624
```

* **Staff Portal**: [http://localhost:8624](http://localhost:8624)
* **Admin Panel**: [http://localhost:8624/admin](http://localhost:8624/admin)

For active frontend development with Hot Module Replacement (HMR):
```bash
composer run dev
```

### Default Admin Credentials
* **Email**: `admin@nordvik.local`
* **Password**: `password`

> [!TIP]
> For complete instructions on first-time installation, testing, and troubleshooting, read the **[Local Development Guide](../docs/RUN_LOCALLY.md)**.

---

## 🧪 Testing

```bash
php artisan test
```

All 15 automated Feature tests cover portal rendering, admin authentication, CRUD operations for services, notices, bookmarks, settings, and backup/restore roundtrips.
