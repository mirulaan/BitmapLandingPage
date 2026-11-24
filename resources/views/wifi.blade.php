@extends('layouts.app')

@section('content')
<main class="wifi-view-wrap">
  <div class="wifi-card card-panel">
    <div class="wifi-header">
      <div class="wifi-badge-icon" aria-hidden="true">📶</div>
      <h2>Wi-Fi Guest Pass</h2>
      <p class="wifi-sub">Scan the QR code with your phone or tablet camera to connect directly to the office network.</p>
    </div>

    <div class="wifi-qr-container">
      <div class="wifi-qr-frame">
        {!! $settings->wifi_qr_svg !!}
      </div>
      <div class="wifi-qr-hint">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
        <span>Point camera to connect</span>
      </div>
    </div>

    <div class="wifi-details">
      <div class="wifi-row">
        <div>
          <div class="wifi-label">Network Name (SSID)</div>
          <div class="wifi-val" id="wifi-ssid-val">{{ $settings->wifi_ssid }}</div>
        </div>
        <button class="copy-btn btn-sm" data-copy-target="wifi-ssid-val" type="button">Copy</button>
      </div>

      <div class="wifi-row">
        <div>
          <div class="wifi-label">Password</div>
          <div class="wifi-val">
            <code id="wifi-pass-val">{{ $settings->wifi_password }}</code>
          </div>
        </div>
        <button class="copy-btn btn-sm" data-copy-target="wifi-pass-val" type="button">Copy</button>
      </div>

      <div class="wifi-meta-row">
        <span class="wifi-meta-chip">Office: <b>{{ $settings->office_name }}</b></span>
      </div>
    </div>

    <div class="wifi-actions">
      <a href="{{ route('portal') }}" class="btn btn-ghost btn-sm">← Back to Staff Portal</a>
      <button type="button" class="btn btn-sm" onclick="window.print()">🖨️ Print Guest Pass</button>
    </div>
  </div>
</main>
@endsection
