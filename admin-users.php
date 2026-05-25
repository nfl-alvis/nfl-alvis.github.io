<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post() && ($_POST['action'] ?? '') === 'delete_user') {
    $userId = (int) ($_POST['id'] ?? 0);
    $currentAdmin = current_user();

    if ($userId < 1) {
        set_flash('error', 'Pengguna tidak valid.');
        redirect_to('admin-users.php');
    }

    if ((int) ($currentAdmin['id'] ?? 0) === $userId) {
        set_flash('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        redirect_to('admin-users.php');
    }

    $connection = db();

    try {
        $connection->beginTransaction();

        $reviewsStmt = $connection->prepare('SELECT DISTINCT product_id FROM reviews WHERE user_id = :id');
        $reviewsStmt->execute(['id' => $userId]);
        $reviewedProductIds = array_map('intval', $reviewsStmt->fetchAll(PDO::FETCH_COLUMN));

        $deleteStmt = $connection->prepare('DELETE FROM users WHERE id = :id');
        $deleteStmt->execute(['id' => $userId]);

        if ($deleteStmt->rowCount() !== 1) {
            throw new RuntimeException('Pengguna tidak ditemukan.');
        }

        foreach ($reviewedProductIds as $productId) {
            recalculate_product_rating($productId);
        }

        $connection->commit();
        set_flash('success', 'Pengguna berhasil dihapus.');
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        set_flash('error', 'Pengguna gagal dihapus.');
    }
    redirect_to('admin-users.php');
}

$userSearch = trim($_GET['user_search'] ?? '');
$userRole = trim($_GET['user_role'] ?? '');
$userSort = trim($_GET['user_sort'] ?? 'created_desc');
$userPage = max(1, (int) ($_GET['user_page'] ?? 1));
$userPerPage = max(1, (int) ($_GET['user_per_page'] ?? 8));

$users = array_values(array_filter(all_users(), static function (array $item) use ($userSearch, $userRole): bool {
    if ($userSearch !== '') {
        $haystack = strtolower(($item['name'] ?? '') . ' ' . ($item['email'] ?? '') . ' ' . ($item['store_name'] ?? ''));
        if (strpos($haystack, strtolower($userSearch)) === false) {
            return false;
        }
    }

    return $userRole === '' || ($item['role'] ?? '') === $userRole;
}));

usort($users, static function (array $a, array $b) use ($userSort): int {
    return match ($userSort) {
        'name_asc' => strcmp((string) $a['name'], (string) $b['name']),
        'name_desc' => strcmp((string) $b['name'], (string) $a['name']),
        default => strcmp((string) $b['created_at'], (string) $a['created_at']),
    };
});

$usersPage = paginate_array($users, $userPage, $userPerPage);

render_layout('Manajemen Pengguna', function (?array $user = null) use ($userSearch, $userRole, $userSort, $userPerPage, $usersPage): void {
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'users'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Manajemen Pengguna</div>
            <div class="topbar-sub">Kelola akun, role, dan akses pengguna platform.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="management-layout">
          <article class="table-card management-table-card">
            <div class="table-card-head">
              <div>
                <h3>Daftar Pengguna</h3>
                <div class="table-meta">Menampilkan <?= e((string) count($usersPage['items'])) ?> dari <?= e((string) $usersPage['total']) ?> data</div>
              </div>
            </div>

            <form class="form-panel management-filters management-filters-users" method="get">
              <label>Cari
                <input type="search" name="user_search" value="<?= e($userSearch) ?>" placeholder="Nama, email, atau toko" />
              </label>
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
              <label>Baris
                <select name="user_per_page">
                  <?php foreach ([5, 10, 20, 50] as $amount): ?>
                    <option value="<?= e((string) $amount) ?>" <?= $userPerPage === $amount ? 'selected' : '' ?>><?= e((string) $amount) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Terapkan</button>
              <a class="filter-reset" href="<?= e(base_path('admin-users.php')) ?>">Reset</a>
            </form>

            <div class="table-scroll">
              <table class="data-table">
                <thead>
                  <tr><th>No</th><th>Nama</th><th>Email</th><th>Role</th><th>Toko</th><th>Aksi</th></tr>
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
                          <form method="post" onsubmit="return confirm('Hapus pengguna ini secara permanen?')">
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
            </div>

            <div class="table-pagination">
              <span class="table-meta">Halaman <?= e((string) $usersPage['page']) ?> dari <?= e((string) $usersPage['total_pages']) ?></span>
              <div class="page-nav">
                <a class="page-btn" href="<?= e(base_path('admin-users.php?' . http_build_query(array_merge($_GET, ['user_page' => max(1, $usersPage['page'] - 1)])))) ?>">&larr;</a>
                <?php for ($p = max(1, $usersPage['page'] - 2); $p <= min($usersPage['total_pages'], $usersPage['page'] + 2); $p++): ?>
                  <a class="page-btn <?= $p === $usersPage['page'] ? 'is-active' : '' ?>" href="<?= e(base_path('admin-users.php?' . http_build_query(array_merge($_GET, ['user_page' => $p])))) ?>"><?= e((string) $p) ?></a>
                <?php endfor; ?>
                <a class="page-btn" href="<?= e(base_path('admin-users.php?' . http_build_query(array_merge($_GET, ['user_page' => min($usersPage['total_pages'], $usersPage['page'] + 1)])))) ?>">&rarr;</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
