<main id="view-portal" {{ !empty($hidden) ? 'hidden' : '' }}>
  <div class="search-row">
    <div class="search-wrap">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      <input id="search-input" type="search" placeholder="Search services and bookmarks…" autocomplete="off" aria-label="Search services and bookmarks">
      <span class="kbd-hint">/</span>
    </div>
    <span class="search-count" id="search-count"></span>
  </div>

  <div class="main-grid">
    <div class="col-main">
      <section aria-label="Service launcher">
        <div class="section-head">
          <h2>Service Launcher</h2>
        </div>
        <div class="services-grid" id="services-grid">
          @forelse($services as $s)
            @php
              $safeUrl = (filter_var($s->url, FILTER_VALIDATE_URL) && in_array(parse_url($s->url, PHP_URL_SCHEME), ['http', 'https'])) ? $s->url : '#';
              $status = ($s->status === 'online') ? 'online' : 'offline';
              $statusLabels = ['online' => 'Online', 'offline' => 'Offline'];
            @endphp
            <a class="svc" data-id="{{ $s->id }}" data-name="{{ strtolower($s->name) }}" data-desc="{{ strtolower($s->description) }}" data-status="{{ $status }}" href="{{ $safeUrl }}" target="_blank" rel="noopener">
              <div class="svc-top">
                <span class="svc-icon">{{ $s->icon ?: '🔧' }}</span>
                <span class="badge {{ $status }}">{{ $statusLabels[$status] }}</span>
              </div>
              <div><div class="svc-name">{{ $s->name }}</div></div>
              <div class="svc-desc">{{ $s->description }}</div>
              <span class="svc-go">Open ↗</span>
            </a>
          @empty
            <div class="empty-note" style="grid-column:1/-1">No services configured yet.</div>
          @endforelse
        </div>
      </section>

      <section class="card-panel bookmarks-panel" aria-label="Quick bookmarks">
        <div class="section-head" style="margin:0 0 2px">
          <h2>Quick Bookmarks</h2>
        </div>
        <div class="chips" id="bookmarks-chips">
          @forelse($bookmarks as $b)
            @php
              $isWifi = strtolower(trim($b->label)) === 'guest wifi pass' || strtolower(trim($b->label)) === 'wi-fi guest pass' || $b->url === '/wifi';
              $isInternal = str_starts_with($b->url, '/') || str_starts_with($b->url, '#');
              $safeUrl = $isWifi ? route('wifi') : ($isInternal ? $b->url : ((filter_var($b->url, FILTER_VALIDATE_URL) && in_array(parse_url($b->url, PHP_URL_SCHEME), ['http', 'https'])) ? $b->url : '#'));
            @endphp
            <a class="chip-link {{ $isWifi ? 'chip-wifi' : '' }}" data-id="{{ $b->id }}" data-label="{{ strtolower($b->label) }}" data-url="{{ strtolower($b->url) }}" href="{{ $safeUrl }}" target="{{ $isInternal || $isWifi ? '_self' : '_blank' }}" rel="noopener" @if($isInternal || $isWifi) data-nav-loading="Opening {{ $b->label }}…" @endif>
              <span class="dot {{ $isWifi ? 'dot-wifi' : '' }}" aria-hidden="true"></span><span class="chip-label">{{ $b->label }}</span>
            </a>
          @empty
            <div class="empty-note" style="width:100%">No bookmarks yet.</div>
          @endforelse
        </div>
      </section>

      <section class="card-panel locations-panel" aria-label="Office locations">
        <div class="section-head" style="margin:0 0 2px">
          <h2>Our Offices</h2>
        </div>
        <div class="loc-list">
          @foreach($settings->locations() as $loc)
            @if($loc['name'] !== '' || $loc['address'] !== '')
              <a class="loc-item"
                 href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode(trim($loc['name'].' '.$loc['address'])) }}"
                 data-map-name="{{ $loc['name'] }}"
                 data-map-address="{{ $loc['address'] }}"
                 target="_blank"
                 rel="noopener">
                <span class="loc-pin" aria-hidden="true">📍</span>
                <span class="loc-text">
                  <span class="loc-name">{{ $loc['name'] }}</span>
                  <span class="loc-addr">{{ $loc['address'] }}</span>
                </span>
                <span class="loc-go">Map ↗</span>
              </a>
            @endif
          @endforeach
        </div>
      </section>
    </div>

    <aside class="card-panel notice-panel" aria-label="Office notice board">
      <div class="notice-head">
        <h2>Notice Board</h2>
        <button class="btn btn-sm" id="post-btn" type="button">＋ Post</button>
      </div>
      <div class="cat-tabs" id="cat-tabs">
        <button class="cat-tab active" data-cat="all" type="button">All</button>
        <button class="cat-tab" data-cat="it" type="button">IT Notice</button>
        <button class="cat-tab" data-cat="general" type="button">General</button>
        <button class="cat-tab" data-cat="reminder" type="button">Reminder</button>
      </div>
      <div class="notices-list" id="notices-list">
        @php
          $catLabels = ['it' => 'IT Notice', 'general' => 'General', 'reminder' => 'Reminder'];
        @endphp
        @forelse($notices as $n)
          <article class="notice {{ $n->pinned ? 'pinned' : '' }}" data-id="{{ $n->id }}" data-category="{{ $n->category }}">
            <div class="notice-top">
              <span class="notice-title">{{ $n->title }}</span>
              <span class="chip-cat {{ $n->category }}">{{ $catLabels[$n->category] ?? ucfirst($n->category) }}</span>
              <span class="chip-prio {{ $n->priority }}">{{ $n->priority === 'high' ? 'High' : 'Normal' }}</span>
            </div>
            <div class="notice-body">{{ $n->body }}</div>
            <div class="notice-meta">
              @if($n->pinned)
                <span class="pin-flag">📌 Pinned</span>
              @endif
              <span>{{ $n->created_at ? $n->created_at->diffForHumans(null, true) . ' ago' : 'recently' }}</span>
            </div>
            @auth
              <button class="notice-del" title="Delete notice" aria-label="Delete notice" data-del-notice="{{ $n->id }}" type="button">✕</button>
            @endauth
          </article>
        @empty
          <div class="empty-note">No notices here yet.<br>The quiet kind of Monday.</div>
        @endforelse
      </div>
    </aside>
  </div>

  <div class="foot">Brewed with <span class="heart">♥</span> by the office IT crew — press <b>/</b> to search, anywhere.</div>
</main>
