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
    $currentUser = current_user();
    if (!$currentUser) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        redirect_to('login.php');
    }

    $canManageReviews = user_can_manage_product_reviews($currentUser, $product);
    $action = $_POST['action'] ?? 'submit_review';

    if ($action === 'reply_review') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $replyText = trim($_POST['reply_text'] ?? '');

        if (!$canManageReviews) {
            set_flash('error', 'Anda tidak memiliki akses untuk membalas ulasan produk ini.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        if ($reviewId < 1 || $replyText === '') {
            set_flash('error', 'Balasan ulasan wajib diisi.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        if (strlen($replyText) > 1200) {
            set_flash('error', 'Balasan ulasan maksimal 1200 karakter.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        if (!save_review_reply($reviewId, (int) $product['id'], (int) $currentUser['id'], $replyText)) {
            set_flash('error', 'Ulasan tidak ditemukan.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        set_flash('success', 'Balasan ulasan berhasil disimpan.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    if ($action === 'delete_review_reply') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);

        if (!$canManageReviews) {
            set_flash('error', 'Anda tidak memiliki akses untuk menghapus balasan ulasan ini.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        if ($reviewId < 1 || !delete_review_reply($reviewId, (int) $product['id'])) {
            set_flash('error', 'Balasan ulasan tidak dapat dihapus.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        set_flash('success', 'Balasan ulasan berhasil dihapus.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    if ($action === 'delete_review') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $deleted = $reviewId > 0
            ? delete_product_review($reviewId, (int) $product['id'], (int) $currentUser['id'])
            : false;

        if (!$deleted && $reviewId > 0 && $canManageReviews) {
            $deleted = delete_product_review_by_manager($reviewId, (int) $product['id']);
        }

        if (!$deleted) {
            set_flash('error', 'Ulasan tidak dapat dihapus.');
            redirect_to('product.php?slug=' . $product['slug']);
        }

        set_flash('success', 'Ulasan berhasil dihapus.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    if ($action !== 'submit_review') {
        set_flash('error', 'Aksi tidak valid.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    $stars = (int) ($_POST['stars'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');

    if ($stars < 1 || $stars > 5 || $reviewText === '') {
        set_flash('error', 'Rating bintang 1 sampai 5 dan isi ulasan wajib diisi.');
        redirect_to('product.php?slug=' . $product['slug']);
    }

    submit_product_review((int) $product['id'], (int) $currentUser['id'], $stars, $reviewText);
    set_flash('success', 'Ulasan berhasil dikirim.');
    redirect_to('product.php?slug=' . $product['slug']);
}

track_store_visit((int) $product['store_id']);
track_product_view((int) $product['id'], (int) $product['store_id']);
$reviews = find_reviews_by_product((int) $product['id']);
$productImages = product_image_paths($product);

render_layout($product['name'], function (?array $user = null) use ($product, $reviews, $productImages): void {
    $canManageReviews = user_can_manage_product_reviews($user, $product);
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
        <div class="dp-product-panel">
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
      </div>

      <div class="dp-review-section">
        <div class="dp-review-header">
          <div class="dp-review-heading-copy">
            <span class="dp-review-title">Ulasan pengguna</span>
            <span class="dp-review-count"><?= e(number_format(count($reviews))) ?> ulasan ditampilkan</span>
          </div>
          <div class="dp-review-filters" data-review-filters aria-label="Filter ulasan berdasarkan rating">
            <button class="dp-review-filter active" type="button" data-review-filter="all">Semua</button>
            <?php for ($filterStar = 5; $filterStar >= 1; $filterStar--): ?>
              <button class="dp-review-filter" type="button" data-review-filter="<?= e((string) $filterStar) ?>"><?= e((string) $filterStar) ?> bintang</button>
            <?php endfor; ?>
          </div>
        </div>
        <div class="dp-review-grid">
          <div class="dp-review-form">
            <form method="post">
              <input type="hidden" name="action" value="submit_review" />
              <div class="dp-review-compose-main">
                <div class="dp-review-form-copy">
                  <span>Tambah ulasan</span>
                  <strong>Bagikan pengalaman Anda</strong>
                </div>
                <div class="dp-review-input-control">
                  <div class="dp-form-label">Tulis ulasan</div>
                  <textarea class="dp-review-textarea" name="review_text" placeholder="Bagikan pengalaman Anda terhadap produk ini..." required></textarea>
                  <div class="dp-review-submit-row">
                    <button type="submit" class="dp-submit-btn">Kirim ulasan</button>
                  </div>
                </div>
              </div>
              <div class="dp-review-side-control">
                <div class="dp-review-rating-control">
                  <div class="dp-form-label">Rating bintang</div>
                  <div class="star-rating-field dp-star-row" data-selected="0" aria-label="Pilih rating bintang">
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                      <label class="star-rating-option dp-star-option" data-value="<?= e((string) $star) ?>" aria-label="<?= e((string) $star) ?> bintang">
                        <input type="radio" name="stars" value="<?= e((string) $star) ?>" required />
                        <span class="star-icon dp-star">★</span>
                      </label>
                    <?php endfor; ?>
                  </div>
                </div>
              </div>
            </form>
          </div>

          <div class="dp-reviews-list">
            <?php if (!$reviews): ?>
              <div class="empty-state">Belum ada review pengguna untuk produk ini.</div>
            <?php endif; ?>
            <?php foreach ($reviews as $review): ?>
              <?php
                $canDeleteReview = $user && ((int) $review['user_id'] === (int) $user['id'] || $canManageReviews);
                $hasReviewActions = $canManageReviews || $canDeleteReview;
                $reviewerName = (string) ($review['reviewer_name'] ?? 'User');
                $reviewerInitial = strtoupper(substr(trim($reviewerName) !== '' ? trim($reviewerName) : 'U', 0, 1));
              ?>
              <div class="dp-review-item <?= $hasReviewActions ? 'has-review-actions' : '' ?>" data-review-stars="<?= e((string) (int) $review['stars']) ?>">
                <div class="dp-review-item-header">
                  <div class="dp-reviewer-profile">
                    <span class="dp-reviewer-avatar"><?= e($reviewerInitial) ?></span>
                    <span class="dp-reviewer-meta">
                      <strong class="dp-reviewer-name"><?= e($reviewerName) ?></strong>
                      <small><?= e(date('d M Y H:i', strtotime((string) $review['created_at']))) ?></small>
                    </span>
                  </div>
                  <span class="dp-reviewer-stars" aria-label="<?= e((string) (int) $review['stars']) ?> dari 5 bintang">
                    <i class="fa-solid fa-star" aria-hidden="true"></i><?= e(number_format((float) $review['stars'], 1)) ?>
                  </span>
                </div>
                <p class="dp-review-text"><?= e($review['review_text']) ?></p>
                <?php if (!empty($review['reply_text'])): ?>
                  <div class="dp-review-reply">
                    <div class="dp-review-reply-head">
                      <span><i class="fa-solid fa-reply" aria-hidden="true"></i><?= ($review['reply_admin_role'] ?? '') === ROLE_SUPER_ADMIN ? 'Balasan Super Admin' : 'Balasan Admin Toko' ?></span>
                      <small><?= e(date('d M Y H:i', strtotime((string) $review['reply_updated_at']))) ?></small>
                    </div>
                    <p><?= e($review['reply_text']) ?></p>
                  </div>
                <?php endif; ?>
                <?php if ($canManageReviews): ?>
                  <div class="review-reply-panel" id="reply-panel-<?= e((string) $review['id']) ?>" hidden>
                    <form method="post" class="review-reply-form">
                      <input type="hidden" name="action" value="reply_review" />
                      <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>" />
                      <label for="reply-<?= e((string) $review['id']) ?>">Balasan admin</label>
                      <textarea id="reply-<?= e((string) $review['id']) ?>" name="reply_text" maxlength="1200" placeholder="Tulis balasan resmi untuk ulasan ini..." required><?= e((string) ($review['reply_text'] ?? '')) ?></textarea>
                      <button type="submit" class="review-reply-btn"><?= empty($review['reply_text']) ? 'Kirim balasan' : 'Perbarui balasan' ?></button>
                    </form>
                  </div>
                <?php endif; ?>
                <?php if ($hasReviewActions): ?>
                  <div class="review-action-menu" data-review-menu>
                    <button class="review-action-toggle" type="button" data-review-menu-toggle aria-expanded="false" aria-controls="review-menu-<?= e((string) $review['id']) ?>" aria-label="Opsi ulasan">
                      <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                    </button>
                    <div class="review-action-popover" id="review-menu-<?= e((string) $review['id']) ?>" data-review-menu-popover hidden>
                      <?php if ($canManageReviews): ?>
                        <button type="button" class="review-menu-item" data-review-reply-open="reply-panel-<?= e((string) $review['id']) ?>">
                          <i class="fa-regular fa-comment-dots" aria-hidden="true"></i><?= empty($review['reply_text']) ? 'Balas komentar' : 'Edit balasan' ?>
                        </button>
                        <?php if (!empty($review['reply_text'])): ?>
                          <form method="post">
                            <input type="hidden" name="action" value="delete_review_reply" />
                            <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>" />
                            <button type="submit" class="review-menu-item is-danger" onclick="return confirm('Hapus balasan ulasan ini?');">
                              <i class="fa-regular fa-trash-can" aria-hidden="true"></i>Hapus balasan
                            </button>
                          </form>
                        <?php endif; ?>
                      <?php endif; ?>
                      <?php if ($canDeleteReview): ?>
                        <form method="post">
                          <input type="hidden" name="action" value="delete_review" />
                          <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>" />
                          <button type="submit" class="review-menu-item is-danger" onclick="return confirm('Hapus ulasan ini?');">
                            <i class="fa-regular fa-trash-can" aria-hidden="true"></i><?= $canManageReviews ? 'Hapus ulasan' : 'Hapus pesan' ?>
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if ($reviews): ?>
              <div class="dp-review-filter-empty" data-review-filter-empty hidden>Tidak ada ulasan untuk filter rating ini.</div>
            <?php endif; ?>
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

      (() => {
        const filterGroup = document.querySelector('[data-review-filters]');
        if (!filterGroup) return;

        const items = Array.from(document.querySelectorAll('[data-review-stars]'));
        const empty = document.querySelector('[data-review-filter-empty]');

        filterGroup.addEventListener('click', (event) => {
          const button = event.target instanceof Element ? event.target.closest('[data-review-filter]') : null;
          if (!button) return;

          const selected = button.dataset.reviewFilter || 'all';
          let visibleCount = 0;

          filterGroup.querySelectorAll('[data-review-filter]').forEach((filterButton) => {
            filterButton.classList.toggle('active', filterButton === button);
          });

          items.forEach((item) => {
            const shouldShow = selected === 'all' || item.dataset.reviewStars === selected;
            item.hidden = !shouldShow;

            if (shouldShow) {
              visibleCount++;
            }
          });

          if (empty) {
            empty.hidden = visibleCount > 0 || items.length === 0;
          }
        });
      })();

      (() => {
        const menus = Array.from(document.querySelectorAll('[data-review-menu]'));

        function closeMenus(except) {
          menus.forEach((menu) => {
            if (except && menu === except) return;

            const toggle = menu.querySelector('[data-review-menu-toggle]');
            const popover = menu.querySelector('[data-review-menu-popover]');

            if (toggle) {
              toggle.setAttribute('aria-expanded', 'false');
            }

            if (popover) {
              popover.hidden = true;
            }
          });
        }

        document.addEventListener('click', (event) => {
          const target = event.target instanceof Element ? event.target : null;
          if (!target) return;

          const toggle = target.closest('[data-review-menu-toggle]');
          if (toggle) {
            const menu = toggle.closest('[data-review-menu]');
            const popover = menu?.querySelector('[data-review-menu-popover]');
            const shouldOpen = Boolean(popover?.hidden);

            closeMenus(menu);

            if (popover) {
              popover.hidden = !shouldOpen;
              toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
            }

            return;
          }

          const replyOpen = target.closest('[data-review-reply-open]');
          if (replyOpen) {
            const panelId = replyOpen.getAttribute('data-review-reply-open') || '';
            const panel = document.getElementById(panelId);

            if (panel) {
              panel.hidden = false;
              panel.querySelector('textarea')?.focus();
            }

            closeMenus();
            return;
          }

          if (!target.closest('[data-review-menu]')) {
            closeMenus();
          }
        });

        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            closeMenus();
          }
        });
      })();
    </script>
    <?php
}, ['hide_footer' => true]);
