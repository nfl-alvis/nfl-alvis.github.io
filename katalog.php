<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$region = trim($_GET['region'] ?? '');
$products = find_products(['search' => $search, 'type' => $type, 'region' => $region]);
$regions = product_regions();

render_layout('Katalog', function (?array $user = null) use ($search, $type, $region, $products, $regions): void {
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

    <div class="search-panel search-panel-static catalog-search-panel">
      <form class="search-form catalog-search-form" method="get">
        <label class="search-field search-field-query">
          <span><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Pencarian</span>
          <input type="search" name="search" value="<?= e($search) ?>" placeholder="Nama produk, daerah, atau toko..." />
        </label>
        <label class="search-field">
          <span>Kategori</span>
          <select name="type">
            <option value="">Semua kategori</option>
            <option value="Makanan" <?= $type === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
            <option value="Minuman" <?= $type === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
          </select>
        </label>
        <label class="search-field">
          <span>Daerah</span>
          <select name="region">
            <option value="">Semua daerah</option>
            <?php foreach ($regions as $availableRegion): ?>
              <option value="<?= e($availableRegion) ?>" <?= $region === $availableRegion ? 'selected' : '' ?>><?= e($availableRegion) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="catalog-search-actions">
          <button type="submit">Cari</button>
          <a href="<?= e(base_path('katalog.php')) ?>" class="catalog-reset-filter" aria-label="Reset filter">Reset</a>
        </div>
      </form>
    </div>

    <section id="katalog" class="catalog-results">
      <div class="section-copy catalog-results-heading">
        <h2>Semua Produk</h2>
        <p><?= e((string) count($products)) ?> hasil ditemukan.</p>
      </div>
      <div class="cards-container">
        <?php if (!$products): ?>
          <div class="empty-state">Belum ada produk yang cocok dengan pencarian Anda.</div>
        <?php endif; ?>

        <?php foreach ($products as $product): ?>
          <?php render_product_card($product, ['catalog_card' => true]); ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
});
