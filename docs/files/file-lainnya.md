# File PHP Lainnya

Dokumen ini menjelaskan file PHP penting lain yang bukan fokus utama auth, tetapi tetap ada di project.

## `index.php`

Halaman beranda. File ini mengambil:

- produk populer dengan `find_popular_products()`
- daftar toko dengan `find_stores()`
- toko populer dengan `find_popular_stores()`

Lalu semua data dirender melalui `render_layout()`.

## `katalog.php`

Halaman katalog produk. File ini dilindungi `require_login()`.

Alurnya:

1. Baca filter `search`, `type`, dan `region` dari query string.
2. Panggil `find_products()`.
3. Panggil `product_regions()` untuk dropdown daerah.
4. Render kartu produk.

## `product.php`

Halaman detail produk dan review.

Fitur utama:

- membaca produk berdasarkan slug
- mencatat `store_visits`
- mencatat `product_views`
- menampilkan review
- menerima submit review
- menerima balasan review oleh store admin
- menghapus review sesuai izin

## `store.php`

Punya dua mode:

- `/store.php`: direktori toko.
- `/store.php?slug=...`: detail toko.

Detail toko mewajibkan login dan mencatat kunjungan toko.

## `favorites.php`

Halaman favorit. Wajib login. Data favorit tidak disimpan di database, tetapi dikelola frontend dari tombol hati.

## `edit-profile.php`

Halaman profil user login. Bisa mengubah:

- nama
- email
- password
- foto profil

## File Super Admin

### `admin-dashboard.php`

Dashboard statistik platform.

### `admin-users.php`

Manajemen user, role, store assignment, status aktif, dan hapus user.

### `admin-stores.php`

Manajemen toko, status publik, cover image, dan delete toko beserta data turunannya.

### `admin-store-create.php`

Tambah toko baru.

### `admin-store-admin-create.php`

Buat akun store admin dan hubungkan ke toko.

### `admin-products.php`

Manajemen semua produk platform.

### `admin-add-product.php`

Tambah produk untuk toko mana pun.

## File Store Admin

### `store-dashboard.php`

Dashboard statistik toko milik store admin.

### `store-profile.php`

Edit profil toko milik store admin.

### `store-products.php`

List, edit, dan nonaktifkan produk milik toko sendiri.

### `store-add-product.php`

Tambah produk baru untuk toko sendiri.

## File Alias/Redirect Lama

Beberapa file hanya redirect:

- `lupassword.php` ke `auth/forgot-password.php`
- `rendang.php` ke `product.php?slug=rendang`
- `dashboard_admin.php` ke `admin-dashboard.php`
- `admin-user-edit.php` ke `admin-users.php?edit=...`
- `admin-store-edit.php` ke `admin-stores.php?edit=...`
- `admin-product-edit.php` ke `admin-products.php?edit=...`
- `Tambah_Makanan_Page.php` ke `store-add-product.php`

## `tentang.php`

Halaman statis tentang PusakaRasa. Memakai `render_layout()`.

## `cs.php`

Halaman customer service. File ini berbeda karena tidak memuat `includes/bootstrap.php`. HTML, navbar, dan footer ditulis langsung di file.

