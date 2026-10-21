<?php
/**
 * Contact section — map, address/phone/email, contact form. Shared between
 * the homepage and the dedicated /contact page so there is one copy of this
 * markup, not two. Self-sufficient (fetches its own settings) the same way
 * _header.php/_footer.php are, so any page can `require` it without having
 * to pass matching variables in.
 */
use App\Repositories\SettingRepository;

$contactSettings = new SettingRepository();
$contactAddress = trim((string) $contactSettings->get('address', ''));
$contactPhone   = trim((string) $contactSettings->get('phone', ''));
$contactEmailDisplay = trim((string) $contactSettings->get('email', ''));
$mapSrc = $contactAddress !== '' ? 'https://www.google.com/maps?q=' . rawurlencode($contactAddress) . '&output=embed' : '';
$oldContact = flash('_old_input', []);
$contactField = static fn (string $key) => (string) ($oldContact[$key] ?? '');
?>
<section class="section-block contact-section" id="contact">
  <div class="container">
    <div class="section-heading reveal-fade-up">
      <p class="hero-eyebrow">Contact</p>
      <h2>Get in Touch</h2>
    </div>

    <?php if ($contactStatus = flash('contact_status')): ?>
      <div class="alert-box alert-success contact-alert" role="status"><?= e((string) $contactStatus) ?></div>
    <?php endif; ?>

    <div class="contact-grid">
      <div class="contact-info reveal-slide-left">
        <?php if ($mapSrc !== ''): ?>
          <div class="contact-map">
            <iframe src="<?= e($mapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Campus location map"></iframe>
          </div>
        <?php endif; ?>

        <?php if ($contactAddress !== ''): ?>
          <div class="contact-detail"><?= icon('geo-alt-fill') ?> <span><?= e($contactAddress) ?></span></div>
        <?php endif; ?>
        <?php if ($contactPhone !== ''): ?>
          <div class="contact-detail"><?= icon('telephone') ?> <a href="tel:<?= e(preg_replace('/[^+0-9]/', '', $contactPhone)) ?>"><?= e($contactPhone) ?></a></div>
        <?php endif; ?>
        <?php if ($contactEmailDisplay !== ''): ?>
          <div class="contact-detail"><?= icon('envelope') ?> <a href="mailto:<?= e($contactEmailDisplay) ?>"><?= e($contactEmailDisplay) ?></a></div>
        <?php endif; ?>
      </div>

      <?php
        // Inline errors are tied to their field (aria-describedby) and announced (role="alert").
        $formError = static fn (string $key): string => has_error($key) ? (string) error($key) : '';
        $fieldAttrs = static fn (string $key): string => $formError($key) !== '' ? ' aria-invalid="true" aria-describedby="err-' . $key . '"' : '';
        $fieldError = static function (string $key) use ($formError): string {
            $msg = $formError($key);

            return $msg !== '' ? '<p class="field-error" id="err-' . $key . '" role="alert">' . e($msg) . '</p>' : '';
        };
      ?>
      <form class="contact-form reveal-slide-right" method="post" action="<?= e(route('contact.store')) ?>" data-submit-lock>
        <?= csrf_field() ?>
        <div style="position:absolute;left:-9999px" aria-hidden="true">
          <label for="f-website">Leave this field blank</label>
          <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off">
        </div>

        <p class="form-note"><span class="req" aria-hidden="true">*</span> Required fields</p>

        <div class="field-grid">
          <div class="field">
            <label for="f-name">Name <span class="req" aria-hidden="true">*</span></label>
            <input class="inp" type="text" id="f-name" name="name" value="<?= e($contactField('name')) ?>" required maxlength="120" autocomplete="name"<?= $fieldAttrs('name') ?>>
            <?= $fieldError('name') ?>
          </div>
          <div class="field">
            <label for="f-email">Email <span class="req" aria-hidden="true">*</span></label>
            <input class="inp" type="email" id="f-email" name="email" value="<?= e($contactField('email')) ?>" required maxlength="191" autocomplete="email" inputmode="email"<?= $fieldAttrs('email') ?>>
            <?= $fieldError('email') ?>
          </div>
        </div>

        <div class="field-grid">
          <div class="field">
            <label for="f-phone">Phone <span class="optional">(optional)</span></label>
            <input class="inp" type="tel" id="f-phone" name="phone" value="<?= e($contactField('phone')) ?>" maxlength="20" autocomplete="tel" inputmode="tel" placeholder="0300 1234567"<?= $fieldAttrs('phone') ?>>
            <?= $fieldError('phone') ?>
          </div>
          <div class="field">
            <label for="f-subject">Subject <span class="optional">(optional)</span></label>
            <input class="inp" type="text" id="f-subject" name="subject" value="<?= e($contactField('subject')) ?>" maxlength="180"<?= $fieldAttrs('subject') ?>>
            <?= $fieldError('subject') ?>
          </div>
        </div>

        <div class="field">
          <label for="f-message">Message <span class="req" aria-hidden="true">*</span></label>
          <textarea class="inp" style="height:auto;padding:10px 12px" rows="5" id="f-message" name="message" required maxlength="2000"<?= $fieldAttrs('message') ?>><?= e($contactField('message')) ?></textarea>
          <?= $fieldError('message') ?>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" data-loading-text="Sending…"><?= icon('send') ?> <span>Send Message</span></button>
      </form>
    </div>
  </div>
</section>
