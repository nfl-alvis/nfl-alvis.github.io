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
            'price_display' => trim($_POST['price_display'] ?? ''),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => trim($_POST['image_path'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        set_flash('success', 'Produk berhasil diperbarui.');
        redirect_to($listingUrl);
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui produk.');
        redirect_to('admin-products.php?' . http_build_query(array_merge($listingQuery, ['edit' => $productId])));
    }
}

if (is_post() && ($_POST['action'] ?? '') === 'delete_product') {
    try {
        db()->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
        set_flash('success', 'Produk berhasil dinonaktifkan.');
    } catch (Throwable $exception) {
        set_flash('error', 'Produk gagal dinonaktifkan.');
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
              <table class="data-table">
                <thead>
                  <tr><th>No</th><th>Produk</th><th>Toko</th><th>Kategori</th><th>Harga</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($productsPage['items'] as $index => $item): ?>
                    <tr>
                      <td><?= e((string) ($productsPage['offset'] + $index + 1)) ?></td>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['store_name']) ?></td>
                      <td><?= e($item['type']) ?></td>
                      <td><?= e(rupiah($item['price_display'])) ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link product-edit-button" href="<?= e(base_path('admin-products.php?' . http_build_query(array_merge($_GET, ['edit' => $item['id']])))) ?>">Edit</a>
                          <form method="post" onsubmit="return confirm('Nonaktifkan produk ini?')">
                            <input type="hidden" name="action" value="delete_product" />
                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>" />
                            <button type="submit" class="inline-link">Hapus</button>
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
        <section class="store-product-modal" role="dialog" aria-modal="true" aria-labelledby="adminProductEditTitle">
          <div class="store-product-modal-head">
            <div>
              <h2 id="adminProductEditTitle">Edit Produk</h2>
              <p>Perbarui detail <?= e($editingProduct['name']) ?> tanpa keluar dari daftar produk.</p>
            </div>
            <a class="store-product-modal-close" href="<?= e(base_path($listingUrl)) ?>" aria-label="Tutup form edit">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </a>
          </div>

          <form method="post" class="form-panel store-product-modal-form" action="<?= e(base_path($listingUrl)) ?>">
            <input type="hidden" name="action" value="edit_product" />
            <input type="hidden" name="id" value="<?= e((string) $editingProduct['id']) ?>" />
            <div class="form-grid-2">
              <label>Nama Produk <input type="text" name="name" value="<?= e($editingProduct['name']) ?>" required /></label>
              <label>Toko
                <select name="store_id" required>
                  <?php foreach ($stores as $store): ?>
                    <option value="<?= e((string) $store['id']) ?>" <?= (int) $editingProduct['store_id'] === (int) $store['id'] ? 'selected' : '' ?>>
                      <?= e($store['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Kategori
                <select name="type" required>
                  <option value="Makanan" <?= $editingProduct['type'] === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                  <option value="Minuman" <?= $editingProduct['type'] === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                </select>
              </label>
              <label>Wilayah
                <select name="region" required>
                  <?php render_province_options($editingProduct['region'] ?? ''); ?>
                </select>
              </label>
              <label>Harga Tampilan <input type="text" name="price_display" value="<?= e($editingProduct['price_display']) ?>" required /></label>
              <label>Tag <input type="text" name="tag_label" value="<?= e($editingProduct['tag_label']) ?>" required /></label>
              <label>Path Gambar <input type="text" name="image_path" value="<?= e($editingProduct['image_path']) ?>" required /></label>
            </div>
            <label>Deskripsi Singkat <textarea name="short_description" required><?= e($editingProduct['short_description']) ?></textarea></label>
            <label>Deskripsi Panjang <textarea name="long_description" required><?= e($editingProduct['long_description']) ?></textarea></label>
            <label><input type="checkbox" name="is_featured" value="1" <?= (int) $editingProduct['is_featured'] === 1 ? 'checked' : '' ?> /> Jadikan produk unggulan</label>
            <label><input type="checkbox" name="is_active" value="1" <?= (int) $editingProduct['is_active'] === 1 ? 'checked' : '' ?> /> Aktif</label>
            <div class="store-product-modal-actions">
              <a class="filter-reset" href="<?= e(base_path($listingUrl)) ?>">Batal</a>
              <button type="submit"><i class="fa-solid fa-check" aria-hidden="true"></i>Simpan Perubahan</button>
            </div>
          </form>
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
        })();
      </script>
    <?php endif; ?>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
