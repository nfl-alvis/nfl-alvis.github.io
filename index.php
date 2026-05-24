<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$featuredProducts = find_featured_products(4);
$stores = array_slice(find_stores(), 0, 3);
$storePlaceholder = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 800 520'%3E%3Cdefs%3E%3ClinearGradient id='g' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' stop-color='%23f6d6c7'/%3E%3Cstop offset='100%25' stop-color='%23dce9da'/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='800' height='520' rx='36' fill='url(%23g)'/%3E%3Crect x='72' y='88' width='656' height='344' rx='28' fill='rgba(255,255,255,0.68)'/%3E%3Cpath d='M140 344l108-118 82 86 126-144 170 176H140z' fill='%23b3c8b4'/%3E%3Ccircle cx='252' cy='196' r='34' fill='%23e06b4c' fill-opacity='0.78'/%3E%3Ctext x='50%25' y='84%25' text-anchor='middle' font-family='Arial,sans-serif' font-size='34' fill='%233e6b48'%3EPlaceholder Toko%3C/text%3E%3C/svg%3E";

render_layout('Beranda', function (?array $user) use ($featuredProducts, $stores, $storePlaceholder): void {
?>
  <section class="hero" id="beranda">
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

  <section id="katalog-popular">
    <div class="section-copy section-copy-panel">
      <h2>Terpopuler Minggu Ini</h2>
      <p>Halaman publik tetap informatif, tetapi katalog lengkap dan detail toko difokuskan untuk pengguna yang sudah masuk.</p>
    </div>

    <div class="cards-container">
      <?php foreach ($featuredProducts as $product): ?>
        <?php render_product_card($product); ?>
      <?php endforeach; ?>
    </div>
  </section>

  <section>
    <div class="section-copy section-copy-panel">
      <h2>Toko Pilihan</h2>
      <p>Setiap toko memiliki informasi kontak, alamat, dan daftar produk. Tidak ada transaksi langsung di platform ini.</p>
    </div>
    <div class="store-grid">
      <?php foreach ($stores as $store): ?>
        <article class="store-card">
          <div class="store-card-media">
            <img src="<?= e($storePlaceholder) ?>" alt="Placeholder toko <?= e($store['name']) ?>" />
          </div>
          <h3><?= e($store['name']) ?></h3>
          <p><?= e($store['description']) ?></p>
          <div class="meta-row">
            <span class="meta-chip"><?= e($store['region']) ?></span>
            <span class="meta-chip"><?= e((string) $store['product_count']) ?> produk</span>
          </div>
          <p class="muted-note"><?= e($store['address']) ?></p>
          <div class="store-links">
            <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">Lihat Toko</a>
            <a href="https://wa.me/<?= e($store['whatsapp']) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>


<?php
});
