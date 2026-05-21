<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$featuredProducts = find_featured_products(4);
$stores = array_slice(find_stores(), 0, 3);

render_layout('Beranda', function (?array $user) use ($featuredProducts, $stores): void {
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
      <div class="section-copy">
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
      <div class="section-copy">
        <h2>Toko Pilihan</h2>
        <p>Setiap toko memiliki informasi kontak, alamat, dan daftar produk. Tidak ada transaksi langsung di platform ini.</p>
      </div>
      <div class="store-grid">
        <?php foreach ($stores as $store): ?>
          <article class="store-card">
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

    <section>
      <div class="section-copy">
        <h2>Role Platform</h2>
      </div>
      <div class="info-grid">
        <article class="info-card">
          <h3>User Biasa</h3>
          <p>Masuk untuk membuka katalog, melihat detail makanan, toko, dan menyimpan preferensi penjelajahan.</p>
        </article>
        <article class="info-card">
          <h3>Store Admin</h3>
          <p>Mengelola toko sendiri, memperbarui informasi kontak, serta memantau statistik pengunjung dan pelihat produk.</p>
        </article>
        <article class="info-card">
          <h3>Super Admin</h3>
          <p>Mengelola seluruh pengguna, toko, produk, dan memonitor pertumbuhan platform dari satu dashboard.</p>
        </article>
      </div>
    </section>
    <?php
});
