<main id="view-admin" {{ !empty($hidden) ? 'hidden' : '' }}>
  <div class="admin-wrap">
    <div class="admin-intro">
      <h2>Admin Settings</h2>
      <p>Configure portal identity, manage launcher services, wallpapers, notices and quick bookmarks.</p>
    </div>

    @if(session('status'))
      <div style="background:var(--sage-soft);border:1px solid var(--sage);color:var(--sage);padding:10px 16px;border-radius:12px;margin-bottom:18px;font-size:0.85rem;font-weight:600;">
        {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div style="background:var(--danger-soft);border:1px solid var(--danger);color:var(--danger);padding:10px 16px;border-radius:12px;margin-bottom:18px;font-size:0.85rem;">
        <ul style="margin:0;padding-left:18px">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Identity & Helpdesk -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">🏛️</span> Portal Identity &amp; Helpdesk</h3>
      <p class="sec-desc">The name on the door and where to reach the friendly IT folks.</p>
      <form method="POST" action="{{ route('admin.settings.update') }}" id="form-settings">
        @csrf
        @method('PUT')
        <input type="hidden" name="wallpaper_enabled" value="{{ $settings->wallpaper_enabled ? '1' : '0' }}" id="hidden-wp-enabled">
        <input type="hidden" name="wallpaper_mode" value="{{ $settings->wallpaper_mode }}" id="hidden-wp-mode">
        <input type="hidden" name="wallpaper_interval" value="{{ $settings->wallpaper_interval }}" id="hidden-wp-interval">
        <input type="hidden" name="wallpaper_blur" value="{{ $settings->wallpaper_blur }}" id="hidden-wp-blur">
        <input type="hidden" name="wallpaper_veil" value="{{ $settings->wallpaper_veil }}" id="hidden-wp-veil">
        <input type="hidden" name="wallpaper_custom_urls" value="{{ is_array($settings->wallpaper_custom_urls) ? implode("\n", $settings->wallpaper_custom_urls) : '' }}" id="hidden-wp-custom">

        <div class="field-grid">
          <div class="field">
            <label for="adm-office">Office name</label>
            <input id="adm-office" name="office_name" type="text" maxlength="60" value="{{ old('office_name', $settings->office_name) }}" required>
          </div>
          <div class="field">
            <label for="adm-tagline">Tagline</label>
            <input id="adm-tagline" name="tagline" type="text" maxlength="90" value="{{ old('tagline', $settings->tagline) }}">
          </div>
          <div class="field">
            <label for="adm-email">Helpdesk email</label>
            <input id="adm-email" name="helpdesk_email" type="email" value="{{ old('helpdesk_email', $settings->helpdesk_email) }}" required>
          </div>
          <div class="field">
            <label for="adm-ext">Helpdesk extension</label>
            <input id="adm-ext" name="helpdesk_ext" type="text" maxlength="12" value="{{ old('helpdesk_ext', $settings->helpdesk_ext) }}">
          </div>
          <div class="field">
            <label for="adm-wifi-ssid">Guest Wi-Fi SSID</label>
            <input id="adm-wifi-ssid" name="wifi_ssid" type="text" maxlength="64" value="{{ old('wifi_ssid', $settings->wifi_ssid) }}" placeholder="e.g. Nordvik-Guest">
          </div>
          <div class="field">
            <label for="adm-wifi-pass">Guest Wi-Fi Password</label>
            <input id="adm-wifi-pass" name="wifi_password" type="text" maxlength="64" value="{{ old('wifi_password', $settings->wifi_password) }}" placeholder="Password">
          </div>
        </div>
        <div style="margin-top:14px;display:flex;justify-content:flex-end">
          <button type="submit" class="btn btn-sm">Save Identity &amp; Wi-Fi Settings</button>
        </div>
      </form>
    </section>

    <!-- Services -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">🧰</span> Service Launcher</h3>
      <p class="sec-desc">Add, edit, reorder or remove the launcher cards. URLs must start with http:// or https://.</p>
      <div class="row-list" id="admin-services">
        @forelse($services as $i => $s)
          <div class="row-item svc-row" data-id="{{ $s->id }}" data-index="{{ $i }}">
            <input class="icon-input" data-f="icon" value="{{ $s->icon }}" maxlength="4" aria-label="Icon">
            <input data-f="name" value="{{ $s->name }}" maxlength="40" aria-label="Name">
            <input class="url-input" data-f="url" value="{{ $s->url }}" placeholder="https://…" aria-label="URL">
            <input class="desc-input" data-f="desc" value="{{ $s->description }}" maxlength="90" aria-label="Description">
            @php
              $status = ($s->status === 'online') ? 'online' : 'offline';
            @endphp
            <span class="status-indicator badge {{ $status }}" title="Auto status via ping">● {{ ucfirst($status) }}</span>
            <div class="row-tools">
              <button type="button" class="tool-btn" data-act="up" title="Move up" {{ $i === 0 ? 'disabled' : '' }}>↑</button>
              <button type="button" class="tool-btn" data-act="down" title="Move down" {{ $i === count($services) - 1 ? 'disabled' : '' }}>↓</button>
              <button type="button" class="tool-btn danger" data-act="del" title="Delete">🗑</button>
            </div>
          </div>
        @empty
          <div class="empty-note">No services yet.</div>
        @endforelse
      </div>
      <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <button class="btn btn-ghost btn-sm" id="add-service-btn" type="button">＋ Add service</button>
        <button class="btn btn-ghost btn-sm" id="ping-services-btn" type="button">⟳ Ping services</button>
        <span class="status-note" id="ping-status-note" style="font-size:0.75rem;color:var(--muted)"></span>
      </div>
    </section>

    <!-- Ambient Wallpaper -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">🖼️</span> Ambient Wallpaper</h3>
      <p class="sec-desc">The soft, drifting backdrop behind everything. Sliders preview live.</p>
      
      <div class="toggle-row" style="margin-bottom:10px">
        <div>
          <div class="t-label">Wallpaper enabled</div>
          <div class="t-sub">When off, a warm bokeh gradient shows instead.</div>
        </div>
        <label class="switch">
          <input type="checkbox" id="adm-wp-enabled" {{ $settings->wallpaper_enabled ? 'checked' : '' }}>
          <span class="slider-ui"></span>
        </label>
      </div>

      <div class="field-grid" style="margin-bottom:12px">
        <div class="field">
          <label for="adm-wp-interval">Auto-refresh interval</label>
          <select id="adm-wp-interval">
            <option value="0" {{ $settings->wallpaper_interval == 0 ? 'selected' : '' }}>Off</option>
            <option value="30000" {{ $settings->wallpaper_interval == 30000 ? 'selected' : '' }}>Every 30 s</option>
            <option value="60000" {{ $settings->wallpaper_interval == 60000 ? 'selected' : '' }}>Every 1 min</option>
            <option value="300000" {{ $settings->wallpaper_interval == 300000 ? 'selected' : '' }}>Every 5 min</option>
            <option value="900000" {{ $settings->wallpaper_interval == 900000 ? 'selected' : '' }}>Every 15 min</option>
          </select>
        </div>
        <div class="field">
          <label for="adm-wp-source">Image source</label>
          <select id="adm-wp-source">
            <option value="picsum" {{ $settings->wallpaper_mode === 'picsum' ? 'selected' : '' }}>Lorem Picsum (random)</option>
            <option value="custom" {{ $settings->wallpaper_mode === 'custom' ? 'selected' : '' }}>Custom URL list</option>
          </select>
        </div>
      </div>

      <div class="field" id="custom-urls-field" style="margin-bottom:14px; {{ $settings->wallpaper_mode === 'custom' ? '' : 'display:none;' }}">
        <label for="adm-wp-custom">Custom image URLs (one per line)</label>
        <textarea id="adm-wp-custom" rows="3" placeholder="https://…&#10;https://…">{{ is_array($settings->wallpaper_custom_urls) ? implode("\n", $settings->wallpaper_custom_urls) : '' }}</textarea>
        <span class="note">Cycled in order on each refresh or shuffle.</span>
      </div>

      <div class="field" style="margin-bottom:8px">
        <label>Blur softness</label>
        <div class="range-row">
          <input type="range" id="adm-wp-blur" min="0" max="60" step="2" value="{{ $settings->wallpaper_blur }}">
          <span class="range-val" id="blur-val">{{ $settings->wallpaper_blur }}px</span>
        </div>
      </div>

      <div class="field" style="margin-bottom:14px">
        <label>Frosted veil opacity</label>
        <div class="range-row">
          <input type="range" id="adm-wp-veil" min="0" max="90" step="5" value="{{ $settings->wallpaper_veil }}">
          <span class="range-val" id="veil-val">{{ $settings->wallpaper_veil }}%</span>
        </div>
      </div>

      <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <button class="btn btn-sm" id="wp-refresh-btn" type="button">⟳ Refresh wallpaper now</button>
        <div class="status-line" style="flex:1;min-width:220px">
          <span class="dot idle" id="bg-status-dot"></span>
          <span id="bg-status-text">Starting…</span>
        </div>
        <button class="btn btn-ghost btn-sm" id="save-wp-btn" type="button">Save Wallpaper Settings</button>
      </div>
    </section>

    <!-- Notices -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">📎</span> Announcements</h3>
      <p class="sec-desc">Pin the important ones to the top of the board.</p>
      <div class="row-list" id="admin-notices">
        @php
          $catLabels = ['it' => 'IT Notice', 'general' => 'General', 'reminder' => 'Reminder'];
        @endphp
        @forelse($notices as $n)
          <div class="row-item notice-row" data-id="{{ $n->id }}">
            <div>
              <div class="nr-title">{{ $n->pinned ? '📌 ' : '' }}{{ $n->title }}</div>
              <div class="nr-meta">{{ $catLabels[$n->category] ?? ucfirst($n->category) }} · {{ $n->priority === 'high' ? 'High priority' : 'Normal' }} · {{ $n->created_at ? $n->created_at->diffForHumans() : '' }}</div>
            </div>
            <button type="button" class="tool-btn" data-act="pin" title="{{ $n->pinned ? 'Unpin' : 'Pin to top' }}">{{ $n->pinned ? '📌' : '📍' }}</button>
            <button type="button" class="tool-btn danger" data-act="del" title="Delete">🗑</button>
          </div>
        @empty
          <div class="empty-note">No notices yet.</div>
        @endforelse
      </div>
      <div style="margin-top:12px">
        <button class="btn btn-ghost btn-sm" id="adm-new-notice-btn" type="button">＋ New notice</button>
      </div>
    </section>

    <!-- Bookmarks -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">🔗</span> Quick Bookmarks</h3>
      <p class="sec-desc">Little chips for the links everyone reaches for daily.</p>
      <div class="row-list" id="admin-bookmarks">
        @forelse($bookmarks as $b)
          <div class="row-item bookmark-row" data-id="{{ $b->id }}">
            <input data-f="label" value="{{ $b->label }}" maxlength="40" aria-label="Label">
            <input data-f="url" value="{{ $b->url }}" placeholder="https://…" aria-label="URL">
            <button type="button" class="tool-btn danger" data-act="del" title="Delete">🗑</button>
          </div>
        @empty
          <div class="empty-note">No bookmarks yet.</div>
        @endforelse
      </div>
      <div style="margin-top:12px">
        <button class="btn btn-ghost btn-sm" id="add-bookmark-btn" type="button">＋ Add bookmark</button>
      </div>
    </section>

    <!-- Backup & Restore -->
    <section class="card-panel admin-section">
      <h3><span aria-hidden="true">🧷</span> Backup &amp; Restore</h3>
      <p class="sec-desc">Export the entire portal database to JSON or restore from a backup file.</p>
      <div class="backup-actions">
        <a href="{{ route('admin.export') }}" class="btn btn-sm" id="export-btn">⬇ Export config (.json)</a>
        <button class="btn btn-ghost btn-sm" id="import-btn" type="button">⬆ Import config</button>
        <button class="btn btn-danger btn-sm" id="reset-btn" type="button">Reset to defaults</button>
      </div>
      <form id="import-form" action="{{ route('admin.import') }}" method="POST" enctype="multipart/form-data" style="display:none">
        @csrf
        <input type="file" id="import-file" name="config_file" accept="application/json,.json">
      </form>
      <form id="reset-form" action="{{ route('admin.reset') }}" method="POST" style="display:none">
        @csrf
      </form>
      <div class="admin-foot-note">Importing replaces everything currently configured here.</div>
    </section>
  </div>
</main>