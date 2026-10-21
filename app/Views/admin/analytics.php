<?php
/**
 * @var int $admissionsTotal
 * @var array<string,int> $admissionsStatusCounts
 * @var array<string,int> $admissionsMonthly
 * @var int $contactMessagesTotal
 * @var array<string,int> $contactMessagesStatusCounts
 * @var int $postsTotal
 * @var array<string,int> $postsStatusCounts
 * @var int $eventsTotal
 * @var int $galleriesTotal
 * @var array<string,int> $galleriesStatusCounts
 * @var int $downloadsTotal
 * @var int $staffTotal
 * @var int $usersTotal
 * @var int $onlineNow
 * @var int $totalViews
 * @var int $views30
 * @var int $views7
 * @var int $viewsToday
 * @var int $viewsYesterday
 * @var int $viewsMonth
 * @var int $viewsYear
 * @var int $days
 * @var int[] $ranges
 * @var int $uniqueVisitors30
 * @var array<string,int> $dailyCounts
 * @var array<int,array<string,mixed>> $topPages
 * @var array<int,array<string,mixed>> $topReferrers
 * @var array{browsers:array<string,int>,systems:array<string,int>,devices:array<string,int>} $agents
 * @var array<int,array<string,mixed>> $recentVisits
 */

// Human label + badge color per status slug. Falls back to Title Case /
// neutral badge for anything not listed, so a new enum value never breaks
// the page — it just renders unstyled-but-correct until this list catches up.
$statusMeta = static function (string $status): array {
    $known = [
        'new' => ['New', 'badge-warning'], 'read' => ['Read', 'badge-info'], 'replied' => ['Replied', 'badge-success'],
        'spam' => ['Spam', 'badge-danger'], 'archived' => ['Archived', 'badge-mute'],
        'submitted' => ['Submitted', 'badge-warning'], 'under_review' => ['Under Review', 'badge-info'],
        'shortlisted' => ['Shortlisted', 'badge-info'], 'test_scheduled' => ['Test Scheduled', 'badge-info'],
        'test_taken' => ['Test Taken', 'badge-info'], 'interview' => ['Interview', 'badge-info'],
        'admitted' => ['Admitted', 'badge-success'], 'rejected' => ['Rejected', 'badge-danger'],
        'waitlisted' => ['Waitlisted', 'badge-warning'], 'withdrawn' => ['Withdrawn', 'badge-mute'],
        'draft' => ['Draft', 'badge-mute'], 'published' => ['Published', 'badge-success'],
        'scheduled' => ['Scheduled', 'badge-info'],
    ];

    return $known[$status] ?? [ucwords(str_replace('_', ' ', $status)), 'badge-mute'];
};

$renderBreakdown = static function (array $counts) use ($statusMeta): void {
    $total = array_sum($counts);
    foreach ($counts as $status => $count) {
        [$label, $badgeClass] = $statusMeta((string) $status);
        $fillClass = 'breakdown-fill--' . str_replace('badge-', '', $badgeClass);
        $pct = $total > 0 ? round($count / $total * 100) : 0;
        ?>
        <div class="breakdown-row">
          <div class="breakdown-row-head">
            <span class="badge <?= $badgeClass ?>"><?= e($label) ?></span>
            <span class="meta"><?= $pct ?>% (<?= number_format($count) ?>)</span>
          </div>
          <div class="breakdown-track"><div class="breakdown-fill <?= $fillClass ?>" style="width: <?= $pct ?>%;"></div></div>
        </div>
        <?php
    }
};
?>
<div class="admin-page-header">
  <div>
    <h1>Analytics</h1>
    <p>Traffic, leads, and content at a glance.</p>
  </div>
  <span class="online-now-badge"><span class="online-now-dot"></span> <?= number_format($onlineNow) ?> online now</span>
</div>

<h2 class="analytics-section-title">Leads &amp; Content</h2>

<div class="kpi-row-6">
  <div class="kpi-card" style="--bar: var(--brand-solid); --bar-tint: var(--brand-tint); --bar-text: var(--brand-text)">
    <span class="kpi-icon"><?= icon('inbox') ?></span>
    <p class="kpi-label">Admissions</p>
    <p class="kpi-value"><?= number_format($admissionsTotal) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--warning-500); --bar-tint: var(--warning-50); --bar-text: var(--warning-700)">
    <span class="kpi-icon"><?= icon('envelope') ?></span>
    <p class="kpi-label">Messages</p>
    <p class="kpi-value"><?= number_format($contactMessagesTotal) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--info-500); --bar-tint: var(--info-50); --bar-text: var(--info-700)">
    <span class="kpi-icon"><?= icon('newspaper') ?></span>
    <p class="kpi-label">News Posts</p>
    <p class="kpi-value"><?= number_format($postsTotal) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--accent-solid); --bar-tint: var(--accent-tint); --bar-text: var(--accent-text)">
    <span class="kpi-icon"><?= icon('calendar-event') ?></span>
    <p class="kpi-label">Events</p>
    <p class="kpi-value"><?= number_format($eventsTotal) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--success-500); --bar-tint: var(--success-50); --bar-text: var(--success-700)">
    <span class="kpi-icon"><?= icon('images') ?></span>
    <p class="kpi-label">Albums</p>
    <p class="kpi-value"><?= number_format($galleriesTotal) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--info-500); --bar-tint: var(--info-50); --bar-text: var(--info-700)">
    <span class="kpi-icon"><?= icon('person-badge') ?></span>
    <p class="kpi-label">Faculty</p>
    <p class="kpi-value"><?= number_format($staffTotal) ?></p>
  </div>
</div>

<div class="dash-grid-2">
  <div class="admin-card">
    <div class="admin-card-header"><h2>Admission Applications by Status</h2></div>
    <?php if ($admissionsStatusCounts === []): ?>
      <p class="text-muted" style="color: var(--text-muted); margin: 0;">No applications yet.</p>
    <?php else: ?>
      <?= $renderBreakdown($admissionsStatusCounts) ?>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <div class="admin-card-header"><h2>Contact Messages by Status</h2></div>
    <?php if ($contactMessagesStatusCounts === []): ?>
      <p style="color: var(--text-muted); margin: 0;">No messages yet.</p>
    <?php else: ?>
      <?= $renderBreakdown($contactMessagesStatusCounts) ?>
    <?php endif; ?>
  </div>
</div>

<div class="admin-card" style="margin-bottom: var(--space-4);">
  <div class="admin-card-header"><h2>New Admission Applications — Last 6 Months</h2></div>
  <div class="chart-wrap" style="height: 220px;">
    <canvas id="leadsChart" role="img" aria-label="Bar chart of new admission applications per month"></canvas>
  </div>
</div>

<div class="dash-grid-2">
  <div class="admin-card">
    <div class="admin-card-header"><h2>News Posts by Status</h2></div>
    <?php if ($postsStatusCounts === []): ?>
      <p style="color: var(--text-muted); margin: 0;">No posts yet.</p>
    <?php else: ?>
      <?= $renderBreakdown($postsStatusCounts) ?>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <div class="admin-card-header"><h2>Gallery Albums by Status</h2></div>
    <?php if ($galleriesStatusCounts === []): ?>
      <p style="color: var(--text-muted); margin: 0;">No albums yet.</p>
    <?php else: ?>
      <?= $renderBreakdown($galleriesStatusCounts) ?>
    <?php endif; ?>
  </div>
</div>

<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:var(--space-3);margin-bottom:var(--space-3);">
  <h2 class="analytics-section-title" style="margin:0;">Website Traffic</h2>
  <div style="display:flex;flex-wrap:wrap;align-items:center;gap:var(--space-2);">
    <?php foreach ($ranges as $r): ?>
      <a class="btn btn-sm <?= $r === $days ? 'btn-primary' : 'btn-secondary' ?>" href="?days=<?= (int) $r ?>"<?= $r === $days ? ' aria-current="true"' : '' ?>><?= (int) $r ?> days</a>
    <?php endforeach; ?>
    <a class="btn btn-sm btn-secondary" href="?days=<?= (int) $days ?>&amp;export=csv"><?= icon('download') ?> Export CSV</a>
  </div>
</div>

<div class="kpi-row-6">
  <div class="kpi-card" style="--bar: var(--brand-solid); --bar-tint: var(--brand-tint); --bar-text: var(--brand-text)">
    <span class="kpi-icon"><?= icon('sun') ?></span>
    <p class="kpi-label">Today</p>
    <p class="kpi-value"><?= number_format($viewsToday) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--info-500); --bar-tint: var(--info-50); --bar-text: var(--info-700)">
    <span class="kpi-icon"><?= icon('moon-stars-fill') ?></span>
    <p class="kpi-label">Yesterday</p>
    <p class="kpi-value"><?= number_format($viewsYesterday) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--accent-solid); --bar-tint: var(--accent-tint); --bar-text: var(--accent-text)">
    <span class="kpi-icon"><?= icon('calendar-week') ?></span>
    <p class="kpi-label">Last 7 Days</p>
    <p class="kpi-value"><?= number_format($views7) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--success-500); --bar-tint: var(--success-50); --bar-text: var(--success-700)">
    <span class="kpi-icon"><?= icon('calendar-event') ?></span>
    <p class="kpi-label">This Month</p>
    <p class="kpi-value"><?= number_format($viewsMonth) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--warning-500); --bar-tint: var(--warning-50); --bar-text: var(--warning-700)">
    <span class="kpi-icon"><?= icon('calendar3') ?></span>
    <p class="kpi-label">This Year</p>
    <p class="kpi-value"><?= number_format($viewsYear) ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--info-500); --bar-tint: var(--info-50); --bar-text: var(--info-700)">
    <span class="kpi-icon"><?= icon('graph-up') ?></span>
    <p class="kpi-label">All Time</p>
    <p class="kpi-value"><?= number_format($totalViews) ?></p>
  </div>
</div>

<div class="dash-grid-2">
  <div class="admin-card">
    <div class="stat-pair-card">
      <div>
        <p class="kpi-label" style="margin:0;">Unique Visitors (<?= (int) $days ?>d)</p>
        <p class="stat-pair-value"><?= number_format($uniqueVisitors30) ?></p>
      </div>
      <div class="stat-pair-meta">distinct visitors</div>
    </div>
  </div>
  <div class="admin-card">
    <div class="stat-pair-card">
      <div>
        <p class="kpi-label" style="margin:0;">Views (<?= (int) $days ?>d)</p>
        <p class="stat-pair-value"><?= number_format($views30) ?></p>
      </div>
      <div class="stat-pair-meta">&asymp; <?= $uniqueVisitors30 > 0 ? number_format($views30 / $uniqueVisitors30, 1) : '0' ?> pages per visitor</div>
    </div>
  </div>
</div>

<div class="admin-card" style="margin-bottom: var(--space-4);">
  <div class="admin-card-header"><h2>Page Views — Last <?= (int) $days ?> Days</h2></div>
  <div class="chart-wrap">
    <canvas id="analyticsChart" role="img" aria-label="Line chart of page views over the last <?= (int) $days ?> days"></canvas>
  </div>
</div>

<div class="dash-grid">
  <div class="admin-card">
    <div class="admin-card-header"><h2>Top Pages (<?= (int) $days ?>d)</h2></div>
    <?php if ($topPages === []): ?>
      <p style="color: var(--text-muted); margin: 0;">No page views recorded yet.</p>
    <?php else: ?>
      <?php $maxViews = max(array_column($topPages, 'views')); ?>
      <?php foreach ($topPages as $p): ?>
        <div class="breakdown-row">
          <div class="breakdown-row-head">
            <span class="mono" style="font-family: var(--font-mono); font-size: 12px;"><?= e($p['url'] !== '' ? $p['url'] : '/') ?></span>
            <span class="meta"><?= number_format((int) $p['views']) ?></span>
          </div>
          <div class="breakdown-track"><div class="breakdown-fill" style="width: <?= $maxViews > 0 ? round((int) $p['views'] / $maxViews * 100) : 0 ?>%;"></div></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div class="admin-card">
    <div class="admin-card-header"><h2>Top External Referrers (<?= (int) $days ?>d)</h2></div>
    <?php if ($topReferrers === []): ?>
      <p style="color: var(--text-muted); margin: 0;">No external referrers yet &mdash; traffic so far is direct.</p>
    <?php else: ?>
      <?php foreach ($topReferrers as $r): ?>
        <div class="breakdown-row-head" style="margin-bottom: var(--space-3);">
          <span class="text-truncate" style="max-width: 75%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($r['referrer']) ?>"><?= e($r['referrer']) ?></span>
          <span class="badge badge-mute"><?= number_format((int) $r['views']) ?></span>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class="dash-grid-3">
  <?php
  $breakdownCards = [
      'Browsers (' . (int) $days . 'd)' => $agents['browsers'],
      'Operating Systems (' . (int) $days . 'd)' => $agents['systems'],
      'Devices (' . (int) $days . 'd)' => $agents['devices'],
  ];
  ?>
  <?php foreach ($breakdownCards as $title => $data): ?>
    <div class="admin-card">
      <div class="admin-card-header"><h2><?= e($title) ?></h2></div>
      <?php if ($data === []): ?>
        <p style="color: var(--text-muted); margin: 0;">No data yet.</p>
      <?php else: ?>
        <?php $total = array_sum($data); ?>
        <?php foreach ($data as $name => $count): ?>
          <?php $pct = $total > 0 ? round($count / $total * 100) : 0; ?>
          <div class="breakdown-row">
            <div class="breakdown-row-head">
              <span class="label"><?= e($name) ?></span>
              <span class="meta"><?= $pct ?>% (<?= number_format($count) ?>)</span>
            </div>
            <div class="breakdown-track"><div class="breakdown-fill" style="width: <?= $pct ?>%;"></div></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="admin-card" style="padding: 0;">
  <div class="admin-card-header" style="padding: var(--space-4) var(--space-4) 0;"><h2>Recent Visits (last 20)</h2></div>
  <div class="admin-table-wrap" style="border: none;">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Time</th>
          <th>Page</th>
          <th>Browser / System</th>
          <th>Came From</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recentVisits === []): ?>
          <tr><td colspan="4" style="color: var(--text-muted);">No visits recorded yet.</td></tr>
        <?php else: ?>
          <?php foreach ($recentVisits as $v): ?>
            <?php $parsed = \App\Repositories\AnalyticsRepository::classifyUserAgent((string) $v['user_agent']); ?>
            <tr>
              <td class="mono" style="white-space: nowrap;"><?= e(date('M j, H:i', strtotime((string) $v['viewed_at']))) ?></td>
              <td class="mono"><?= e($v['url'] !== '' ? $v['url'] : '/') ?></td>
              <td><?= e($parsed['browser']) ?> &middot; <?= e($parsed['system']) ?> &middot; <?= e($parsed['device']) ?></td>
              <td style="max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e((string) ($v['referrer'] ?? '')) ?>">
                <?= $v['referrer'] ? e((string) $v['referrer']) : '<span style="color: var(--text-muted);">Direct</span>' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var muted = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();
  var gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-subtle').trim();
  var tooltipBg = getComputedStyle(document.documentElement).getPropertyValue('--bg-inverse').trim();
  var tooltipText = getComputedStyle(document.documentElement).getPropertyValue('--text-inverse').trim();
  var brand = getComputedStyle(document.documentElement).getPropertyValue('--brand-solid').trim();

  var dailyLabels = <?= json_encode(array_map(static fn ($d) => date('M j', strtotime($d)), array_keys($dailyCounts))) ?>;
  var dailyData = <?= json_encode(array_values($dailyCounts)) ?>;
  var trafficCanvas = document.getElementById('analyticsChart');
  if (trafficCanvas && window.Chart) {
    new Chart(trafficCanvas, {
      type: 'line',
      data: {
        labels: dailyLabels,
        datasets: [{
          label: 'Page Views',
          data: dailyData,
          borderColor: brand,
          backgroundColor: brand + '14',
          fill: true,
          tension: 0.3,
          pointRadius: 2,
        }],
      },
      options: {
        maintainAspectRatio: false,
        animation: { duration: 600 },
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, padding: 10, cornerRadius: 8 },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: muted, font: { size: 11 } } },
          y: { beginAtZero: true, ticks: { precision: 0, color: muted, font: { size: 11 } }, grid: { color: gridColor } },
        },
      },
    });
  }

  var leadsLabels = <?= json_encode(array_map(static fn ($ym) => date('M Y', strtotime($ym . '-01')), array_keys($admissionsMonthly))) ?>;
  var leadsData = <?= json_encode(array_values($admissionsMonthly)) ?>;
  var leadsCanvas = document.getElementById('leadsChart');
  if (leadsCanvas && window.Chart) {
    new Chart(leadsCanvas, {
      type: 'bar',
      data: {
        labels: leadsLabels,
        datasets: [{
          label: 'New Applications',
          data: leadsData,
          backgroundColor: brand,
          borderRadius: 4,
          maxBarThickness: 40,
        }],
      },
      options: {
        maintainAspectRatio: false,
        animation: { duration: 600 },
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, padding: 10, cornerRadius: 8 },
        },
        scales: {
          x: { grid: { display: false }, ticks: { color: muted, font: { size: 11 } } },
          y: { beginAtZero: true, ticks: { precision: 0, color: muted, font: { size: 11 } }, grid: { color: gridColor } },
        },
      },
    });
  }
});
</script>
