<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

$stats = super_admin_stats();

$chartStart = (new DateTimeImmutable('today'))->modify('-29 days');
$chartDates = [];
for ($offset = 0; $offset < 30; $offset++) {
    $date = $chartStart->modify('+' . $offset . ' days');
    $chartDates[$date->format('Y-m-d')] = $date->format('d/m');
}

$storeGrowthRows = db()->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS total
     FROM stores
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY day
     ORDER BY day ASC"
)->fetchAll();
$productGrowthRows = db()->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS total
     FROM products
     WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
     GROUP BY day
     ORDER BY day ASC"
)->fetchAll();
$roleRows = db()->query(
    'SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role'
)->fetchAll();

$storeGrowthByDay = array_column($storeGrowthRows, 'total', 'day');
$productGrowthByDay = array_column($productGrowthRows, 'total', 'day');
$chartData = [
    'stores' => [
        'labels' => array_values($chartDates),
        'values' => array_map(
            static fn(string $date): int => (int) ($storeGrowthByDay[$date] ?? 0),
            array_keys($chartDates)
        ),
    ],
    'products' => [
        'labels' => array_values($chartDates),
        'values' => array_map(
            static fn(string $date): int => (int) ($productGrowthByDay[$date] ?? 0),
            array_keys($chartDates)
        ),
    ],
    'roles' => [
        'labels' => array_column($roleRows, 'role'),
        'values' => array_map('intval', array_column($roleRows, 'total')),
    ],
];

render_layout('Dashboard Super Admin', function (?array $user = null) use ($stats, $chartData): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'dashboard'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Ringkasan Platform</div>
            <div class="topbar-sub">Pantau pertumbuhan pengguna, toko, dan katalog PusakaRasa.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="stats-grid" aria-label="Statistik platform">
          <article class="stat-box"><p>Total pengguna</p><h3><?= e(number_short($stats['users'])) ?></h3></article>
          <article class="stat-box"><p>Total toko</p><h3><?= e(number_short($stats['stores'])) ?></h3></article>
          <article class="stat-box"><p>Total produk</p><h3><?= e(number_short($stats['products'])) ?></h3></article>
        </section>

        <section class="chart-section" aria-label="Pertumbuhan platform">
          <div class="chart-section-head">
            <div>
              <h2>Pertumbuhan Platform</h2>
              <p>Penambahan harian selama 30 hari terakhir serta distribusi role akun.</p>
            </div>
          </div>
          <div class="chart-grid">
            <article class="chart-card">
              <h3>Pertumbuhan Toko</h3>
              <p>Toko baru per hari dalam 30 hari terakhir</p>
              <div class="chart-frame"><canvas id="storeGrowthChart"></canvas></div>
            </article>
            <article class="chart-card">
              <h3>Pertumbuhan Produk</h3>
              <p>Produk baru per hari dalam 30 hari terakhir</p>
              <div class="chart-frame"><canvas id="productGrowthChart"></canvas></div>
            </article>
            <article class="chart-card chart-card-wide">
              <h3>Distribusi Pengguna per Role</h3>
              <p>Jumlah akun berdasarkan tipe akses</p>
              <div class="chart-frame chart-frame-wide"><canvas id="roleDistributionChart"></canvas></div>
            </article>
          </div>
        </section>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const adminChartData = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
      const lineOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { precision: 0 } },
          x: {
            grid: { display: false },
            ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0 }
          }
        }
      };

      new Chart(document.getElementById('storeGrowthChart'), {
        type: 'line',
        data: {
          labels: adminChartData.stores.labels,
          datasets: [{
            data: adminChartData.stores.values,
            borderColor: '#3e6b48',
            backgroundColor: 'rgba(62, 107, 72, 0.12)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#3e6b48'
          }]
        },
        options: lineOptions
      });

      new Chart(document.getElementById('productGrowthChart'), {
        type: 'line',
        data: {
          labels: adminChartData.products.labels,
          datasets: [{
            data: adminChartData.products.values,
            borderColor: '#e06b4c',
            backgroundColor: 'rgba(224, 107, 76, 0.12)',
            fill: true,
            tension: 0.35,
            pointBackgroundColor: '#e06b4c'
          }]
        },
        options: lineOptions
      });

      new Chart(document.getElementById('roleDistributionChart'), {
        type: 'bar',
        data: {
          labels: adminChartData.roles.labels,
          datasets: [{
            data: adminChartData.roles.values,
            backgroundColor: ['#3e6b48', '#e06b4c', '#90c790'],
            borderRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false } }
          }
        }
      });
    </script>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
