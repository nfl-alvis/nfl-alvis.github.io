<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/admin-sidebar.php';

require_role(ROLE_SUPER_ADMIN);
ensure_store_operational_columns();

$storeId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM stores WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $storeId]);
$store = $stmt->fetch();

if (!$store) {
    set_flash('error', 'Toko tidak ditemukan.');
    redirect_to('admin-stores.php');
}

if (is_post()) {
    try {
        db()->prepare(
            'UPDATE stores
             SET name = :name, slug = :slug, region = :region, address = :address, whatsapp = :whatsapp, instagram = :instagram,
                 description = :description, operating_hours = :operating_hours, is_open = :is_open,
                 cover_image = :cover_image, updated_at = NOW()
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
            'operating_hours' => trim($_POST['operating_hours'] ?? ''),
            'is_open' => ($_POST['is_open'] ?? '0') === '1' ? 1 : 0,
            'cover_image' => trim($_POST['cover_image'] ?? ''),
        ]);
        set_flash('success', 'Toko berhasil diperbarui.');
        redirect_to('admin-stores.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui toko.');
        redirect_to('admin-store-edit.php?id=' . $storeId);
    }
}

render_layout('Edit Toko', function (?array $user = null) use ($store): void {
    ?>
    <div class="shell">
      <?php render_admin_sidebar($user, 'stores'); ?>
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
              <label>Wilayah
                <select name="region" required>
                  <?php render_province_options($store['region'] ?? ''); ?>
                </select>
              </label>
              <label>Alamat <textarea name="address" required><?= e($store['address']) ?></textarea></label>
              <label>WhatsApp <input type="text" name="whatsapp" value="<?= e($store['whatsapp']) ?>" required /></label>
              <label>Instagram <input type="text" name="instagram" value="<?= e($store['instagram']) ?>" required /></label>
              <label>Jam Operasional <input type="text" name="operating_hours" value="<?= e($store['operating_hours'] ?? '') ?>" placeholder="Setiap hari, 08.00 - 21.00 WIB" required /></label>
              <label>Status Toko
                <select name="is_open" required>
                  <option value="1" <?= (int) ($store['is_open'] ?? 1) === 1 ? 'selected' : '' ?>>Buka</option>
                  <option value="0" <?= (int) ($store['is_open'] ?? 1) === 0 ? 'selected' : '' ?>>Tutup</option>
                </select>
              </label>
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
