<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

$productId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT p.*, s.name AS store_name
     FROM products p
     INNER JOIN stores s ON s.id = p.store_id
     WHERE p.id = :id LIMIT 1'
);
$stmt->execute(['id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    set_flash('error', 'Produk tidak ditemukan.');
    redirect_to('admin-dashboard.php');
}

$stores = all_stores_with_admins();

if (is_post()) {
    try {
        db()->prepare(
            'UPDATE products
             SET store_id = :store_id, name = :name, slug = :slug, type = :type, region = :region,
                 short_description = :short_description, long_description = :long_description,
                 price_display = :price_display, rating = :rating, review_count = :review_count,
                 tag_label = :tag_label, image_path = :image_path, is_featured = :is_featured,
                 is_active = :is_active, updated_at = NOW()
             WHERE id = :id'
        )->execute([
            'id' => $productId,
            'store_id' => (int) ($_POST['store_id'] ?? 0),
            'name' => trim($_POST['name'] ?? ''),
            'slug' => slugify(trim($_POST['name'] ?? '') . '-' . substr((string) time(), -4)),
            'type' => trim($_POST['type'] ?? 'Makanan'),
            'region' => trim($_POST['region'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'price_display' => trim($_POST['price_display'] ?? ''),
            'rating' => (float) ($_POST['rating'] ?? 4.5),
            'review_count' => (int) ($_POST['review_count'] ?? 0),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => trim($_POST['image_path'] ?? ''),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ]);
        set_flash('success', 'Produk berhasil diperbarui.');
        redirect_to('admin-dashboard.php');
    } catch (Throwable $exception) {
        set_flash('error', 'Gagal memperbarui produk.');
        redirect_to('admin-product-edit.php?id=' . $productId);
    }
}

render_layout('Edit Produk', function (?array $user = null) use ($product, $stores): void {
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
          <a href="<?= e(base_path('admin-store-admin-create.php')) ?>">Buat Store Admin</a>
          <a href="<?= e(base_path('admin-product-edit.php?id=' . $product['id'])) ?>" class="active">Edit Produk</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Edit Produk</h2>
            <p class="muted-note">Perbarui detail produk, gambar, dan status publikasinya.</p>
          </div>
          <div class="pill-role">Super Admin</div>
        </div>

        <section class="dashboard-grid">
          <article class="table-card">
            <h3>Form Edit Produk</h3>
            <form method="post" class="form-panel" style="margin-top: 18px;">
              <input type="hidden" name="id" value="<?= e((string) $product['id']) ?>" />
              <label>Nama Produk <input type="text" name="name" value="<?= e($product['name']) ?>" required /></label>
              <label>Toko
                <select name="store_id" required>
                  <?php foreach ($stores as $store): ?>
                    <option value="<?= e((string) $store['id']) ?>" <?= (int) $product['store_id'] === (int) $store['id'] ? 'selected' : '' ?>>
                      <?= e($store['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Kategori
                <select name="type">
                  <option value="Makanan" <?= $product['type'] === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                  <option value="Minuman" <?= $product['type'] === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                </select>
              </label>
              <label>Wilayah <input type="text" name="region" value="<?= e($product['region']) ?>" required /></label>
              <label>Harga Tampilan <input type="text" name="price_display" value="<?= e($product['price_display']) ?>" required /></label>
              <label>Tag <input type="text" name="tag_label" value="<?= e($product['tag_label']) ?>" required /></label>
              <label>Path Gambar <input type="text" name="image_path" value="<?= e($product['image_path']) ?>" required /></label>
              <label>Deskripsi Singkat <textarea name="short_description" required><?= e($product['short_description']) ?></textarea></label>
              <label>Deskripsi Panjang <textarea name="long_description" required><?= e($product['long_description']) ?></textarea></label>
              <label>Rating <input type="number" name="rating" value="<?= e((string) $product['rating']) ?>" min="1" max="5" step="0.1" required /></label>
              <label>Jumlah Ulasan <input type="number" name="review_count" value="<?= e((string) $product['review_count']) ?>" min="0" required /></label>
              <label><input type="checkbox" name="is_featured" value="1" <?= (int) $product['is_featured'] === 1 ? 'checked' : '' ?> /> Jadikan produk unggulan</label>
              <label><input type="checkbox" name="is_active" value="1" <?= (int) $product['is_active'] === 1 ? 'checked' : '' ?> /> Aktif</label>
              <button type="submit">Simpan Perubahan</button>
            </form>
          </article>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
