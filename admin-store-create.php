<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post()) {
    try {
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
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek input toko yang mungkin duplikat atau tidak valid.');
        redirect_to('admin-store-create.php');
    }
}

render_layout('Tambah Toko Baru', function (?array $user = null): void {
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Super Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('admin-dashboard.php')) ?>">Dashboard</a>
          <a href="<?= e(base_path('admin-store-create.php')) ?>" class="active">Tambah Toko</a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>">Buat Store Admin</a>
          <a href="<?= e(base_path('index.php')) ?>">Beranda</a>
          <a href="<?= e(base_path('katalog.php')) ?>">Katalog</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Tambah Toko Baru</h2>
            <p class="muted-note">Halaman khusus untuk menambah toko baru dari area superadmin.</p>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card">
            <h3>Form Toko Baru</h3>
            <form method="post" class="form-panel" style="margin-top: 18px;">
              <label>Nama Toko <input type="text" name="name" required /></label>
              <label>Wilayah <input type="text" name="region" required /></label>
              <label>Alamat <textarea name="address" required></textarea></label>
              <label>WhatsApp <input type="text" name="whatsapp" required /></label>
              <label>Instagram <input type="text" name="instagram" required /></label>
              <label>Cover Image Path <input type="text" name="cover_image" value="assets/image/image.png" required /></label>
              <label>Deskripsi <textarea name="description" required></textarea></label>
              <button type="submit">Tambah Toko</button>
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
                <strong>Buat Store Admin</strong>
                <p>Tambah akun admin toko dari halaman khusus.</p>
                <a class="inline-link" href="<?= e(base_path('admin-store-admin-create.php')) ?>">Buka halaman</a>
              </div>
            </div>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
