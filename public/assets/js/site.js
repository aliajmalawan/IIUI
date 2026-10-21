(function () {
  var header = document.getElementById('site-header');
  if (!header) return;

  var threshold = 40;

  function onScroll() {
    if (window.scrollY > threshold) {
      header.classList.add('is-scrolled');
    } else {
      header.classList.remove('is-scrolled');
    }
  }

  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
})();

(function () {
  var toggle = document.getElementById('nav-toggle');
  var nav = document.getElementById('site-nav');
  var backdrop = document.getElementById('nav-backdrop');
  if (!toggle || !nav || !backdrop) return;

  function isOpen() {
    return nav.classList.contains('is-open');
  }

  function openDrawer() {
    nav.classList.add('is-open');
    backdrop.classList.add('is-open');
    toggle.classList.add('is-open');
    toggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    nav.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    toggle.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    nav.querySelectorAll('.nav-dropdown.is-open').forEach(function (d) {
      d.classList.remove('is-open');
    });
  }

  toggle.addEventListener('click', function () {
    if (isOpen()) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  backdrop.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && isOpen()) closeDrawer();
  });

  // Every real link (top-level items, dropdown children, Login/Apply Online)
  // closes the drawer after navigating/jumping to an anchor; a dropdown
  // trigger toggles its submenu instead, since a touch device has no hover
  // state to reveal it (handled separately below).
  nav.querySelectorAll('a:not(.nav-dropdown-trigger)').forEach(function (link) {
    link.addEventListener('click', function () {
      if (window.matchMedia('(max-width: 900px)').matches) closeDrawer();
    });
  });

  nav.querySelectorAll('.nav-dropdown-trigger').forEach(function (trigger) {
    trigger.addEventListener('click', function (e) {
      if (!window.matchMedia('(max-width: 900px)').matches) return;
      e.preventDefault();
      trigger.closest('.nav-dropdown').classList.toggle('is-open');
    });
  });

  window.addEventListener('resize', function () {
    if (!window.matchMedia('(max-width: 900px)').matches && isOpen()) closeDrawer();
  });
})();

(function () {
  var targets = document.querySelectorAll('.reveal-fade-up, .reveal-slide-left, .reveal-slide-right, .reveal-zoom');
  if (targets.length === 0) return;

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    targets.forEach(function (el) { el.classList.add('is-visible'); });
    return;
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  targets.forEach(function (el) { observer.observe(el); });
})();

(function () {
  var btn = document.getElementById('scroll-top-btn');
  if (!btn) return;

  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var threshold = 400;

  function onScroll() {
    btn.classList.toggle('is-visible', window.scrollY > threshold);
  }

  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  btn.addEventListener('click', function () {
    window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
  });
})();

/* Navigation: dropdown ARIA state, drawer focus handling. */
(function () {
  var nav = document.getElementById('site-nav');
  var toggle = document.getElementById('nav-toggle');
  if (!nav) return;
  var mobile = window.matchMedia('(max-width: 900px)');

  // Keep aria-expanded truthful on the dropdown triggers (hover/focus on desktop, tap on mobile).
  nav.querySelectorAll('.nav-dropdown').forEach(function (dd) {
    var trigger = dd.querySelector('.nav-dropdown-trigger');
    if (!trigger) return;
    function sync() {
      var open = mobile.matches ? dd.classList.contains('is-open') : (dd.matches(':hover') || dd.matches(':focus-within'));
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    ['mouseenter', 'mouseleave', 'focusin', 'focusout', 'click'].forEach(function (evt) {
      dd.addEventListener(evt, function () { setTimeout(sync, 0); });
    });
    dd.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !mobile.matches) {
        if (dd.contains(document.activeElement)) trigger.blur();
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  });

  if (!toggle) return;

  // Mobile drawer: move focus in when it opens, keep Tab inside it, hand focus back on close.
  var wasOpen = false;
  function navLinks() {
    return Array.prototype.filter.call(
      nav.querySelectorAll('a[href], [tabindex="0"]'),
      function (el) { return el.offsetParent !== null; }
    );
  }
  new MutationObserver(function () {
    var open = nav.classList.contains('is-open');
    if (open && !wasOpen) {
      // Wait a beat: with "reduce motion" the drawer becomes visible a frame late, and an element
      // that is still visibility:hidden cannot take focus.
      setTimeout(function () {
        var first = navLinks()[0];
        if (first && nav.classList.contains('is-open')) first.focus();
      }, 60);
    } else if (!open && wasOpen && mobile.matches) {
      toggle.focus();
    }
    wasOpen = open;
  }).observe(nav, { attributes: true, attributeFilter: ['class'] });

  // The toggle button sits before the nav in the page, so the cycle is: toggle -> links ... -> last link -> toggle.
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Tab' || !nav.classList.contains('is-open') || !mobile.matches) return;
    var links = navLinks();
    if (!links.length) return;
    var last = links[links.length - 1];
    if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); toggle.focus(); }
    else if (e.shiftKey && document.activeElement === toggle) { e.preventDefault(); last.focus(); }
    else if (!nav.contains(document.activeElement) && document.activeElement !== toggle) { e.preventDefault(); (e.shiftKey ? last : links[0]).focus(); }
  });
})();

/* Forms marked data-submit-lock: block double submits and show a busy label. */
document.addEventListener('submit', function (e) {
  var form = e.target;
  if (!form.matches || !form.matches('form[data-submit-lock]') || e.defaultPrevented) return;
  if (form.dataset.submitting === '1') { e.preventDefault(); return; }
  form.dataset.submitting = '1';
  form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
    btn.classList.add('is-busy');
    btn.setAttribute('aria-disabled', 'true');
    var label = btn.querySelector('span');
    if (label && btn.dataset.loadingText) label.textContent = btn.dataset.loadingText;
  });
});
// Coming back with the browser's Back button must not leave the form stuck in its busy state.
window.addEventListener('pageshow', function (e) {
  if (!e.persisted) return;
  document.querySelectorAll('form[data-submit-lock]').forEach(function (form) {
    form.dataset.submitting = '0';
    form.querySelectorAll('button.is-busy').forEach(function (btn) { btn.classList.remove('is-busy'); btn.removeAttribute('aria-disabled'); });
  });
});
