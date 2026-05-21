<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_login();

$slug = trim($_GET['slug'] ?? '');
$product = $slug !== '' ? find_product_by_slug($slug) : null;

if (!$product) {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect_to('katalog.php');
}

if (is_post()) {
    $action = $_POST['action'] ?? 'submit_review';

    if ($action === 'delete_review') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);

        if ($reviewId < 1 || !delete_product_review($reviewId, (int) $product['id'], (int) current_user()['id'])) {
            set_flash('error', 'Ulasan tidak dapat dihapus.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        set_flash('success', 'Ulasan Anda berhasil dihapus.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    $stars = (int) ($_POST['stars'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($stars < 1 || $stars > 5 || $reviewText === '') {
        set_flash('error', 'Rating bintang 1 sampai 5 dan isi ulasan wajib diisi.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    submit_product_review((int) $product['id'], (int) current_user()['id'], $stars, $reviewText);
    set_flash('success', 'Ulasan berhasil dikirim.');
    redirect_to('product.php?slug=' . $product['slug']);
}

track_store_visit((int) $product['store_id']);
track_product_view((int) $product['id'], (int) $product['store_id']);
$reviews = find_reviews_by_product((int) $product['id']);

render_layout($product['name'], function (?array $user = null) use ($product, $reviews): void {
    ?>
    <section class="page-intro detail-intro">
      <div class="page-intro-copy">
        <span class="eyebrow">Detail Produk</span>
        <h2><?= e($product['name']) ?></h2>
        <p><?= e($product['region']) ?> • <?= e($product['type']) ?> • oleh <?= e($product['store_name']) ?></p>
      </div>
      <div class="detail-intro-actions">
        <a href="<?= e(base_path('katalog.php')) ?>" class="ghost-link"><i class="fa-solid fa-arrow-left"></i>Kembali ke katalog</a>
        <button class="fav-btn intro-fav-btn" type="button" data-id="<?= e(favorite_product_id($product)) ?>" aria-label="Simpan ke favorit">
          <span class="fav-btn-copy">
            <i class="fa-solid fa-heart"></i>
            <span>Favoritkan</span>
          </span>
        </button>
      </div>
    </section>

    <section class="detail-layout">
      <article class="detail-panel detail-product-card">
        <img src="<?= e(base_path($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" />
        <div class="detail-product-copy">
          <div class="detail-product-head">
            <div>
              <h2><?= e($product['name']) ?></h2>
              <p class="detail-lead"><?= e($product['short_description']) ?></p>
            </div>
            <div class="detail-price-box">
              <span>Estimasi Harga</span>
              <div class="detail-price"><?= e(rupiah($product['price_display'])) ?></div>
            </div>
          </div>
        </div>
        <div class="meta-row">
          <span class="meta-chip"><?= e($product['region']) ?></span>
          <span class="meta-chip"><?= e($product['type']) ?></span>
          <span class="meta-chip"><?= e(number_format((float) $product['rating'], 1)) ?> / 5</span>
          <span class="meta-chip"><?= e(stars_from_rating((float) $product['rating'])) ?></span>
        </div>
        <p class="detail-body-copy"><?= e($product['long_description']) ?></p>
      </article>

      <aside class="detail-panel detail-store-card">
        <div class="detail-section-heading">
          <span class="eyebrow soft">Mitra Toko</span>
          <h3>Informasi Toko</h3>
        </div>
        <div class="store-identity">
          <div class="store-identity-badge"><i class="fa-solid fa-store"></i></div>
          <div>
            <p><strong><?= e($product['store_name']) ?></strong></p>
            <p><?= e($product['store_description']) ?></p>
          </div>
        </div>
        <p class="store-address"><i class="fa-solid fa-location-dot"></i><?= e($product['address']) ?></p>
        <div class="contact-links">
          <a href="<?= e(base_path('store.php?slug=' . $product['store_slug'])) ?>"><i class="fa-solid fa-shop"></i>Halaman Toko</a>
          <a href="https://wa.me/<?= e($product['whatsapp']) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
          <a href="https://instagram.com/<?= e(ltrim($product['instagram'], '@')) ?>" target="_blank" rel="noreferrer"><i class="fa-brands fa-instagram"></i>Instagram</a>
        </div>
        <div class="product-store-list">
          <div class="product-mini-card">
            <strong>Jumlah Ulasan</strong>
            <p><?= e(number_short((int) $product['review_count'])) ?> ulasan publik.</p>
          </div>
          <div class="product-mini-card">
            <strong>Rata-rata Bintang</strong>
            <p><?= e(number_format((float) $product['rating'], 1)) ?> dari 5 bintang.</p>
          </div>
          <div class="product-mini-card">
            <strong>Catatan Platform</strong>
            <p>PusakaRasa hanya menampilkan informasi katalog dan kontak toko, tanpa proses checkout.</p>
          </div>
        </div>
      </aside>
    </section>

    <section class="review-section-wrap">
      <div class="detail-layout">
        <article class="detail-panel">
          <div class="detail-section-heading">
            <span class="eyebrow soft">Bagikan Pendapat</span>
            <h3>Tulis Review</h3>
          </div>
          <p class="muted-note">Berikan penilaian 1 sampai 5 bintang. Nilai ini akan masuk ke rata-rata rating produk.</p>
          <form method="post" class="form-panel review-form-panel">
            <input type="hidden" name="action" value="submit_review" />
            <label>
              Rating Bintang
              <div class="star-rating-field" data-selected="0" aria-label="Pilih rating bintang">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                  <label class="star-rating-option" data-value="<?= e((string) $star) ?>" aria-label="<?= e((string) $star) ?> bintang">
                    <input type="radio" name="stars" value="<?= e((string) $star) ?>" required />
                    <span class="star-icon">★</span>
                  </label>
                <?php endfor; ?>
              </div>
            </label>
            <label>
              Ulasan
              <textarea name="review_text" placeholder="Bagikan pengalaman Anda terhadap produk ini..." required></textarea>
            </label>
            <button type="submit">Kirim Review</button>
          </form>
        </article>

        <aside class="detail-panel">
          <div class="detail-section-heading">
            <span class="eyebrow soft">Suara Pengunjung</span>
            <h3>Review Pengguna</h3>
          </div>
          <div class="review-list">
            <?php if (!$reviews): ?>
              <div class="empty-state">Belum ada review pengguna untuk produk ini.</div>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
              <article class="review-card-simple">
                <div class="review-head">
                  <div>
                    <strong><?= e($review['reviewer_name']) ?></strong>
                    <small><?= e(date('d M Y H:i', strtotime((string) $review['created_at']))) ?></small>
                  </div>
                  <span class="review-stars"><?= e(str_repeat('★', (int) $review['stars']) . str_repeat('☆', 5 - (int) $review['stars'])) ?></span>
                </div>
                <p><?= e($review['review_text']) ?></p>
                <?php if ($user && (int) $review['user_id'] === (int) $user['id']): ?>
                  <form method="post" class="review-delete-form">
                    <input type="hidden" name="action" value="delete_review" />
                    <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>" />
                    <button type="submit" class="review-delete-btn" onclick="return confirm('Hapus ulasan Anda ini?');">
                      <i class="fa-regular fa-trash-can"></i>Hapus pesan
                    </button>
                  </form>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        </aside>
      </div>
    </section>
    <?php
});
