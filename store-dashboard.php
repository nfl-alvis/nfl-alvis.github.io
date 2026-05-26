<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/store-sidebar.php';

require_role(ROLE_STORE_ADMIN);

$user = current_user();
$storeId = (int) ($user['store_id'] ?? 0);

if (!$storeId) {
    set_flash('error', 'Akun store admin belum terhubung ke toko.');
    redirect_to('index.php');
}

$storeStmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch();

$products = find_store_products($storeId);

$stats = store_dashboard_stats($storeId);

$startDate = (new DateTimeImmutable('today'))->modify('-29 days');
$visitTrendStmt = db()->prepare(
    'SELECT visit_date AS day, COUNT(*) AS total
     FROM store_visits
     WHERE store_id = :store_id AND visit_date >= :start_date
     GROUP BY visit_date
     ORDER BY visit_date'
);
$visitTrendStmt->execute(['store_id' => $storeId, 'start_date' => $startDate->format('Y-m-d')]);
$viewTrendStmt = db()->prepare(
    'SELECT view_date AS day, COUNT(*) AS total
     FROM product_views
     WHERE store_id = :store_id AND view_date >= :start_date
     GROUP BY view_date
     ORDER BY view_date'
);
$viewTrendStmt->execute(['store_id' => $storeId, 'start_date' => $startDate->format('Y-m-d')]);

$visitsByDay = [];
foreach ($visitTrendStmt->fetchAll() as $row) {
    $visitsByDay[(string) $row['day']] = (int) $row['total'];
}
$viewsByDay = [];
foreach ($viewTrendStmt->fetchAll() as $row) {
    $viewsByDay[(string) $row['day']] = (int) $row['total'];
}

$trendLabels = [];
$visitValues = [];
$viewValues = [];
for ($offset = 0; $offset < 30; $offset++) {
    $date = $startDate->modify('+' . $offset . ' days');
    $dateKey = $date->format('Y-m-d');
    $trendLabels[] = $date->format('d/m');
    $visitValues[] = $visitsByDay[$dateKey] ?? 0;
    $viewValues[] = $viewsByDay[$dateKey] ?? 0;
}

$topProducts = array_slice($stats['top_products'], 0, 5);
$chartData = [
    'traffic' => [
        'labels' => $trendLabels,
        'visits' => $visitValues,
        'views' => $viewValues,
    ],
    'products' => [
        'labels' => array_column($topProducts, 'name'),
        'values' => array_map('intval', array_column($topProducts, 'total_views')),
    ],
];

render_layout('Dashboard Toko', function (?array $currentUser = null) use ($user, $store, $products, $stats, $chartData): void {
    ?>
    <div class="shell">
      <?php render_store_sidebar($user, $store, 'dashboard'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading"><?= e($store['name']) ?></div>
            <div class="topbar-sub">Kelola toko sendiri dan pantau statistik pengunjung.</div>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> &bull; Store Admin</div>
        </div>

        <section class="stats-grid">
          <article class="stat-box">
            <p>Total produk aktif</p>
            <h3><?= e((string) $stats['total_products']) ?></h3>
          </article>
          <article class="stat-box">
            <p>Pengunjung toko</p>
            <h3><?= e(number_short($stats['store_visitors'])) ?></h3>
          </article>
          <article class="stat-box">
            <p>Pengunjung 30 hari</p>
            <h3><?= e(number_short($stats['monthly_visitors'])) ?></h3>
          </article>
          <article class="stat-box">
            <p>Total pelihat produk</p>
            <h3><?= e(number_short($stats['product_views'])) ?></h3>
          </article>
        </section>

        <section class="chart-section" aria-label="Analitik toko">
          <div class="chart-section-head">
            <h2>Analitik Toko</h2>
            <p>Perbandingan interaksi toko selama 30 hari terakhir dan produk yang paling menarik perhatian.</p>
          </div>
          <div class="chart-grid">
            <article class="chart-card">
              <h3>Tren Pengunjung</h3>
              <p>Kunjungan toko dan views produk harian</p>
              <div class="chart-frame"><canvas id="storeTrafficChart"></canvas></div>
            </article>
            <article class="chart-card">
              <h3>Produk Paling Dilihat</h3>
              <p>Lima produk dengan views tertinggi</p>
              <div class="chart-frame"><canvas id="storeProductViewsChart"></canvas></div>
            </article>
          </div>
        </section>

        <section class="dashboard-grid store-dashboard-details">
          <div class="stacked-card">
            <article class="table-card">
              <h3>Ringkasan Toko</h3>
              <div class="product-store-list" style="margin-top: 18px;">
                <div class="product-mini-card">
                  <strong>Nama Toko</strong>
                  <p><?= e($store['name']) ?></p>
                </div>
                <div class="product-mini-card">
                  <strong>Wilayah</strong>
                  <p><?= e($store['region']) ?></p>
                </div>
                <div class="product-mini-card">
                  <strong>Kontak</strong>
                  <p>WA: <?= e($store['whatsapp']) ?></p>
                  <p>IG: <?= e($store['instagram']) ?></p>
                </div>
                <div class="product-mini-card">
                  <strong>Alamat</strong>
                  <p><?= e($store['address']) ?></p>
                </div>
              </div>
            </article>
          </div>

          <div class="stacked-card">
            <article class="table-card">
              <h3>Produk Paling Dilihat</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Rating</th>
                    <th>Views</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($stats['top_products'] as $item): ?>
                    <tr>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e(number_format((float) ($item['rating'] ?? 0), 1)) ?></td>
                      <td><?= e(number_short((int) $item['total_views'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </article>

            <article class="table-card">
              <h3>Daftar Produk Toko</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Rating</th>
                    <th>Views</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $product): ?>
                    <tr>
                      <td><?= e($product['name']) ?></td>
                      <td><?= e($product['type']) ?></td>
                      <td><?= e(rupiah($product['price_display'])) ?></td>
                      <td><?= e(number_format((float) $product['rating'], 1)) ?></td>
                      <td><?= e(number_short((int) $product['total_views'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </article>
          </div>
        </section>
      </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      const storeChartData = <?= json_encode($chartData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

      new Chart(document.getElementById('storeTrafficChart'), {
        type: 'line',
        data: {
          labels: storeChartData.traffic.labels,
          datasets: [
            {
              label: 'Pengunjung toko',
              data: storeChartData.traffic.visits,
              borderColor: '#3e6b48',
              backgroundColor: 'rgba(62, 107, 72, 0.10)',
              fill: true,
              tension: 0.35,
              pointRadius: 2
            },
            {
              label: 'Views produk',
              data: storeChartData.traffic.views,
              borderColor: '#e06b4c',
              backgroundColor: 'rgba(224, 107, 76, 0.08)',
              fill: true,
              tension: 0.35,
              pointRadius: 2
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } },
            x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
          }
        }
      });

      new Chart(document.getElementById('storeProductViewsChart'), {
        type: 'bar',
        data: {
          labels: storeChartData.products.labels,
          datasets: [{
            label: 'Views',
            data: storeChartData.products.values,
            backgroundColor: '#90c790',
            borderRadius: 8
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            x: { beginAtZero: true, ticks: { precision: 0 } },
            y: { grid: { display: false } }
          }
        }
      });
    </script>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
