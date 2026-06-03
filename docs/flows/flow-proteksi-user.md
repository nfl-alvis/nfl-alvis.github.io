# Flow: Proteksi User

## Tujuan Flow

Flow ini menjelaskan bagaimana halaman yang butuh login dilindungi agar tidak bisa dibuka oleh pengunjung biasa.

## File yang Terlibat

- `includes/bootstrap.php`
- `katalog.php`
- `product.php`
- `favorites.php`
- `edit-profile.php`
- `store.php` untuk detail toko

## Alur Lengkap

1. User membuka halaman protected, misalnya `katalog.php`.
2. File memuat `includes/bootstrap.php`.
3. File memanggil `require_login()`.
4. `require_login()` memanggil `is_logged_in()`.
5. `is_logged_in()` memanggil `current_user()`.
6. `current_user()` membaca `$_SESSION['user_id']`.
7. Jika session tidak ada, user dianggap belum login.
8. Jika session ada, user dicek ke database.
9. Jika user tidak ditemukan, session dihapus.
10. Jika user valid, halaman lanjut berjalan.
11. Jika tidak valid, user diarahkan ke `login.php`.

## Data yang Diproses

Data session:

- `user_id`

Data database:

- data user berdasarkan `users.id`
- data toko jika user punya `store_id`

## Session yang Digunakan

Session dibaca:

- `$_SESSION['user_id']`

Session dibuat jika akses ditolak:

- `$_SESSION['flash']`

Session dihapus jika user dari session tidak ditemukan:

- `$_SESSION['user_id']`

## Database yang Digunakan

Tabel:

- `users`
- `stores`

Query:

```php
SELECT u.*, s.name AS store_name, s.slug AS store_slug
FROM users u
LEFT JOIN stores s ON s.id = u.store_id
WHERE u.id = :id
LIMIT 1
```

## Redirect yang Terjadi

Jika belum login:

```text
login.php
```

Jika sudah login:

Halaman lanjut diproses.

## Penjelasan Sederhana

Proteksi user seperti penjaga pintu yang meminta tiket. Tiketnya adalah session `user_id`. Kalau tidak ada tiket, user diarahkan ke loket login.

