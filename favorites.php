<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$products = find_products();

render_layout('Favorit', function (?array $user = null) use ($products): void {
    ?>
    <section class="page-intro compact">
      <div class="page-intro-copy">
        <span class="eyebrow">Koleksi Favorit</span>
        <h2>Semua makanan dan minuman yang sudah Anda tandai dengan hati.</h2>
        <p>Halaman ini menampilkan daftar favorit dari perangkat yang sedang Anda pakai, sehingga Anda bisa kembali membuka detail produknya dengan cepat.</p>
      </div>
      <div class="page-intro-stats">
        <div class="intro-stat-card">
          <strong id="favoriteCount">0</strong>
          <span>item favorit</span>
        </div>
      </div>
    </section>

    <section id="favoritesPage" class="favorites-page">
      <div class="section-copy">
        <h2>Produk Tersimpan</h2>
        <p>Daftar ini tersinkron dari tombol hati pada kartu produk.</p>
      </div>
      <div class="cards-container favorites-grid" id="favoritesGrid">
        <?php foreach ($products as $product): ?>
          <?php render_product_card($product); ?>
        <?php endforeach; ?>
      </div>
      <div class="empty-state favorites-empty" id="favoritesEmpty">Belum ada produk favorit. Tekan ikon hati pada katalog atau halaman beranda untuk menyimpannya di sini.</div>
    </section>
    <?php
});
