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
        $name = trim($_POST['name'] ?? '');
        $imagePath = save_uploaded_product_image($_FILES['product_image'] ?? []);
        $stmt = db()->prepare(
            'INSERT INTO products
             (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, base_rating_total, base_review_count, is_featured, is_active, created_at, updated_at)
             VALUES
             (:store_id, :name, :slug, :type, :region, :short_description, :long_description, :price_display, 0, 0, :tag_label, :image_path, 0, 0, 0, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'store_id' => $storeId,
            'name' => $name,
            'slug' => slugify($name . '-' . substr((string) time(), -4)),
            'type' => trim($_POST['type'] ?? 'Makanan'),
            'region' => trim($_POST['region'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'price_display' => trim($_POST['price_display'] ?? ''),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => $imagePath,
        ]);
        set_flash('success', 'Produk baru berhasil ditambahkan.');
        redirect_to('store-products.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect_to('store-add-product.php');
    }
}

render_layout('Tambah Produk', function (?array $currentUser = null) use ($user, $store): void {
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Store Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('store-dashboard.php')) ?>">Dashboard</a>
          <a href="<?= e(base_path('store-profile.php')) ?>">Profil Toko</a>
          <a href="<?= e(base_path('store-add-product.php')) ?>" class="active">Tambah Produk</a>
          <a href="<?= e(base_path('store-products.php')) ?>">Produk Saya</a>
          <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">Lihat Halaman Toko</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Tambah Produk Baru</h2>
            <p class="muted-note">Produk baru akan tampil di katalog toko setelah data disimpan.</p>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> • Store Admin</div>
        </div>

        <article class="table-card">
          <form method="post" enctype="multipart/form-data" class="form-panel">
            <label>Nama Produk <input type="text" name="name" required /></label>
            <label>Kategori
              <select name="type">
                <option value="Makanan">Makanan</option>
                <option value="Minuman">Minuman</option>
              </select>
            </label>
            <label>Daerah <input type="text" name="region" required /></label>
            <label>Harga Tampilan <input type="text" name="price_display" placeholder="25.000" required /></label>
            <label>Tag <input type="text" name="tag_label" placeholder="#gurih" required /></label>
            <label>Upload Gambar Produk <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" required /></label>
            <label>Deskripsi Singkat <textarea name="short_description" required></textarea></label>
            <label>Deskripsi Panjang <textarea name="long_description" required></textarea></label>
            <button type="submit">Simpan Produk</button>
          </form>
        </article>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'hide_footer' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
