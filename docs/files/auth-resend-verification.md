# Penjelasan File: auth/resend-verification.php

## Fungsi Utama File

File ini menampilkan form untuk mengirim ulang link verifikasi email.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memuat `config/mail.php`.
3. Jika user sudah login, user diarahkan ke dashboard sesuai role.
4. File membaca email dari query string untuk prefill form.
5. Saat POST, email divalidasi.
6. Jika user aktif dan belum verified, token baru dibuat.
7. Email verifikasi baru dikirim.
8. User diarahkan ke login dengan pesan generik.

## Penjelasan Kode Per Bagian

Prefill email:

```php
$prefillEmail = strtolower(trim((string) ($_GET['email'] ?? '')));
```

Bagian ini membuat form bisa otomatis terisi setelah user diarahkan dari link verifikasi gagal.

Validasi email:

```php
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Masukkan email yang valid.');
    redirect_to('auth/resend-verification.php');
}
```

Membuat token baru:

```php
$token = create_email_verification_token((int) $user['id']);
send_verification_email($user['email'], $user['name'], $token);
```

Response generik:

```php
set_flash('success', 'Jika email terdaftar dan belum diverifikasi, link verifikasi baru sudah dikirim.');
redirect_to('login.php');
```

Pesan dibuat generik agar orang lain tidak mudah menebak apakah email tertentu terdaftar.

## Function yang Digunakan

- `is_logged_in()`
- `nav_target_for_user()`
- `current_user()`
- `find_user_by_email()`
- `user_needs_email_verification()`
- `create_email_verification_token()`
- `send_verification_email()`
- `set_flash()`
- `redirect_to()`
- `render_layout()`

## Variabel Penting

- `$prefillEmail`: email dari query string.
- `$email`: email dari form POST.
- `$user`: user aktif berdasarkan email.
- `$token`: token verifikasi baru.

## Query Database

Query mencari user:

```php
SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1
```

Query update token:

```php
UPDATE users
SET email_verify_token = :token_hash,
    email_verify_expires = DATE_ADD(NOW(), INTERVAL ... SECOND),
    updated_at = NOW()
WHERE id = :id
```

## Session

Memakai flash message. Tidak membuat session login.

## Redirect

- User sudah login: redirect sesuai role.
- Email invalid: kembali ke `auth/resend-verification.php`.
- Email berhasil/generik: redirect ke `login.php`.
- Pengiriman email gagal: kembali ke resend verification dengan email.

## Hubungan dengan File Lain

- Dipakai setelah register gagal mengirim email.
- Dipakai setelah link verifikasi invalid.
- Memakai `config/mail.php`.

