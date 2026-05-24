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

$editId = (int) ($_GET['edit'] ?? 0);
$editingProduct = null;

if ($editId > 0) {
    $editStmt = db()->prepare('SELECT * FROM products WHERE id = :id AND store_id = :store_id LIMIT 1');
    $editStmt->execute(['id' => $editId, 'store_id' => $storeId]);
    $editingProduct = $editStmt->fetch() ?: null;
}

if (is_post()) {
    try {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $currentImage = trim($_POST['current_image'] ?? '');
        $imagePath = save_uploaded_product_image($_FILES['product_image'] ?? [], $currentImage);
        $stmt = db()->prepare(
            'UPDATE products
             SET name = :name,
                 type = :type,
                 region = :region,
                 short_description = :short_description,
                 long_description = :long_description,
                 price_display = :price_display,
                 tag_label = :tag_label,
                 image_path = :image_path,
                 updated_at = NOW()
             WHERE id = :id AND store_id = :store_id'
        );
        $stmt->execute([
            'name' => trim($_POST['name'] ?? ''),
            'type' => trim($_POST['type'] ?? 'Makanan'),
            'region' => trim($_POST['region'] ?? ''),
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'price_display' => trim($_POST['price_display'] ?? ''),
            'tag_label' => trim($_POST['tag_label'] ?? ''),
            'image_path' => $imagePath,
            'id' => $productId,
            'store_id' => $storeId,
        ]);
        set_flash('success', 'Produk berhasil diperbarui.');
        redirect_to('store-products.php');
    } catch (Throwable $exception) {
        set_flash('error', $exception->getMessage());
        redirect_to('store-products.php' . ($editId > 0 ? '?edit=' . $editId : ''));
    }
}

$products = find_store_products($storeId);

render_layout('Produk Saya', function (?array $currentUser = null) use ($user, $store, $products, $editingProduct): void {
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
          <a href="<?= e(base_path('store-add-product.php')) ?>">Tambah Produk</a>
          <a href="<?= e(base_path('store-products.php')) ?>" class="active">Produk Saya</a>
          <a href="<?= e(base_path('store.php?slug=' . $store['slug'])) ?>">Lihat Halaman Toko</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Produk Saya</h2>
            <p class="muted-note">Kelola produk yang sudah dimiliki toko Anda.</p>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> • Store Admin</div>
        </div>

        <section class="dashboard-grid">
          <div class="stacked-card">
            <article class="table-card">
              <h3><?= $editingProduct ? 'Edit Produk' : 'Pilih Produk Untuk Diedit' ?></h3>
              <?php if ($editingProduct): ?>
                <form method="post" enctype="multipart/form-data" class="form-panel" style="margin-top: 18px;">
                  <input type="hidden" name="product_id" value="<?= e((string) $editingProduct['id']) ?>" />
                  <input type="hidden" name="current_image" value="<?= e($editingProduct['image_path']) ?>" />
                  <label>Nama Produk <input type="text" name="name" value="<?= e($editingProduct['name']) ?>" required /></label>
                  <label>Kategori
                    <select name="type">
                      <option value="Makanan" <?= $editingProduct['type'] === 'Makanan' ? 'selected' : '' ?>>Makanan</option>
                      <option value="Minuman" <?= $editingProduct['type'] === 'Minuman' ? 'selected' : '' ?>>Minuman</option>
                    </select>
                  </label>
                  <label>Daerah <input type="text" name="region" value="<?= e($editingProduct['region']) ?>" required /></label>
                  <label>Harga Tampilan <input type="text" name="price_display" value="<?= e($editingProduct['price_display']) ?>" required /></label>
                  <label>Tag <input type="text" name="tag_label" value="<?= e($editingProduct['tag_label']) ?>" required /></label>
                  <label>Upload Gambar Baru <input type="file" name="product_image" accept=".jpg,.jpeg,.png,.webp" /></label>
                  <label>Deskripsi Singkat <textarea name="short_description" required><?= e($editingProduct['short_description']) ?></textarea></label>
                  <label>Deskripsi Panjang <textarea name="long_description" required><?= e($editingProduct['long_description']) ?></textarea></label>
                  <button type="submit">Simpan Perubahan</button>
                  <a class="inline-link" href="<?= e(base_path('store-products.php')) ?>">Batalkan edit</a>
                </form>
              <?php else: ?>
                <div class="empty-state">Pilih tombol edit dari daftar produk di samping untuk mengubah data produk.</div>
              <?php endif; ?>
            </article>
          </div>

          <div class="stacked-card">
            <article class="table-card">
              <h3>Daftar Produk</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                    <th>Rating</th>
                    <th>Views</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $product): ?>
                    <tr>
                      <td><?= e($product['name']) ?></td>
                      <td><?= e($product['type']) ?></td>
                      <td><?= e(rupiah($product['price_display'])) ?></td>
                      <td><?= e(number_format((float) $product['rating'], 1)) ?></td>
                      <td><?= e(number_short((int) $product['total_views'])) ?></td>
                      <td><a class="inline-link" href="<?= e(base_path('store-products.php?edit=' . $product['id'])) ?>">Edit</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </article>
          </div>
        </section>
      </main>
    </div>
    <?php
}, ['hide_header' => true, 'dashboard_css' => true, 'body_class' => 'dashboard-body']);
