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
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Super Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('admin-dashboard.php')) ?>">Dashboard</a>
          <a href="<?= e(base_path('admin-store-create.php')) ?>">Tambah Toko</a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="active">Buat Store Admin</a>
          <a href="<?= e(base_path('index.php')) ?>">Beranda</a>
          <a href="<?= e(base_path('katalog.php')) ?>">Katalog</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Buat Store Admin</h2>
            <p class="muted-note">Halaman khusus untuk menambah admin toko dari area superadmin.</p>
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
