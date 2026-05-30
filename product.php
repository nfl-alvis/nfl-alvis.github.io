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
$productImages = product_image_paths($product);

render_layout($product['name'], function (?array $user = null) use ($product, $reviews, $productImages): void {
    $galleryImages = array_map(static fn(string $path): string => base_path($path), $productImages);
    $galleryJson = json_encode($galleryImages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?: '[]';
    ?>
    <div class="dp-wrap">
      <div class="dp-breadcrumb">
        <a href="<?= e(base_path('index.php')) ?>">Beranda</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <a href="<?= e(base_path('katalog.php')) ?>">Katalog</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span><?= e($product['name']) ?></span>
      </div>

      <div class="dp-main">
        <div class="dp-left">
          <div class="dp-img-panel">
            <div class="dp-img-box dp-gallery" data-product-gallery data-images="<?= e($galleryJson) ?>">
              <img data-gallery-image src="<?= e($galleryImages[0] ?? base_path($product['image_path'])) ?>" alt="<?= e($product['name']) ?>" />
              <?php if (count($galleryImages) > 1): ?>
                <button class="dp-gallery-nav dp-gallery-prev" type="button" data-gallery-prev aria-label="Gambar sebelumnya">
                  <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="dp-gallery-nav dp-gallery-next" type="button" data-gallery-next aria-label="Gambar berikutnya">
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </button>
                <div class="dp-gallery-count" data-gallery-count>1 / <?= e((string) count($galleryImages)) ?></div>
              <?php endif; ?>
            </div>
            <div class="dp-img-meta">
              <div class="dp-img-tags">
                <span class="dp-chip"><?= e($product['region']) ?></span>
                <span class="dp-chip"><?= e($product['type']) ?></span>
                <span class="dp-chip green"><?= e($product['tag_label']) ?></span>
              </div>
              <button class="fav-btn dp-fav-btn" type="button" data-id="<?= e(favorite_product_id($product)) ?>" aria-label="Simpan ke favorit" aria-pressed="false">
                <i class="fa-regular fa-heart" aria-hidden="true"></i>
                <span>Favoritkan</span>
              </button>
            </div>
          </div>

          <div class="dp-info-panel">
            <div class="dp-eyebrow">Detail Produk</div>
            <div class="dp-product-header">
              <div>
                <div class="dp-product-name"><?= e($product['name']) ?></div>
              </div>
              <div class="dp-price-box">
                <div class="dp-price-label">Estimasi Harga</div>
                <div class="dp-price"><?= e(rupiah($product['price_display'])) ?></div>
              </div>
            </div>
            <div class="dp-rating-row">
              <span class="dp-stars"><?= rating_stars_html((float) $product['rating']) ?></span>
              <span class="dp-rating-val"><?= e(number_format((float) $product['rating'], 1)) ?></span>
              <span class="dp-rating-count">· <?= e(number_short((int) $product['review_count'])) ?> ulasan</span>
            </div>
            <p class="dp-desc"><?= e($product['short_description']) ?></p>
            <div class="dp-chips-row">
              <span class="dp-chip"><?= e($product['tag_label']) ?></span>
              <span class="dp-chip"><?= e(number_format((float) $product['rating'], 1)) ?> / 5</span>
              <span class="dp-chip"><?= rating_stars_html((float) $product['rating']) ?></span>
            </div>
            <div class="dp-history">
              <div class="dp-history-title">Deskripsi produk</div>
              <p class="dp-history-text"><?= e($product['long_description']) ?></p>
            </div>
          </div>
        </div>

        <div class="dp-right">
          <div class="dp-store-card">
            <div class="dp-store-header">
              <div class="dp-store-avatar"><i class="fa-solid fa-store" aria-hidden="true"></i></div>
              <div>
                <div class="dp-store-name"><?= e($product['store_name']) ?></div>
                <div class="dp-store-region"><?= e($product['region']) ?></div>
              </div>
            </div>
            <div class="dp-store-body">
              <div class="dp-store-row">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                <span><?= e($product['address']) ?></span>
              </div>
              <div class="dp-store-row">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span><?= e($product['store_description']) ?></span>
              </div>
              <div class="dp-store-row">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                <span>PusakaRasa hanya menampilkan informasi katalog tanpa proses checkout.</span>
              </div>
            </div>
            <div class="dp-store-actions">
              <a href="<?= e(base_path('store.php?slug=' . $product['store_slug'])) ?>" class="dp-store-btn green"><i class="fa-solid fa-shop" aria-hidden="true"></i>Lihat Halaman Toko</a>
              <a href="https://wa.me/<?= e($product['whatsapp']) ?>" target="_blank" rel="noreferrer" class="dp-store-btn wa"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i>WhatsApp Toko</a>
              <a href="https://instagram.com/<?= e(ltrim($product['instagram'], '@')) ?>" target="_blank" rel="noreferrer" class="dp-store-btn ig"><i class="fa-brands fa-instagram" aria-hidden="true"></i>Instagram Toko</a>
            </div>
          </div>

          <div class="dp-stats-card">
            <div class="dp-stats-title">Statistik produk</div>
            <div class="dp-stats-row"><span>Total ulasan</span><span><?= e(number_format((int) $product['review_count'])) ?></span></div>
            <div class="dp-stats-row"><span>Rata-rata bintang</span><span><?= e(number_format((float) $product['rating'], 1)) ?> / 5</span></div>
            <div class="dp-stats-row"><span>Kategori</span><span><?= e($product['type']) ?></span></div>
            <div class="dp-stats-row"><span>Asal daerah</span><span><?= e($product['region']) ?></span></div>
          </div>
        </div>
      </div>

      <div class="dp-review-section">
        <div class="dp-review-header">
          <span class="dp-review-title">Ulasan pengguna</span>
          <span class="dp-review-count"><?= e(number_format(count($reviews))) ?> ulasan ditampilkan</span>
        </div>
        <div class="dp-review-grid">
          <div class="dp-review-form">
            <form method="post">
              <input type="hidden" name="action" value="submit_review" />
              <div class="dp-form-label">Rating bintang</div>
              <div class="star-rating-field dp-star-row" data-selected="0" aria-label="Pilih rating bintang">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                  <label class="star-rating-option dp-star-option" data-value="<?= e((string) $star) ?>" aria-label="<?= e((string) $star) ?> bintang">
                    <input type="radio" name="stars" value="<?= e((string) $star) ?>" required />
                    <span class="star-icon dp-star">★</span>
                  </label>
                <?php endfor; ?>
              </div>
              <div class="dp-form-label">Tulis ulasan</div>
              <textarea class="dp-review-textarea" name="review_text" placeholder="Bagikan pengalaman Anda terhadap produk ini..." required></textarea>
              <button type="submit" class="dp-submit-btn">Kirim ulasan</button>
            </form>
          </div>

          <div class="dp-reviews-list">
            <?php if (!$reviews): ?>
              <div class="empty-state">Belum ada review pengguna untuk produk ini.</div>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
              <div class="dp-review-item">
                <div class="dp-review-item-header">
                  <span class="dp-reviewer-name"><?= e($review['reviewer_name']) ?></span>
                  <span class="dp-reviewer-stars"><?= e(str_repeat('★', (int) $review['stars']) . str_repeat('☆', 5 - (int) $review['stars'])) ?></span>
                </div>
                <div class="dp-review-date"><?= e(date('d M Y H:i', strtotime((string) $review['created_at']))) ?></div>
                <p class="dp-review-text"><?= e($review['review_text']) ?></p>
                <?php if ($user && (int) $review['user_id'] === (int) $user['id']): ?>
                  <form method="post" class="review-delete-form">
                    <input type="hidden" name="action" value="delete_review" />
                    <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>" />
                    <button type="submit" class="review-delete-btn" onclick="return confirm('Hapus ulasan Anda ini?');">
                      <i class="fa-regular fa-trash-can"></i>Hapus pesan
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    <script>
      (() => {
        const gallery = document.querySelector('[data-product-gallery]');
        if (!gallery) return;

        const image = gallery.querySelector('[data-gallery-image]');
        const prev = gallery.querySelector('[data-gallery-prev]');
        const next = gallery.querySelector('[data-gallery-next]');
        const count = gallery.querySelector('[data-gallery-count]');
        let images = [];
        let index = 0;

        try {
          images = JSON.parse(gallery.dataset.images || '[]');
        } catch (error) {
          images = [];
        }

        function render(nextIndex) {
          if (!image || images.length === 0) return;

          index = (nextIndex + images.length) % images.length;
          image.src = images[index];

          if (count) {
            count.textContent = (index + 1) + ' / ' + images.length;
          }
        }

        prev?.addEventListener('click', () => render(index - 1));
        next?.addEventListener('click', () => render(index + 1));

        gallery.addEventListener('keydown', (event) => {
          if (event.key === 'ArrowLeft') render(index - 1);
          if (event.key === 'ArrowRight') render(index + 1);
        });

        gallery.tabIndex = 0;
      })();
    </script>
    <?php
}, ['hide_footer' => true]);
