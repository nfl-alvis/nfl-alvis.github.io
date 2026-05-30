<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
$store = $slug !== '' ? find_store_by_slug($slug) : null;
$stores = $slug === '' ? find_stores(trim($_GET['search'] ?? '')) : [];
$productType = trim($_GET['product_type'] ?? '');
$productType = in_array($productType, ['Makanan', 'Minuman'], true) ? $productType : '';
$storePlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 520'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f6d6c7'/%3E%3Cstop offset='100%25' stop-color='%23dce9da'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='800' height='520' rx='36' fill='url(%23g)'/%3E%3Crect x='72' y='88' width='656' height='344' rx='28' fill='rgba(255,255,255,0.68)'/%3E%3Cpath d='M140 344l108-118 82 86 126-144 170 176H140z' fill='%23b3c8b4'/%3E%3Ccircle cx='252' cy='196' r='34' fill='%23e06b4c' fill-opacity='0.78'/%3E%3Ctext x='50%25' y='84%25' text-anchor='middle' font-family='Arial,sans-serif' font-size='34' fill='%233e6b48'%3EPlaceholder Toko%3C/text%3E%3C/svg%3E";

if ($slug !== '' && !$store) {
    set_flash('error', 'Toko tidak ditemukan.');
    redirect_to('store.php');
}

if ($store) {
    require_login();
    track_store_visit((int) $store['id']);
}

render_layout($store ? $store['name'] : 'Daftar Toko', function (?array $user = null) use ($store, $stores, $storePlaceholder, $productType): void {
    if ($store): ?>
      <?php
      $products = $store['products'] ?? [];
      $filteredProducts = in_array($productType, ['Makanan', 'Minuman'], true)
          ? array_values(array_filter($products, static fn(array $product): bool => ($product['type'] ?? '') === $productType))
          : $products;
      $ratedProducts = array_values(array_filter($products, static fn(array $product): bool => (float) ($product['rating'] ?? 0) > 0));
      $averageRating = $ratedProducts
          ? array_sum(array_map(static fn(array $product): float => (float) $product['rating'], $ratedProducts)) / count($ratedProducts)
          : 0.0;
      $visitorCount = 0;

      try {
          $visitorStmt = db()->prepare('SELECT COUNT(*) FROM store_visits WHERE store_id = :store_id');
          $visitorStmt->execute(['store_id' => (int) $store['id']]);
          $visitorCount = (int) $visitorStmt->fetchColumn();
      } catch (Throwable $exception) {
          $visitorCount = 0;
      }

      $schedule = parse_operating_schedule($store['operating_hours'] ?? '');
      $coverImage = $store['cover_image'] !== '' ? base_path($store['cover_image']) : $storePlaceholder;
      $isOpen = (int) ($store['is_open'] ?? 1) === 1;
      $filterUrl = static fn(string $type): string => base_path('store.php?' . http_build_query(array_filter([
          'slug' => $store['slug'],
          'product_type' => $type,
      ], static fn(string $value): bool => $value !== '')));
      ?>

      <div class="dp-breadcrumb">
        <a href="<?= e(base_path('index.php')) ?>">Beranda</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(base_path('store.php')) ?>">Toko</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span><?= e($store['name']) ?></span>
      </div>

      <section class="store-hero">
        <div class="store-hero-cover">
          <img src="<?= e($coverImage) ?>" alt="<?= e($store['name']) ?>" />
        </div>
        <div class="store-hero-inner">
          <div class="store-hero-badge <?= $isOpen ? '' : 'closed' ?>"><?= $isOpen ? 'Sedang Buka' : 'Sedang Tutup' ?></div>
          <h2 class="store-hero-name"><?= e($store['name']) ?></h2>
          <div class="store-hero-region">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <?= e($store['region']) ?>, Indonesia
          </div>
          <div class="store-hero-stats">
            <div class="store-hero-stat">
              <div class="store-hero-stat-val"><?= e((string) count($products)) ?></div>
              <div class="store-hero-stat-label">Produk tersedia</div>
            </div>
            <div class="store-hero-stat">
              <div class="store-hero-stat-val"><?= e(number_format($averageRating, 1)) ?></div>
              <div class="store-hero-stat-label">Rating rata-rata</div>
            </div>
            <div class="store-hero-stat">
              <div class="store-hero-stat-val"><?= e(number_short($visitorCount)) ?></div>
              <div class="store-hero-stat-label">Pengunjung toko</div>
            </div>
          </div>
        </div>
      </section>

      <div class="page-body">
        <aside class="info-sidebar">
          <div class="sidebar-card">
            <div class="sidebar-card-head">
              <span class="sidebar-card-head-icon"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span>
              <span class="sidebar-card-head-title">Informasi Toko</span>
            </div>
            <div class="info-row">
              <div class="info-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></div>
              <div class="info-text">
                <div class="info-label">Alamat</div>
                <div class="info-val"><?= e($store['address']) ?></div>
              </div>
            </div>
            <div class="info-row">
              <div class="info-icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></div>
              <div class="info-text">
                <div class="info-label">Wilayah</div>
                <div class="info-val"><?= e($store['region']) ?></div>
              </div>
            </div>
            <div class="info-row">
              <div class="info-icon"><i class="fa-solid fa-box-open" aria-hidden="true"></i></div>
              <div class="info-text">
                <div class="info-label">Jumlah produk</div>
                <div class="info-val"><?= e((string) count($products)) ?> produk aktif</div>
              </div>
            </div>
          </div>

          <div class="sidebar-card">
            <div class="sidebar-card-head">
              <span class="sidebar-card-head-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <span class="sidebar-card-head-title">Jam Operasional</span>
            </div>
            <div class="hours-grid">
              <?php foreach (operating_days() as $day): ?>
                <?php $slot = $schedule[$day] ?? '08:00 - 21:00'; ?>
                <div class="hours-row">
                  <span class="hours-day"><?= e($day) ?></span>
                  <span class="hours-slot <?= $slot === 'Tutup' ? 'closed' : '' ?>"><?= e($slot) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="sidebar-card">
            <div class="sidebar-card-head">
              <span class="sidebar-card-head-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>
              <span class="sidebar-card-head-title">Hubungi Toko</span>
            </div>
            <div class="contact-btns">
              <?php if (trim((string) ($store['whatsapp'] ?? '')) !== ''): ?>
                <a href="https://wa.me/<?= e($store['whatsapp']) ?>" class="contact-btn wa" target="_blank" rel="noreferrer">
                  <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
                  Chat via WhatsApp
                </a>
              <?php endif; ?>
              <?php if (trim((string) ($store['instagram'] ?? '')) !== ''): ?>
                <a href="https://instagram.com/<?= e(ltrim($store['instagram'], '@')) ?>" class="contact-btn ig" target="_blank" rel="noreferrer">
                  <i class="fa-brands fa-instagram" aria-hidden="true"></i>
                  Lihat Instagram
                </a>
              <?php endif; ?>
              <a href="https://www.google.com/maps/search/?api=1&query=<?= e(rawurlencode((string) $store['address'])) ?>" class="contact-btn" target="_blank" rel="noreferrer">
                <i class="fa-solid fa-map-pin" aria-hidden="true"></i>
                Lihat di Peta
              </a>
            </div>
          </div>
        </aside>

        <section class="products-area">
          <div class="desc-card">
            <div class="desc-title"><i class="fa-solid fa-store" aria-hidden="true"></i> Tentang <?= e($store['name']) ?></div>
            <div class="desc-text"><?= e($store['description']) ?></div>
          </div>

          <div class="products-toolbar">
            <div>
              <div class="products-title">Katalog Produk</div>
              <div class="products-count"><?= e((string) count($filteredProducts)) ?> produk tersedia</div>
            </div>
            <div class="filter-tabs" aria-label="Filter kategori produk">
              <a class="filter-tab <?= $productType === '' ? 'active' : '' ?>" href="<?= e($filterUrl('')) ?>">Semua</a>
              <a class="filter-tab <?= $productType === 'Makanan' ? 'active' : '' ?>" href="<?= e($filterUrl('Makanan')) ?>">Makanan</a>
              <a class="filter-tab <?= $productType === 'Minuman' ? 'active' : '' ?>" href="<?= e($filterUrl('Minuman')) ?>">Minuman</a>
            </div>
          </div>

          <div class="products-grid">
            <?php foreach ($filteredProducts as $product): ?>
              <article class="prod-card">
                <a class="prod-card-link" href="<?= e(base_path('product.php?slug=' . $product['slug'])) ?>" aria-label="Lihat detail <?= e($product['name']) ?>">
                  <div class="prod-img">
                    <?php if (trim((string) ($product['image_path'] ?? '')) !== ''): ?>
                      <img src="<?= e(base_path($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" />
                    <?php else: ?>
                      <div class="prod-img-placeholder">
                        <i class="fa-solid <?= ($product['type'] ?? '') === 'Minuman' ? 'fa-mug-hot' : 'fa-bowl-food' ?>" aria-hidden="true"></i>
                      </div>
                    <?php endif; ?>
                  </div>
                  <div class="prod-body">
                    <h3 class="prod-name"><?= e($product['name']) ?></h3>
                    <div class="prod-desc"><?= e($product['short_description']) ?></div>
                    <div class="prod-rating">
                      <span class="prod-stars"><?= rating_stars_html((float) ($product['rating'] ?? 0)) ?></span>
                      <?= e(number_format((float) ($product['rating'] ?? 0), 1)) ?> &middot; <?= e(number_short((int) ($product['review_count'] ?? 0))) ?> ulasan
                    </div>
                    <div class="prod-footer">
                      <span class="prod-price"><?= e(rupiah($product['price_display'])) ?></span>
                    </div>
                  </div>
                </a>
              </article>
            <?php endforeach; ?>
            <?php if (!$filteredProducts): ?>
              <div class="empty-state store-empty-products">
                <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                <h3>Produk belum tersedia</h3>
                <p>Belum ada produk aktif untuk filter ini.</p>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    <?php else: ?>
      <section class="hero">
        <h2>Direktori<br />Toko<br />Kuliner.</h2>
        <p>Lihat status buka toko, jam operasional, wilayah, dan kontak resmi di platform.</p>
      </section>
      <div class="search-panel">
        <form class="search-form" method="get">
          <input type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Cari nama toko atau daerah..." />
          <div></div>
          <button type="submit">Cari Toko</button>
        </form>
      </div>
      <div class="store-grid store-directory-grid">
        <?php foreach ($stores as $item): ?>
          <article class="public-store-card">
            <a class="public-store-card-link" href="<?= e(base_path(is_logged_in() ? 'store.php?slug=' . $item['slug'] : 'login.php')) ?>" aria-label="Lihat detail toko <?= e($item['name']) ?>">
              <div class="public-store-cover">
                <img src="<?= e($item['cover_image'] !== '' ? base_path($item['cover_image']) : $storePlaceholder) ?>" alt="<?= e($item['name']) ?>" />
                <span class="public-store-status <?= (int) ($item['is_open'] ?? 1) === 1 ? '' : 'is-closed' ?>"><?= (int) ($item['is_open'] ?? 1) === 1 ? 'Buka' : 'Tutup' ?></span>
              </div>
              <div class="public-store-body">
                <h3><?= e($item['name']) ?></h3>
                <div class="public-store-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($item['region']) ?></div>
                <p class="public-store-description"><?= e($item['description']) ?></p>
                <div class="public-store-row">
                  <span class="public-store-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></span>
                  <p><?= e($item['address']) ?></p>
                </div>
                <div class="public-store-row">
                  <span class="public-store-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                  <p><?= e(operating_hours_display($item['operating_hours'] ?? '')) ?></p>
                </div>
                <div class="public-store-meta">
                  <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                  <?= e((string) $item['product_count']) ?> produk tersedia
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif;
});
