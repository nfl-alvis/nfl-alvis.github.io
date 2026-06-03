# Penjelasan File: register.php

## Fungsi Utama File

File `register.php` digunakan untuk menampilkan form pendaftaran user biasa dan memproses register manual.

User yang dibuat dari file ini selalu role `user`, bukan admin.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memuat `config/mail.php`.
3. Jika user sudah login, user diarahkan ke halaman sesuai role.
4. Saat POST, file membaca nama, email, password, dan konfirmasi password.
5. Semua field wajib diisi.
6. Email harus valid.
7. Password dan konfirmasi password harus sama.
8. File membuat user baru dengan `email_verified = false`.
9. File membuat token verifikasi email.
10. File mengirim email verifikasi.
11. User diarahkan ke login untuk menunggu verifikasi.

## Penjelasan Kode Per Bagian

Memuat helper:

```php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/config/mail.php';
```

`bootstrap.php` menyediakan helper user dan database. `mail.php` menyediakan function email.

Membaca input:

```php
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$passwordConfirm = $_POST['password_confirm'] ?? '';
```

Validasi email:

```php
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Format email tidak valid.');
    redirect_to('register.php');
}
```

Membuat user:

```php
$userId = create_user($name, $email, $password, ROLE_USER, null, false);
$token = create_email_verification_token($userId);
```

Parameter terakhir `false` berarti email user belum diverifikasi.

Mengirim email:

```php
send_verification_email($email, $name, $token);
```

Jika email gagal dikirim, user diarahkan ke halaman resend verification.

## Function yang Digunakan

- `is_logged_in()`
- `nav_target_for_user()`
- `current_user()`
- `is_post()`
- `create_user()`
- `create_email_verification_token()`
- `send_verification_email()`
- `set_flash()`
- `redirect_to()`
- `render_layout()`

## Variabel Penting

- `$name`: nama user.
- `$email`: email user.
- `$password`: password user.
- `$passwordConfirm`: konfirmasi password.
- `$userId`: ID user baru.
- `$token`: token verifikasi email.

## Query Database

Query insert user dilakukan oleh `create_user()`:

```php
INSERT INTO users
    (name, email, password_hash, auth_provider, email_verified, role, store_id, is_active, created_at, updated_at)
VALUES
    (:name, :email, :password_hash, :auth_provider, :email_verified, :role, :store_id, 1, NOW(), NOW())
```

Query token verifikasi dilakukan oleh `create_email_verification_token()`:

```php
UPDATE users
SET email_verify_token = :token_hash,
    email_verify_expires = DATE_ADD(NOW(), INTERVAL ... SECOND),
    updated_at = NOW()
WHERE id = :id
```

## Session

File ini membaca session login melalui `is_logged_in()`, tetapi tidak membuat session login. User baru harus verifikasi email lalu login.

## Redirect

- User sudah login: redirect sesuai role.
- Validasi gagal: redirect ke `register.php`.
- Email verifikasi gagal dikirim: redirect ke `auth/resend-verification.php?email=...`.
- Register sukses: redirect ke `login.php`.

## Hubungan dengan File Lain

- Memakai `includes/bootstrap.php` untuk database dan helper.
- Memakai `config/mail.php` untuk email.
- Hasil link email mengarah ke `auth/verify-email.php`.
- Jika email gagal, diarahkan ke `auth/resend-verification.php`.

