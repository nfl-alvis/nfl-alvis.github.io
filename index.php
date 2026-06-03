<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$popularProducts = find_popular_products(4);
$availableStores = find_stores();
$stores = find_popular_stores(3);
$storePlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 520'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f6d6c7'/%3E%3Cstop offset='100%25' stop-color='%23dce9da'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='800' height='520' rx='36' fill='url(%23g)'/%3E%3Crect x='72' y='88' width='656' height='344' rx='28' fill='rgba(255,255,255,0.68)'/%3E%3Cpath d='M140 344l108-118 82 86 126-144 170 176H140z' fill='%23b3c8b4'/%3E%3Ccircle cx='252' cy='196' r='34' fill='%23e06b4c' fill-opacity='0.78'/%3E%3Ctext x='50%25' y='84%25' text-anchor='middle' font-family='Arial,sans-serif' font-size='34' fill='%233e6b48'%3EPlaceholder Toko%3C/text%3E%3C/svg%3E";

render_layout('Beranda', function (?array $user) use ($popularProducts, $availableStores, $stores, $storePlaceholder): void {
?>
  <section class="hero home-hero" id="beranda">
    <span class="home-hero-kicker">Kuliner Indonesia terkurasi</span>
    <h2>Warisan<br />Rasa<br />Nusantara.</h2>
    <p>Eksplorasi makanan dan toko kuliner dari berbagai daerah Indonesia dalam satu katalog informatif.</p>
    <div class="hero-actions">
      <a href="<?= e(base_path(is_logged_in() ? 'katalog.php' : 'login.php')) ?>">
        <button>Jelajahi Katalog</button>
      </a>
      <a href="<?= e(base_path('store.php')) ?>">
        <button class="btn-outline">Lihat Toko</button>
      </a>
    </div>
  </section>

  <section class="home-highlights" aria-label="Keunggulan PusakaRasa">
    <article>
      <strong><?= e((string) count($popularProducts)) ?></strong>
      <span>Produk pilihan untuk mulai menjelajah</span>
    </article>
    <article>
      <strong><?= e((string) count($availableStores)) ?></strong>
      <span>Toko lokal dengan informasi langsung</span>
    </article>
    <article class="home-highlight-message">
      <i class="fa-solid fa-leaf" aria-hidden="true"></i>
      <span>Kenali cerita, asal, dan penjual kuliner nusantara.</span>
    </article>
  </section>

  <section id="katalog-popular">
    <div class="section-copy section-copy-panel">
      <h2>Terpopuler Minggu Ini</h2>
      <p>Temukan produk yang paling banyak dikunjungi dalam 7 hari terakhir, dengan informasi singkat yang mudah dilihat langsung dari halaman utama.</p>
    </div>

    <div class="cards-container">
      <?php foreach ($popularProducts as $product): ?>
        <?php render_product_card($product, ['catalog_card' => true]); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <div class="section-copy section-copy-panel">
      <h2>Toko Pilihan</h2>
      <p>Temukan berbagai toko makanan pilihan lengkap dengan informasi kontak, alamat, dan daftar produk yang tersedia.</p>
    </div>
    <div class="store-grid">
      <?php foreach ($stores as $store): ?>
        <article class="public-store-card">
          <div class="public-store-cover">
            <img src="<?= e($store['cover_image'] !== '' ? base_path($store['cover_image']) : $storePlaceholder) ?>" alt="<?= e($store['name']) ?>" />
            <span class="public-store-status <?= (int) ($store['is_open'] ?? 1) === 1 ? '' : 'is-closed' ?>"><?= (int) ($store['is_open'] ?? 1) === 1 ? 'Buka' : 'Tutup' ?></span>
          </div>
          <div class="public-store-body">
            <h3><?= e($store['name']) ?></h3>
            <div class="public-store-region"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= e($store['region']) ?></div>
            <p class="public-store-description"><?= e($store['description']) ?></p>
            <div class="public-store-row">
              <span class="public-store-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p><?= e(operating_hours_display($store['operating_hours'] ?? '')) ?></p>
            </div>
            <div class="public-store-meta">
              <i class="fa-solid fa-box-open" aria-hidden="true"></i>
              <?= e((string) $store['product_count']) ?> produk tersedia
            </div>
          </div>
          <div class="public-store-actions">
            <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>"><i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>Lihat Toko</a>
            <a class="is-whatsapp" href="https://wa.me/<?= e($store['whatsapp']) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>


<?php
});
