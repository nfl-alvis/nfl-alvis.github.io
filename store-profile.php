<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_STORE_ADMIN);

$user = current_user();
$storeId = (int) ($user['store_id'] ?? 0);

if (!$storeId) {
    set_flash('error', 'Akun store admin belum terhubung ke toko.');
    redirect_to('index.php');
}

$storeStmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$storeStmt->execute(['id' => $storeId]);
$store = $storeStmt->fetch();

if (is_post()) {
    try {
        $stmt = db()->prepare(
            'UPDATE stores
             SET name = :name,
                 region = :region,
                 address = :address,
                 whatsapp = :whatsapp,
                 instagram = :instagram,
                 description = :description,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'region' => trim($_POST['region'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'whatsapp' => preg_replace('/\D+/', '', $_POST['whatsapp'] ?? ''),
            'instagram' => trim($_POST['instagram'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'id' => $storeId,
        ]);
        set_flash('success', 'Informasi toko berhasil diperbarui.');
        redirect_to('store-profile.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Informasi toko gagal diperbarui.');
        redirect_to('store-profile.php');
    }
}

render_layout('Profil Toko', function (?array $currentUser = null) use ($user, $store): void {
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Store Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('store-dashboard.php')) ?>">Dashboard</a>
          <a href="<?= e(base_path('store-profile.php')) ?>" class="active">Profil Toko</a>
          <a href="<?= e(base_path('store-add-product.php')) ?>">Tambah Produk</a>
          <a href="<?= e(base_path('store-products.php')) ?>">Produk Saya</a>
          <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">Lihat Halaman Toko</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Profil Toko</h2>
            <p class="muted-note">Perbarui kontak dan informasi toko yang tampil ke publik.</p>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> • Store Admin</div>
        </div>

        <article class="table-card">
          <form method="post" class="form-panel">
            <label>Nama Toko <input type="text" name="name" value="<?= e($store['name']) ?>" required /></label>
            <label>Wilayah <input type="text" name="region" value="<?= e($store['region']) ?>" required /></label>
            <label>Alamat <textarea name="address" required><?= e($store['address']) ?></textarea></label>
            <label>WhatsApp <input type="text" name="whatsapp" value="<?= e($store['whatsapp']) ?>" required /></label>
            <label>Instagram <input type="text" name="instagram" value="<?= e($store['instagram']) ?>" required /></label>
            <label>Deskripsi <textarea name="description" required><?= e($store['description']) ?></textarea></label>
            <button type="submit">Simpan Perubahan Toko</button>
          </form>
        </article>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
