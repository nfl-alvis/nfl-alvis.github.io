<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);

$stores = all_stores_with_admins();

if (is_post()) {
    try {
        create_user(
            trim($_POST['name'] ?? ''),
            trim($_POST['email'] ?? ''),
            $_POST['password'] ?? '',
            ROLE_STORE_ADMIN,
            (int) ($_POST['store_id'] ?? 0)
        );
        set_flash('success', 'Store admin berhasil dibuat.');
        redirect_to('admin-users.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek email yang mungkin sudah dipakai atau input tidak valid.');
        redirect_to('admin-store-admin-create.php');
    }
}

render_layout('Buat Store Admin', function (?array $user = null) use ($stores): void {
    $hasStores = count($stores) > 0;
    $userName = (string) ($user['name'] ?? 'Super Admin');
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'admin-create'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Buat Store Admin</div>
            <div class="topbar-sub">Tambahkan akun pengelola dan tetapkan toko yang dapat diakses.</div>
          </div>
          <div class="pill-role"><?= e($userName) ?> &bull; Super Admin</div>
        </div>

        <section class="management-layout" aria-label="Form pembuatan store admin">
          <article class="table-card management-table-card store-admin-form-card">
            <div class="table-card-head">
              <div>
                <h3>Akun Store Admin Baru</h3>
                <div class="table-meta">Isi informasi akun dan tentukan toko yang akan dikelola.</div>
              </div>
              <span class="store-admin-availability">
                <i class="fa-solid fa-store" aria-hidden="true"></i>
                <?= e((string) count($stores)) ?> toko tersedia
              </span>
            </div>

            <form method="post" class="form-panel admin-form-panel store-admin-management-form">
              <div class="form-grid-2">
                <label for="admin-name">
                  <span class="store-admin-field-label">Nama Lengkap <span class="required-mark">&ast;</span></span>
                  <input id="admin-name" type="text" name="name" placeholder="Nama pengelola toko" autocomplete="name" required />
                </label>
                <label for="admin-email">
                  <span class="store-admin-field-label">Email <span class="required-mark">&ast;</span></span>
                  <input id="admin-email" type="email" name="email" placeholder="admin@toko.com" autocomplete="email" required />
                </label>
                <label for="admin-password">
                  <span class="store-admin-field-label">Password Awal <span class="required-mark">&ast;</span></span>
                  <input id="admin-password" type="password" name="password" placeholder="Masukkan password awal" autocomplete="new-password" required />
                </label>
                <label for="admin-store">
                  <span class="store-admin-field-label">Toko yang Dikelola <span class="required-mark">&ast;</span></span>
                  <select id="admin-store" name="store_id" required <?= $hasStores ? '' : 'disabled' ?>>
                    <?php if (!$hasStores): ?>
                      <option value="">Belum ada toko tersedia</option>
                    <?php else: ?>
                      <option value="" selected disabled>Pilih toko</option>
                      <?php foreach ($stores as $store): ?>
                        <option value="<?= e((string) $store['id']) ?>"><?= e($store['name']) ?></option>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </select>
                </label>
              </div>

              <div class="store-admin-notice <?= $hasStores ? '' : 'is-warning' ?>">
                <i class="fa-solid <?= $hasStores ? 'fa-shield-halved' : 'fa-circle-exclamation' ?>" aria-hidden="true"></i>
                <span><?= $hasStores
                    ? 'Role akun ditetapkan sebagai Store Admin dan akses dibatasi pada toko pilihan.'
                    : 'Buat toko terlebih dahulu sebelum menambahkan akun Store Admin.' ?></span>
              </div>

              <div class="form-footer-actions store-admin-actions">
                <p class="form-footer-note">Semua field wajib diisi. Akun akan aktif setelah berhasil dibuat.</p>
                <button type="submit" <?= $hasStores ? '' : 'disabled' ?>>
                  <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                  Buat Store Admin
                </button>
              </div>
            </form>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
