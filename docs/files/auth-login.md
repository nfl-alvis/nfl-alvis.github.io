# Penjelasan File: login.php

## Fungsi Utama File

File `login.php` digunakan untuk menampilkan form login manual dan memproses login memakai email serta password.

Jika login berhasil, file ini membuat session login melalui `login_user($user)` lalu mengarahkan user ke halaman sesuai role:

- `super_admin` ke `admin-dashboard.php`
- `store_admin` ke `store-dashboard.php`
- `user` ke `katalog.php`

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. Jika user sudah login, user langsung diarahkan ke dashboard sesuai role.
3. Jika request adalah POST, file membaca email dan password dari form.
4. Email dan password divalidasi agar tidak kosong.
5. File memanggil `authenticate_user($email, $password)`.
6. Jika user tidak ditemukan atau password salah, user kembali ke halaman login.
7. Jika user lokal belum verifikasi email, login ditolak.
8. Jika valid, session `user_id` dibuat.
9. User diarahkan ke halaman tujuan sesuai role.
10. Jika request GET, file menampilkan form login.

## Penjelasan Kode Per Bagian

Memuat bootstrap:

```php
require_once __DIR__ . '/includes/bootstrap.php';
```

Artinya file ini membutuhkan semua helper seperti `is_logged_in()`, `authenticate_user()`, `login_user()`, dan `redirect_to()`.

Mencegah user login membuka form login lagi:

```php
if (is_logged_in()) {
  redirect_to(nav_target_for_user(current_user()));
}
```

Jika user sudah login, tidak perlu login ulang.

Membaca input:

```php
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
```

Email di-trim agar spasi di awal/akhir tidak ikut diproses.

Autentikasi:

```php
$user = authenticate_user($email, $password);
if (!$user) {
  set_flash('error', 'Email atau kata sandi tidak cocok.');
  redirect_to('login.php');
}
```

Jika email atau password salah, user mendapat pesan error.

Validasi email verified:

```php
if (user_needs_email_verification($user)) {
  set_flash('error', 'Email belum diverifikasi. Silakan cek email Anda atau kirim ulang verifikasi.');
  redirect_to('login.php');
}
```

User lokal wajib melakukan verifikasi email sebelum login.

Membuat session login:

```php
login_user($user);
set_flash('success', 'Berhasil masuk ke akun Anda.');
redirect_to(nav_target_for_user($user));
```

`login_user()` menyimpan `user_id` ke session.

## Function yang Digunakan

- `is_logged_in()`
- `current_user()`
- `nav_target_for_user()`
- `is_post()`
- `authenticate_user()`
- `user_needs_email_verification()`
- `login_user()`
- `set_flash()`
- `redirect_to()`
- `render_layout()`
- `base_path()`
- `e()`

## Variabel Penting

- `$email`: email dari form login.
- `$password`: password dari form login.
- `$user`: data user dari database jika login valid.

## Query Database

Query utama tidak ditulis langsung di `login.php`, tetapi berada di `authenticate_user()` dan `find_user_by_email()` di `includes/bootstrap.php`.

Query mencari user:

```php
SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1
```

Lalu password dicek dengan `password_verify()`.

## Session

Session yang dibuat:

- `$_SESSION['user_id']`

Session ini dibuat oleh `login_user($user)`.

## Redirect

- Sudah login: redirect ke hasil `nav_target_for_user()`.
- Email/password kosong: redirect ke `login.php`.
- Email/password salah: redirect ke `login.php`.
- Email belum verified: redirect ke `login.php`.
- Login berhasil: redirect sesuai role.

## Hubungan dengan File Lain

- Memakai helper dari `includes/bootstrap.php`.
- Link Google login menuju `auth/google-login.php`.
- Link lupa password menuju `auth/forgot-password.php`.
- Link kirim ulang verifikasi menuju `auth/resend-verification.php`.
- Link daftar menuju `register.php`.

