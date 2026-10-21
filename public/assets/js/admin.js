/**
 * Admin panel behaviour: sidebar collapse/off-canvas, dropdowns, theme
 * toggle, command palette, notification read state, KPI count-up.
 * Vanilla JS only — no framework, no build step (docs/06-standards.md §3).
 */
(function () {
  'use strict';

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var csrfToken = document.querySelector('meta[name="csrf-token"]');
  csrfToken = csrfToken ? csrfToken.getAttribute('content') : null;

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body || '',
      credentials: 'same-origin',
    }).then(function (r) { return r.json(); });
  }

  function getCsrf() {
    return csrfToken || '';
  }

  // -----------------------------------------------------------------------
  // Sidebar collapse (desktop) — persisted via a plain cookie so the NEXT
  // page load renders already-collapsed server-side (no flash/jump).
  // -----------------------------------------------------------------------
  var shell = document.getElementById('admin-shell');
  var collapseBtn = document.getElementById('sidebar-collapse-btn');
  var desktopToggle = document.getElementById('sidebar-desktop-toggle');

  function setCollapsed(collapsed) {
    shell.classList.toggle('is-collapsed', collapsed);
    document.cookie = 'sidebar_collapsed=' + (collapsed ? '1' : '0') + ';path=/;max-age=31536000';
  }

  [collapseBtn, desktopToggle].forEach(function (btn) {
    if (!btn) return;
    btn.addEventListener('click', function () {
      setCollapsed(!shell.classList.contains('is-collapsed'));
    });
  });

  // Mobile off-canvas
  var mobileToggle = document.getElementById('sidebar-mobile-toggle');
  var backdrop = document.getElementById('sidebar-backdrop');

  function closeMobile() { shell.classList.remove('is-mobile-open'); }

  if (mobileToggle) {
    mobileToggle.addEventListener('click', function () {
      shell.classList.add('is-mobile-open');
    });
  }
  if (backdrop) backdrop.addEventListener('click', closeMobile);

  // -----------------------------------------------------------------------
  // Generic dropdowns (notifications, profile menu)
  // -----------------------------------------------------------------------
  var dropdowns = document.querySelectorAll('.dropdown-wrap');

  dropdowns.forEach(function (wrap) {
    var btn = wrap.querySelector('button');
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var wasOpen = wrap.classList.contains('is-open');
      dropdowns.forEach(function (d) { d.classList.remove('is-open'); });
      if (!wasOpen) wrap.classList.add('is-open');
    });
  });

  document.addEventListener('click', function () {
    dropdowns.forEach(function (d) { d.classList.remove('is-open'); });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      dropdowns.forEach(function (d) { d.classList.remove('is-open'); });
    }
  });

  // -----------------------------------------------------------------------
  // Theme toggle — flips light/dark instantly (optimistic UI), persists to
  // the server so the NEXT page load also renders correctly with no flash.
  // -----------------------------------------------------------------------
  var themeBtn = document.getElementById('theme-toggle-btn');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      var html = document.documentElement;
      var current = html.getAttribute('data-bs-theme');
      if (!current) {
        current = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
      }
      var next = current === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-bs-theme', next);

      post(window.ADMIN_THEME_URL, 'theme=' + encodeURIComponent(next) + '&_token=' + encodeURIComponent(getCsrf()))
        .catch(function () { /* theme still applied client-side even if the save fails */ });
    });
  }

  // -----------------------------------------------------------------------
  // Notifications — mark one read on click, mark all read via the link.
  // -----------------------------------------------------------------------
  document.querySelectorAll('.notif-item').forEach(function (item) {
    item.addEventListener('click', function () {
      if (!item.classList.contains('is-unread')) return;
      var id = item.getAttribute('data-notif-id');
      post(window.ADMIN_NOTIF_READ_URL_TEMPLATE.replace('__ID__', id), '_token=' + encodeURIComponent(getCsrf()))
        .then(function () {
          item.classList.remove('is-unread');
          updateBadge(-1);
        });
    });
  });

  var markAllLink = document.getElementById('notif-mark-all');
  if (markAllLink) {
    markAllLink.addEventListener('click', function (e) {
      e.preventDefault();
      post(window.ADMIN_NOTIF_READ_ALL_URL, '_token=' + encodeURIComponent(getCsrf())).then(function () {
        document.querySelectorAll('.notif-item.is-unread').forEach(function (el) { el.classList.remove('is-unread'); });
        var badge = document.querySelector('.topbar-badge');
        if (badge) badge.remove();
        markAllLink.remove();
      });
    });
  }

  function updateBadge(delta) {
    var badge = document.querySelector('.topbar-badge');
    if (!badge) return;
    var n = parseInt(badge.textContent, 10) || 0;
    n = Math.max(0, n + delta);
    if (n === 0) { badge.remove(); return; }
    badge.textContent = n > 99 ? '99+' : String(n);
  }

  // -----------------------------------------------------------------------
  // Command palette (search) — Ctrl/Cmd+K or clicking the topbar search box.
  // -----------------------------------------------------------------------
  var cmdkOverlay = document.getElementById('cmdk-overlay');
  var cmdkInput = document.getElementById('cmdk-input');
  var cmdkResults = document.getElementById('cmdk-results');
  var cmdkTrigger = document.getElementById('cmdk-trigger');
  var cmdkDataEl = document.getElementById('cmdk-data');
  var cmdkItems = [];
  try { cmdkItems = JSON.parse(cmdkDataEl.textContent || '[]'); } catch (e) { cmdkItems = []; }
  var cmdkSelected = 0;
  var cmdkFiltered = cmdkItems;

  function openCmdk() {
    cmdkOverlay.classList.add('is-open');
    cmdkInput.value = '';
    cmdkSelected = 0;
    renderCmdk(cmdkItems);
    setTimeout(function () { cmdkInput.focus(); }, 10);
  }
  function closeCmdk() { cmdkOverlay.classList.remove('is-open'); }

  function renderCmdk(items) {
    cmdkFiltered = items;
    if (items.length === 0) {
      cmdkResults.innerHTML = '<div class="cmdk-empty">No matching sections.</div>';
      return;
    }
    cmdkResults.innerHTML = items.map(function (item, i) {
      return '<a class="cmdk-item' + (i === cmdkSelected ? ' is-selected' : '') + '" href="' + item.url + '" data-index="' + i + '">' +
        '<svg class="icon" aria-hidden="true"><use href="' + (window.ADMIN_ICON_SPRITE || '') + '#bi-' + item.icon + '"></use></svg>' +
        '<span>' + item.label + '<br><small style="color:var(--text-muted);font-size:11px">' + item.section + '</small></span>' +
        '</a>';
    }).join('');
  }

  if (cmdkTrigger) cmdkTrigger.addEventListener('click', openCmdk);

  document.addEventListener('keydown', function (e) {
    var isK = e.key === 'k' || e.key === 'K';
    if ((e.ctrlKey || e.metaKey) && isK) {
      e.preventDefault();
      cmdkOverlay.classList.contains('is-open') ? closeCmdk() : openCmdk();
    }
    if (e.key === 'Escape' && cmdkOverlay.classList.contains('is-open')) {
      closeCmdk();
    }
  });

  if (cmdkOverlay) {
    cmdkOverlay.addEventListener('click', function (e) {
      if (e.target === cmdkOverlay) closeCmdk();
    });
  }

  if (cmdkInput) {
    cmdkInput.addEventListener('input', function () {
      var q = cmdkInput.value.trim().toLowerCase();
      cmdkSelected = 0;
      var filtered = q === '' ? cmdkItems : cmdkItems.filter(function (item) {
        return item.label.toLowerCase().indexOf(q) !== -1 || item.section.toLowerCase().indexOf(q) !== -1;
      });
      renderCmdk(filtered);
    });

    cmdkInput.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        cmdkSelected = Math.min(cmdkSelected + 1, cmdkFiltered.length - 1);
        renderCmdk(cmdkFiltered);
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        cmdkSelected = Math.max(cmdkSelected - 1, 0);
        renderCmdk(cmdkFiltered);
      } else if (e.key === 'Enter') {
        e.preventDefault();
        var target = cmdkFiltered[cmdkSelected];
        if (target) window.location.href = target.url;
      }
    });
  }

  // -----------------------------------------------------------------------
  // Delete-confirmation — any <form data-confirm="..."> asks before
  // submitting. Using a data attribute (never inline onsubmit with
  // interpolated PHP strings) means quote characters in the confirmation
  // text can never break out of a JS string literal — the browser only
  // ever reads it as a plain attribute value, never re-parses it as code.
  // -----------------------------------------------------------------------
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm) {
      if (!window.confirm(form.dataset.confirm)) {
        e.preventDefault();
      }
    }
  });

  // -----------------------------------------------------------------------
  // KPI count-up on first paint — skipped entirely under reduced motion.
  // -----------------------------------------------------------------------
  document.querySelectorAll('.kpi-value[data-count-to]').forEach(function (el) {
    var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;

    if (prefersReducedMotion) {
      el.textContent = target.toLocaleString();
      return;
    }

    var start = null;
    var duration = 800;

    function step(ts) {
      if (start === null) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.round(eased * target).toLocaleString();
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  });
})();
