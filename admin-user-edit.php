<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

$userId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT u.*, s.name AS store_name
     FROM users u
     LEFT JOIN stores s ON s.id = u.store_id
     WHERE u.id = :id LIMIT 1'
);
$stmt->execute(['id' => $userId]);
$targetUser = $stmt->fetch();

if (!$targetUser) {
    set_flash('error', 'Pengguna tidak ditemukan.');
    redirect_to('admin-users.php');
}

$stores = all_stores_with_admins();

if (is_post()) {
    try {
        $stmt = db()->prepare(
            'UPDATE users
             SET name = :name, email = :email, role = :role, store_id = :store_id, is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $userId,
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'role' => trim($_POST['role'] ?? ROLE_USER),
            'store_id' => (int) ($_POST['store_id'] ?? 0) ?: null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        set_flash('success', 'Pengguna berhasil diperbarui.');
        redirect_to('admin-users.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui pengguna.');
        redirect_to('admin-user-edit.php?id=' . $userId);
    }
}

render_layout('Edit Pengguna', function (?array $user = null) use ($targetUser, $stores): void {
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'users'); ?>
      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Edit Pengguna</div>
            <div class="topbar-sub">Perbarui role, toko, dan status akun pengguna.</div>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card">
            <h3>Form Edit Pengguna</h3>
            <form method="post" class="form-panel" style="margin-top: 18px;">
              <input type="hidden" name="id" value="<?= e((string) $targetUser['id']) ?>" />
              <label>Nama <input type="text" name="name" value="<?= e($targetUser['name']) ?>" required /></label>
              <label>Email <input type="email" name="email" value="<?= e($targetUser['email']) ?>" required /></label>
              <label>Role
                <select name="role">
                  <option value="user" <?= $targetUser['role'] === 'user' ? 'selected' : '' ?>>User</option>
                  <option value="store_admin" <?= $targetUser['role'] === 'store_admin' ? 'selected' : '' ?>>Store Admin</option>
                  <option value="super_admin" <?= $targetUser['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                </select>
              </label>
              <label>Toko
                <select name="store_id">
                  <option value="0">-</option>
                  <?php foreach ($stores as $store): ?>
                    <option value="<?= e((string) $store['id']) ?>" <?= (int) $targetUser['store_id'] === (int) $store['id'] ? 'selected' : '' ?>>
                      <?= e($store['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label><input type="checkbox" name="is_active" value="1" <?= (int) $targetUser['is_active'] === 1 ? 'checked' : '' ?> /> Aktif</label>
              <button type="submit">Simpan Perubahan</button>
            </form>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
