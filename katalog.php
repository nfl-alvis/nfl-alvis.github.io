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
    <section class="search-panel search-panel-static catalog-search-panel catalog-toolbar">
      <div class="catalog-toolbar-head">
        <div>
          <span class="catalog-toolbar-kicker">Katalog Produk</span>
          <h1>Jelajahi Rasa Nusantara</h1>
        </div>
        <div class="catalog-result-pill">
          <strong><?= e((string) count($products)) ?></strong>
          <span>produk ditemukan</span>
        </div>
      </div>
      <form class="search-form catalog-search-form" method="get">
        <label class="search-field search-field-query">
          <span>Pencarian</span>
          <div class="catalog-query-control">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="search" value="<?= e($search) ?>" placeholder="Cari nama produk atau toko" />
          </div>
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
          <button type="submit"><i class="fa-solid fa-filter" aria-hidden="true"></i>Tampilkan</button>
          <a href="<?= e(base_path('katalog.php')) ?>" class="catalog-reset-filter" aria-label="Reset filter"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i>Reset</a>
        </div>
        <?php if ($search !== '' || $type !== '' || $region !== ''): ?>
          <div class="catalog-active-filters" aria-label="Filter aktif">
            <span class="catalog-active-label">Filter aktif</span>
            <?php if ($search !== ''): ?><span class="catalog-filter-chip"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><?= e($search) ?></span><?php endif; ?>
            <?php if ($type !== ''): ?><span class="catalog-filter-chip"><i class="fa-solid fa-utensils" aria-hidden="true"></i><?= e($type) ?></span><?php endif; ?>
            <?php if ($region !== ''): ?><span class="catalog-filter-chip"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($region) ?></span><?php endif; ?>
          </div>
        <?php endif; ?>
      </form>
    </section>

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
