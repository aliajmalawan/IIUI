<?php
/**
 * Topbar. Notification data is fetched here (not passed from every
 * controller) because the bell appears on every admin page — a genuinely
 * cross-cutting UI element, not page content.
 */
use App\Core\Auth;
use App\Repositories\NotificationRepository;

$notifRepo = new NotificationRepository();
$authUserId = (int) Auth::id();
$unreadCount = $notifRepo->unreadCount($authUserId);
$recentNotifications = $notifRepo->recent($authUserId, 8);
$initials = mb_strtoupper(mb_substr((string) ($authUser['first_name'] ?? '?'), 0, 1));
$authAvatarPath = (string) ($authUser['avatar'] ?? '');
$hasAuthAvatar = $authAvatarPath !== '' && is_file(PUBLIC_PATH . $authAvatarPath);
?>
<header class="admin-topbar">
  <button type="button" class="topbar-toggle topbar-toggle-mobile" id="sidebar-mobile-toggle" aria-label="Open menu">
    <?= icon('list') ?>
  </button>
  <button type="button" class="topbar-toggle topbar-toggle-desktop" id="sidebar-desktop-toggle" aria-label="Toggle sidebar">
    <?= icon('list') ?>
  </button>

  <div class="topbar-breadcrumb"><strong><?= e($title ?? 'Dashboard') ?></strong></div>

  <button type="button" class="topbar-search" id="cmdk-trigger">
    <?= icon('search') ?>
    <span>Search admin&hellip;</span>
    <kbd>Ctrl K</kbd>
  </button>

  <div class="topbar-actions">

    <button type="button" class="topbar-icon-btn" id="theme-toggle-btn" aria-label="Toggle dark mode" title="Toggle theme">
      <?= icon('sun-fill', 'icon theme-icon-light') ?>
      <?= icon('moon-stars-fill', 'icon theme-icon-dark') ?>
    </button>

    <div class="dropdown-wrap" id="notif-dropdown">
      <button type="button" class="topbar-icon-btn" aria-label="Notifications">
        <?= icon('bell') ?>
        <?php if ($unreadCount > 0): ?>
          <span class="topbar-badge"><?= $unreadCount > 99 ? '99+' : $unreadCount ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-panel">
        <div class="dropdown-header">
          <span>Notifications</span>
          <?php if ($unreadCount > 0): ?>
            <a href="#" id="notif-mark-all">Mark all read</a>
          <?php endif; ?>
        </div>
        <?php if ($recentNotifications === []): ?>
          <div class="dropdown-empty">
            <?= icon('bell', 'icon') ?>
            <p>No notifications yet.</p>
          </div>
        <?php else: ?>
          <div class="notif-list">
            <?php foreach ($recentNotifications as $notif): ?>
              <div class="notif-item<?= $notif['read_at'] === null ? ' is-unread' : '' ?>" data-notif-id="<?= (int) $notif['id'] ?>">
                <span class="notif-icon"><?= icon($notif['icon'] ?: 'bell') ?></span>
                <div class="notif-body">
                  <p><?= e($notif['title']) ?></p>
                  <span><?= e(date('d M, h:i A', strtotime((string) $notif['created_at']))) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="dropdown-wrap" id="profile-dropdown">
      <button type="button" class="topbar-avatar-btn">
        <span class="avatar-circle">
          <?php if ($hasAuthAvatar): ?>
            <img src="<?= e(base_url(ltrim($authAvatarPath, '/'))) ?>" alt="">
          <?php else: ?>
            <?= e($initials) ?>
          <?php endif; ?>
        </span>
        <span class="topbar-avatar-name"><?= e((string) ($authUser['first_name'] ?? '')) ?></span>
        <?= icon('chevron-down', 'icon') ?>
      </button>
      <div class="dropdown-panel">
        <div class="profile-menu">
          <div class="profile-menu-header">
            <span class="avatar-circle">
              <?php if ($hasAuthAvatar): ?>
                <img src="<?= e(base_url(ltrim($authAvatarPath, '/'))) ?>" alt="">
              <?php else: ?>
                <?= e($initials) ?>
              <?php endif; ?>
            </span>
            <div>
              <strong><?= e(trim(($authUser['first_name'] ?? '') . ' ' . ($authUser['last_name'] ?? ''))) ?></strong>
              <span><?= e((string) ($authUser['email'] ?? '')) ?></span>
            </div>
          </div>
          <hr>
          <a class="profile-menu-item" href="<?= e(route('admin.profile')) ?>"><?= icon('person-circle') ?> My Profile</a>
          <a class="profile-menu-item" href="<?= e(route('admin.appearance')) ?>"><?= icon('moon-stars-fill') ?> Appearance</a>
          <hr>
          <form method="post" action="<?= e(route('logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="profile-menu-item is-danger"><?= icon('box-arrow-right') ?> Logout</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</header>
