<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

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
        redirect_to('admin-dashboard.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek email yang mungkin sudah dipakai atau input tidak valid.');
        redirect_to('admin-store-admin-create.php');
    }
}

render_layout('Buat Store Admin', function (?array $user = null) use ($stores): void {
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
          <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🏠</span>
            Dashboard
          </a>
          <a href="<?= e(base_path('admin-store-create.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🏪</span>
            Tambah Toko
          </a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="nav-link active">
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
            <div class="topbar-heading">Buat Store Admin</div>
            <div class="topbar-sub">Halaman khusus untuk menambah admin toko dari area superadmin.</div>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card">
            <h3>Form Store Admin</h3>
            <form method="post" class="form-panel" style="margin-top: 18px;">
              <label>Nama <input type="text" name="name" required /></label>
              <label>Email <input type="email" name="email" required /></label>
              <label>Password <input type="password" name="password" required /></label>
              <label>Toko
                <select name="store_id" required>
                  <?php foreach ($stores as $store): ?>
                    <option value="<?= e((string) $store['id']) ?>"><?= e($store['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button type="submit">Buat Store Admin</button>
            </form>
          </article>

          <article class="table-card">
            <h3>Menu Cepat</h3>
            <div class="product-store-list" style="margin-top: 12px;">
              <div class="product-mini-card">
                <strong>Dashboard</strong>
                <p>Kembali ke ringkasan super admin.</p>
                <a class="inline-link" href="<?= e(base_path('admin-dashboard.php')) ?>">Buka dashboard</a>
              </div>
              <div class="product-mini-card">
                <strong>Tambah Toko</strong>
                <p>Tambah toko baru dari halaman khusus.</p>
                <a class="inline-link" href="<?= e(base_path('admin-store-create.php')) ?>">Buka halaman</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
