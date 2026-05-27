<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$products = find_products();

render_layout('Favorit', function (?array $user = null) use ($products): void {
    ?>
    <section class="fav-hero">
      <div class="fav-hero-inner">
        <div>
          <div class="fav-hero-eyebrow">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
            Koleksi Favorit
          </div>
          <h1>Semua makanan yang Anda tandai</h1>
          <p>Halaman ini menampilkan daftar favorit dari perangkat yang sedang Anda pakai, sehingga Anda bisa kembali membuka detail produknya dengan cepat.</p>
        </div>
        <div class="fav-hero-stats">
          <div class="fav-stat-pill">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
            <div>
              <div class="stat-num" id="heroCount">0</div>
              <div class="stat-label">item favorit</div>
            </div>
          </div>
          <div class="fav-stat-pill">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            <div>
              <div class="stat-num" id="storeCount">0</div>
              <div class="stat-label">toko berbeda</div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="fav-toolbar-wrap">
      <div class="fav-toolbar">
        <div class="fav-toolbar-left">
          <span class="fav-toolbar-label">Produk tersimpan:</span>
          <div class="fav-count-badge">
            <i class="fa-solid fa-heart" aria-hidden="true"></i>
            <span id="favCount">0</span> item
          </div>
        </div>
        <div class="fav-sort-group">
          <label class="fav-sort-label" for="favSortSelect">Urutkan:</label>
          <select class="fav-sort-select" id="favSortSelect">
            <option value="saved">Terbaru ditambahkan</option>
            <option value="price-low">Harga terendah</option>
            <option value="rating-high">Rating tertinggi</option>
            <option value="name-az">Nama A-Z</option>
          </select>
          <button class="fav-clear-btn" id="favClearBtn" type="button">
            <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
            Hapus semua
          </button>
        </div>
      </div>
    </div>

    <section id="favoritesPage" class="favorites-page fav-main">
      <div class="fav-section-head">
        <h2>Produk Tersimpan</h2>
        <span>Daftar ini tersinkron dari tombol hati pada kartu produk.</span>
      </div>
      <div class="cards-container favorites-grid fav-grid" id="favoritesGrid">
        <?php foreach ($products as $product): ?>
          <?php render_product_card($product, ['catalog_card' => true]); ?>
        <?php endforeach; ?>
        <div class="favorites-empty fav-empty" id="favoritesEmpty">
          <div class="fav-empty-icon">
            <i class="fa-regular fa-heart" aria-hidden="true"></i>
          </div>
          <h3>Belum ada produk favorit</h3>
          <p>Tekan ikon hati pada katalog atau halaman beranda untuk menyimpan produk di sini.</p>
          <a class="fav-empty-cta" href="<?= e(base_path('katalog.php')) ?>">
            <i class="fa-solid fa-compass" aria-hidden="true"></i>
            Jelajahi Katalog
          </a>
        </div>
      </div>
    </section>
    <?php
});
