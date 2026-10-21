<?php
/**
 * Public homepage. Section order (top to bottom): Hero Slider, Welcome/About,
 * Statistics, Why Choose Us, Academic Programs, Admission CTA banner, Our
 * Faculty, Facilities, Vibrant Campus, Testimonials ("What Our Students
 * Say"), Latest News. Every section guards on its own data being
 * non-empty, so nothing renders as an empty shell. The old full masonry
 * Gallery preview section was dropped in favor of Vibrant Campus's single
 * curated photo — back-to-back photo-grid sections felt redundant; the
 * standalone /gallery page and its admin module are untouched.
 * @var array $slides
 * @var array $testimonials
 * @var array $features
 * @var array $programs
 * @var array $facilities
 * @var array $stats
 * @var array $galleryImages
 * @var array $latestNews
 * @var array $facultyPreview
 * @var array|null $campusImage
 * @var array<string,mixed> $content key_name => value, every public setting
 */
$typeLabel = ['student' => 'Student', 'parent' => 'Parent', 'alumni' => 'Alumnus/Alumna', 'staff' => 'Staff Member'];
// Apply routes straight to the standalone application form (the site's
// quick-apply action); Contact has the full map/details/form.
$applyHref = resolve_url('/admission-form');
$contactHref = resolve_url('/contact');

$c = static fn (string $key, string $default = '') => trim((string) ($content[$key] ?? '')) !== '' ? (string) $content[$key] : $default;
$messengers = [
    [
        'name'        => $c('welcome_principal_name'),
        'designation' => $c('welcome_principal_designation', 'Principal'),
        'photo'       => $c('welcome_principal_photo'),
        'message'     => $c('welcome_principal_message'),
    ],
    [
        'name'        => $c('welcome_owner_name'),
        'designation' => $c('welcome_owner_designation', 'Chairman / Owner'),
        'photo'       => $c('welcome_owner_photo'),
        'message'     => $c('welcome_owner_message'),
    ],
];
// One item per line -> array (admin edits these as plain text); falls back to the defaults if left empty.
$lines = static function (string $key, array $default) use ($c): array {
    $items = array_values(array_filter(array_map('trim', preg_split('/\R/', $c($key)) ?: [])));

    return $items !== [] ? $items : $default;
};
$ctaBadges = $lines('cta_badges', ['Merit-Based Admissions', 'Easy Application Process', 'Affordable Fee Structure', 'Scholarships Available']);
$campusHighlights = array_slice($lines('campus_highlights', ['Annual Events', 'Seminars & Workshops', 'Sports Activities', 'Educational Tours']), 0, 6);
$highlightIcons = ['calendar-event', 'mic', 'trophy', 'bus-front'];
$messengers = array_values(array_filter($messengers, static fn (array $m) => $m['name'] !== '' && $m['message'] !== ''));
?>

<?php if ($slides !== []): ?>
  <link rel="preload" as="image" href="<?= e(base_url(ltrim($slides[0]['image'], '/'))) ?>" imagesrcset="<?= e(srcset_url($slides[0]['image'])) ?>" imagesizes="100vw" fetchpriority="high">
  <section class="hero-slider swiper" id="hero-slider" aria-label="Featured highlights">
    <div class="swiper-wrapper">
      <?php foreach ($slides as $slideIndex => $slide): ?>
        <div class="swiper-slide hero-slide">
          <img class="hero-slide-bg" src="<?= e(base_url(ltrim($slide['image'], '/'))) ?>" srcset="<?= e(srcset_url($slide['image'])) ?>" sizes="100vw" alt="" decoding="async"<?= $slideIndex === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
          <div class="hero-overlay" style="--hero-overlay-alpha: <?= (int) $slide['overlay_opacity'] / 100 ?>"></div>
          <div class="hero-khatam" aria-hidden="true"></div>
          <div class="container">
            <div class="hero-content hero-content--<?= e($slide['text_position']) ?>">
              <?php if (!empty($slide['subtitle'])): ?>
                <p class="hero-eyebrow"><?= e($slide['subtitle']) ?></p>
              <?php endif; ?>
              <?php $heroTag = $slideIndex === 0 ? 'h1' : 'h2'; // one <h1> per page ?>
              <<?= $heroTag ?> class="hero-title"><?= e($slide['title']) ?></<?= $heroTag ?>>
              <?php if (!empty($slide['description'])): ?>
                <p class="hero-lead"><?= e($slide['description']) ?></p>
              <?php endif; ?>
              <?php if (!empty($slide['button_text']) && !empty($slide['button_url'])): ?>
                <div class="hero-actions">
                  <a class="btn btn-accent btn-lg" href="<?= e(resolve_url($slide['button_url'])) ?>"><?= e($slide['button_text']) ?></a>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($slides) > 1): ?>
      <div class="swiper-pagination"></div>
      <button type="button" class="swiper-button-prev" aria-label="Previous slide"></button>
      <button type="button" class="swiper-button-next" aria-label="Next slide"></button>
    <?php endif; ?>
  </section>
<?php else: ?>
  <section class="home-hero">
    <div class="home-hero-bg" aria-hidden="true"></div>
    <div class="home-hero-overlay" aria-hidden="true"></div>
    <div class="hero-khatam" aria-hidden="true"></div>
    <div class="home-hero-shapes" aria-hidden="true">
      <span class="home-hero-shape home-hero-shape--1"></span>
      <span class="home-hero-shape home-hero-shape--2"></span>
      <span class="home-hero-shape home-hero-shape--3"></span>
      <span class="home-hero-shape home-hero-shape--4"></span>
    </div>
    <div class="container">
      <p class="home-hero-eyebrow">International Islamic University Islamabad</p>
      <h1 class="home-hero-title">
        <span class="line-1">Schools &amp; College</span>
        <span class="line-2">Mardan Campus</span>
      </h1>
      <div class="home-hero-actions">
        <a class="btn btn-accent btn-lg" href="<?= e($applyHref) ?>">Apply Online</a>
        <a class="btn btn-outline-white btn-lg" href="<?= e($contactHref) ?>">Contact Us</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($slides !== []): ?>
<script>
(function () {
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var slideCount = <?= count($slides) ?>;

  // This script sits inside <main>, before the swiper-bundle.min.js tag at
  // the bottom of <body> (layouts/web.php) — the global `Swiper` class
  // isn't defined yet at this point in parsing, so initialization has to
  // wait for the full document (including that later script) to finish.
  document.addEventListener('DOMContentLoaded', function () {
    // Swiper's loop mode clones slide 0 (including its <h1>); keep the page at one <h1>.
    var swiper = new Swiper('#hero-slider', {
      loop: slideCount > 1,
      effect: 'fade',
      fadeEffect: { crossFade: true },
      speed: 700,
      autoplay: (slideCount > 1 && !prefersReducedMotion) ? {
        delay: 6000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      } : false,
      pagination: slideCount > 1 ? { el: '.swiper-pagination', clickable: true } : false,
      navigation: slideCount > 1 ? { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' } : false,
      a11y: { enabled: true },
    });
    document.querySelectorAll('#hero-slider .swiper-slide-duplicate h1').forEach(function (h) {
      var d = document.createElement('h2');
      d.className = h.className;
      d.textContent = h.textContent;
      h.replaceWith(d);
    });
  });
})();
</script>
<?php endif; ?>

<?php if ($messengers !== [] || $c('about_body') !== ''): ?>
  <section class="section-block welcome-section" id="about">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_welcome_eyebrow', 'Welcome')) ?></p>
        <h2><?= e($c('about_heading', 'About Our Campus')) ?></h2>
        <?php if ($c('about_subheading') !== ''): ?><p class="section-lead"><?= e($c('about_subheading')) ?></p><?php endif; ?>
      </div>

      <?php if ($c('about_image') !== '' && is_file(PUBLIC_PATH . $c('about_image'))): ?>
        <img class="welcome-intro-image" src="<?= e(base_url(ltrim($c('about_image'), '/'))) ?>" alt="<?= e($c('about_heading', 'About Our Campus')) ?>" loading="lazy" decoding="async">
      <?php endif; ?>

      <?php if ($c('about_body') !== ''): ?>
        <p class="welcome-intro"><?= e($c('about_body')) ?></p>
      <?php endif; ?>

      <?php if ($messengers !== []): ?>
        <div class="welcome-messages">
          <?php foreach ($messengers as $mIndex => $m): ?>
            <div class="welcome-message-card <?= $mIndex % 2 === 0 ? 'reveal-slide-left' : 'reveal-slide-right' ?>">
              <div class="welcome-message-side">
                <div class="welcome-message-photo">
                  <?php if ($m['photo'] !== '' && is_file(PUBLIC_PATH . $m['photo'])): ?>
                    <img src="<?= e(thumb_url($m['photo'])) ?>" alt="<?= e($m['name']) ?>" loading="lazy" decoding="async">
                  <?php else: ?>
                    <?= icon('person-badge') ?>
                  <?php endif; ?>
                </div>
                <span class="welcome-message-badge"><?= e(mb_strtoupper($m['designation'])) ?></span>
                <p class="welcome-message-name"><?= e($m['name']) ?></p>
                <p class="welcome-message-role"><?= e($m['designation']) ?></p>
              </div>
              <div class="welcome-message-main">
                <span class="welcome-message-quote-mark" aria-hidden="true">&ldquo;</span>
                <p class="welcome-message-subtitle"><?= e($m['designation']) ?> &mdash; IIUI Schools &amp; College, Mardan Campus</p>
                <blockquote class="welcome-message-quote">&ldquo;<?= nl2br(e($m['message'])) ?>&rdquo;</blockquote>
                <div class="welcome-message-footer">
                  <p class="welcome-message-footer-name"><?= e($m['name']) ?></p>
                  <p class="welcome-message-footer-role"><?= e($m['designation']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
<?php endif; ?>

<?php if ($stats !== []): ?>
  <section class="stats-section" id="stats-section">
    <div class="container">
      <div class="stats-grid">
        <?php foreach ($stats as $stat): ?>
          <div class="stat-item">
            <div class="stat-item-icon"><?= icon($stat['icon'] ?: 'bar-chart-line') ?></div>
            <p class="stat-item-value"><span data-count-to="<?= (int) $stat['value'] ?>">0</span><?= e((string) ($stat['suffix'] ?? '')) ?></p>
            <p class="stat-item-label"><?= e($stat['label']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <script>
  (function () {
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var counters = document.querySelectorAll('#stats-section [data-count-to]');
    if (counters.length === 0) return;

    function animate(el) {
      var target = parseInt(el.getAttribute('data-count-to'), 10) || 0;
      if (prefersReducedMotion) {
        el.textContent = target.toLocaleString();
        return;
      }
      var start = null;
      var duration = 1400;
      function step(ts) {
        if (start === null) start = ts;
        var progress = Math.min((ts - start) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(eased * target).toLocaleString();
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    var section = document.getElementById('stats-section');
    if ('IntersectionObserver' in window) {
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            counters.forEach(animate);
            observer.disconnect();
          }
        });
      }, { threshold: 0.4 });
      observer.observe(section);
    } else {
      counters.forEach(animate);
    }
  })();
  </script>
<?php endif; ?>

<?php if ($features !== []): ?>
  <section class="section-block features-section">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_features_eyebrow', 'Why Choose Us')) ?></p>
        <h2><?= e($c('section_features_heading', 'What Sets Our Campus Apart')) ?></h2>
      </div>

      <div class="features-grid reveal-stagger">
        <?php foreach ($features as $feat): ?>
          <div class="feature-card reveal-zoom">
            <div class="feature-card-icon"><?= icon($feat['icon'] ?: 'award') ?></div>
            <h3><?= e($feat['title']) ?></h3>
            <?php if ($feat['description']): ?><p><?= e($feat['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($programs !== []): ?>
  <section class="section-block programs-section" id="academics">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_programs_eyebrow', 'Academic Programs')) ?></p>
        <h2><?= e($c('section_programs_heading', 'A Program for Every Stage')) ?></h2>
      </div>

      <div class="programs-grid reveal-stagger">
        <?php foreach ($programs as $prog): ?>
          <div class="program-card reveal-fade-up">
            <div class="program-card-icon"><?= icon($prog['icon'] ?: 'mortarboard') ?></div>
            <h3><?= e($prog['title']) ?></h3>
            <?php if ($prog['grade_range']): ?><span class="program-card-range"><?= e($prog['grade_range']) ?></span><?php endif; ?>
            <?php if ($prog['description']): ?><p><?= e($prog['description']) ?></p><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($c('cta_heading') !== '' || $c('cta_body') !== ''): ?>
  <section class="admission-cta-section">
    <div class="container">
      <div class="admission-cta-inner reveal-fade-up">
        <?php if ($c('cta_heading') !== ''): ?><h2><?= e($c('cta_heading')) ?></h2><?php endif; ?>
        <?php if ($c('cta_body') !== ''): ?><p><?= e($c('cta_body')) ?></p><?php endif; ?>

        <ul class="admission-cta-badges">
          <?php foreach ($ctaBadges as $badge): ?>
            <li><?= icon('check-circle-fill') ?> <?= e($badge) ?></li>
          <?php endforeach; ?>
        </ul>

        <div class="admission-cta-actions">
          <?php if ($c('cta_button_text') !== '' && $c('cta_button_url') !== ''): ?>
            <a class="btn btn-accent btn-lg" href="<?= e(resolve_url($c('cta_button_url'))) ?>"><?= e($c('cta_button_text')) ?></a>
          <?php endif; ?>
          <a class="btn btn-outline-white btn-lg" href="<?= e($contactHref) ?>">Contact Us</a>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($facultyPreview !== []): ?>
  <section class="section-block faculty-section">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_faculty_eyebrow', 'Our Faculty')) ?></p>
        <h2><?= e($c('faculty_heading', 'Meet Our Expert Faculty')) ?></h2>
        <?php if ($c('faculty_subheading') !== ''): ?><p class="section-lead"><?= e($c('faculty_subheading')) ?></p><?php endif; ?>
      </div>

      <div class="faculty-grid reveal-stagger">
        <?php foreach ($facultyPreview as $member): ?>
          <div class="faculty-card reveal-zoom">
            <div class="faculty-card-photo">
              <?php if (!empty($member['photo']) && is_file(PUBLIC_PATH . $member['photo'])): ?>
                <img src="<?= e(thumb_url($member['photo'])) ?>" alt="<?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <?= icon('person-badge') ?>
              <?php endif; ?>
            </div>
            <h3><?= e(trim($member['first_name'] . ' ' . (string) $member['last_name'])) ?></h3>
            <?php if (!empty($member['designation_name'])): ?><p class="faculty-card-role"><?= e($member['designation_name']) ?></p><?php endif; ?>
            <?php if (!empty($member['department_name'])): ?><p class="faculty-card-dept"><?= e($member['department_name']) ?></p><?php endif; ?>
            <?php if ((int) $member['experience_years'] > 0): ?><span class="faculty-card-exp"><?= (int) $member['experience_years'] ?>+ Yrs</span><?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="section-cta">
        <a class="btn btn-secondary" href="<?= e(route('faculty.index')) ?>">View All Faculty</a>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($facilities !== []): ?>
  <section class="section-block facilities-section">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_facilities_eyebrow', 'Facilities')) ?></p>
        <h2><?= e($c('section_facilities_heading', 'Everything Our Campus Offers')) ?></h2>
      </div>

      <div class="facilities-slider swiper" id="facilities-slider">
        <div class="swiper-wrapper">
          <?php foreach ($facilities as $fac): ?>
            <div class="swiper-slide">
              <div class="facility-card">
                <div class="facility-card-media">
                  <?php if ($fac['image'] && is_file(PUBLIC_PATH . $fac['image'])): ?>
                    <img src="<?= e(thumb_url($fac['image'])) ?>" alt="<?= e($fac['title']) ?>" loading="lazy" decoding="async">
                  <?php else: ?>
                    <div class="facility-card-placeholder"><?= icon($fac['icon'] ?: 'building') ?></div>
                  <?php endif; ?>
                  <div class="facility-card-scrim"></div>
                  <h3 class="facility-card-title"><?= e($fac['title']) ?></h3>
                </div>
                <?php if ($fac['description']): ?>
                  <p class="facility-card-desc"><?= e($fac['description']) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($facilities) > 1): ?>
          <div class="swiper-pagination"></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script>
  (function () {
    var count = <?= count($facilities) ?>;
    document.addEventListener('DOMContentLoaded', function () {
      new Swiper('#facilities-slider', {
        loop: count > 4,
        slidesPerView: 1.15,
        spaceBetween: 20,
        autoplay: count > 1 ? { delay: 4000, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
        pagination: count > 1 ? { el: '#facilities-slider .swiper-pagination', clickable: true } : false,
        a11y: { enabled: true },
        breakpoints: {
          576:  { slidesPerView: 2, spaceBetween: 20 },
          992:  { slidesPerView: 3, spaceBetween: 24 },
          1200: { slidesPerView: 4, spaceBetween: 24 },
        },
      });
    });
  })();
  </script>
<?php endif; ?>

<?php if ($c('campus_heading') !== '' || $c('campus_body') !== '' || $campusImage !== null): ?>
  <section class="section-block campus-section">
    <div class="container">
      <div class="campus-split">
        <div class="campus-split-text reveal-slide-left">
          <p class="hero-eyebrow"><?= e($c('section_campus_eyebrow', 'Campus Life')) ?></p>
          <?php if ($c('campus_heading') !== ''): ?><h2><?= e($c('campus_heading')) ?></h2><?php endif; ?>
          <?php if ($c('campus_body') !== ''): ?><p><?= e($c('campus_body')) ?></p><?php endif; ?>

          <ul class="campus-highlights">
            <?php foreach ($campusHighlights as $hi => $highlight): ?>
              <li><?= icon($highlightIcons[$hi % count($highlightIcons)]) ?> <?= e($highlight) ?></li>
            <?php endforeach; ?>
          </ul>

          <a class="btn btn-primary" href="<?= e(route('gallery.index')) ?>"><?= e($c('campus_button_text', 'Explore Student Life')) ?></a>
        </div>

        <?php if ($campusImage !== null): ?>
          <div class="campus-split-image reveal-slide-right">
            <img src="<?= e(base_url(ltrim($campusImage['image_path'], '/'))) ?>" alt="<?= e((string) ($campusImage['caption'] ?: 'Campus life at IIUI Schools & College'))?>" loading="lazy" decoding="async">
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if ($testimonials !== []): ?>
  <section class="section-block testimonials-section">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_testimonials_eyebrow', 'In Their Words')) ?></p>
        <h2><?= e($c('section_testimonials_heading', 'What Our Students Say')) ?></h2>
      </div>

      <div class="testimonials-slider swiper" id="testimonials-slider">
        <div class="swiper-wrapper">
          <?php $cardColors = ['brand', 'gold', 'info', 'success', 'violet']; ?>
          <?php foreach ($testimonials as $ti => $t): ?>
            <?php $cardColor = $cardColors[$ti % count($cardColors)]; ?>
            <div class="swiper-slide">
              <figure class="testimonial-card testimonial-card--<?= e($cardColor) ?>">
                <blockquote class="testimonial-quote">&ldquo;<?= e($t['content']) ?>&rdquo;</blockquote>
                <?php if ($t['rating']): ?>
                  <div class="testimonial-rating" role="img" aria-label="<?= (int) $t['rating'] ?> out of 5 stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <?= $i <= (int) $t['rating'] ? icon('star-fill') : icon('star') ?>
                    <?php endfor; ?>
                  </div>
                <?php endif; ?>
                <figcaption class="testimonial-author">
                  <div class="testimonial-avatar">
                    <?php if ($t['photo'] && is_file(PUBLIC_PATH . $t['photo'])): ?>
                      <img src="<?= e(thumb_url($t['photo'])) ?>" alt="" loading="lazy" decoding="async">
                    <?php else: ?>
                      <?= e(mb_strtoupper(mb_substr($t['name'], 0, 1))) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <span class="testimonial-name"><?= e($t['name']) ?></span>
                    <span class="testimonial-role">
                      <?= $t['designation'] ? e($t['designation']) . ' &middot; ' : '' ?><?= e($typeLabel[$t['type']] ?? ucfirst($t['type'])) ?>
                    </span>
                  </div>
                </figcaption>
              </figure>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if (count($testimonials) > 1): ?>
          <div class="swiper-pagination"></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <script>
  (function () {
    var count = <?= count($testimonials) ?>;
    document.addEventListener('DOMContentLoaded', function () {
      new Swiper('#testimonials-slider', {
        loop: count > 4,
        slidesPerView: 1,
        spaceBetween: 24,
        autoplay: count > 1 ? { delay: 7000, disableOnInteraction: false, pauseOnMouseEnter: true } : false,
        pagination: count > 1 ? { el: '#testimonials-slider .swiper-pagination', clickable: true } : false,
        a11y: { enabled: true },
        breakpoints: {
          576:  { slidesPerView: 2 },
          992:  { slidesPerView: 3 },
          1200: { slidesPerView: 4 },
        },
      });
    });
  })();
  </script>
<?php endif; ?>

<?php if ($latestNews !== []): ?>
  <section class="section-block news-preview-section">
    <div class="container">
      <div class="section-heading reveal-fade-up">
        <p class="hero-eyebrow"><?= e($c('section_news_eyebrow', 'Latest News')) ?></p>
        <h2><?= e($c('section_news_heading', "What's Happening on Campus")) ?></h2>
      </div>

      <div class="news-grid reveal-stagger">
        <?php foreach ($latestNews as $post): ?>
          <a class="news-card reveal-fade-up" href="<?= e(route('news.show', ['slug' => $post['slug']])) ?>">
            <div class="news-card-cover">
              <?php if ($post['featured_image'] && is_file(PUBLIC_PATH . $post['featured_image'])): ?>
                <img src="<?= e(thumb_url($post['featured_image'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" decoding="async">
              <?php else: ?>
                <div class="news-card-placeholder"><?= icon('newspaper') ?></div>
              <?php endif; ?>
            </div>
            <div class="news-card-body">
              <span class="news-card-meta"><?= e(date('d M Y', strtotime((string) $post['published_at']))) ?></span>
              <h3><?= e($post['title']) ?></h3>
              <?php if ($post['excerpt']): ?><p><?= e(mb_strimwidth((string) $post['excerpt'], 0, 110, '…')) ?></p><?php endif; ?>
              <span class="news-card-readmore">Read More <?= icon('arrow-right') ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="section-cta">
        <a class="btn btn-secondary" href="<?= e(route('news.index')) ?>">View All News</a>
      </div>
    </div>
  </section>
<?php endif; ?>
