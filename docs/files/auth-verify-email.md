# Penjelasan File: auth/verify-email.php

## Fungsi Utama File

File ini memproses link verifikasi email. Link ini dikirim setelah user register atau meminta kirim ulang verifikasi.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File membaca `email` dan `token` dari query string.
3. Jika email sudah terverifikasi, user diarahkan ke login.
4. Jika belum, file memanggil `verify_user_email_token($email, $token)`.
5. Jika token valid, email user ditandai verified.
6. Jika token invalid atau expired, user diarahkan ke halaman resend verification.

## Penjelasan Kode Per Bagian

Membaca input:

```php
$email = strtolower(trim((string) ($_GET['email'] ?? '')));
$token = trim((string) ($_GET['token'] ?? ''));
```

Email dinormalisasi menjadi huruf kecil.

Cek apakah sudah verified:

```php
if ($user && (int) ($user['email_verified'] ?? 0) === 1) {
    set_flash('success', 'Email akun Anda sudah terverifikasi. Silakan masuk.');
    redirect_to('login.php');
}
```

Jika user sudah verified, token tidak perlu diproses lagi.

Verifikasi token:

```php
if (verify_user_email_token($email, $token)) {
    set_flash('success', 'Email berhasil diverifikasi. Silakan masuk.');
    redirect_to('login.php');
}
```

Jika berhasil, status `email_verified` di database berubah menjadi `1`.

## Function yang Digunakan

- `find_user_by_email()`
- `verify_user_email_token()`
- `set_flash()`
- `redirect_to()`

## Variabel Penting

- `$email`: email dari link verifikasi.
- `$token`: token dari link verifikasi.
- `$user`: user berdasarkan email.

## Query Database

Query dilakukan oleh `verify_user_email_token()`:

```php
SELECT id
FROM users
WHERE email = :email
  AND email_verify_token = :token_hash
  AND email_verify_expires IS NOT NULL
  AND email_verify_expires >= NOW()
LIMIT 1
```

Jika valid:

```php
UPDATE users
SET email_verified = 1,
    email_verify_token = NULL,
    email_verify_expires = NULL,
    updated_at = NOW()
WHERE id = :id
```

## Session

File ini memakai flash message melalui session, tetapi tidak membuat session login.

## Redirect

- Email sudah verified: `login.php`.
- Token valid: `login.php`.
- Token invalid: `auth/resend-verification.php?email=...`.

## Hubungan dengan File Lain

- Link dibuat oleh `send_verification_email()` di `config/mail.php`.
- Token dibuat oleh `create_email_verification_token()` di `includes/bootstrap.php`.
- Jika gagal, flow lanjut ke `auth/resend-verification.php`.

