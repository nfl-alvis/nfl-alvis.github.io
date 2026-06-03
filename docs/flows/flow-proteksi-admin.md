# Flow: Proteksi Admin

## Tujuan Flow

Flow ini menjelaskan bagaimana halaman admin dilindungi berdasarkan role.

## File yang Terlibat

- `includes/bootstrap.php`
- `admin-dashboard.php`
- `admin-users.php`
- `admin-stores.php`
- `admin-products.php`
- `admin-add-product.php`
- `admin-store-create.php`
- `admin-store-admin-create.php`
- `store-dashboard.php`
- `store-profile.php`
- `store-products.php`
- `store-add-product.php`

## Alur Lengkap

1. User membuka halaman admin.
2. File memuat `includes/bootstrap.php`.
3. Halaman super admin memanggil `require_role(ROLE_SUPER_ADMIN)`.
4. Halaman store admin memanggil `require_role(ROLE_STORE_ADMIN)`.
5. `require_role()` terlebih dahulu memanggil `require_login()`.
6. Jika belum login, user diarahkan ke `login.php`.
7. Jika sudah login, function mengecek role user.
8. Jika role cocok, halaman lanjut berjalan.
9. Jika role tidak cocok, user mendapat flash error.
10. User diarahkan ke `index.php`.

## Data yang Diproses

Data session:

- `user_id`

Data database:

- `users.role`
- `users.store_id`

Untuk store admin, `store_id` penting karena data toko dan produk dibatasi pada toko tersebut.

## Session yang Digunakan

Session dibaca:

- `$_SESSION['user_id']`

Session flash:

- dibuat jika user belum login atau role tidak cocok.

## Database yang Digunakan

Tabel:

- `users`
- `stores`

Query user:

```php
SELECT u.*, s.name AS store_name, s.slug AS store_slug
FROM users u
LEFT JOIN stores s ON s.id = u.store_id
WHERE u.id = :id
LIMIT 1
```

## Redirect yang Terjadi

- Belum login: `login.php`.
- Login tapi role salah: `index.php`.
- Role benar: halaman admin lanjut.

## Penjelasan Sederhana

Proteksi admin seperti pintu dengan kartu akses khusus. Semua user login punya kartu masuk biasa, tetapi halaman admin membutuhkan kartu dengan level tertentu: super admin atau store admin.

