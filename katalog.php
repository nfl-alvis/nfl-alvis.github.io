<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
<<<<<<< HEAD
$region = trim($_GET['region'] ?? '');
$products = find_products(['search' => $search, 'type' => $type, 'region' => $region]);
$regions = product_regions();

render_layout('Katalog', function (?array $user = null) use ($search, $type, $region, $products, $regions): void {
=======
$products = find_products(['search' => $search, 'type' => $type]);

render_layout('Katalog', function (?array $user = null) use ($search, $type, $products): void {
>>>>>>> 1f10600b3b58378f025383875086f6a4552707a2
    ?>
    <section class="page-intro compact">
      <div class="page-intro-copy">
        <span class="eyebrow">Jelajahi Produk</span>
        <h2>Katalog kuliner yang lebih cepat dicari dan lebih nyaman dipindai.</h2>
        <p>Filter produk berdasarkan nama, daerah, atau kategori, lalu simpan item yang Anda suka ke halaman favorit.</p>
      </div>
      <div class="page-intro-stats">
        <div class="intro-stat-card">
          <strong><?= e((string) count($products)) ?></strong>
          <span>produk tampil</span>
        </div>
        <div class="intro-stat-card">
          <strong><?= e($type !== '' ? $type : 'Semua') ?></strong>
          <span>kategori aktif</span>
        </div>
      </div>
    </section>

    <div class="search-panel search-panel-static">
      <form class="search-form" method="get">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari produk, daerah, atau nama toko..." />
        <select name="type">
          <option value="">Semua kategori</option>
          <option value="Makanan" <?= $type === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
          <option value="Minuman" <?= $type === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
        </select>
<<<<<<< HEAD
        <select name="region">
          <option value="">Semua daerah</option>
          <?php foreach ($regions as $availableRegion): ?>
            <option value="<?= e($availableRegion) ?>" <?= $region === $availableRegion ? 'selected' : '' ?>><?= e($availableRegion) ?></option>
          <?php endforeach; ?>
        </select>
=======
>>>>>>> 1f10600b3b58378f025383875086f6a4552707a2
        <button type="submit">Cari Sekarang</button>
      </form>
    </div>

    <section id="katalog">
      <div class="section-copy" style="margin-top: 50px;">
        <h2>Semua Produk</h2>
        <p><?= e((string) count($products)) ?> hasil ditemukan.</p>
      </div>
      <div class="cards-container">
        <?php if (!$products): ?>
          <div class="empty-state">Belum ada produk yang cocok dengan pencarian Anda.</div>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
          <?php render_product_card($product); ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
});
