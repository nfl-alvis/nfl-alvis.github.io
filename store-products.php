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

$listingQuery = $_GET;
unset($listingQuery['edit']);
$listingPath = 'store-products.php' . ($listingQuery ? '?' . http_build_query($listingQuery) : '');
$listingUrl = base_path($listingPath);

$editId = (int) ($_GET['edit'] ?? 0);
$editingProduct = null;

if ($editId > 0) {
    $editStmt = db()->prepare('SELECT * FROM products WHERE id = :id AND store_id = :store_id AND is_active = 1 LIMIT 1');
    $editStmt->execute(['id' => $editId, 'store_id' => $storeId]);
    $editingProduct = $editStmt->fetch() ?: null;
}

if (is_post()) {
    if (($_POST['action'] ?? '') === 'delete_product') {
        $returnQuery = array_filter([
            'product_search' => trim($_POST['product_search'] ?? ''),
            'product_type' => trim($_POST['product_type'] ?? ''),
            'product_sort' => trim($_POST['product_sort'] ?? ''),
            'product_page' => max(1, (int) ($_POST['product_page'] ?? 1)),
            'product_per_page' => max(1, (int) ($_POST['product_per_page'] ?? 5)),
        ], static fn (mixed $value): bool => $value !== '');

        try {
            $deleteStmt = db()->prepare(
                'UPDATE products
                 SET is_active = 0, updated_at = NOW()
                 WHERE id = :id AND store_id = :store_id AND is_active = 1'
            );
            $deleteStmt->execute([
                'id' => (int) ($_POST['product_id'] ?? 0),
                'store_id' => $storeId,
            ]);
            set_flash(
                $deleteStmt->rowCount() > 0 ? 'success' : 'error',
                $deleteStmt->rowCount() > 0 ? 'Produk berhasil dihapus.' : 'Produk tidak ditemukan atau sudah dihapus.'
            );
        } catch (Throwable $exception) {
            set_flash('error', 'Produk gagal dihapus.');
        }

        redirect_to('store-products.php' . ($returnQuery ? '?' . http_build_query($returnQuery) : ''));
    }

    if (($_POST['action'] ?? '') === 'edit_product') {
        try {
            $productId = (int) ($_POST['product_id'] ?? 0);
            $currentImage = trim($_POST['current_image'] ?? '');
            $uploadedImages = $_FILES['product_images'] ?? ($_FILES['product_image'] ?? []);
            $hasNewImages = product_upload_entries($uploadedImages) !== [];
            $imagePaths = save_uploaded_product_images($uploadedImages, $currentImage);
            $imagePath = $imagePaths[0] ?? $currentImage;

            if ($hasNewImages) {
                ensure_product_images_table();
            }

            db()->beginTransaction();

            $stmt = db()->prepare(
                'UPDATE products
                 SET name = :name,
                     type = :type,
                     region = :region,
                     short_description = :short_description,
                     long_description = :long_description,
                     price_display = :price_display,
                     tag_label = :tag_label,
                     image_path = :image_path,
                     updated_at = NOW()
                 WHERE id = :id AND store_id = :store_id AND is_active = 1'
            );
            $stmt->execute([
                'name' => trim($_POST['name'] ?? ''),
                'type' => trim($_POST['type'] ?? 'Makanan'),
                'region' => trim($_POST['region'] ?? ''),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'long_description' => trim($_POST['long_description'] ?? ''),
                'price_display' => normalize_price_display($_POST['price_display'] ?? ''),
                'tag_label' => trim($_POST['tag_label'] ?? ''),
                'image_path' => $imagePath,
                'id' => $productId,
                'store_id' => $storeId,
            ]);

            if ($hasNewImages) {
                replace_product_images($productId, $imagePaths);
            }

            db()->commit();

            set_flash('success', 'Produk berhasil diperbarui.');
            redirect_to($listingPath);
        } catch (Throwable $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }

            set_flash('error', $exception->getMessage());
            $productId = (int) ($_POST['product_id'] ?? 0);
            redirect_to('store-products.php?' . http_build_query(array_merge($listingQuery, ['edit' => $productId])));
        }
    }

    redirect_to($listingPath);
}

$products = array_values(array_filter(
    find_store_products($storeId),
    static fn (array $product): bool => (int) ($product['is_active'] ?? 0) === 1
));

$activeProducts = array_values(array_filter($products, static fn (array $product): bool => (int) ($product['is_active'] ?? 0) === 1));
$totalViews = array_sum(array_map(static fn (array $product): int => (int) ($product['total_views'] ?? 0), $products));
$ratedProducts = array_values(array_filter($products, static fn (array $product): bool => (float) ($product['rating'] ?? 0) > 0));
$averageRating = $ratedProducts
    ? array_sum(array_map(static fn (array $product): float => (float) $product['rating'], $ratedProducts)) / count($ratedProducts)
    : 0.0;
$foodCount = count(array_filter($activeProducts, static fn (array $product): bool => ($product['type'] ?? '') === 'Makanan'));

$productSearch = trim($_GET['product_search'] ?? '');
$productType = trim($_GET['product_type'] ?? '');
$productSort = trim($_GET['product_sort'] ?? 'views_desc');
$productPage = max(1, (int) ($_GET['product_page'] ?? 1));
$productPerPage = max(1, (int) ($_GET['product_per_page'] ?? 5));

$filteredProducts = array_values(array_filter($products, static function (array $product) use ($productSearch, $productType): bool {
    if ($productType !== '' && ($product['type'] ?? '') !== $productType) {
        return false;
    }

    if ($productSearch === '') {
        return true;
    }

    $haystack = strtolower(($product['name'] ?? '') . ' ' . ($product['region'] ?? '') . ' ' . ($product['tag_label'] ?? ''));

    return strpos($haystack, strtolower($productSearch)) !== false;
}));

usort($filteredProducts, static function (array $a, array $b) use ($productSort): int {
    return match ($productSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'latest' => strcmp((string) $b['created_at'], (string) $a['created_at']),
        default => (int) $b['total_views'] <=> (int) $a['total_views'],
    };
});

$productsPage = paginate_array($filteredProducts, $productPage, $productPerPage);

render_layout('Produk Saya', function (?array $currentUser = null) use (
    $user,
    $store,
    $activeProducts,
    $totalViews,
    $averageRating,
    $foodCount,
    $productsPage,
    $editingProduct,
    $productSearch,
    $productType,
    $productSort,
    $productPerPage,
    $listingQuery,
    $listingUrl
): void {
    ?>
    <div class="shell">
      <?php render_store_sidebar($user, $store, 'products'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Produk Saya</div>
            <div class="topbar-sub">Kelola produk yang sudah dimiliki toko Anda.</div>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> &bull; Store Admin</div>
        </div>

        <section class="stats-grid store-product-stats" aria-label="Ringkasan produk toko">
          <article class="stat-box">
            <p>Produk Tersedia</p>
            <h3><?= e((string) count($activeProducts)) ?></h3>
          </article>
          <article class="stat-box">
            <p>Total Views</p>
            <h3><?= e(number_short($totalViews)) ?></h3>
          </article>
          <article class="stat-box">
            <p>Rating Rata-rata</p>
            <h3><?= e(number_format($averageRating, 1)) ?></h3>
          </article>
          <article class="stat-box">
            <p>Menu Makanan</p>
            <h3><?= e((string) $foodCount) ?></h3>
          </article>
        </section>

        <section class="management-layout" aria-label="Manajemen produk toko">
          <article class="table-card management-table-card store-product-table-card">
            <div class="table-card-head">
              <div>
                <h3>Daftar Produk</h3>
                <div class="table-meta">Menampilkan <?= e((string) count($productsPage['items'])) ?> dari <?= e((string) $productsPage['total']) ?> produk milik <?= e($store['name']) ?></div>
              </div>
              <a class="action-button-link" href="<?= e(base_path('store-add-product.php')) ?>">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Tambah Produk
              </a>
            </div>

            <form class="form-panel management-filters store-product-filters" method="get">
              <label>Cari Produk
                <input type="search" name="product_search" value="<?= e($productSearch) ?>" placeholder="Nama, daerah, atau tag" />
              </label>
              <label>Kategori
                <select name="product_type">
                  <option value="">Semua</option>
                  <option value="Makanan" <?= $productType === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                  <option value="Minuman" <?= $productType === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                </select>
              </label>
              <label>Urutkan
                <select name="product_sort">
                  <option value="views_desc" <?= $productSort === 'views_desc' ? 'selected' : '' ?>>Paling dilihat</option>
                  <option value="latest" <?= $productSort === 'latest' ? 'selected' : '' ?>>Terbaru</option>
                  <option value="name_asc" <?= $productSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                </select>
              </label>
              <label>Baris
                <select name="product_per_page">
                  <?php foreach ([5, 10, 20] as $amount): ?>
                    <option value="<?= e((string) $amount) ?>" <?= $productPerPage === $amount ? 'selected' : '' ?>><?= e((string) $amount) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Terapkan</button>
              <a class="filter-reset" href="<?= e(base_path('store-products.php')) ?>">Reset</a>
            </form>

            <div class="table-scroll">
              <table class="data-table store-product-table">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Rating</th>
                    <th>Views</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($productsPage['items'] as $index => $product): ?>
                    <tr>
                      <td><?= e((string) ($productsPage['offset'] + $index + 1)) ?></td>
                      <td>
                        <div class="store-product-cell">
                          <img src="<?= e(base_path($product['image_path'])) ?>" alt="" />
                          <div>
                            <strong><?= e($product['name']) ?></strong>
                            <span><?= e($product['region']) ?></span>
                          </div>
                        </div>
                      </td>
                      <td><span class="store-product-category"><?= e($product['type']) ?></span></td>
                      <td><?= e(rupiah($product['price_display'])) ?></td>
                      <td><span class="store-product-rating"><i class="fa-solid fa-star" aria-hidden="true"></i><?= e(number_format((float) $product['rating'], 1)) ?></span></td>
                      <td><?= e(number_short((int) $product['total_views'])) ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link product-edit-button" href="<?= e(base_path('store-products.php?' . http_build_query(array_merge($_GET, ['edit' => $product['id']])))) ?>">
                            <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                            Edit
                          </a>
                          <form method="post" onsubmit="return confirm('Hapus produk ini dari katalog toko?')">
                            <input type="hidden" name="action" value="delete_product" />
                            <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>" />
                            <input type="hidden" name="product_search" value="<?= e($productSearch) ?>" />
                            <input type="hidden" name="product_type" value="<?= e($productType) ?>" />
                            <input type="hidden" name="product_sort" value="<?= e($productSort) ?>" />
                            <input type="hidden" name="product_page" value="<?= e((string) $productsPage['page']) ?>" />
                            <input type="hidden" name="product_per_page" value="<?= e((string) $productPerPage) ?>" />
                            <button type="submit" class="inline-link product-delete-button" aria-label="Hapus <?= e($product['name']) ?>">
                              <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                              Hapus
                            </button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$productsPage['items']): ?>
                    <tr>
                      <td class="store-product-empty" colspan="7">Tidak ada produk yang sesuai dengan pencarian.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <div class="table-pagination">
              <span class="table-meta">Halaman <?= e((string) $productsPage['page']) ?> dari <?= e((string) $productsPage['total_pages']) ?></span>
              <div class="page-nav" aria-label="Navigasi halaman produk">
                <a class="page-btn" href="<?= e(base_path('store-products.php?' . http_build_query(array_merge($listingQuery, ['product_page' => max(1, $productsPage['page'] - 1)])))) ?>" aria-label="Halaman sebelumnya">&larr;</a>
                <?php for ($page = max(1, $productsPage['page'] - 2); $page <= min($productsPage['total_pages'], $productsPage['page'] + 2); $page++): ?>
                  <a class="page-btn <?= $page === $productsPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('store-products.php?' . http_build_query(array_merge($listingQuery, ['product_page' => $page])))) ?>"><?= e((string) $page) ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="<?= e(base_path('store-products.php?' . http_build_query(array_merge($listingQuery, ['product_page' => min($productsPage['total_pages'], $productsPage['page'] + 1)])))) ?>" aria-label="Halaman berikutnya">&rarr;</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>

    <?php if ($editingProduct): ?>
      <div class="store-product-modal-backdrop" id="editProductModal" data-close-url="<?= e($listingUrl) ?>">
        <section class="store-product-modal" role="dialog" aria-modal="true" aria-labelledby="editProductTitle">
          <div class="store-product-modal-head">
            <div>
              <h2 id="editProductTitle">Edit Produk</h2>
              <p>Perbarui detail <?= e($editingProduct['name']) ?>.</p>
            </div>
            <a class="store-product-modal-close" href="<?= e($listingUrl) ?>" aria-label="Tutup form edit">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </a>
          </div>

          <form method="post" enctype="multipart/form-data" class="form-panel store-product-modal-form">
            <input type="hidden" name="action" value="edit_product" />
            <input type="hidden" name="product_id" value="<?= e((string) $editingProduct['id']) ?>" />
            <input type="hidden" name="current_image" value="<?= e($editingProduct['image_path']) ?>" />
            <div class="form-grid-2">
              <label>Nama Produk <input type="text" name="name" value="<?= e($editingProduct['name']) ?>" required /></label>
              <label>Kategori
                <select name="type">
                  <option value="Makanan" <?= $editingProduct['type'] === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                  <option value="Minuman" <?= $editingProduct['type'] === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                </select>
              </label>
              <label>Daerah
                <select name="region" required>
                  <?php render_province_options($editingProduct['region'] ?? ''); ?>
                </select>
              </label>
              <label>Harga Tampilan <input type="text" name="price_display" value="<?= e($editingProduct['price_display']) ?>" inputmode="numeric" autocomplete="off" data-price-format required /></label>
              <label>Tag <input type="text" name="tag_label" value="<?= e($editingProduct['tag_label']) ?>" required /></label>
              <label>Ganti Gambar <input type="file" name="product_images[]" accept=".jpg,.jpeg,.png,.webp" multiple /></label>
            </div>
            <label>Deskripsi Singkat <textarea name="short_description" required><?= e($editingProduct['short_description']) ?></textarea></label>
            <label>Deskripsi Panjang <textarea name="long_description" required><?= e($editingProduct['long_description']) ?></textarea></label>
            <div class="store-product-modal-actions">
              <a class="filter-reset" href="<?= e($listingUrl) ?>">Batal</a>
              <button type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i>Simpan Perubahan</button>
            </div>
          </form>
        </section>
      </div>
      <script>
        (() => {
          const modal = document.getElementById('editProductModal');
          if (!modal) return;
          document.body.classList.add('has-store-product-modal');
          modal.querySelector('input[name="name"]').focus();
          modal.addEventListener('click', (event) => {
            if (event.target === modal) window.location.href = modal.dataset.closeUrl;
          });
          document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') window.location.href = modal.dataset.closeUrl;
          });
        })();
      </script>
    <?php endif; ?>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
