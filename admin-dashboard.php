<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

if (is_post()) {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create_store') {
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
        }

        if ($action === 'create_store_admin') {
            create_user(
                trim($_POST['name'] ?? ''),
                trim($_POST['email'] ?? ''),
                $_POST['password'] ?? '',
                ROLE_STORE_ADMIN,
                (int) ($_POST['store_id'] ?? 0)
            );
            set_flash('success', 'Store admin berhasil dibuat.');
            redirect_to('admin-dashboard.php');
        }

        if ($action === 'create_product') {
            $name = trim($_POST['name'] ?? '');
            $stmt = db()->prepare(
                'INSERT INTO products
                 (store_id, name, slug, type, region, short_description, long_description, price_display, rating, review_count, tag_label, image_path, is_featured, is_active, created_at, updated_at)
                 VALUES
                 (:store_id, :name, :slug, :type, :region, :short_description, :long_description, :price_display, :rating, :review_count, :tag_label, :image_path, :is_featured, 1, NOW(), NOW())'
            );
            $stmt->execute([
                'store_id' => (int) ($_POST['store_id'] ?? 0),
                'name' => $name,
                'slug' => slugify($name . '-' . substr((string) time(), -4)),
                'type' => trim($_POST['type'] ?? 'Makanan'),
                'region' => trim($_POST['region'] ?? ''),
                'short_description' => trim($_POST['short_description'] ?? ''),
                'long_description' => trim($_POST['long_description'] ?? ''),
                'price_display' => trim($_POST['price_display'] ?? ''),
                'rating' => (float) ($_POST['rating'] ?? 4.5),
                'review_count' => (int) ($_POST['review_count'] ?? 0),
                'tag_label' => trim($_POST['tag_label'] ?? ''),
                'image_path' => trim($_POST['image_path'] ?? 'assets/image/image.png'),
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            ]);
            set_flash('success', 'Produk platform berhasil ditambahkan.');
            redirect_to('admin-dashboard.php');
        }
    } catch (Throwable $exception) {
        set_flash('error', 'Data gagal disimpan. Cek kemungkinan email, slug, atau input duplikat.');
        redirect_to('admin-dashboard.php');
    }
}

$stats = super_admin_stats();
$users = all_users();
$stores = all_stores_with_admins();
$products = all_products_for_admin();

render_layout('Dashboard Super Admin', function (?array $user = null) use ($stats, $users, $stores, $products): void {
    ?>
    <div class="dashboard-shell">
      <aside class="dashboard-sidebar">
        <div class="dashboard-brand">
          <h1>PusakaRasa</h1>
          <p>Super Admin Dashboard</p>
        </div>
        <nav class="dashboard-nav">
          <a href="<?= e(base_path('admin-dashboard.php')) ?>" class="active">Dashboard</a>
          <a href="<?= e(base_path('index.php')) ?>">Beranda</a>
          <a href="<?= e(base_path('katalog.php')) ?>">Katalog</a>
          <a href="<?= e(base_path('logout.php')) ?>">Keluar</a>
        </nav>
      </aside>
      <main class="dashboard-main">
        <div class="dashboard-header">
          <div>
            <h2>Kontrol Platform</h2>
            <p class="muted-note">Kelola pengguna, toko, dan produk dari satu tempat.</p>
          </div>
          <div class="pill-role"><?= e($user['name']) ?> • Super Admin</div>
        </div>

        <section class="stats-grid">
          <article class="stat-box"><p>Total pengguna</p><h3><?= e(number_short($stats['users'])) ?></h3></article>
          <article class="stat-box"><p>Total toko</p><h3><?= e(number_short($stats['stores'])) ?></h3></article>
          <article class="stat-box"><p>Total produk</p><h3><?= e(number_short($stats['products'])) ?></h3></article>
          <article class="stat-box"><p>Total views produk</p><h3><?= e(number_short($stats['views'])) ?></h3></article>
        </section>

        <section class="dashboard-grid">
          <div class="stacked-card">
            <article class="table-card">
              <h3>Buat Toko Baru</h3>
              <form method="post" class="form-panel" style="margin-top: 18px;">
                <input type="hidden" name="action" value="create_store" />
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
              <h3>Buat Store Admin</h3>
              <form method="post" class="form-panel" style="margin-top: 18px;">
                <input type="hidden" name="action" value="create_store_admin" />
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
              <h3>Tambah Produk Global</h3>
              <form method="post" class="form-panel" style="margin-top: 18px;">
                <input type="hidden" name="action" value="create_product" />
                <label>Nama Produk <input type="text" name="name" required /></label>
                <label>Toko
                  <select name="store_id" required>
                    <?php foreach ($stores as $store): ?>
                      <option value="<?= e((string) $store['id']) ?>"><?= e($store['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>Kategori
                  <select name="type">
                    <option value="Makanan">Makanan</option>
                    <option value="Minuman">Minuman</option>
                  </select>
                </label>
                <label>Wilayah <input type="text" name="region" required /></label>
                <label>Harga Tampilan <input type="text" name="price_display" required /></label>
                <label>Tag <input type="text" name="tag_label" required /></label>
                <label>Path Gambar <input type="text" name="image_path" value="assets/image/image.png" required /></label>
                <label>Deskripsi Singkat <textarea name="short_description" required></textarea></label>
                <label>Deskripsi Panjang <textarea name="long_description" required></textarea></label>
                <label>Rating <input type="number" name="rating" value="4.5" min="1" max="5" step="0.1" required /></label>
                <label>Jumlah Ulasan <input type="number" name="review_count" value="0" min="0" required /></label>
                <label><input type="checkbox" name="is_featured" value="1" /> Jadikan produk unggulan</label>
                <button type="submit">Tambah Produk</button>
              </form>
            </article>
          </div>

          <div class="stacked-card">
            <article class="table-card">
              <h3>Pengguna</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Toko</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($users as $item): ?>
                    <tr>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['email']) ?></td>
                      <td><span class="pill-role"><?= e($item['role']) ?></span></td>
                      <td><?= e($item['store_name'] ?: '-') ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </article>

            <article class="table-card">
              <h3>Daftar Toko</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Toko</th>
                    <th>Wilayah</th>
                    <th>Admin</th>
                    <th>Produk</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($stores as $item): ?>
                    <tr>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['region']) ?></td>
                      <td><?= e($item['admins'] ?: '-') ?></td>
                      <td><?= e((string) $item['product_count']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </article>

            <article class="table-card">
              <h3>Produk Platform</h3>
              <table class="data-table" style="margin-top: 12px;">
                <thead>
                  <tr>
                    <th>Produk</th>
                    <th>Toko</th>
                    <th>Kategori</th>
                    <th>Harga</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($products as $item): ?>
                    <tr>
                      <td><?= e($item['name']) ?></td>
                      <td><?= e($item['store_name']) ?></td>
                      <td><?= e($item['type']) ?></td>
                      <td><?= e(rupiah($item['price_display'])) ?></td>
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
