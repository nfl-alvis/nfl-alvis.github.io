<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post() && ($_POST['action'] ?? '') === 'delete_product') {
    try {
        db()->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
        set_flash('success', 'Produk berhasil dinonaktifkan.');
    } catch (Throwable $exception) {
        set_flash('error', 'Produk gagal dinonaktifkan.');
    }
    redirect_to('admin-products.php');
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

render_layout('Manajemen Produk Platform', function (?array $user = null) use ($productSearch, $productSort, $productPerPage, $productsPage): void {
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
                          <a class="inline-link" href="<?= e(base_path('admin-product-edit.php?id=' . $item['id'])) ?>">Edit</a>
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
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
