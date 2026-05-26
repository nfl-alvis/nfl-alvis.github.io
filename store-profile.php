<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/store-sidebar.php';

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
    <div class="shell">
      <?php render_store_sidebar($user, $store, 'profile'); ?>

      <main class="main">
        <div class="topbar">
          <div>
            <div class="topbar-heading">Profil Toko</div>
            <div class="topbar-sub">Perbarui kontak dan informasi toko yang tampil ke publik.</div>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> &bull; Store Admin</div>
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
