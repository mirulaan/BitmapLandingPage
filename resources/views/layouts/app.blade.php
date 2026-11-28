<!DOCTYPE html>
<html lang="en" data-theme="{{ $settings->theme ?? 'day' }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="#F9F6F0" media="(prefers-color-scheme: light)">
  <meta name="theme-color" content="#1B1512" media="(prefers-color-scheme: dark)">
  <title>{{ $settings->office_name ?? 'Staff Hub' }} — Office Intranet</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('bitmap_logo.ico') }}">
  <link rel="shortcut icon" href="{{ asset('bitmap_logo.ico') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #F9F6F0;
      --ink: #2C221E;
    }
    [data-theme="night"] {
      --bg: #1B1512;
      --ink: #F4ECE1;
    }
    html {
      background-color: #F9F6F0;
    }
    html[data-theme="night"] {
      background-color: #1B1512;
    }
    body {
      background-color: var(--bg);
      color: var(--ink);
      margin: 0;
    }
    [hidden] {
      display: none !important;
    }
  </style>
  <script>
    (function() {
      try {
        var saved = localStorage.getItem('staffhub-theme');
        var server = document.documentElement.dataset.theme;
        var t = saved || server;
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          t = 'night';
        }
        if (t) {
          document.documentElement.dataset.theme = t;
          if (t === 'night') {
            document.documentElement.style.backgroundColor = '#1B1512';
          }
        }
      } catch (e) {}
    })();
  </script>

  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('styles')
</head>
<body>

<div id="nav-loading-bar" class="nav-loading-bar" aria-hidden="true"></div>
<div id="nav-loading-pill" class="nav-loading-pill" role="status" aria-live="polite" hidden>
  <span class="nav-spinner" aria-hidden="true"></span>
  <span id="nav-loading-text">Loading…</span>
</div>

<!-- Dreamy blurred wallpaper -->
<div id="bg-wrap" aria-hidden="true">
  <div id="bg-layer-a" class="bg-layer"></div>
  <div id="bg-layer-b" class="bg-layer"></div>
  <div id="bg-bokeh"></div>
  <div id="bg-veil"></div>
</div>

<div class="shell">

  <!-- ================= HEADER ================= -->
  <header class="topbar">
    <div class="brand">
      <a href="{{ route('portal') }}" class="brand-mark" style="text-decoration:none">
        <img src="{{ asset('bitmap_logo.ico') }}" alt="{{ $settings->office_name ?? 'Staff Hub' }}" class="brand-logo-img">
      </a>
      <div class="brand-text">
        <div class="office-name" id="office-name" title="Office name">{{ $settings->office_name }}</div>
        <div class="tagline" id="tagline" title="Tagline">{{ $settings->tagline }}</div>
      </div>
    </div>
    <div class="clock-block">
      <div class="greeting" id="greeting">Good day</div>
      <div class="clock" id="clock">--:--:--</div>
      <div class="clock-date" id="clock-date"></div>
    </div>
    <div class="topbar-actions">
      <button class="icon-btn" id="shuffle-btn" title="Shuffle wallpaper" aria-label="Shuffle wallpaper" type="button">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><polyline points="21 3 21 9 15 9"/></svg>
      </button>
      <button class="icon-btn" id="theme-btn" title="Toggle day / night" aria-label="Toggle theme" type="button">☾</button>
      <nav class="view-switch" aria-label="View switcher">
        <a href="{{ route('portal') }}" class="switch-btn {{ (request()->routeIs('portal') && !request()->routeIs('admin.*')) ? 'active' : '' }}" data-view="portal">Staff Portal</a>
        <a href="{{ route('admin.index') }}" class="switch-btn {{ request()->routeIs('admin.*') ? 'active' : '' }}" data-view="admin">Admin</a>
      </nav>
      @auth
        <form method="POST" action="{{ route('logout') }}" style="display:inline">
          @csrf
          <button type="submit" class="btn btn-quiet btn-sm" title="Sign out" style="font-size:0.75rem;">Sign out</button>
        </form>
      @endauth
    </div>
  </header>

  @yield('content')

</div>

@include('components.notice-modal')

<div id="toast" role="status" aria-live="polite"></div>

<script>
window.__INITIAL_STATE__ = {
  office: @json($settings->office_name),
  tagline: @json($settings->tagline),
  offices: @json($settings->locations()),
  theme: @json($settings->theme),
  wallpaper: {
    enabled: @json((bool)$settings->wallpaper_enabled),
    mode: @json($settings->wallpaper_mode),
    customUrls: @json($settings->wallpaper_custom_urls ?? []),
    interval: @json($settings->wallpaper_interval),
    blur: @json($settings->wallpaper_blur),
    veil: @json($settings->wallpaper_veil)
  },
  routes: {
    portal: @json(route('portal')),
    admin: @json(route('admin.index')),
    postNotice: @json(route('notices.public_store')),
  },
  isAdmin: @json(auth()->check()),
  csrfToken: @json(csrf_token())
};
</script>

@stack('scripts')

</body>
</html>
