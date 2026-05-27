<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
$store = $slug !== '' ? find_store_by_slug($slug) : null;
$stores = $slug === '' ? find_stores(trim($_GET['search'] ?? '')) : [];
$storePlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 520'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f6d6c7'/%3E%3Cstop offset='100%25' stop-color='%23dce9da'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='800' height='520' rx='36' fill='url(%23g)'/%3E%3Crect x='72' y='88' width='656' height='344' rx='28' fill='rgba(255,255,255,0.68)'/%3E%3Cpath d='M140 344l108-118 82 86 126-144 170 176H140z' fill='%23b3c8b4'/%3E%3Ccircle cx='252' cy='196' r='34' fill='%23e06b4c' fill-opacity='0.78'/%3E%3Ctext x='50%25' y='84%25' text-anchor='middle' font-family='Arial,sans-serif' font-size='34' fill='%233e6b48'%3EPlaceholder Toko%3C/text%3E%3C/svg%3E";

if ($slug !== '' && !$store) {
    set_flash('error', 'Toko tidak ditemukan.');
    redirect_to('store.php');
}

if ($store) {
    require_login();
    track_store_visit((int) $store['id']);
}

render_layout($store ? $store['name'] : 'Daftar Toko', function (?array $user = null) use ($store, $stores, $storePlaceholder): void {
    if ($store): ?>
      <section class="hero">
        <h2><?= e($store['name']) ?></h2>
        <p><?= e($store['region']) ?> • Informasi toko, kontak, dan katalog produk</p>
      </section>

      <section class="detail-layout">
        <article class="detail-panel public-store-detail-card">
          <div class="public-store-cover">
            <img src="<?= e($store['cover_image'] !== '' ? base_path($store['cover_image']) : $storePlaceholder) ?>" alt="<?= e($store['name']) ?>" />
            <span class="public-store-status <?= (int) ($store['is_open'] ?? 1) === 1 ? '' : 'is-closed' ?>"><?= (int) ($store['is_open'] ?? 1) === 1 ? 'Buka' : 'Tutup' ?></span>
          </div>
          <div class="public-store-body">
            <h2><?= e($store['name']) ?></h2>
            <div class="public-store-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($store['region']) ?></div>
            <div class="public-store-row">
              <span class="public-store-icon"><i class="fa-solid fa-align-left" aria-hidden="true"></i></span>
              <p><?= e($store['description']) ?></p>
            </div>
            <div class="public-store-row">
              <span class="public-store-icon"><i class="fa-solid fa-map-pin" aria-hidden="true"></i></span>
              <p><?= e($store['address']) ?></p>
            </div>
            <div class="public-store-row">
              <span class="public-store-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p><?= e(operating_hours_display($store['operating_hours'] ?? '')) ?></p>
            </div>
            <div class="public-store-meta">
              <i class="fa-solid fa-box-open" aria-hidden="true"></i>
              <?= e((string) count($store['products'])) ?> produk tersedia
            </div>
          </div>
          <div class="public-store-actions">
            <a class="is-whatsapp" href="https://wa.me/<?= e($store['whatsapp']) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a>
            <a href="https://instagram.com/<?= e(ltrim($store['instagram'], '@')) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-instagram" aria-hidden="true"></i>Instagram</a>
          </div>
        </article>
        <aside class="detail-panel">
          <h3>Produk Toko</h3>
          <div class="product-store-list">
            <?php foreach ($store['products'] as $product): ?>
              <div class="product-mini-card">
                <strong><?= e($product['name']) ?></strong>
                <p><?= e($product['short_description']) ?></p>
                <p><?= e(rupiah($product['price_display'])) ?></p>
                <a class="inline-link" href="<?= e(base_path('product.php?slug=' . $product['slug'])) ?>">Lihat detail produk</a>
              </div>
            <?php endforeach; ?>
          </div>
        </aside>
      </section>
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
            <div class="public-store-actions">
              <a href="<?= e(base_path(is_logged_in() ? 'store.php?slug=' . $item['slug'] : 'login.php')) ?>"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Lihat Toko</a>
              <a class="is-whatsapp" href="https://wa.me/<?= e($item['whatsapp']) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif;
});
