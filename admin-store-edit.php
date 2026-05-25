<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

$storeId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $storeId]);
$store = $stmt->fetch();

if (!$store) {
    set_flash('error', 'Toko tidak ditemukan.');
    redirect_to('admin-dashboard.php');
}

if (is_post()) {
    try {
        db()->prepare(
            'UPDATE stores
             SET name = :name, slug = :slug, region = :region, address = :address, whatsapp = :whatsapp, instagram = :instagram,
                 description = :description, cover_image = :cover_image, updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $storeId,
            'name' => trim($_POST['name'] ?? ''),
            'slug' => slugify(trim($_POST['name'] ?? '') . '-' . substr((string) time(), -4)),
            'region' => trim($_POST['region'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'cover_image' => trim($_POST['cover_image'] ?? ''),
        ]);
        set_flash('success', 'Toko berhasil diperbarui.');
        redirect_to('admin-dashboard.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui toko.');
        redirect_to('admin-store-edit.php?id=' . $storeId);
    }
}

render_layout('Edit Toko', function (?array $user = null) use ($store): void {
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
            <span class="nav-link-icon">🏠</span> Dashboard
          </a>
          <a href="<?= e(base_path('admin-store-create.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🏪</span> Tambah Toko
          </a>
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>" class="nav-link">
            <span class="nav-link-icon">👤</span> Buat Store Admin
          </a>
          <a href="<?= e(base_path('admin-store-edit.php?id=' . $store['id'])) ?>" class="nav-link active">
            <span class="nav-link-icon">✏️</span> Edit Toko
          </a>

          <div class="nav-divider"></div>
          <div class="nav-label">Platform</div>
          <a href="<?= e(base_path('index.php')) ?>" class="nav-link">
            <span class="nav-link-icon">🌐</span> Beranda
          </a>
          <a href="<?= e(base_path('katalog.php')) ?>" class="nav-link">
            <span class="nav-link-icon">📦</span> Katalog
          </a>
          <div class="nav-divider"></div>
          <a href="<?= e(base_path('logout.php')) ?>" class="nav-link" style="margin-top:auto;color:#c0645a;">
            <span class="nav-link-icon" style="font-size:14px">🚪</span> Keluar
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
            <div class="topbar-heading">Edit Toko</div>
            <div class="topbar-sub">Perbarui data toko tanpa keluar dari area superadmin.</div>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card">
            <h3>Form Edit Toko</h3>
            <form method="post" class="form-panel" style="margin-top: 18px;">
              <input type="hidden" name="id" value="<?= e((string) $store['id']) ?>" />
              <label>Nama Toko <input type="text" name="name" value="<?= e($store['name']) ?>" required /></label>
              <label>Wilayah <input type="text" name="region" value="<?= e($store['region']) ?>" required /></label>
              <label>Alamat <textarea name="address" required><?= e($store['address']) ?></textarea></label>
              <label>WhatsApp <input type="text" name="whatsapp" value="<?= e($store['whatsapp']) ?>" required /></label>
              <label>Instagram <input type="text" name="instagram" value="<?= e($store['instagram']) ?>" required /></label>
              <label>Cover Image Path <input type="text" name="cover_image" value="<?= e($store['cover_image']) ?>" required /></label>
              <label>Deskripsi <textarea name="description" required><?= e($store['description']) ?></textarea></label>
              <button type="submit">Simpan Perubahan</button>
            </form>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
