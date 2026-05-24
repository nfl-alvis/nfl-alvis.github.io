<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$slug = trim($_GET['slug'] ?? '');
$store = $slug !== '' ? find_store_by_slug($slug) : null;
$stores = $slug === '' ? find_stores(trim($_GET['search'] ?? '')) : [];

if ($slug !== '' && !$store) {
    set_flash('error', 'Toko tidak ditemukan.');
    redirect_to('store.php');
}

if ($store) {
    require_login();
    track_store_visit((int) $store['id']);
}

render_layout($store ? $store['name'] : 'Daftar Toko', function (?array $user = null) use ($store, $stores): void {
    if ($store): ?>
      <section class="hero">
        <h2><?= e($store['name']) ?></h2>
        <p><?= e($store['region']) ?> • Informasi toko, kontak, dan katalog produk</p>
      </section>

      <section class="detail-layout">
        <article class="detail-panel">
          <img src="<?= e(base_path($store['cover_image'])) ?>" alt="<?= e($store['name']) ?>" />
          <h2><?= e($store['name']) ?></h2>
          <p><?= e($store['description']) ?></p>
          <div class="meta-row">
            <span class="meta-chip"><?= e($store['region']) ?></span>
            <span class="meta-chip"><?= e((string) count($store['products'])) ?> produk</span>
          </div>
          <p><?= e($store['address']) ?></p>
          <div class="contact-links">
            <a href="https://wa.me/<?= e($store['whatsapp']) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
            <a href="https://instagram.com/<?= e(ltrim($store['instagram'], '@')) ?>" target="_blank" rel="noreferrer">Instagram</a>
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
        <p>Lihat toko aktif, wilayah, dan kontak resmi yang sudah ditampilkan di platform.</p>
      </section>
      <div class="search-panel">
        <form class="search-form" method="get">
          <input type="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="Cari nama toko atau daerah..." />
          <div></div>
          <button type="submit">Cari Toko</button>
        </form>
      </div>
      <div class="store-grid" style="margin-top: 50px;">
        <?php foreach ($stores as $item): ?>
          <article class="store-card">
            <h3><?= e($item['name']) ?></h3>
            <p><?= e($item['description']) ?></p>
            <div class="meta-row">
              <span class="meta-chip"><?= e($item['region']) ?></span>
              <span class="meta-chip"><?= e((string) $item['product_count']) ?> produk</span>
            </div>
            <p class="muted-note"><?= e($item['address']) ?></p>
            <div class="store-links">
              <a href="<?= e(base_path(is_logged_in() ? 'store.php?slug=' . $item['slug'] : 'login.php')) ?>">Detail Toko</a>
              <a href="https://wa.me/<?= e($item['whatsapp']) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif;
});
