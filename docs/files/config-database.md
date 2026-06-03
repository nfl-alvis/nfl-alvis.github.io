# Penjelasan File: config/database.php

## Fungsi Utama File

File ini menyediakan function `db()` untuk koneksi ke database MySQL.

## Alur Kerja File

1. File mendefinisikan function `db()`.
2. Saat `db()` dipanggil, function mengecek apakah koneksi PDO sudah dibuat.
3. Jika sudah ada, koneksi lama dikembalikan.
4. Jika belum, function membuat DSN MySQL.
5. Function membuat object PDO.
6. Object PDO dikembalikan.

## Penjelasan Kode Per Bagian

Static koneksi:

```php
static $pdo = null;
```

Static variable membuat koneksi dipakai ulang dalam satu request.

Konfigurasi:

```php
$host = '127.0.0.1';
$port = 3306;
$database = 'pusakarasa';
$username = 'root';
$password = '';
```

Ini konfigurasi database. Berbeda dari Google dan mail, bagian ini belum memakai `.env`.

Membuat PDO:

```php
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

`ERRMODE_EXCEPTION` membuat error database lebih mudah ditangani dengan `try/catch`.

## Function yang Digunakan

File ini mendefinisikan:

- `db()`

## Variabel Penting

- `$host`
- `$port`
- `$database`
- `$username`
- `$password`
- `$dsn`
- `$pdo`

## Query Database

Tidak ada query SQL di file ini. File ini hanya membuat koneksi.

## Session

Tidak memakai session.

## Redirect

Tidak ada redirect.

## Hubungan dengan File Lain

File ini dimuat oleh `includes/bootstrap.php`. Setelah itu, semua file dapat memakai `db()`.

