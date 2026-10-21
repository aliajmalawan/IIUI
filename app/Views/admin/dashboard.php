<?php
/**
 * @var int $totalStudents
 * @var int $totalStaff
 * @var int $totalClasses
 * @var int $totalUsers
 * @var int $pendingAdmissions
 * @var array|null $currentSession
 * @var array $classesByWing
 * @var array $subjectsByType
 * @var array $recentAdmissions
 */
$wingLabels = ['primary' => 'Primary', 'middle' => 'Middle', 'secondary' => 'Secondary', 'college' => 'College'];
$wingChart = array_map(static fn (array $r) => ['label' => $wingLabels[$r['wing']] ?? $r['wing'], 'total' => (int) $r['total']], $classesByWing);

$typeLabels = ['theory' => 'Theory', 'practical' => 'Practical', 'both' => 'Theory + Practical'];
$typeChart = array_map(static fn (array $r) => ['label' => $typeLabels[$r['type']] ?? $r['type'], 'total' => (int) $r['total']], $subjectsByType);

$admissionStatusBadge = [
    'submitted'      => 'badge-info',
    'under_review'   => 'badge-mute',
    'shortlisted'    => 'badge-info',
    'test_scheduled' => 'badge-mute',
    'test_taken'     => 'badge-mute',
    'interview'      => 'badge-mute',
    'admitted'       => 'badge-success',
    'rejected'       => 'badge-danger',
    'waitlisted'     => 'badge-mute',
    'withdrawn'      => 'badge-mute',
];
?>
<div class="admin-page-header">
  <div>
    <h1>Dashboard</h1>
    <p>Welcome back &mdash; here's what's happening at IIUI Mardan Campus.</p>
  </div>
</div>

<?php $canDash = \App\Core\Auth::can('dashboard.view'); ?>
<?php if ($canDash): ?>
<div class="kpi-row-6">
  <div class="kpi-card" style="--bar: var(--brand-solid); --bar-tint: var(--brand-tint); --bar-text: var(--brand-text)">
    <span class="kpi-icon"><?= icon('mortarboard') ?></span>
    <p class="kpi-label">Total Students</p>
    <p class="kpi-value" data-count-to="<?= (int) $totalStudents ?>">0</p>
    <p class="kpi-meta"><?= $totalStudents === 0 ? 'No students enrolled yet' : 'Enrolled &amp; active' ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--info-500); --bar-tint: var(--info-50); --bar-text: var(--info-700)">
    <span class="kpi-icon"><?= icon('person-badge') ?></span>
    <p class="kpi-label">Total Staff</p>
    <p class="kpi-value" data-count-to="<?= (int) $totalStaff ?>">0</p>
    <p class="kpi-meta"><?= $totalStaff === 0 ? 'No staff added yet' : 'Active employees' ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--accent-solid); --bar-tint: var(--accent-tint); --bar-text: var(--accent-text)">
    <span class="kpi-icon"><?= icon('calendar-event') ?></span>
    <p class="kpi-label">Academic Session</p>
    <p class="kpi-value" style="font-size: 20px"><?= e($currentSession['name'] ?? '&mdash;') ?></p>
    <p class="kpi-meta"><?= $currentSession ? e(ucfirst((string) $currentSession['status'])) : 'No active session' ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--success-500); --bar-tint: var(--success-50); --bar-text: var(--success-700)">
    <span class="kpi-icon"><?= icon('grid-1x2') ?></span>
    <p class="kpi-label">Classes</p>
    <p class="kpi-value" data-count-to="<?= (int) $totalClasses ?>">0</p>
    <p class="kpi-meta">Nursery through Class 12</p>
  </div>
  <div class="kpi-card" style="--bar: var(--warning-500); --bar-tint: var(--warning-50); --bar-text: var(--warning-700)">
    <span class="kpi-icon"><?= icon('inbox') ?></span>
    <p class="kpi-label">Pending Admissions</p>
    <p class="kpi-value" data-count-to="<?= (int) $pendingAdmissions ?>">0</p>
    <p class="kpi-meta"><?= $pendingAdmissions === 0 ? 'Inbox clear' : 'Awaiting a decision' ?></p>
  </div>
  <div class="kpi-card" style="--bar: var(--danger-500); --bar-tint: var(--danger-50); --bar-text: var(--danger-700)">
    <span class="kpi-icon"><?= icon('people') ?></span>
    <p class="kpi-label">Admin Users</p>
    <p class="kpi-value" data-count-to="<?= (int) $totalUsers ?>">0</p>
    <p class="kpi-meta">With panel access</p>
  </div>
</div>

<div class="dash-grid">
  <div class="admin-card">
    <div class="admin-card-header">
      <h2>Classes by Wing</h2>
    </div>
    <div class="chart-wrap">
      <canvas id="chart-wing" role="img" aria-label="Bar chart of class count by school wing"></canvas>
    </div>
    <table class="sr-table">
      <caption>Classes by wing</caption>
      <thead><tr><th>Wing</th><th>Classes</th></tr></thead>
      <tbody>
        <?php foreach ($wingChart as $row): ?>
          <tr><td><?= e($row['label']) ?></td><td><?= (int) $row['total'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <h2>Recent Admissions</h2>
      <a href="<?= e(route('admin.admissions')) ?>">View all</a>
    </div>
    <?php if ($recentAdmissions === []): ?>
      <div class="empty-state">
        <?= icon('inbox') ?>
        <h3>No applications yet</h3>
        <p>Submissions from the website's "Apply Now" form will show up here.</p>
      </div>
    <?php else: ?>
      <div class="activity-list">
        <?php foreach ($recentAdmissions as $app): ?>
          <a class="activity-item" href="<?= e(route('admin.admissions.show', ['id' => $app['id']])) ?>">
            <span class="activity-dot"><?= icon('inbox') ?></span>
            <div class="activity-body">
              <p><?= e($app['applicant_name']) ?> &mdash; <?= e($app['class_name']) ?>
                <span class="badge <?= $admissionStatusBadge[$app['status']] ?? 'badge-mute' ?>" style="margin-left:6px"><?= e(ucwords(str_replace('_', ' ', (string) $app['status']))) ?></span>
              </p>
              <span><?= e($app['application_no']) ?> &middot; <?= e(date('d M Y, h:i A', strtotime((string) $app['created_at']))) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

<div class="dash-grid-2">
  <?php if ($canDash): ?>
  <div class="admin-card">
    <div class="admin-card-header">
      <h2>Subjects by Type</h2>
    </div>
    <div class="chart-wrap" style="height: 220px">
      <canvas id="chart-subjects" role="img" aria-label="Doughnut chart of subject count by type"></canvas>
    </div>
    <div class="chart-legend" id="subjects-legend"></div>
    <table class="sr-table">
      <caption>Subjects by type</caption>
      <thead><tr><th>Type</th><th>Count</th></tr></thead>
      <tbody>
        <?php foreach ($typeChart as $row): ?>
          <tr><td><?= e($row['label']) ?></td><td><?= (int) $row['total'] ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="admin-card-header">
      <h2>Quick Actions</h2>
    </div>
    <div class="quick-actions">
      <a class="quick-action" href="<?= e(route('admin.profile')) ?>">
        <span class="quick-action-icon"><?= icon('person-circle') ?></span>
        <span class="label">My Profile</span>
      </a>
      <?php if (\App\Core\Auth::can('settings.view')): ?>
      <a class="quick-action" href="<?= e(route('admin.settings')) ?>">
        <span class="quick-action-icon"><?= icon('gear') ?></span>
        <span class="label">Settings</span>
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('students.create')): ?>
      <a class="quick-action" href="<?= e(route('admin.students.create')) ?>">
        <span class="quick-action-icon"><?= icon('mortarboard') ?></span>
        <span class="label">Add Student</span>
      </a>
      <?php endif; ?>
      <?php if (\App\Core\Auth::can('admissions.view')): ?>
      <a class="quick-action" href="<?= e(route('admin.admissions')) ?>">
        <span class="quick-action-icon"><?= icon('inbox') ?></span>
        <span class="label">Review Admissions</span>
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($canDash): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var muted = getComputedStyle(document.documentElement).getPropertyValue('--text-muted').trim();
  var gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border-subtle').trim();
  var tooltipBg = getComputedStyle(document.documentElement).getPropertyValue('--bg-inverse').trim();
  var tooltipText = getComputedStyle(document.documentElement).getPropertyValue('--text-inverse').trim();

  var wingData = <?= json_encode($wingChart, JSON_UNESCAPED_SLASHES) ?>;
  var typeData = <?= json_encode($typeChart, JSON_UNESCAPED_SLASHES) ?>;
  var palette = ['#C62828', '#FBC02D', '#0284C7', '#7C3AED', '#0E9F6E'];

  var wingCanvas = document.getElementById('chart-wing');
  if (wingCanvas && window.Chart) {
    new Chart(wingCanvas, {
      type: 'bar',
      data: {
        labels: wingData.map(function (r) { return r.label; }),
        datasets: [{
          data: wingData.map(function (r) { return r.total; }),
          backgroundColor: palette[0],
          borderRadius: 6,
          maxBarThickness: 44,
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

  var subjCanvas = document.getElementById('chart-subjects');
  if (subjCanvas && window.Chart) {
    new Chart(subjCanvas, {
      type: 'doughnut',
      data: {
        labels: typeData.map(function (r) { return r.label; }),
        datasets: [{
          data: typeData.map(function (r) { return r.total; }),
          backgroundColor: palette,
          borderWidth: 0,
        }],
      },
      options: {
        maintainAspectRatio: false,
        animation: { duration: 600 },
        cutout: '65%',
        plugins: {
          legend: { display: false },
          tooltip: { backgroundColor: tooltipBg, titleColor: tooltipText, bodyColor: tooltipText, padding: 10, cornerRadius: 8 },
        },
      },
    });

    var legend = document.getElementById('subjects-legend');
    legend.innerHTML = typeData.map(function (r, i) {
      return '<span><span class="legend-dot" style="background:' + palette[i % palette.length] + '"></span>' + r.label + ' (' + r.total + ')</span>';
    }).join('');
  }
});
</script>
<?php endif; ?>
