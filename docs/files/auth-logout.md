# Penjelasan File: logout.php

## Fungsi Utama File

File ini mengeluarkan user dari sesi login.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memanggil `logout_user()`.
3. File membuat flash message sukses.
4. User diarahkan ke beranda.

## Penjelasan Kode Per Bagian

Memuat bootstrap:

```php
require_once __DIR__ . '/includes/bootstrap.php';
```

Menghapus session:

```php
logout_user();
```

Function ini menghapus `$_SESSION['user_id']`.

Redirect:

```php
set_flash('success', 'Anda telah keluar dari sesi.');
redirect_to('index.php');
```

## Function yang Digunakan

- `logout_user()`
- `set_flash()`
- `redirect_to()`

## Variabel Penting

Tidak ada variabel khusus.

## Query Database

Tidak ada query database.

## Session

Session yang dihapus:

- `$_SESSION['user_id']`

Session yang dibuat:

- `$_SESSION['flash']`

## Redirect

Selalu redirect ke `index.php`.

## Hubungan dengan File Lain

Link logout muncul di layout umum, admin sidebar, dan store sidebar.

