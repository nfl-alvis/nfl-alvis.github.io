<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/store-sidebar.php';

require_role(ROLE_STORE_ADMIN);

$user = current_user();
$storeId = (int) ($user['store_id'] ?? 0);

if (!$storeId) {
    set_flash('error', 'Akun store admin belum terhubung ke toko.');
    redirect_to('index.php');
}

$storeStmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch();

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $imagePaths = save_uploaded_product_images($_FILES['product_images'] ?? [], null, true);
        $imagePath = $imagePaths[0];

        ensure_product_images_table();
        db()->beginTransaction();

        $stmt = db()->prepare(
            'INSERT INTO products
             (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
             VALUES
             (:store_id, :name, :slug, :type, :region, :short_description, :long_description, :price_display, 0, 0, :tag_label, :image_path, 0, 0, 0, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'store_id' => $storeId,
            'name' => $name,
            'slug' => slugify($name . '-' . substr((string) time(), -4)),
            'type' => trim($_POST['type'] ?? 'Makanan'),
            'region' => trim($_POST['region'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'price_display' => normalize_price_display($_POST['price_display'] ?? ''),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => $imagePath,
        ]);
        replace_product_images((int) db()->lastInsertId(), $imagePaths);
        db()->commit();

        set_flash('success', 'Produk baru berhasil ditambahkan.');
        redirect_to('store-products.php');
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        set_flash('error', $exception->getMessage());
        redirect_to('store-add-product.php');
    }
}

render_layout('Tambah Produk', function (?array $currentUser = null) use ($user, $store): void {
    ?>
    <div class="shell">
      <?php render_store_sidebar($user, $store, 'products'); ?>

      <main class="main">
        <div class="topbar">
          <div class="topbar-left">
            <a href="<?= e(base_path('store-products.php')) ?>" class="back-btn" aria-label="Kembali ke daftar produk">
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>
            <div>
              <div class="topbar-heading">Tambah Produk Baru</div>
              <div class="topbar-sub">Isi detail produk untuk ditampilkan di katalog toko.</div>
            </div>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> &bull; Store Admin</div>
        </div>

        <div class="content-grid store-add-product-grid">
          <article class="form-card">
            <div class="form-card-head">
              <div>
                <div class="form-card-title">Detail Produk</div>
                <div class="form-card-meta">Semua field bertanda <span class="required-mark">*</span> wajib diisi</div>
              </div>
              <span class="badge-new">Produk Baru</span>
            </div>

            <form method="post" enctype="multipart/form-data" class="store-add-product-form">
              <div class="form-body">
                <div class="sec-divider"><span class="sec-divider-label">Informasi dasar</span></div>

                <div class="field-wrap">
                  <label class="field-label" for="productName">Nama Produk <span class="required-mark">*</span></label>
                  <input type="text" id="productName" name="name" placeholder="Contoh: Rawon Sapi Spesial" required />
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="productType">Kategori <span class="required-mark">*</span></label>
                    <select id="productType" name="type" required>
                      <option value="Makanan">Makanan</option>
                      <option value="Minuman">Minuman</option>
                    </select>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="productRegion">Daerah Asal <span class="required-mark">*</span></label>
                    <select id="productRegion" name="region" required>
                      <?php render_province_options(); ?>
                    </select>
                  </div>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="productPrice">Harga Tampilan <span class="required-mark">*</span></label>
                    <input type="text" id="productPrice" name="price_display" inputmode="numeric" autocomplete="off" data-price-format placeholder="Contoh: 25.000" required />
                    <div class="field-hint">Ketik angka saja, sistem akan memformat otomatis.</div>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="productTag">Tag Label <span class="required-mark">*</span></label>
                    <input type="text" id="productTag" name="tag_label" placeholder="#gurih #tradisional" required />
                    <div class="field-hint">Pisahkan dengan spasi jika lebih dari satu.</div>
                  </div>
                </div>

                <div class="sec-divider"><span class="sec-divider-label">Gambar produk</span></div>

                <label class="file-drop" for="productImage">
                  <input type="file" id="productImage" name="product_images[]" accept=".jpg,.jpeg,.png,.webp" multiple required />
                  <span class="file-drop-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></span>
                  <span class="file-drop-text"><strong>Klik untuk upload</strong> atau seret file ke sini</span>
                  <span class="file-drop-sub" id="productImageHint">Bisa pilih beberapa gambar. JPG, PNG, WEBP - maks. 5MB/file</span>
                </label>

                <div class="sec-divider"><span class="sec-divider-label">Deskripsi produk</span></div>

                <div class="field-wrap">
                  <label class="field-label" for="productShortDescription">Deskripsi Singkat <span class="required-mark">*</span></label>
                  <textarea id="productShortDescription" name="short_description" class="short-description-field" placeholder="Cerita singkat tentang produk ini, tampil di kartu katalog..." maxlength="160" required></textarea>
                  <div class="field-hint">Maks. 160 karakter, digunakan pada pratinjau kartu.</div>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="productLongDescription">Deskripsi Panjang <span class="required-mark">*</span></label>
                  <textarea id="productLongDescription" name="long_description" placeholder="Jelaskan lebih lengkap: bahan, cara penyajian, sejarah, dan lainnya..." required></textarea>
                </div>
              </div>

              <div class="submit-bar">
                <div class="submit-note">Data akan segera tampil di katalog setelah disimpan.</div>
                <button type="submit" class="btn-submit">
                  <span class="btn-icon"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i></span>
                  Simpan Produk
                </button>
              </div>
            </form>
          </article>

          <aside class="product-preview-panel" aria-label="Pratinjau produk">
            <div class="product-preview-heading">
              <span class="product-preview-kicker">Pratinjau Katalog</span>
              <strong>Food Card</strong>
            </div>

            <div class="food-card food-card--catalog is-clickable" data-favorite-id="preview-product">
              <a class="food-card-detail-link" href="<?= e(base_path('katalog.php')) ?>" aria-label="Pratinjau produk katalog">
                <div class="card-image">
                  <img id="previewImage" src="<?= e(base_path('assets/image/image.png')) ?>" alt="Pratinjau gambar produk" />
                  <div class="image-tags">
                    <span id="previewRegion">Daerah asal</span>
                  </div>
                </div>
                <div class="card-content">
                  <div class="card-meta-line">
                    <span class="food-store"><?= e($store['name'] ?? 'Toko PusakaRasa') ?></span>
                  </div>
                  <h3 class="food-title" id="previewName">Nama Produk</h3>
                  <p class="food-desc" id="previewDescription">Deskripsi singkat produk akan tampil di sini sebelum produk disimpan.</p>
                  <div class="food-rating">
                    <span class="stars">
                      <span class="rating-stars" aria-label="0.0 dari 5 bintang">
                        <span class="rating-star is-empty" aria-hidden="true">★</span>
                        <span class="rating-star is-empty" aria-hidden="true">★</span>
                        <span class="rating-star is-empty" aria-hidden="true">★</span>
                        <span class="rating-star is-empty" aria-hidden="true">★</span>
                        <span class="rating-star is-empty" aria-hidden="true">★</span>
                      </span>
                    </span>
                    <span class="review">0.0 • 0 ulasan</span>
                  </div>
                  <p class="food-price" id="previewPrice">Rp 25.000</p>
                </div>
              </a>
              <button class="fav-btn" type="button" aria-label="Simpan ke favorit"></button>
            </div>
          </aside>
        </div>
      </main>
    </div>

    <script>
      (function () {
        const fields = {
          name: document.getElementById('productName'),
          region: document.getElementById('productRegion'),
          price: document.getElementById('productPrice'),
          shortDescription: document.getElementById('productShortDescription'),
          image: document.getElementById('productImage')
        };

        const preview = {
          name: document.getElementById('previewName'),
          region: document.getElementById('previewRegion'),
          price: document.getElementById('previewPrice'),
          description: document.getElementById('previewDescription'),
          image: document.getElementById('previewImage'),
          imageHint: document.getElementById('productImageHint')
        };

        function setText(element, value, fallback) {
          if (!element) {
            return;
          }

          element.textContent = value.trim() || fallback;
        }

        function formattedPrice(value) {
          const digits = value.replace(/\D/g, '');

          if (!digits) {
            return 'Rp 25.000';
          }

          return 'Rp ' + digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function syncPreview() {
          setText(preview.name, fields.name?.value || '', 'Nama Produk');
          setText(preview.region, fields.region?.value || '', 'Daerah asal');
          setText(preview.description, fields.shortDescription?.value || '', 'Deskripsi singkat produk akan tampil di sini sebelum produk disimpan.');

          if (preview.price && fields.price) {
            preview.price.textContent = formattedPrice(fields.price.value);
          }
        }

        Object.keys(fields).forEach(function (key) {
          const field = fields[key];

          if (!field || key === 'image') {
            return;
          }

          field.addEventListener('input', syncPreview);
          field.addEventListener('change', syncPreview);
        });

        if (fields.image && preview.image) {
          fields.image.addEventListener('change', function (event) {
            const files = Array.from(event.target.files || []);
            const file = files[0];

            if (!file) {
              return;
            }

            if (preview.imageHint) {
              preview.imageHint.textContent = files.length > 1 ? files.length + ' gambar dipilih' : file.name;
            }

            const reader = new FileReader();
            reader.addEventListener('load', function (readerEvent) {
              preview.image.src = String(readerEvent.target.result || '');
            });
            reader.readAsDataURL(file);
          });
        }

        syncPreview();
      })();
    </script>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body store-add-product-page']);
