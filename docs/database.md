# Database

Project ini memakai MySQL dengan koneksi PDO dari `config/database.php`. Nama database yang dipakai di kode adalah `pusakarasa`.

Function utama untuk koneksi database adalah:

```php
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}
```

Maksudnya: aplikasi membuat satu koneksi PDO per request, lalu koneksi itu dipakai oleh semua query.

## Tabel `users`

### Fungsi Tabel

Menyimpan akun semua pengguna aplikasi: user biasa, store admin, dan super admin.

### Kolom Penting

- `id`: ID user.
- `name`: nama user.
- `email`: email unik untuk login.
- `password_hash`: password yang sudah di-hash, bukan password asli.
- `profile_image`: foto profil lokal.
- `google_id`: ID dari akun Google.
- `picture`: foto profil dari Google.
- `auth_provider`: `local` atau `google`.
- `email_verified`: status verifikasi email.
- `email_verify_token`: hash token verifikasi email.
- `email_verify_expires`: waktu kedaluwarsa token verifikasi.
- `reset_token`: hash token reset password.
- `reset_expires`: waktu kedaluwarsa token reset password.
- `role`: `user`, `store_admin`, atau `super_admin`.
- `store_id`: toko yang dikelola jika role adalah `store_admin`.
- `is_active`: status aktif akun.

### Fitur yang Memakai

- Login manual.
- Register manual.
- Verifikasi email.
- Forgot password.
- Reset password.
- Google OAuth.
- Proteksi halaman berdasarkan role.
- Dashboard super admin.
- Dashboard store admin.
- Review produk.

### Query Penting

Login manual mengambil user aktif berdasarkan email:

```php
$sql = 'SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1';
```

Register membuat user baru:

```php
INSERT INTO users
    (name, email, password_hash, auth_provider, email_verified, role, store_id, is_active, created_at, updated_at)
VALUES
    (:name, :email, :password_hash, :auth_provider, :email_verified, :role, :store_id, 1, NOW(), NOW())
```

Reset password mengubah hash password dan menghapus token reset:

```php
UPDATE users
SET password_hash = :password_hash,
    reset_token = NULL,
    reset_expires = NULL,
    updated_at = NOW()
WHERE id = :id
```

## Tabel `stores`

### Fungsi Tabel

Menyimpan data toko kuliner yang produknya tampil di katalog.

### Kolom Penting

- `id`: ID toko.
- `name`: nama toko.
- `slug`: slug untuk URL detail toko.
- `region`: daerah/provinsi toko.
- `address`: alamat toko.
- `whatsapp`: nomor WhatsApp.
- `instagram`: akun Instagram.
- `description`: deskripsi toko.
- `operating_hours`: jam operasional, biasanya JSON hasil form.
- `cover_image`: gambar toko.
- `is_open`: status buka/tutup hari ini.
- `is_active`: apakah toko tampil di halaman publik.

### Fitur yang Memakai

- Direktori toko.
- Detail toko.
- Dashboard store admin.
- Manajemen toko super admin.
- Relasi store admin ke toko.
- Statistik kunjungan toko.

### Query Penting

Detail toko berdasarkan slug:

```php
SELECT * FROM stores
WHERE slug = :slug AND is_active = 1
LIMIT 1
```

Update profil toko oleh store admin:

```php
UPDATE stores
SET name = :name,
    region = :region,
    address = :address,
    whatsapp = :whatsapp,
    instagram = :instagram,
    description = :description,
    operating_hours = :operating_hours,
    is_open = :is_open,
    updated_at = NOW()
WHERE id = :id
```

## Tabel `products`

### Fungsi Tabel

Menyimpan produk makanan/minuman yang tampil di katalog.

### Kolom Penting

- `id`: ID produk.
- `store_id`: toko pemilik produk.
- `name`: nama produk.
- `slug`: slug untuk URL detail produk.
- `type`: `Makanan` atau `Minuman`.
- `region`: asal daerah produk.
- `short_description`: deskripsi singkat untuk kartu katalog.
- `long_description`: deskripsi panjang di detail produk.
- `price_display`: harga dalam format tampilan, misalnya `25.000`.
- `rating`: rating rata-rata hasil review.
- `review_count`: jumlah review.
- `tag_label`: label/tag produk.
- `image_path`: gambar utama produk.
- `is_active`: status tampil di katalog.

### Fitur yang Memakai

- Beranda produk populer.
- Katalog.
- Detail produk.
- Favorite frontend.
- Dashboard toko.
- Manajemen produk super admin.
- Manajemen produk store admin.
- Review dan rating.

### Query Penting

Produk katalog aktif:

```php
SELECT p.*, s.name AS store_name, s.slug AS store_slug
FROM products p
INNER JOIN stores s ON s.id = p.store_id
WHERE p.is_active = 1 AND s.is_active = 1
ORDER BY p.name ASC
```

Produk detail berdasarkan slug:

```php
SELECT p.*, s.name AS store_name, s.slug AS store_slug
FROM products p
INNER JOIN stores s ON s.id = p.store_id
WHERE p.slug = :slug AND p.is_active = 1 AND s.is_active = 1
LIMIT 1
```

## Tabel `product_images`

### Fungsi Tabel

Menyimpan banyak gambar untuk satu produk.

### Kolom Penting

- `id`: ID gambar.
- `product_id`: produk pemilik gambar.
- `image_path`: path file gambar.
- `sort_order`: urutan gambar.
- `created_at`: waktu upload.

### Fitur yang Memakai

- Galeri detail produk.
- Upload multi-gambar di tambah/edit produk.
- Kontrol hapus gambar produk.

### Query Penting

Mengambil gambar produk:

```php
SELECT image_path
FROM product_images
WHERE product_id = :product_id
ORDER BY sort_order ASC, id ASC
```

Mengganti gambar produk:

```php
DELETE FROM product_images WHERE product_id = :product_id
```

lalu insert ulang setiap path gambar.

## Tabel `reviews`

### Fungsi Tabel

Menyimpan review user untuk produk.

### Kolom Penting

- `id`: ID review.
- `product_id`: produk yang direview.
- `user_id`: user pembuat review.
- `stars`: rating 1 sampai 5.
- `review_text`: isi review.
- `created_at`: waktu dibuat.
- `updated_at`: waktu update.

### Fitur yang Memakai

- Form review di `product.php`.
- Daftar review produk.
- Hitung ulang rating produk.
- Hapus review oleh pemilik review, store admin, atau super admin.

### Query Penting

Insert review:

```php
INSERT INTO reviews (product_id, user_id, stars, review_text, created_at, updated_at)
VALUES (:product_id, :user_id, :stars, :review_text, NOW(), NOW())
```

Hitung ulang rating:

```php
SELECT COALESCE(AVG(stars), 0) AS rating, COUNT(id) AS review_count
FROM reviews
WHERE product_id = :product_id
```

## Tabel `review_replies`

### Fungsi Tabel

Menyimpan balasan admin terhadap review. Satu review hanya punya satu balasan karena ada unique key `review_id`.

### Kolom Penting

- `id`: ID balasan.
- `review_id`: review yang dibalas.
- `admin_user_id`: admin yang membalas.
- `reply_text`: isi balasan.
- `created_at`: waktu dibuat.
- `updated_at`: waktu update.

### Fitur yang Memakai

- Balasan review oleh store admin.
- Hapus balasan review oleh admin berwenang.
- Tampilan review di `product.php`.

### Query Penting

Insert atau update balasan:

```php
INSERT INTO review_replies (review_id, admin_user_id, reply_text, created_at, updated_at)
VALUES (:review_id, :admin_user_id, :reply_text, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    admin_user_id = VALUES(admin_user_id),
    reply_text = VALUES(reply_text),
    updated_at = NOW()
```

## Tabel `store_visits`

### Fungsi Tabel

Mencatat kunjungan toko per session per hari.

### Kolom Penting

- `store_id`: toko yang dikunjungi.
- `session_key`: ID visitor dari session.
- `visit_date`: tanggal kunjungan.
- `visited_at`: waktu kunjungan.

### Fitur yang Memakai

- Statistik toko populer.
- Dashboard store admin.
- Detail toko.
- Detail produk, karena membuka produk juga mencatat kunjungan toko.

### Query Penting

```php
INSERT IGNORE INTO store_visits (store_id, session_key, visit_date, visited_at)
VALUES (:store_id, :session_key, CURDATE(), NOW())
```

`INSERT IGNORE` dipakai agar kunjungan session yang sama pada hari yang sama tidak dihitung berulang.

## Tabel `product_views`

### Fungsi Tabel

Mencatat view produk per session per hari.

### Kolom Penting

- `product_id`: produk yang dilihat.
- `store_id`: toko pemilik produk.
- `session_key`: ID visitor dari session.
- `view_date`: tanggal view.
- `viewed_at`: waktu view.

### Fitur yang Memakai

- Produk populer di beranda.
- Dashboard store admin.
- Statistik produk paling dilihat.

### Query Penting

```php
INSERT IGNORE INTO product_views (product_id, store_id, session_key, view_date, viewed_at)
VALUES (:product_id, :store_id, :session_key, CURDATE(), NOW())
```

## Tabel Wilayah Indonesia

File `indonesia.sql` menyediakan tabel:

- `provinces`
- `regencies`
- `districts`
- `villages`

Yang paling terlihat dipakai oleh aplikasi adalah `provinces`, terutama untuk dropdown region di form toko dan produk.

Query penting:

```php
SELECT name FROM provinces ORDER BY name ASC
```

Jika tabel `provinces` tidak ada, aplikasi mencoba membaca nama provinsi langsung dari file `indonesia.sql`.

