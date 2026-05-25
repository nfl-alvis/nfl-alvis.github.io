<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post()) {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'delete_store') {
            db()->prepare('UPDATE stores SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
                'id' => (int) ($_POST['id'] ?? 0),
            ]);
            set_flash('success', 'Toko berhasil dinonaktifkan.');
            redirect_to('admin-dashboard.php');
        }

        if ($action === 'delete_user') {
            db()->prepare('UPDATE users SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
                'id' => (int) ($_POST['id'] ?? 0),
            ]);
            set_flash('success', 'Pengguna berhasil dinonaktifkan.');
            redirect_to('admin-dashboard.php');
        }

        if ($action === 'delete_product') {
            db()->prepare('UPDATE products SET is_active = 0, updated_at = NOW() WHERE id = :id')->execute([
                'id' => (int) ($_POST['id'] ?? 0),
            ]);
            set_flash('success', 'Produk berhasil dinonaktifkan.');
            redirect_to('admin-dashboard.php');
        }

        if ($action === 'create_store') {
            $name = trim($_POST['name'] ?? '');
            $stmt = db()->prepare(
                'INSERT INTO stores
                 (name, slug, region, address, whatsapp, instagram, description, cover_image, is_active, created_at, updated_at)
                 VALUES
                 (:name, :slug, :region, :address, :whatsapp, :instagram, :description, :cover_image, 1, NOW(), NOW())'
            );
            $stmt->execute([
                'name' => $name,
                'slug' => slugify($name . '-' . substr((string) time(), -4)),
                'region' => trim($_POST['region'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
                'instagram' => trim($_POST['instagram'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'cover_image' => trim($_POST['cover_image'] ?? 'assets/image/image.png'),
            ]);
            set_flash('success', 'Toko baru berhasil dibuat.');
            redirect_to('admin-dashboard.php');
        }

        if ($action === 'create_store_admin') {
            create_user(
                trim($_POST['name'] ?? ''),
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? '',
                ROLE_STORE_ADMIN,
                (int) ($_POST['store_id'] ?? 0)
            );
            set_flash('success', 'Store admin berhasil dibuat.');
            redirect_to('admin-dashboard.php');
        }
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek kemungkinan email, slug, atau input duplikat.');
        redirect_to('admin-dashboard.php');
    }
}

$stats = super_admin_stats();

$userSearch = trim($_GET['user_search'] ?? '');
$userRole = trim($_GET['user_role'] ?? '');
$userSort = trim($_GET['user_sort'] ?? 'created_desc');
$userPage = max(1, (int) ($_GET['user_page'] ?? 1));
$userPerPage = max(1, (int) ($_GET['user_per_page'] ?? 8));

$storeSearch = trim($_GET['store_search'] ?? '');
$storeSort = trim($_GET['store_sort'] ?? 'created_desc');
$storePage = max(1, (int) ($_GET['store_page'] ?? 1));
$storePerPage = max(1, (int) ($_GET['store_per_page'] ?? 8));

$productSearch = trim($_GET['product_search'] ?? '');
$productSort = trim($_GET['product_sort'] ?? 'created_desc');
$productPage = max(1, (int) ($_GET['product_page'] ?? 1));
$productPerPage = max(1, (int) ($_GET['product_per_page'] ?? 8));

$users = array_values(array_filter(all_users(), static function (array $item) use ($userSearch, $userRole): bool {
    if ($userSearch !== '') {
        $haystack = strtolower(($item['name'] ?? '') . ' ' . ($item['email'] ?? '') . ' ' . ($item['store_name'] ?? ''));
        if (strpos($haystack, strtolower($userSearch)) === false) {
            return false;
        }
    }

    if ($userRole !== '' && ($item['role'] ?? '') !== $userRole) {
        return false;
    }

    return true;
}));

usort($users, static function (array $a, array $b) use ($userSort): int {
    return match ($userSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'name_desc' => strcmp((string) $b['name'], (string) $a['name']),
        default => strcmp((string) $b['created_at'], (string) $a['created_at']),
    };
});

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

$usersPage = paginate_array($users, $userPage, $userPerPage);
$storesPage = paginate_array($stores, $storePage, $storePerPage);
$productsPage = paginate_array($products, $productPage, $productPerPage);

render_layout('Dashboard Super Admin', function (?array $user = null) use (
    $stats,
    $userSearch, $userRole, $userSort, $userPage, $userPerPage, $usersPage,
    $storeSearch, $storeSort, $storePage, $storePerPage, $storesPage,
    $productSearch, $productSort, $productPage, $productPerPage, $productsPage
): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    $userEmail = (string) ($user['email'] ?? 'admin@pusaka.id');
    ?>
    <div class="shell">
      <aside class="sidebar">
        <div class="sidebar-brand">
          <div class="sidebar-brand-name">PusakaRasa</div>
          <div class="sidebar-brand-role">Super Admin Dashboard</div>
        </div>

        <nav class="sidebar-nav">
          <div class="nav-label">Menu Utama</div>
          <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="nav-link active">
            <span class="nav-link-icon">🏠</span>
            Dashboard
          </a>
          <a href="<?= e(base_path('admin-store-create.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🏪</span>
            Tambah Toko
          </a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="nav-link">
            <span class="nav-link-icon">👤</span>
            Buat Store Admin
          </a>

          <div class="nav-divider"></div>
          <div class="nav-label">Platform</div>

          <a href="<?= e(base_path('index.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🌐</span>
            Beranda
          </a>
          <a href="<?= e(base_path('katalog.php')) ?>" class="nav-link">
            <span class="nav-link-icon">📦</span>
            Katalog
          </a>

          <div class="nav-divider"></div>

          <a href="<?= e(base_path('logout.php')) ?>" class="nav-link" style="margin-top:auto;color:#c0645a;">
            <span class="nav-link-icon" style="font-size:14px">🚪</span>
            Keluar
          </a>
        </nav>

        <div class="sidebar-footer">
          <div class="sidebar-user">
            <div class="sidebar-avatar"><?= e(strtoupper(substr($userName, 0, 2))) ?></div>
            <div>
              <div class="sidebar-user-name"><?= e($userName) ?></div>
              <div class="sidebar-user-role"><?= e($userEmail) ?></div>
            </div>
          </div>
        </div>
      </aside>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Kontrol Platform</div>
            <div class="topbar-sub">Kelola pengguna, toko, dan produk dari satu tempat.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> • Super Admin</div>
        </div>

        <section class="stats-grid">
          <article class="stat-box"><p>Total pengguna</p><h3><?= e(number_short($stats['users'])) ?></h3></article>
          <article class="stat-box"><p>Total toko</p><h3><?= e(number_short($stats['stores'])) ?></h3></article>
          <article class="stat-box"><p>Total produk</p><h3><?= e(number_short($stats['products'])) ?></h3></article>
        </section>

        <section class="dashboard-grid">
          <div class="stacked-card">
            <article class="table-card">
              <h3>Pengguna</h3>
              <form class="form-panel" method="get" style="margin-top: 12px; display: grid; gap: 12px;">
                <label>Cari <input type="search" name="user_search" value="<?= e($userSearch) ?>" placeholder="Nama, email, atau toko" /></label>
                <label>Role
                  <select name="user_role">
                    <option value="">Semua</option>
                    <option value="user" <?= $userRole === 'user' ? 'selected' : '' ?>>User</option>
                    <option value="store_admin" <?= $userRole === 'store_admin' ? 'selected' : '' ?>>Store Admin</option>
                    <option value="super_admin" <?= $userRole === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                  </select>
                </label>
                <label>Sorting
                  <select name="user_sort">
                    <option value="created_desc" <?= $userSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="name_asc" <?= $userSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                    <option value="name_desc" <?= $userSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                  </select>
                </label>
                <label>Baris per halaman
                  <select name="user_per_page">
                    <option value="5" <?= $userPerPage === 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $userPerPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= $userPerPage === 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= $userPerPage === 50 ? 'selected' : '' ?>>50</option>
                  </select>
                </label>
                <button type="submit">Terapkan</button>
              </form>
              <div class="table-meta" style="margin-top: 12px;">Menampilkan <?= e((string) count($usersPage['items'])) ?> dari <?= e((string) $usersPage['total']) ?> data</div>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Toko</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($usersPage['items'] as $index => $item): ?>
                    <tr>
                      <td><?= e((string) ($usersPage['offset'] + $index + 1)) ?></td>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['email']) ?></td>
                      <td><span class="pill-role"><?= e($item['role']) ?></span></td>
                      <td><?= e($item['store_name'] ?: '-') ?></td>
                      <td>
                        <div class="table-actions">
                          <a class="inline-link" href="<?= e(base_path('admin-user-edit.php?id=' . $item['id'])) ?>">Edit</a>
                          <form method="post" style="display:inline;" onsubmit="return confirm('Nonaktifkan pengguna ini?')">
                            <input type="hidden" name="action" value="delete_user" />
                            <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>" />
                            <button type="submit" class="inline-link">Hapus</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <div class="table-pagination">
                <div class="pagination-controls">
                  <label>Halaman
                    <span class="table-meta"><?= e((string) $usersPage['page']) ?> dari <?= e((string) $usersPage['total_pages']) ?></span>
                  </label>
                </div>
                <div class="page-nav">
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['user_page' => max(1, $usersPage['page'] - 1)])))) ?>">←</a>
                  <?php for ($p = max(1, $usersPage['page'] - 2); $p <= min($usersPage['total_pages'], $usersPage['page'] + 2); $p++): ?>
                    <a class="page-btn <?= $p === $usersPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['user_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                  <?php endfor; ?>
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['user_page' => min($usersPage['total_pages'], $usersPage['page'] + 1)])))) ?>">→</a>
                </div>
              </div>
            </article>

            <article class="table-card">
              <h3>Daftar Toko</h3>
              <form class="form-panel" method="get" style="margin-top: 12px; display: grid; gap: 12px;">
                <label>Cari <input type="search" name="store_search" value="<?= e($storeSearch) ?>" placeholder="Nama toko, wilayah, admin" /></label>
                <label>Sorting
                  <select name="store_sort">
                    <option value="created_desc" <?= $storeSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="name_asc" <?= $storeSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                    <option value="name_desc" <?= $storeSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                  </select>
                </label>
                <label>Baris per halaman
                  <select name="store_per_page">
                    <option value="5" <?= $storePerPage === 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $storePerPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= $storePerPage === 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= $storePerPage === 50 ? 'selected' : '' ?>>50</option>
                  </select>
                </label>
                <button type="submit">Terapkan</button>
              </form>
              <div class="table-meta" style="margin-top: 12px;">Menampilkan <?= e((string) count($storesPage['items'])) ?> dari <?= e((string) $storesPage['total']) ?> data</div>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Toko</th>
                    <th>Wilayah</th>
                    <th>Admin</th>
                    <th>Produk</th>
                    <th>Aksi</th>
                  </tr>
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
                          <form method="post" style="display:inline;" onsubmit="return confirm('Nonaktifkan toko ini?')">
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
              <div class="table-pagination">
                <div class="pagination-controls">
                  <label>Halaman
                    <span class="table-meta"><?= e((string) $storesPage['page']) ?> dari <?= e((string) $storesPage['total_pages']) ?></span>
                  </label>
                </div>
                <div class="page-nav">
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['store_page' => max(1, $storesPage['page'] - 1)])))) ?>">←</a>
                  <?php for ($p = max(1, $storesPage['page'] - 2); $p <= min($storesPage['total_pages'], $storesPage['page'] + 2); $p++): ?>
                    <a class="page-btn <?= $p === $storesPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['store_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                  <?php endfor; ?>
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['store_page' => min($storesPage['total_pages'], $storesPage['page'] + 1)])))) ?>">→</a>
                </div>
              </div>
            </article>

            <article class="table-card">
              <h3>Produk Platform</h3>
              <form class="form-panel" method="get" style="margin-top: 12px; display: grid; gap: 12px;">
                <label>Cari <input type="search" name="product_search" value="<?= e($productSearch) ?>" placeholder="Nama produk, toko, kategori" /></label>
                <label>Sorting
                  <select name="product_sort">
                    <option value="created_desc" <?= $productSort === 'created_desc' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="name_asc" <?= $productSort === 'name_asc' ? 'selected' : '' ?>>Nama A-Z</option>
                    <option value="name_desc" <?= $productSort === 'name_desc' ? 'selected' : '' ?>>Nama Z-A</option>
                  </select>
                </label>
                <label>Baris per halaman
                  <select name="product_per_page">
                    <option value="5" <?= $productPerPage === 5 ? 'selected' : '' ?>>5</option>
                    <option value="10" <?= $productPerPage === 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= $productPerPage === 20 ? 'selected' : '' ?>>20</option>
                    <option value="50" <?= $productPerPage === 50 ? 'selected' : '' ?>>50</option>
                  </select>
                </label>
                <button type="submit">Terapkan</button>
              </form>
              <div class="table-meta" style="margin-top: 12px;">Menampilkan <?= e((string) count($productsPage['items'])) ?> dari <?= e((string) $productsPage['total']) ?> data</div>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Toko</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                  </tr>
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
                          <form method="post" style="display:inline;" onsubmit="return confirm('Nonaktifkan produk ini?')">
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
              <div class="table-pagination">
                <div class="pagination-controls">
                  <label>Halaman
                    <span class="table-meta"><?= e((string) $productsPage['page']) ?> dari <?= e((string) $productsPage['total_pages']) ?></span>
                  </label>
                </div>
                <div class="page-nav">
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['product_page' => max(1, $productsPage['page'] - 1)])))) ?>">←</a>
                  <?php for ($p = max(1, $productsPage['page'] - 2); $p <= min($productsPage['total_pages'], $productsPage['page'] + 2); $p++): ?>
                    <a class="page-btn <?= $p === $productsPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['product_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                  <?php endfor; ?>
                  <a class="page-btn" href="<?= e(base_path('admin-dashboard.php?' . http_build_query(array_merge($_GET, ['product_page' => min($productsPage['total_pages'], $productsPage['page'] + 1)])))) ?>">→</a>
                </div>
              </div>
            </article>
          </div>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
