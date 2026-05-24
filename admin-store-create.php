<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post()) {
    try {
        $name = trim($_POST['name'] ?? '');
        $coverImage = save_uploaded_store_image($_FILES['cover_image'] ?? []);
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
            'cover_image' => $coverImage,
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
            <p class="muted-note">Tambahkan data toko baru dari area superadmin. Isi data inti, kontak, dan deskripsi sebelum toko dipublikasikan.</p>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card admin-form-card">
            <div class="table-card-head">
              <div>
                <h3>Form Toko Baru</h3>
                <p class="table-meta">Pastikan nama, wilayah, dan kontak sudah benar sebelum disimpan.</p>
              </div>
              <a class="inline-link" href="<?= e(base_path('admin-dashboard.php')) ?>">Kembali ke dashboard</a>
            </div>

            <form method="post" enctype="multipart/form-data" class="form-panel admin-form-panel">
              <div class="form-grid-2">
                <label>Nama Toko <input type="text" name="name" required placeholder="Contoh: Rumah Makan Nusantara" /></label>
                <label>Wilayah <input type="text" name="region" required placeholder="Contoh: Jawa Timur" /></label>
              </div>
              <label>Alamat <textarea name="address" required placeholder="Tuliskan alamat lengkap toko"></textarea></label>
              <div class="form-grid-2">
                <label>WhatsApp <input type="text" name="whatsapp" required placeholder="Contoh: 6281234567890" /></label>
                <label>Instagram <input type="text" name="instagram" required placeholder="Contoh: @namatoko" /></label>
              </div>
              <label>Foto Toko
                <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp" />
              </label>
              <div class="profile-upload-note">Upload gambar toko langsung. Format yang didukung: JPG, PNG, WEBP.</div>
              <label>Deskripsi <textarea name="description" required placeholder="Deskripsi singkat tentang toko dan spesialisasinya"></textarea></label>
              <div class="form-footer-actions">
                <button type="submit">Tambah Toko</button>
                <span class="form-footer-note">Data akan langsung masuk ke daftar toko aktif setelah disimpan.</span>
              </div>
            </form>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
