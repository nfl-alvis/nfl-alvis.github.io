# Flow: Logout

## Tujuan Flow

Flow ini menjelaskan cara user keluar dari aplikasi.

## File yang Terlibat

- `logout.php`
- `includes/bootstrap.php`

## Alur Lengkap

1. User klik link logout.
2. Browser membuka `logout.php`.
3. File memuat `includes/bootstrap.php`.
4. `logout.php` memanggil `logout_user()`.
5. `logout_user()` menghapus `$_SESSION['user_id']`.
6. File membuat flash message sukses.
7. User diarahkan ke `index.php`.

## Data yang Diproses

Tidak ada data form.

Data session:

- `user_id`

## Session yang Digunakan

Session yang dihapus:

- `$_SESSION['user_id']`

Session yang dibuat:

- `$_SESSION['flash']`

## Database yang Digunakan

Tidak ada query database.

## Redirect yang Terjadi

Selalu redirect ke:

```text
index.php
```

## Penjelasan Sederhana

Logout seperti mengembalikan kartu akses. Setelah kartu akses dihapus, halaman yang butuh login tidak bisa dibuka lagi sampai user login ulang.

