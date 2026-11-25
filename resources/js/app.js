/**
 * Staff Hub — Interactive Client Engine
 */

(() => {
  'use strict';

  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const initState = window.__INITIAL_STATE__ || {};
  const csrfToken = initState.csrfToken || $('meta[name="csrf-token"]')?.getAttribute('content') || '';

  // Local state cache
  let state = {
    office: initState.office || 'Nordvik & Co. — Staff Hub',
    tagline: initState.tagline || 'A calm place to start the workday.',
    helpdesk: initState.helpdesk || { email: 'helpdesk@nordvik.local', ext: 'x4210' },
    theme: initState.theme || localStorage.getItem('staffhub-theme') || null,
    wallpaper: initState.wallpaper || { enabled: true, mode: 'picsum', customUrls: [], interval: 300000, blur: 28, veil: 55 },
  };

  /* ---------------- Helpers ---------------- */
  function safeUrl(u) {
    try {
      const url = new URL(String(u).trim(), location.href);
      return (url.protocol === 'http:' || url.protocol === 'https:') ? url.href : null;
    } catch {
      return null;
    }
  }

  function ago(ts) {
    const s = (Date.now() - ts) / 1000;
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s / 60) + 'm ago';
    if (s < 86400) return Math.floor(s / 3600) + 'h ago';
    return Math.floor(s / 86400) + 'd ago';
  }

  let toastTimer;
  function toast(msg) {
    const t = $('#toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => t.classList.remove('show'), 2400);
  }

  async function copyText(text) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.cssText = 'position:fixed;opacity:0';
      document.body.appendChild(ta);
      ta.select();
      let ok = false;
      try { ok = document.execCommand('copy'); } catch {}
      ta.remove();
      return ok;
    }
  }

  async function apiFetch(url, options = {}) {
    options.headers = {
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      ...(options.headers || {})
    };
    return fetch(url, options);
  }

  /* ---------------- Theme ---------------- */
  function currentTheme() {
    return state.theme || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'night' : 'day');
  }

  function applyTheme() {
    const t = currentTheme();
    document.documentElement.dataset.theme = t;
    const btn = $('#theme-btn');
    if (btn) {
      btn.textContent = t === 'day' ? '☾' : '☀';
      btn.title = t === 'day' ? 'Switch to Cozy Espresso night' : 'Switch to Warm Linen day';
    }
  }

  const themeBtn = $('#theme-btn');
  if (themeBtn) {
    themeBtn.addEventListener('click', () => {
      state.theme = currentTheme() === 'day' ? 'night' : 'day';
      localStorage.setItem('staffhub-theme', state.theme);
      applyTheme();
    });
  }

  /* ---------------- Clock ---------------- */
  function tickClock() {
    const clockEl = $('#clock');
    if (!clockEl) return;
    const now = new Date();
    clockEl.textContent = now.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const dateEl = $('#clock-date');
    if (dateEl) dateEl.textContent = now.toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    const greetingEl = $('#greeting');
    if (greetingEl) {
      const h = now.getHours();
      greetingEl.textContent = h < 5 ? '🌙 Late shift' : h < 12 ? '☀️ Good morning' : h < 18 ? '🌤 Good afternoon' : '🌙 Good evening';
    }
  }
  setInterval(tickClock, 1000);
  tickClock();

  /* ---------------- Wallpaper engine ---------------- */
  const layerA = $('#bg-layer-a');
  const layerB = $('#bg-layer-b');
  const bg = { showing: 'a', timer: null, customIdx: -1, status: 'idle' };

  function applyBgVars() {
    const r = document.documentElement.style;
    r.setProperty('--bg-blur', (state.wallpaper.blur || 28) + 'px');
    r.setProperty('--bg-veil-a', ((state.wallpaper.veil || 55) / 100).toFixed(2));
  }

  function setBgStatus(kind, text) {
    bg.status = kind;
    const dot = $('#bg-status-dot');
    const txt = $('#bg-status-text');
    if (dot) dot.className = 'dot ' + (kind === 'live' ? 'online' : kind === 'fallback' ? 'maintenance' : 'idle');
    if (txt) txt.textContent = text;
  }

  function bgUrl() {
    if (state.wallpaper.mode === 'custom' && Array.isArray(state.wallpaper.customUrls)) {
      const urls = state.wallpaper.customUrls.filter(Boolean);
      if (urls.length) {
        bg.customIdx = (bg.customIdx + 1) % urls.length;
        return urls[bg.customIdx];
      }
    }
    return 'https://picsum.photos/1920/1080?random=' + Date.now() + '-' + Math.floor(Math.random() * 1e4);
  }

  function showBokeh(msg) {
    document.body.classList.add('bg-bokeh');
    if (layerA) layerA.style.opacity = 0;
    if (layerB) layerB.style.opacity = 0;
    setBgStatus('fallback', msg);
  }

  function refreshWallpaper(manual = false) {
    applyBgVars();
    if (!state.wallpaper.enabled) {
      document.body.classList.add('bg-bokeh');
      if (layerA) layerA.style.opacity = 0;
      if (layerB) layerB.style.opacity = 0;
      setBgStatus('idle', 'Wallpaper is off — warm bokeh gradient showing.');
      return;
    }
    document.body.classList.remove('bg-bokeh');
    const url = bgUrl();
    const srcName = state.wallpaper.mode === 'custom' ? 'custom URL' : 'Lorem Picsum';
    setBgStatus('idle', 'Loading fresh image…');
    const shuffleBtn = $('#shuffle-btn');
    if (manual && shuffleBtn) shuffleBtn.classList.add('spinning');

    const img = new Image();
    let done = false;
    const finish = fn => {
      if (done) return;
      done = true;
      clearTimeout(timer);
      if (shuffleBtn) shuffleBtn.classList.remove('spinning');
      fn();
    };
    const timer = setTimeout(() => finish(() => showBokeh('Image timed out (8 s) — showing warm bokeh instead.')), 8000);

    img.onload = () => finish(() => {
      if (!layerA || !layerB) return;
      const show = bg.showing === 'a' ? layerB : layerA;
      const hide = bg.showing === 'a' ? layerA : layerB;
      show.style.backgroundImage = `url("${url}")`;
      void show.offsetWidth;
      show.style.opacity = 1;
      hide.style.opacity = 0;
      bg.showing = bg.showing === 'a' ? 'b' : 'a';
      try {
        sessionStorage.setItem('staffhub-last-wallpaper', url);
      } catch (e) {}
      const t = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
      setBgStatus('live', `Live · ${srcName} · updated ${t}`);
    });

    img.onerror = () => finish(() => showBokeh(`${srcName} unreachable — warm bokeh fallback active.`));
    img.src = url;
  }

  function restartBgTimer() {
    clearInterval(bg.timer);
    if (state.wallpaper.enabled && state.wallpaper.interval > 0) {
      bg.timer = setInterval(() => refreshWallpaper(false), state.wallpaper.interval);
    }
  }

  const shuffleBtn = $('#shuffle-btn');
  if (shuffleBtn) shuffleBtn.addEventListener('click', () => refreshWallpaper(true));

  const wpRefreshBtn = $('#wp-refresh-btn');
  if (wpRefreshBtn) wpRefreshBtn.addEventListener('click', () => refreshWallpaper(true));

  /* ---------------- Search & Filter (Portal) ---------------- */
  let searchQuery = '';
  function filterCards() {
    const q = searchQuery.toLowerCase();
    const services = $$('.services-grid .svc');
    let matchSvc = 0;
    services.forEach(card => {
      const name = (card.dataset.name || '').toLowerCase();
      const desc = (card.dataset.desc || '').toLowerCase();
      const status = (card.dataset.status || '').toLowerCase();
      const visible = !q || name.includes(q) || desc.includes(q) || status.includes(q);
      card.style.display = visible ? '' : 'none';
      if (visible) matchSvc++;
    });

    const bookmarks = $$('#bookmarks-chips .chip-link');
    let matchBm = 0;
    bookmarks.forEach(chip => {
      const label = (chip.dataset.label || '').toLowerCase();
      const url = (chip.dataset.url || '').toLowerCase();
      const visible = !q || label.includes(q) || url.includes(q);
      chip.style.display = visible ? '' : 'none';
      if (visible) matchBm++;
    });

    const countEl = $('#search-count');
    if (countEl) {
      if (!q) {
        countEl.textContent = '';
      } else {
        countEl.textContent = `${matchSvc} service${matchSvc === 1 ? '' : 's'} · ${matchBm} bookmark${matchBm === 1 ? '' : 's'}`;
      }
    }
  }

  document.addEventListener('input', e => {
    if (e.target && e.target.id === 'search-input') {
      searchQuery = e.target.value.trim();
      filterCards();
    }
  });

  document.addEventListener('keydown', e => {
    const typing = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName) || document.activeElement.isContentEditable;
    if (e.key === '/' && !typing) {
      e.preventDefault();
      const si = $('#search-input');
      if (si) si.focus();
    }
    if (e.key === 'Escape') {
      const modal = $('#notice-modal');
      if (modal && modal.classList.contains('open')) {
        closeModal();
      } else if (document.activeElement === $('#search-input')) {
        const si = $('#search-input');
        if (si) {
          si.value = '';
          searchQuery = '';
          filterCards();
          si.blur();
        }
      }
    }
  });

  /* ---------------- Notice Tabs & Deletion ---------------- */
  document.addEventListener('click', e => {
    const tab = e.target.closest('#cat-tabs .cat-tab');
    if (!tab) return;
    const cat = tab.dataset.cat;
    $$('#cat-tabs .cat-tab').forEach(t => t.classList.toggle('active', t === tab));
    $$('#notices-list .notice').forEach(article => {
      const itemCat = article.dataset.category;
      article.style.display = (cat === 'all' || itemCat === cat) ? '' : 'none';
    });
  });

  // Delete notice directly from portal (if auth)
  document.addEventListener('click', async e => {
    const delBtn = e.target.closest('[data-del-notice]');
    if (!delBtn) return;
    const id = delBtn.dataset.delNotice;
    if (!confirm('Delete this notice?')) return;
    try {
      const res = await apiFetch(`/admin/notices/${id}`, { method: 'DELETE' });
      if (res.ok) {
        delBtn.closest('.notice')?.remove();
        toast('Notice removed.');
      } else {
        toast('Failed to delete notice.');
      }
    } catch {
      toast('Network error deleting notice.');
    }
  });

  /* ---------------- Helpdesk copy ---------------- */
  document.addEventListener('click', async e => {
    const copyBtn = e.target.closest('.copy-btn');
    if (!copyBtn) return;
    const target = $('#' + copyBtn.dataset.copyTarget);
    if (!target) return;
    const ok = await copyText(target.textContent.trim());
    toast(ok ? 'Copied to clipboard ☕' : 'Copy failed — select it manually.');
  });

  /* ---------------- Notice Modal ---------------- */
  function openModal() {
    const modal = $('#notice-modal');
    if (!modal) return;
    $('#nm-title').value = '';
    $('#nm-body').value = '';
    $('#nm-cat').value = 'general';
    $('#nm-prio').value = 'normal';
    $('#nm-pin').checked = false;
    modal.classList.add('open');
    setTimeout(() => $('#nm-title')?.focus(), 60);
  }

  function closeModal() {
    const modal = $('#notice-modal');
    if (modal) modal.classList.remove('open');
  }

  document.addEventListener('click', e => {
    if (e.target.closest('#post-btn') || e.target.closest('#adm-new-notice-btn')) {
      openModal();
    }
  });

  const nmCancel = $('#nm-cancel');
  if (nmCancel) nmCancel.addEventListener('click', closeModal);

  const noticeModal = $('#notice-modal');
  if (noticeModal) {
    noticeModal.addEventListener('click', e => {
      if (e.target === e.currentTarget) closeModal();
    });
  }

  const nmSave = $('#nm-save');
  if (nmSave) {
    nmSave.addEventListener('click', async () => {
      const title = $('#nm-title').value.trim();
      if (!title) {
        $('#nm-title').focus();
        toast('Give the notice a title first.');
        return;
      }
      const payload = {
        title,
        body: $('#nm-body').value.trim() || '—',
        category: $('#nm-cat').value,
        priority: $('#nm-prio').value,
        pinned: $('#nm-pin').checked,
      };

      try {
        const res = await apiFetch('/notices', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        if (res.ok) {
          toast('Notice published 📌');
          closeModal();
          refreshStaffPortal();
        } else {
          const err = await res.json().catch(() => ({}));
          toast(err.message || 'Error publishing notice.');
        }
      } catch {
        toast('Network error publishing notice.');
      }
    });
  }

  /* ---------------- Admin Section Controls ---------------- */
  // Wallpaper blur & veil range inputs
  const admWpBlur = $('#adm-wp-blur');
  if (admWpBlur) {
    admWpBlur.addEventListener('input', e => {
      state.wallpaper.blur = Number(e.target.value);
      const valEl = $('#blur-val');
      if (valEl) valEl.textContent = state.wallpaper.blur + 'px';
      applyBgVars();
    });
  }

  const admWpVeil = $('#adm-wp-veil');
  if (admWpVeil) {
    admWpVeil.addEventListener('input', e => {
      state.wallpaper.veil = Number(e.target.value);
      const valEl = $('#veil-val');
      if (valEl) valEl.textContent = state.wallpaper.veil + '%';
      applyBgVars();
    });
  }

  const admWpSource = $('#adm-wp-source');
  if (admWpSource) {
    admWpSource.addEventListener('change', e => {
      state.wallpaper.mode = e.target.value;
      const customField = $('#custom-urls-field');
      if (customField) customField.style.display = state.wallpaper.mode === 'custom' ? '' : 'none';
      refreshWallpaper();
    });
  }

  const admWpEnabled = $('#adm-wp-enabled');
  if (admWpEnabled) {
    admWpEnabled.addEventListener('change', e => {
      state.wallpaper.enabled = e.target.checked;
      restartBgTimer();
      refreshWallpaper();
    });
  }

  const admWpInterval = $('#adm-wp-interval');
  if (admWpInterval) {
    admWpInterval.addEventListener('change', e => {
      state.wallpaper.interval = Number(e.target.value);
      restartBgTimer();
      toast(state.wallpaper.interval ? 'Auto-refresh updated.' : 'Auto-refresh off.');
    });
  }

  // Save Wallpaper settings button in Admin
  const saveWpBtn = $('#save-wp-btn');
  if (saveWpBtn) {
    saveWpBtn.addEventListener('click', async () => {
      const customUrls = ($('#adm-wp-custom')?.value || '')
        .split('\n')
        .map(s => s.trim())
        .filter(Boolean);

      const payload = {
        office_name: state.office,
        tagline: state.tagline,
        helpdesk_email: state.helpdesk.email,
        helpdesk_ext: state.helpdesk.ext,
        wallpaper_enabled: $('#adm-wp-enabled')?.checked ?? true,
        wallpaper_mode: $('#adm-wp-source')?.value || 'picsum',
        wallpaper_custom_urls: customUrls,
        wallpaper_interval: Number($('#adm-wp-interval')?.value || 300000),
        wallpaper_blur: Number($('#adm-wp-blur')?.value || 28),
        wallpaper_veil: Number($('#adm-wp-veil')?.value || 55),
      };

      try {
        const res = await apiFetch('/admin/settings', {
          method: 'PUT',
          body: JSON.stringify(payload),
        });
        if (res.ok) {
          toast('Wallpaper settings saved.');
          // update local state
          state.wallpaper = {
            enabled: payload.wallpaper_enabled,
            mode: payload.wallpaper_mode,
            customUrls: payload.wallpaper_custom_urls,
            interval: payload.wallpaper_interval,
            blur: payload.wallpaper_blur,
            veil: payload.wallpaper_veil,
          };
          applyBgVars();
          restartBgTimer();
          refreshWallpaper();
        } else {
          toast('Failed to save wallpaper settings.');
        }
      } catch {
        toast('Network error saving wallpaper.');
      }
    });
  }

  // Admin Services: Inline Editing, Reorder & Delete
  const adminServices = $('#admin-services');
  if (adminServices) {
    // Input change: debounce update
    let svcDebounce;
    adminServices.addEventListener('input', e => {
      const row = e.target.closest('.row-item');
      if (!row) return;
      const id = row.dataset.id;
      const field = e.target.dataset.f;
      if (field === 'url') {
        e.target.classList.toggle('invalid', !!e.target.value && !safeUrl(e.target.value));
      }
      clearTimeout(svcDebounce);
      svcDebounce = setTimeout(async () => {
        const payload = {
          icon: row.querySelector('[data-f="icon"]')?.value || '🔧',
          name: row.querySelector('[data-f="name"]')?.value || 'Service',
          description: row.querySelector('[data-f="desc"]')?.value || '',
          url: row.querySelector('[data-f="url"]')?.value || 'https://',
        };
        try {
          const res = await apiFetch(`/admin/services/${id}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
          });
          if (res.ok) {
            toast('Service updated.');
            if (field === 'url') {
              pingSingleService(id);
            }
          }
        } catch {}
      }, 500);
    });

    // Buttons: Up, Down, Delete
    adminServices.addEventListener('click', async e => {
      const btn = e.target.closest('.tool-btn');
      if (!btn) return;
      const row = btn.closest('.row-item');
      if (!row) return;
      const id = row.dataset.id;
      const act = btn.dataset.act;

      if (act === 'del') {
        const name = row.querySelector('[data-f="name"]')?.value || 'this service';
        if (!confirm(`Delete “${name}”?`)) return;
        try {
          const res = await apiFetch(`/admin/services/${id}`, { method: 'DELETE' });
          if (res.ok) {
            row.remove();
            toast('Service removed.');
          }
        } catch {
          toast('Error deleting service.');
        }
      } else if (act === 'up' && row.previousElementSibling) {
        row.parentNode.insertBefore(row, row.previousElementSibling);
        saveServicesOrder();
      } else if (act === 'down' && row.nextElementSibling) {
        row.parentNode.insertBefore(row.nextElementSibling, row);
        saveServicesOrder();
      }
    });
  }

  async function saveServicesOrder() {
    const ids = $$('#admin-services .row-item').map(r => Number(r.dataset.id));
    try {
      await apiFetch('/admin/services/reorder', {
        method: 'POST',
        body: JSON.stringify({ order: ids }),
      });
      toast('Order saved.');
    } catch {}
  }

  // Add Service Button
  const addServiceBtn = $('#add-service-btn');
  if (addServiceBtn) {
    addServiceBtn.addEventListener('click', async () => {
      try {
        const res = await apiFetch('/admin/services', {
          method: 'POST',
          body: JSON.stringify({
            icon: '🔧',
            name: 'New service',
            description: 'What is this for?',
            url: 'https://example.com',
          }),
        });
        if (res.ok) {
          const json = await res.json();
          toast('Service created.');
          if (json.service && adminServices) {
            const s = json.service;
            const status = s.status === 'online' ? 'online' : 'offline';
            const label = status === 'online' ? 'Online' : 'Offline';
            const div = document.createElement('div');
            div.className = 'row-item svc-row';
            div.dataset.id = s.id;
            div.innerHTML = `
              <input class="icon-input" data-f="icon" value="${esc(s.icon || '🔧')}" maxlength="4" aria-label="Icon">
              <input data-f="name" value="${esc(s.name)}" maxlength="40" aria-label="Name">
              <input class="url-input" data-f="url" value="${esc(s.url)}" placeholder="https://…" aria-label="URL">
              <input class="desc-input" data-f="desc" value="${esc(s.description)}" maxlength="90" aria-label="Description">
              <span class="status-indicator badge ${status}">● ${label}</span>
              <div class="row-tools">
                <button type="button" class="tool-btn" data-act="up" title="Move up">↑</button>
                <button type="button" class="tool-btn" data-act="down" title="Move down">↓</button>
                <button type="button" class="tool-btn danger" data-act="del" title="Delete">🗑</button>
              </div>
            `;
            adminServices.appendChild(div);
            const svcGrid = $('#services-grid');
            if (svcGrid) {
              const card = document.createElement('a');
              card.className = 'svc';
              card.dataset.id = s.id;
              card.dataset.name = (s.name || '').toLowerCase();
              card.dataset.desc = (s.description || '').toLowerCase();
              card.dataset.status = status;
              card.href = safeUrl(s.url) || '#';
              card.target = '_blank';
              card.rel = 'noopener';
              const statusLabels = { online: 'Online', offline: 'Offline' };
              card.innerHTML = `
                <div class="svc-top">
                  <span class="svc-icon">${esc(s.icon || '🔧')}</span>
                  <span class="badge ${status}">${statusLabels[status]}</span>
                </div>
                <div><div class="svc-name">${esc(s.name)}</div></div>
                <div class="svc-desc">${esc(s.description)}</div>
                <span class="svc-go">Open ↗</span>
              `;
              svcGrid.appendChild(card);
            }
          }
        }
      } catch {
        toast('Error creating service.');
      }
    });
  }

  // Admin Notices: Pin toggle and Delete
  const adminNotices = $('#admin-notices');
  if (adminNotices) {
    adminNotices.addEventListener('click', async e => {
      const btn = e.target.closest('.tool-btn');
      if (!btn) return;
      const row = btn.closest('.row-item');
      if (!row) return;
      const id = row.dataset.id;
      const act = btn.dataset.act;

      if (act === 'pin') {
        const isCurrentlyPinned = btn.textContent.includes('📌');
        try {
          const res = await apiFetch(`/admin/notices/${id}`, {
            method: 'PUT',
            body: JSON.stringify({ pinned: !isCurrentlyPinned }),
          });
          if (res.ok) {
            toast(isCurrentlyPinned ? 'Notice unpinned.' : 'Notice pinned 📌');
            setTimeout(() => location.reload(), 300);
          }
        } catch {
          toast('Error updating notice.');
        }
      } else if (act === 'del') {
        if (!confirm('Delete this notice?')) return;
        try {
          const res = await apiFetch(`/admin/notices/${id}`, { method: 'DELETE' });
          if (res.ok) {
            row.remove();
            toast('Notice deleted.');
          }
        } catch {
          toast('Error deleting notice.');
        }
      }
    });
  }

  // Admin Bookmarks: Inline Editing and Delete
  const adminBookmarks = $('#admin-bookmarks');
  if (adminBookmarks) {
    let bmDebounce;
    adminBookmarks.addEventListener('input', e => {
      const row = e.target.closest('.row-item');
      if (!row) return;
      const id = row.dataset.id;
      clearTimeout(bmDebounce);
      bmDebounce = setTimeout(async () => {
        const payload = {
          label: row.querySelector('[data-f="label"]')?.value || 'Bookmark',
          url: row.querySelector('[data-f="url"]')?.value || 'https://',
        };
        try {
          await apiFetch(`/admin/bookmarks/${id}`, {
            method: 'PUT',
            body: JSON.stringify(payload),
          });
          toast('Bookmark updated.');
        } catch {}
      }, 500);
    });

    adminBookmarks.addEventListener('click', async e => {
      const btn = e.target.closest('.tool-btn');
      if (!btn) return;
      const row = btn.closest('.row-item');
      if (!row) return;
      const id = row.dataset.id;
      if (btn.dataset.act === 'del') {
        try {
          const res = await apiFetch(`/admin/bookmarks/${id}`, { method: 'DELETE' });
          if (res.ok) {
            row.remove();
            toast('Bookmark removed.');
          }
        } catch {
          toast('Error deleting bookmark.');
        }
      }
    });
  }

  // Add Bookmark Button
  const addBookmarkBtn = $('#add-bookmark-btn');
  if (addBookmarkBtn) {
    addBookmarkBtn.addEventListener('click', async () => {
      try {
        const res = await apiFetch('/admin/bookmarks', {
          method: 'POST',
          body: JSON.stringify({
            label: 'New bookmark',
            url: 'https://example.com',
          }),
        });
        if (res.ok) {
          const json = await res.json();
          toast('Bookmark created.');
          if (json.bookmark && adminBookmarks) {
            const b = json.bookmark;
            const div = document.createElement('div');
            div.className = 'row-item bookmark-row';
            div.dataset.id = b.id;
            div.innerHTML = `
              <input data-f="label" value="${esc(b.label)}" maxlength="40" aria-label="Label">
              <input data-f="url" value="${esc(b.url)}" placeholder="https://…" aria-label="URL">
              <button type="button" class="tool-btn danger" data-act="del" title="Delete">🗑</button>
            `;
            adminBookmarks.appendChild(div);
            const bmChips = $('#bookmarks-chips');
            if (bmChips) {
              const chip = document.createElement('a');
              chip.className = 'chip-link';
              chip.dataset.id = b.id;
              chip.dataset.label = (b.label || '').toLowerCase();
              chip.dataset.url = (b.url || '').toLowerCase();
              chip.href = safeUrl(b.url) || '#';
              chip.target = '_blank';
              chip.rel = 'noopener';
              chip.innerHTML = `<span class="dot"></span>${esc(b.label)}`;
              bmChips.appendChild(chip);
            }
          }
        }
      } catch {
        toast('Error creating bookmark.');
      }
    });
  }

  // Backup & Restore: Import / Reset buttons
  const importBtn = $('#import-btn');
  const importFile = $('#import-file');
  const importForm = $('#import-form');
  if (importBtn && importFile && importForm) {
    importBtn.addEventListener('click', () => importFile.click());
    importFile.addEventListener('change', () => {
      if (importFile.files.length) {
        toast('Importing configuration…');
        importForm.submit();
      }
    });
  }

  const resetBtn = $('#reset-btn');
  const resetForm = $('#reset-form');
  if (resetBtn && resetForm) {
    resetBtn.addEventListener('click', () => {
      if (confirm('Reset everything to default settings? Export first if unsure.')) {
        toast('Resetting to defaults…');
        resetForm.submit();
      }
    });
  }

  // Admin Identity Settings form
  const formSettings = $('#form-settings');
  if (formSettings) {
    formSettings.addEventListener('submit', async e => {
      e.preventDefault();
      const formData = new FormData(formSettings);
      const data = Object.fromEntries(formData.entries());
      try {
        const res = await apiFetch(formSettings.action, {
          method: 'PUT',
          body: JSON.stringify(data),
        });
        if (res.ok) {
          toast('Identity settings saved.');
          if (data.office_name) {
            state.office = data.office_name;
            const el = $('#office-name');
            if (el) el.textContent = data.office_name;
            document.title = data.office_name + ' — Office Intranet';
          }
          if (data.tagline) {
            state.tagline = data.tagline;
            const el = $('#tagline');
            if (el) el.textContent = data.tagline;
          }
          if (data.helpdesk_email) {
            state.helpdesk.email = data.helpdesk_email;
            const el = $('#hd-email');
            if (el) el.textContent = data.helpdesk_email;
          }
          if (data.helpdesk_ext) {
            state.helpdesk.ext = data.helpdesk_ext;
            const el = $('#hd-ext');
            if (el) el.textContent = data.helpdesk_ext;
          }
        } else {
          formSettings.submit();
        }
      } catch {
        formSettings.submit();
      }
    });
  }

  /* ---------------- Service Ping Engine ---------------- */
  let pingAbortController = null;

  async function pingServices() {
    if (pingAbortController) {
      pingAbortController.abort();
    }
    pingAbortController = new AbortController();
    const note = $('#ping-status-note');
    if (note) note.textContent = 'Pinging…';
    try {
      const res = await fetch('/services/ping', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        signal: pingAbortController.signal,
      });
      if (!res.ok) {
        if (note) note.textContent = '';
        return;
      }
      const data = await res.json();
      if (Array.isArray(data.services)) {
        data.services.forEach(item => {
          updateServiceStatusDom(item.id, item.status);
        });
      }
      if (note) {
        const time = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
        note.textContent = `Pinged ${time}`;
      }
    } catch (err) {
      if (err?.name === 'AbortError') return;
      if (note) note.textContent = 'Ping failed.';
    }
  }

  async function pingSingleService(id) {
    try {
      const res = await fetch(`/services/${id}/ping`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) return;
      const data = await res.json();
      if (data.status) {
        updateServiceStatusDom(id, data.status);
      }
    } catch {}
  }

  function updateServiceStatusDom(id, rawStatus) {
    const status = rawStatus === 'online' ? 'online' : 'offline';
    const label = status === 'online' ? 'Online' : 'Offline';

    // Update portal card
    const card = document.querySelector(`.services-grid .svc[data-id="${id}"]`);
    if (card) {
      card.dataset.status = status;
      const badge = card.querySelector('.badge');
      if (badge) {
        badge.className = `badge ${status}`;
        badge.textContent = label;
      }
    }

    // Update admin row
    const row = document.querySelector(`.svc-row[data-id="${id}"]`);
    if (row) {
      const indicator = row.querySelector('.status-indicator');
      if (indicator) {
        indicator.className = `status-indicator badge ${status}`;
        indicator.textContent = `● ${label}`;
      }
    }
  }

  document.addEventListener('click', e => {
    if (e.target && e.target.id === 'ping-services-btn') {
      toast('Checking service availability…');
      pingServices();
    }
  });

  /* ---------------- View Routing & Instantaneous DOM Toggle ---------------- */
  const viewPortal = $('#view-portal');
  const viewAdmin = $('#view-admin');
  const switchBtns = $$('.switch-btn');

  async function refreshStaffPortal() {
    try {
      const portalUrl = window.__INITIAL_STATE__?.routes?.portal || '/';
      const res = await fetch(portalUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) return;
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');

      // Update brand in topbar if changed
      const newOffice = doc.querySelector('#office-name');
      const curOffice = $('#office-name');
      if (newOffice && curOffice) curOffice.textContent = newOffice.textContent;

      const newTagline = doc.querySelector('#tagline');
      const curTagline = $('#tagline');
      if (newTagline && curTagline) curTagline.textContent = newTagline.textContent;

      // Update staff portal view content
      const newPortal = doc.querySelector('#view-portal');
      const curPortal = $('#view-portal');
      if (newPortal && curPortal) {
        curPortal.innerHTML = newPortal.innerHTML;
        // Re-apply search filter if active
        if (searchQuery) {
          const si = $('#search-input');
          if (si) si.value = searchQuery;
          filterCards();
        }
      }

      // Check live availability via ping
      pingServices();
    } catch (e) {
      console.warn('Staff portal background refresh failed:', e);
    }
  }

  function updateView(target, updateHistory = true) {
    if (!viewPortal || !viewAdmin) return false;
    const wasAdmin = !viewAdmin.hidden;
    const isAdmin = target === 'admin';
    viewPortal.hidden = isAdmin;
    viewAdmin.hidden = !isAdmin;

    switchBtns.forEach(btn => {
      btn.classList.toggle('active', btn.dataset.view === target);
    });

    if (updateHistory) {
      const targetUrl = isAdmin
        ? (window.__INITIAL_STATE__?.routes?.admin || '/admin')
        : (window.__INITIAL_STATE__?.routes?.portal || '/');
      if (window.location.pathname !== targetUrl) {
        history.pushState({ view: target }, '', targetUrl);
      }
    }
    window.scrollTo({ top: 0 });

    // Refresh staff portal when changing from admin to staff portal
    if (wasAdmin && !isAdmin) {
      refreshStaffPortal();
    }

    return true;
  }

  switchBtns.forEach(btn => {
    btn.addEventListener('click', e => {
      const target = btn.dataset.view;
      if (viewPortal && viewAdmin && target) {
        e.preventDefault();
        updateView(target, true);
      }
    });
  });

  window.addEventListener('popstate', () => {
    if (viewPortal && viewAdmin) {
      const isAdmin = window.location.pathname.startsWith('/admin');
      updateView(isAdmin ? 'admin' : 'portal', false);
    }
  });

  /* ---------------- Navigation Loading Indicator ---------------- */
  const navLoadingBar = $('#nav-loading-bar');
  const navLoadingPill = $('#nav-loading-pill');
  const navLoadingText = $('#nav-loading-text');

  function startNavLoading(el, labelText) {
    if (pingAbortController) {
      pingAbortController.abort();
    }
    document.body.classList.add('is-navigating');
    if (navLoadingBar) navLoadingBar.classList.add('active');
    if (navLoadingPill) {
      if (navLoadingText) navLoadingText.textContent = labelText || 'Loading…';
      navLoadingPill.hidden = false;
    }
    if (el) {
      el.classList.add('is-loading');
      el.setAttribute('aria-busy', 'true');
      const labelEl = el.querySelector('.chip-label');
      if (labelEl) {
        if (!el.dataset.origLabel) el.dataset.origLabel = labelEl.textContent;
        labelEl.textContent = `${el.dataset.origLabel} · Loading…`;
      }
    }
  }

  function resetNavLoading() {
    document.body.classList.remove('is-navigating');
    if (navLoadingBar) navLoadingBar.classList.remove('active');
    if (navLoadingPill) navLoadingPill.hidden = true;
    $$('.is-loading').forEach(el => {
      el.classList.remove('is-loading');
      el.removeAttribute('aria-busy');
      const labelEl = el.querySelector('.chip-label');
      if (labelEl && el.dataset.origLabel) {
        labelEl.textContent = el.dataset.origLabel;
      }
    });
  }

  document.addEventListener('click', e => {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
      return;
    }
    const navLink = e.target.closest('[data-nav-loading], #bookmarks-chips .chip-link[target="_self"]');
    if (!navLink) return;
    const href = navLink.getAttribute('href') || '';
    if (!href || href.startsWith('#')) return;
    const label = navLink.dataset.navLoading || `Opening ${navLink.textContent.trim()}…`;
    startNavLoading(navLink, label);
  });

  window.addEventListener('pageshow', resetNavLoading);

  /* ---------------- Boot ---------------- */
  applyTheme();
  applyBgVars();

  // Instant wallpaper recovery from session cache to eliminate white / blank flash
  try {
    const cachedWp = sessionStorage.getItem('staffhub-last-wallpaper');
    if (cachedWp && layerA && state.wallpaper.enabled) {
      layerA.style.backgroundImage = `url("${cachedWp}")`;
      layerA.style.opacity = 1;
      bg.showing = 'a';
    }
  } catch (e) {}

  restartBgTimer();
  refreshWallpaper();
  if ($('#services-grid') || $('#adm-services-list')) {
    pingServices();
  }

})();
