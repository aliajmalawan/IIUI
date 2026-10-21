/*
 * Shared gallery lightbox (photos + YouTube videos).
 * Expects #lightbox-overlay markup and a #lightbox-data JSON script — see
 * app/Views/partials/web/_lightbox.php. Grid buttons carry data-lightbox-index.
 */
(function () {
  'use strict';

  var overlay = document.getElementById('lightbox-overlay');
  var dataEl = document.getElementById('lightbox-data');
  if (!overlay || !dataEl) return;

  var items = JSON.parse(dataEl.textContent || '[]');
  if (!items.length) return;

  var imgEl = overlay.querySelector('#lightbox-image');
  var videoBox = overlay.querySelector('#lightbox-video');
  var captionEl = overlay.querySelector('#lightbox-caption');
  var counterEl = overlay.querySelector('#lightbox-counter');
  var closeBtn = overlay.querySelector('#lightbox-close');
  var prevBtn = overlay.querySelector('#lightbox-prev');
  var nextBtn = overlay.querySelector('#lightbox-next');

  var current = 0;
  var lastTrigger = null;
  var touchX = null;

  if (items.length < 2) {
    prevBtn.hidden = true;
    nextBtn.hidden = true;
  }

  function clearVideo() {
    videoBox.innerHTML = '';
    videoBox.hidden = true;
  }

  function preload(index) {
    var item = items[(index + items.length) % items.length];
    if (item && item.type === 'image') {
      new Image().src = item.src;
    }
  }

  function show(index) {
    current = (index + items.length) % items.length;
    var item = items[current];
    clearVideo();

    if (item.type === 'video') {
      imgEl.hidden = true;
      overlay.classList.remove('is-loading');
      var frame = document.createElement('iframe');
      frame.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(item.id) + '?autoplay=1&rel=0&modestbranding=1';
      frame.title = item.caption || 'Video';
      frame.allow = 'accelerometer; autoplay; encrypted-media; picture-in-picture; fullscreen';
      frame.allowFullscreen = true;
      videoBox.appendChild(frame);
      videoBox.hidden = false;
    } else {
      imgEl.hidden = false;
      overlay.classList.add('is-loading');
      imgEl.onload = imgEl.onerror = function () { overlay.classList.remove('is-loading'); };
      imgEl.alt = item.caption || '';
      imgEl.src = item.src;
      preload(current + 1);
      preload(current - 1);
    }

    captionEl.textContent = item.caption || '';
    captionEl.hidden = !item.caption;
    counterEl.textContent = (current + 1) + ' / ' + items.length;
  }

  function open(index, trigger) {
    lastTrigger = trigger || null;
    show(index);
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
  }

  function close() {
    overlay.classList.remove('is-open', 'is-loading');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    clearVideo();
    imgEl.removeAttribute('src');
    if (lastTrigger) lastTrigger.focus();
  }

  document.querySelectorAll('[data-lightbox-index]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      open(parseInt(btn.getAttribute('data-lightbox-index'), 10), btn);
    });
  });

  closeBtn.addEventListener('click', close);
  prevBtn.addEventListener('click', function () { show(current - 1); });
  nextBtn.addEventListener('click', function () { show(current + 1); });
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay || e.target.classList.contains('lightbox-content')) close();
  });

  document.addEventListener('keydown', function (e) {
    if (!overlay.classList.contains('is-open')) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key === 'ArrowLeft') { show(current - 1); return; }
    if (e.key === 'ArrowRight') { show(current + 1); return; }
    if (e.key === 'Tab') {
      var focusable = Array.prototype.filter.call(
        overlay.querySelectorAll('button'),
        function (b) { return !b.hidden; }
      );
      var first = focusable[0];
      var last = focusable[focusable.length - 1];
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    }
  });

  overlay.addEventListener('touchstart', function (e) {
    touchX = e.changedTouches[0].clientX;
  }, { passive: true });
  overlay.addEventListener('touchend', function (e) {
    if (touchX === null) return;
    var dx = e.changedTouches[0].clientX - touchX;
    touchX = null;
    if (Math.abs(dx) > 50) show(current + (dx < 0 ? 1 : -1));
  }, { passive: true });
})();
