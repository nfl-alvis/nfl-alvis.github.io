<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post() && ($_POST['action'] ?? '') === 'delete_store') {
    try {
        db()->prepare('UPDATE stores SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
            'id' => (int) ($_POST['id'] ?? 0),
        ]);
        set_flash('success', 'Toko berhasil dinonaktifkan.');
    } catch (Throwable $exception) {
        set_flash('error', 'Toko gagal dinonaktifkan.');
    }
    redirect_to('admin-stores.php');
}

$storeSearch = trim($_GET['store_search'] ?? '');
$storeSort = trim($_GET['store_sort'] ?? 'created_desc');
$storePage = max(1, (int) ($_GET['store_page'] ?? 1));
$storePerPage = max(1, (int) ($_GET['store_per_page'] ?? 8));

$stores = array_values(array_filter(all_stores_with_admins(), static function (array $item) use ($storeSearch): bool {
    if ($storeSearch === '') {
        return true;
    }

    $haystack = strtolower(($item['name'] ?? '') . ' ' . ($item['region'] ?? '') . ' ' . ($item['admins'] ?? ''));
    return strpos($haystack, strtolower($storeSearch)) !== false;
}));

usort($stores, static function (array $a, array $b) use ($storeSort): int {
    return match ($storeSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'name_desc' => strcmp((string) $b['name'], (string) $a['name']),
        default => strcmp((string) $b['created_at'], (string) $a['created_at']),
    };
});

$storesPage = paginate_array($stores, $storePage, $storePerPage);

render_layout('Manajemen Toko', function (?array $user = null) use ($storeSearch, $storeSort, $storePerPage, $storesPage): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'stores'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Manajemen Toko</div>
            <div class="topbar-sub">Kelola mitra toko, admin, dan jumlah produknya.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="management-layout">
          <article class="table-card management-table-card">
            <div class="table-card-head">
              <div>
                <h3>Daftar Toko</h3>
                <div class="table-meta">Menampilkan <?= e((string) count($storesPage['items'])) ?> dari <?= e((string) $storesPage['total']) ?> data</div>
              </div>
              <a class="action-button-link" href="<?= e(base_path('admin-store-create.php')) ?>"><i class="fa-solid fa-plus" aria-hidden="true"></i>Tambah Toko Baru</a>
            </div>

            <form class="form-panel management-filters" method="get">
              <label>Cari
                <input type="search" name="store_search" value="<?= e($storeSearch) ?>" placeholder="Nama toko, wilayah, admin" />
              </label>
              <label>Sorting
                <select name="store_sort">
                  <option value="created_desc" <?= $storeSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                  <option value="name_asc" <?= $storeSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                  <option value="name_desc" <?= $storeSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                </select>
              </label>
              <label>Baris
                <select name="store_per_page">
                  <?php foreach ([5, 10, 20, 50] as $amount): ?>
                    <option value="<?= e((string) $amount) ?>" <?= $storePerPage === $amount ? 'selected' : '' ?>><?= e((string) $amount) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Terapkan</button>
              <a class="filter-reset" href="<?= e(base_path('admin-stores.php')) ?>">Reset</a>
            </form>

            <div class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr><th>No</th><th>Toko</th><th>Wilayah</th><th>Admin</th><th>Produk</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($storesPage['items'] as $index => $item): ?>
                    <tr>
                      <td><?= e((string) ($storesPage['offset'] + $index + 1)) ?></td>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['region']) ?></td>
                      <td><?= e($item['admins'] ?: '-') ?></td>
                      <td><?= e((string) $item['product_count']) ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link" href="<?= e(base_path('admin-store-edit.php?id=' . $item['id'])) ?>">Edit</a>
                          <form method="post" onsubmit="return confirm('Nonaktifkan toko ini?')">
                            <input type="hidden" name="action" value="delete_store" />
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
              <span class="table-meta">Halaman <?= e((string) $storesPage['page']) ?> dari <?= e((string) $storesPage['total_pages']) ?></span>
              <div class="page-nav">
                <a class="page-btn" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => max(1, $storesPage['page'] - 1)])))) ?>">&larr;</a>
                <?php for ($p = max(1, $storesPage['page'] - 2); $p <= min($storesPage['total_pages'], $storesPage['page'] + 2); $p++): ?>
                  <a class="page-btn <?= $p === $storesPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="<?= e(base_path('admin-stores.php?' . http_build_query(array_merge($_GET, ['store_page' => min($storesPage['total_pages'], $storesPage['page'] + 1)])))) ?>">&rarr;</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
