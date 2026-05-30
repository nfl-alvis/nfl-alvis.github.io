<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

$listingQuery = $_GET;
unset($listingQuery['edit']);
$listingUrl = 'admin-products.php' . ($listingQuery ? '?' . http_build_query($listingQuery) : '');

if (is_post() && ($_POST['action'] ?? '') === 'edit_product') {
    $productId = (int) ($_POST['id'] ?? 0);

    try {
        $uploadedImages = $_FILES['product_images'] ?? ($_FILES['product_image'] ?? []);

        $currentProductStmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
        $currentProductStmt->execute(['id' => $productId]);
        $currentProduct = $currentProductStmt->fetch();

        if (!$currentProduct) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        $imagePaths = edited_product_image_paths($currentProduct, $uploadedImages, $_POST['remove_images'] ?? []);
        $imagePath = $imagePaths[0];

        db()->beginTransaction();

        db()->prepare(
            'UPDATE products
             SET store_id = :store_id, name = :name, slug = :slug, type = :type, region = :region,
                 short_description = :short_description, long_description = :long_description,
                 price_display = :price_display, tag_label = :tag_label, image_path = :image_path, is_featured = :is_featured,
                 is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $productId,
            'store_id' => (int) ($_POST['store_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'slug' => slugify(trim($_POST['name'] ?? '') . '-' . substr((string) time(), -4)),
            'type' => trim($_POST['type'] ?? 'Makanan'),
            'region' => trim($_POST['region'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'price_display' => normalize_price_display($_POST['price_display'] ?? ''),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => $imagePath,
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);

        replace_product_images($productId, $imagePaths);

        db()->commit();

        set_flash('success', 'Produk berhasil diperbarui.');
        redirect_to($listingUrl);
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        set_flash('error', $exception->getMessage());
        redirect_to('admin-products.php?' . http_build_query(array_merge($listingQuery, ['edit' => $productId])));
    }
}

if (is_post() && ($_POST['action'] ?? '') === 'delete_product') {
    $productId = (int) ($_POST['id'] ?? 0);

    try {
        if ($productId < 1) {
            throw new RuntimeException('Produk tidak valid.');
        }

        ensure_product_images_table();
        db()->beginTransaction();

        db()->prepare('DELETE FROM product_images WHERE product_id = :product_id')->execute([
            'product_id' => $productId,
        ]);
        db()->prepare('DELETE FROM reviews WHERE product_id = :product_id')->execute([
            'product_id' => $productId,
        ]);
        db()->prepare('DELETE FROM product_views WHERE product_id = :product_id')->execute([
            'product_id' => $productId,
        ]);

        $deleteStmt = db()->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute(['id' => $productId]);

        if ($deleteStmt->rowCount() < 1) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        db()->commit();
        set_flash('success', 'Produk berhasil dihapus.');
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }

        set_flash('error', 'Produk gagal dihapus.');
    }
    redirect_to($listingUrl);
}

$productSearch = trim($_GET['product_search'] ?? '');
$productSort = trim($_GET['product_sort'] ?? 'created_desc');
$productPage = max(1, (int) ($_GET['product_page'] ?? 1));
$productPerPage = max(1, (int) ($_GET['product_per_page'] ?? 8));

$products = array_values(array_filter(all_products_for_admin(), static function (array $item) use ($productSearch): bool {
    if ($productSearch === '') {
        return true;
    }

    $haystack = strtolower(($item['name'] ?? '') . ' ' . ($item['store_name'] ?? '') . ' ' . ($item['type'] ?? ''));
    return strpos($haystack, strtolower($productSearch)) !== false;
}));

usort($products, static function (array $a, array $b) use ($productSort): int {
    return match ($productSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'name_desc' => strcmp((string) $b['name'], (string) $a['name']),
        default => strcmp((string) $b['created_at'], (string) $a['created_at']),
    };
});

$productsPage = paginate_array($products, $productPage, $productPerPage);
$stores = all_stores_with_admins();
$editingProduct = null;
$editProductId = (int) ($_GET['edit'] ?? 0);

if ($editProductId > 0) {
    $stmt = db()->prepare(
        'SELECT p.*, s.name AS store_name
         FROM products p
         INNER JOIN stores s ON s.id = p.store_id
         WHERE p.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $editProductId]);
    $editingProduct = $stmt->fetch() ?: null;

    if (!$editingProduct) {
        set_flash('error', 'Produk tidak ditemukan.');
        redirect_to($listingUrl);
    }
}

render_layout('Manajemen Produk Platform', function (?array $user = null) use ($productSearch, $productSort, $productPerPage, $productsPage, $stores, $editingProduct, $listingUrl): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'products'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Manajemen Produk Platform</div>
            <div class="topbar-sub">Tinjau dan kelola produk yang tampil pada katalog.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="management-layout">
          <article class="table-card management-table-card">
            <div class="table-card-head">
              <div>
                <h3>Daftar Produk</h3>
                <div class="table-meta">Menampilkan <?= e((string) count($productsPage['items'])) ?> dari <?= e((string) $productsPage['total']) ?> data</div>
              </div>
              <a class="action-button-link" href="<?= e(base_path('admin-add-product.php')) ?>">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Produk
              </a>
            </div>

            <form class="form-panel management-filters" method="get">
              <label>Cari
                <input type="search" name="product_search" value="<?= e($productSearch) ?>" placeholder="Nama produk, toko, kategori" />
              </label>
              <label>Sorting
                <select name="product_sort">
                  <option value="created_desc" <?= $productSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                  <option value="name_asc" <?= $productSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                  <option value="name_desc" <?= $productSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                </select>
              </label>
              <label>Baris
                <select name="product_per_page">
                  <?php foreach ([5, 10, 20, 50] as $amount): ?>
                    <option value="<?= e((string) $amount) ?>" <?= $productPerPage === $amount ? 'selected' : '' ?>><?= e((string) $amount) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Terapkan</button>
              <a class="filter-reset" href="<?= e(base_path('admin-products.php')) ?>">Reset</a>
            </form>

            <div class="table-scroll">
              <table class="data-table store-product-table">
                <thead>
                  <tr><th>No</th><th>Produk</th><th>Toko</th><th>Kategori</th><th>Harga</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($productsPage['items'] as $index => $item): ?>
                    <tr>
                      <td><?= e((string) ($productsPage['offset'] + $index + 1)) ?></td>
                      <td>
                        <div class="store-product-cell">
                          <img src="<?= e(base_path(trim((string) ($item['image_path'] ?? '')) !== '' ? $item['image_path'] : 'assets/image/PusakaRasa.webp')) ?>" alt="" />
                          <div>
                            <strong><?= e($item['name']) ?></strong>
                            <span><?= e($item['region'] ?? $item['store_name']) ?></span>
                          </div>
                        </div>
                      </td>
                      <td><?= e($item['store_name']) ?></td>
                      <td><?= e($item['type']) ?></td>
                      <td><?= e(rupiah($item['price_display'])) ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link product-edit-button" href="<?= e(base_path('admin-products.php?' . http_build_query(array_merge($_GET, ['edit' => $item['id']])))) ?>">Edit</a>
                          <form method="post" onsubmit="return confirm('Hapus produk ini secara permanen?')">
                            <input type="hidden" name="action" value="delete_product" />
                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>" />
                            <button type="submit" class="inline-link product-delete-button">Hapus</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <div class="table-pagination">
              <span class="table-meta">Halaman <?= e((string) $productsPage['page']) ?> dari <?= e((string) $productsPage['total_pages']) ?></span>
              <div class="page-nav">
                <a class="page-btn" href="<?= e(base_path('admin-products.php?' . http_build_query(array_merge($_GET, ['product_page' => max(1, $productsPage['page'] - 1)])))) ?>">&larr;</a>
                <?php for ($p = max(1, $productsPage['page'] - 2); $p <= min($productsPage['total_pages'], $productsPage['page'] + 2); $p++): ?>
                  <a class="page-btn <?= $p === $productsPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-products.php?' . http_build_query(array_merge($_GET, ['product_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="<?= e(base_path('admin-products.php?' . http_build_query(array_merge($_GET, ['product_page' => min($productsPage['total_pages'], $productsPage['page'] + 1)])))) ?>">&rarr;</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>

    <?php if ($editingProduct): ?>
      <div class="store-product-modal-backdrop" id="adminProductEditModal" data-close-url="<?= e(base_path($listingUrl)) ?>">
        <section class="admin-product-edit-modal" role="dialog" aria-modal="true" aria-labelledby="adminProductEditTitle">
          <article class="form-card">
            <div class="form-card-head">
              <div>
                <div class="form-card-title" id="adminProductEditTitle">Edit Produk</div>
                <div class="form-card-meta">Perbarui detail <?= e($editingProduct['name']) ?> tanpa keluar dari daftar produk</div>
              </div>
              <a class="store-product-modal-close" href="<?= e(base_path($listingUrl)) ?>" aria-label="Tutup form edit">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </a>
            </div>

            <form method="post" enctype="multipart/form-data" action="<?= e(base_path($listingUrl)) ?>">
              <input type="hidden" name="action" value="edit_product" />
              <input type="hidden" name="id" value="<?= e((string) $editingProduct['id']) ?>" />
              <input type="hidden" name="current_image" value="<?= e($editingProduct['image_path']) ?>" />

              <div class="form-body">
                <div class="sec-divider">
                  <span class="sec-divider-label">Data Utama</span>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-name">Nama Produk <span class="req">*</span></label>
                    <input id="admin-product-name" type="text" name="name" value="<?= e($editingProduct['name']) ?>" required />
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-store">Toko <span class="req">*</span></label>
                    <select id="admin-product-store" name="store_id" required>
                      <?php foreach ($stores as $store): ?>
                        <option value="<?= e((string) $store['id']) ?>" <?= (int) $editingProduct['store_id'] === (int) $store['id'] ? 'selected' : '' ?>>
                          <?= e($store['name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-type">Kategori <span class="req">*</span></label>
                    <select id="admin-product-type" name="type" required>
                      <option value="Makanan" <?= $editingProduct['type'] === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                      <option value="Minuman" <?= $editingProduct['type'] === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    </select>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-region">Wilayah <span class="req">*</span></label>
                    <select id="admin-product-region" name="region" required>
                      <?php render_province_options($editingProduct['region'] ?? ''); ?>
                    </select>
                  </div>
                </div>

                <div class="sec-divider">
                  <span class="sec-divider-label">Harga &amp; Label</span>
                </div>

                <div class="grid-2">
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-price">Harga Tampilan <span class="req">*</span></label>
                    <input id="admin-product-price" type="text" name="price_display" value="<?= e($editingProduct['price_display']) ?>" inputmode="numeric" autocomplete="off" data-price-format required />
                    <span class="field-hint">Ketik angka saja, sistem akan memformat otomatis.</span>
                  </div>
                  <div class="field-wrap">
                    <label class="field-label" for="admin-product-tag">Tag <span class="req">*</span></label>
                    <input id="admin-product-tag" type="text" name="tag_label" value="<?= e($editingProduct['tag_label']) ?>" required />
                    <span class="field-hint">Pisahkan dengan spasi jika lebih dari satu.</span>
                  </div>
                </div>

                <div class="sec-divider">
                  <span class="sec-divider-label">Media &amp; Deskripsi</span>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-product-image">Tambah Gambar Baru</label>
                  <label class="file-drop" for="admin-product-image">
                    <input id="admin-product-image" type="file" name="product_images[]" accept=".jpg,.jpeg,.png,.webp" multiple />
                    <div class="file-drop-icon"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i></div>
                    <div class="file-drop-text">
                      <strong>Klik untuk upload</strong> atau drag &amp; drop
                    </div>
                    <div class="file-drop-sub">Bisa pilih beberapa gambar. Untuk mengganti, hapus foto lama lalu upload foto baru</div>
                  </label>
                  <?php render_product_image_delete_controls($editingProduct); ?>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-product-short">Deskripsi Singkat <span class="req">*</span></label>
                  <textarea id="admin-product-short" name="short_description" required><?= e($editingProduct['short_description']) ?></textarea>
                </div>

                <div class="field-wrap">
                  <label class="field-label" for="admin-product-long">Deskripsi Panjang <span class="req">*</span></label>
                  <textarea id="admin-product-long" name="long_description" required><?= e($editingProduct['long_description']) ?></textarea>
                </div>

                <div class="admin-product-toggle-row">
                  <label><input type="checkbox" name="is_featured" value="1" <?= (int) $editingProduct['is_featured'] === 1 ? 'checked' : '' ?> /> Jadikan produk unggulan</label>
                  <label><input type="checkbox" name="is_active" value="1" <?= (int) $editingProduct['is_active'] === 1 ? 'checked' : '' ?> /> Aktif</label>
                </div>
              </div>

              <div class="submit-bar">
                <p class="submit-note">Perubahan akan langsung tampil pada katalog publik setelah disimpan.</p>
                <div class="admin-product-edit-actions">
                  <a class="filter-reset" href="<?= e(base_path($listingUrl)) ?>">Batal</a>
                  <button type="submit" class="btn-submit">
                    <span class="btn-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span>
                    Simpan Perubahan
                  </button>
                </div>
              </div>
            </form>
          </article>
        </section>
      </div>
      <script>
        (() => {
          const modal = document.getElementById('adminProductEditModal');
          if (!modal) return;
          document.body.classList.add('has-store-product-modal');
          modal.querySelector('input[name="name"]').focus();
          modal.addEventListener('click', (event) => {
            if (event.target === modal) window.location.href = modal.dataset.closeUrl;
          });
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') window.location.href = modal.dataset.closeUrl;
          });
          modal.querySelectorAll('[data-product-image-manager]').forEach((manager) => {
            const form = manager.closest('form');
            const removeInputs = Array.from(manager.querySelectorAll('[data-product-image-remove]'));
            const fileInput = form?.querySelector('input[type="file"][name="product_images[]"]');
            form?.addEventListener('submit', (event) => {
              const remaining = removeInputs.filter((input) => !input.checked).length;
              const hasNewUpload = fileInput && fileInput.files.length > 0;
              if (remaining < 1 && !hasNewUpload) {
                event.preventDefault();
                alert('Minimal satu foto produk harus tersisa.');
              }
            });
          });
        })();
      </script>
    <?php endif; ?>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
