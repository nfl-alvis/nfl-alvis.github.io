# Penjelasan File: includes/bootstrap.php

## Fungsi Utama File

`includes/bootstrap.php` adalah file pusat aplikasi. Hampir semua endpoint memuat file ini.

File ini mengatur:

- session
- koneksi database
- role
- helper redirect
- flash message
- login/session user
- proteksi halaman
- query produk dan toko
- token email/reset password
- upload gambar
- review dan balasan review
- dashboard statistik
- render layout HTML

## Alur Kerja File

1. PHP menjalankan `session_start()`.
2. File memuat `config/database.php`.
3. Role aplikasi didefinisikan.
4. Helper dasar seperti `redirect_to()` dan `e()` tersedia.
5. Function auth seperti `current_user()`, `require_login()`, dan `require_role()` tersedia.
6. Function query produk/toko/user tersedia.
7. Function upload dan review tersedia.
8. Function render layout tersedia.
9. Endpoint yang memuat file ini dapat memakai semua function tersebut.

## Penjelasan Kode Per Bagian

Session:

```php
session_start();
```

Ini membuat `$_SESSION` bisa dipakai.

Database:

```php
require_once __DIR__ . '/../config/database.php';
```

Membuat function `db()` tersedia.

Role:

```php
const ROLE_USER = 'user';
const ROLE_STORE_ADMIN = 'store_admin';
const ROLE_SUPER_ADMIN = 'super_admin';
```

Role dipakai untuk membatasi akses halaman.

Current user:

```php
$userId = $_SESSION['user_id'] ?? null;
```

User login dikenali dari session `user_id`.

Proteksi halaman:

```php
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        redirect_to('login.php');
    }
}
```

Query produk:

```php
function find_products(array $filters = []): array
```

Function ini membaca produk aktif dari toko aktif.

Token auth:

```php
function make_auth_token(): string
{
    return bin2hex(random_bytes(32));
}
```

Token dipakai untuk verifikasi email dan reset password.

Render layout:

```php
function render_layout(string $title, callable $content, array $options = []): void
```

Function ini membuat struktur HTML utama aplikasi.

## Function yang Digunakan

File ini mendefinisikan banyak function. Ringkasannya ada di [includes-functions.md](includes-functions.md).

## Variabel Penting

- `$_SESSION['user_id']`: ID user login.
- `$_SESSION['flash']`: pesan sementara.
- `$_SESSION['visitor_key']`: visitor anonim untuk statistik.
- `$user`: user aktif.
- `$filters`: filter katalog.
- `$product`: data produk.
- `$store`: data toko.

## Query Database

File ini berisi banyak query, antara lain:

- Query user login.
- Query produk katalog.
- Query detail produk.
- Query review.
- Query toko.
- Query statistik dashboard.
- Query token email/reset password.

Contoh:

```php
SELECT u.*, s.name AS store_name, s.slug AS store_slug
FROM users u
LEFT JOIN stores s ON s.id = u.store_id
WHERE u.id = :id LIMIT 1
```

## Session

Session yang digunakan:

- `user_id`
- `flash`
- `visitor_key`

## Redirect

File ini menyediakan `redirect_to()`, `require_login()`, dan `require_role()`.

## Hubungan dengan File Lain

Dipanggil oleh hampir semua file PHP project. File ini juga memanggil `config/database.php`.

