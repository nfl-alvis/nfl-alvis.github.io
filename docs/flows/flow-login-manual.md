# Flow: Login Manual

## Tujuan Flow

Flow ini menjelaskan cara user login memakai email dan password melalui `login.php`.

## File yang Terlibat

- `login.php`
- `includes/bootstrap.php`
- `config/database.php`

## Alur Lengkap

1. User membuka `login.php`.
2. `login.php` memuat `includes/bootstrap.php`.
3. `bootstrap.php` menjalankan `session_start()` dan memuat `config/database.php`.
4. Jika user sudah login, user langsung diarahkan ke halaman sesuai role.
5. Jika belum login, user melihat form login.
6. User mengisi email dan password.
7. Browser mengirim POST ke `login.php`.
8. `login.php` membaca `$_POST['email']` dan `$_POST['password']`.
9. Jika salah satu kosong, user kembali ke login.
10. `login.php` memanggil `authenticate_user($email, $password)`.
11. `authenticate_user()` mencari user aktif berdasarkan email.
12. Jika user tidak ada, login gagal.
13. Jika user ada, password dicek dengan `password_verify()`.
14. Jika password salah, login gagal.
15. Jika user lokal belum verifikasi email, login ditolak.
16. Jika semua valid, `login_user($user)` menyimpan `$_SESSION['user_id']`.
17. User diarahkan ke halaman sesuai role.

## Data yang Diproses

Data dari form:

- `email`
- `password`

Data dari database:

- `users.email`
- `users.password_hash`
- `users.email_verified`
- `users.role`
- `users.is_active`

## Session yang Digunakan

Session yang dibuat:

- `$_SESSION['user_id']`

Session flash:

- dibuat jika login gagal atau sukses.

## Database yang Digunakan

Tabel:

- `users`

Query utama:

```php
SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1
```

Password tidak dicocokkan dengan query SQL. Password dicek oleh PHP:

```php
password_verify($password, $user['password_hash'])
```

## Redirect yang Terjadi

- Sudah login: redirect sesuai role.
- Email/password kosong: `login.php`.
- Login gagal: `login.php`.
- Email belum verified: `login.php`.
- Login sukses:
  - super admin: `admin-dashboard.php`
  - store admin: `store-dashboard.php`
  - user biasa: `katalog.php`

## Penjelasan Sederhana

Login manual seperti penjaga pintu. User memberikan email dan password. Aplikasi mencari nama user di daftar tamu database. Jika password cocok dan akun aktif, user diberi tanda masuk berupa session `user_id`.

