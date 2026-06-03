# Function Lainnya

File ini merangkum function penting lain yang ada di `includes/bootstrap.php`, `config/mail.php`, dan file sidebar.

## Helper Dasar

### `app_name()`

Mengembalikan nama aplikasi:

```php
return 'PusakaRasa';
```

Dipakai oleh layout untuk title dan header.

### `base_path($path)`

Membuat path URL yang benar, termasuk jika file berjalan dari folder `auth/`.

Contoh:

```php
base_path('login.php')
```

### `is_post()`

Mengecek apakah request memakai method POST.

```php
return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
```

### `e($value)`

Escape output HTML dengan `htmlspecialchars()`. Function ini penting untuk mencegah output HTML berbahaya.

## Flash Message

### `set_flash($type, $message)`

Menyimpan pesan sementara ke session.

### `get_flash()`

Mengambil pesan flash lalu menghapusnya dari session.

## Role dan Navigasi

### `is_logged_in()`

Mengembalikan true jika `current_user()` tidak null.

### `has_role(...$roles)`

Mengecek apakah user login memiliki salah satu role yang diizinkan.

### `nav_target_for_user($user)`

Menentukan tujuan setelah login:

- `super_admin` ke `admin-dashboard.php`
- `store_admin` ke `store-dashboard.php`
- user biasa ke `katalog.php`

## Token Auth

### `make_auth_token()`

Membuat token random:

```php
return bin2hex(random_bytes(32));
```

### `auth_token_hash($token)`

Mengubah token menjadi hash SHA-256 sebelum disimpan di database.

## Query Produk dan Toko

### `find_popular_products($limit)`

Mengambil produk populer berdasarkan view mingguan, total view, rating, dan ID terbaru.

### `find_products($filters)`

Mengambil produk katalog dengan filter search, type, dan region.

### `find_product_by_slug($slug)`

Mengambil detail satu produk berdasarkan slug.

### `find_stores($search)`

Mengambil daftar toko aktif.

### `find_store_by_slug($slug)`

Mengambil detail toko aktif dan produk aktif toko tersebut.

## Statistik

### `track_store_visit($storeId)`

Mencatat kunjungan toko ke tabel `store_visits`.

### `track_product_view($productId, $storeId)`

Mencatat view produk ke tabel `product_views`.

### `store_dashboard_stats($storeId)`

Mengambil statistik untuk dashboard store admin.

### `super_admin_stats()`

Mengambil total user, toko, produk, dan view untuk dashboard super admin.

## Upload File

### `save_uploaded_profile_image($file, $currentPath)`

Menyimpan foto profil ke `uploads/profiles/`.

### `save_uploaded_product_images($files, $currentPath, $required)`

Menyimpan satu atau banyak gambar produk ke `uploads/products/`.

### `save_uploaded_store_image($file, $currentPath)`

Menyimpan gambar toko ke `uploads/stores/`.

## Review

### `submit_product_review($productId, $userId, $stars, $reviewText)`

Menyimpan review lalu menghitung ulang rating produk.

### `save_review_reply($reviewId, $productId, $adminUserId, $replyText)`

Menyimpan atau memperbarui balasan admin terhadap review.

### `delete_product_review()`

Menghapus review milik user sendiri.

### `delete_product_review_by_manager()`

Menghapus review oleh admin yang berwenang.

### `recalculate_product_rating($productId)`

Menghitung rata-rata rating dan jumlah review dari tabel `reviews`, lalu update tabel `products`.

## Render UI

### `render_product_card($product, $options)`

Merender kartu produk untuk beranda, katalog, dan favorit.

### `render_layout($title, $content, $options)`

Merender struktur HTML utama: head, CSS, header, navbar, flash message, main content, footer, dan script.

### `render_admin_sidebar($user, $activePage)`

Ada di `includes/admin-sidebar.php`. Merender sidebar super admin.

### `render_store_sidebar($user, $store, $activePage)`

Ada di `includes/store-sidebar.php`. Merender sidebar store admin.

