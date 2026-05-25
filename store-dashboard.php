<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

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

render_layout('Dashboard Toko', function (?array $currentUser = null) use ($user, $store, $products, $stats): void {
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Store Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('store-dashboard.php')) ?>" class="active">Dashboard</a>
          <a href="<?= e(base_path('store-profile.php')) ?>">Profil Toko</a>
          <a href="<?= e(base_path('store-add-product.php')) ?>">Tambah Produk</a>
          <a href="<?= e(base_path('store-products.php')) ?>">Produk Saya</a>
          <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">Lihat Halaman Toko</a>
          <a href="<?= e(base_path('katalog.php')) ?>">Katalog</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2><?= e($store['name']) ?></h2>
            <p class="muted-note">Kelola toko sendiri dan pantau statistik pengunjung.</p>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> • Store Admin</div>
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

        <section class="dashboard-grid">
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
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
