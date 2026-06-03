# Flow: Verifikasi Email

## Tujuan Flow

Flow ini menjelaskan bagaimana email user diverifikasi setelah register atau kirim ulang verifikasi.

## File yang Terlibat

- `register.php`
- `auth/resend-verification.php`
- `config/mail.php`
- `auth/verify-email.php`
- `includes/bootstrap.php`

## Alur Lengkap

1. User register atau meminta kirim ulang verifikasi.
2. Aplikasi membuat token verifikasi dengan `create_email_verification_token()`.
3. Token asli dikirim ke email user.
4. Hash token disimpan ke tabel `users.email_verify_token`.
5. Waktu expired disimpan ke `users.email_verify_expires`.
6. User klik link email menuju `auth/verify-email.php?email=...&token=...`.
7. `auth/verify-email.php` membaca email dan token.
8. Jika email sudah verified, user langsung diarahkan ke login.
9. Jika belum verified, token dicek dengan `verify_user_email_token()`.
10. Token asli dari URL di-hash dan dibandingkan dengan hash di database.
11. Jika cocok dan belum expired, `email_verified` diubah menjadi `1`.
12. Token verifikasi dan expiry dihapus dari database.
13. User diarahkan ke login.

## Data yang Diproses

Data dalam link:

- `email`
- `token`

Data database:

- `email_verified`
- `email_verify_token`
- `email_verify_expires`

## Session yang Digunakan

Session yang dipakai:

- `flash`

Tidak ada session login yang dibuat.

## Database yang Digunakan

Tabel:

- `users`

Validasi token:

```php
SELECT id
FROM users
WHERE email = :email
  AND email_verify_token = :token_hash
  AND email_verify_expires >= NOW()
LIMIT 1
```

Update user:

```php
UPDATE users
SET email_verified = 1,
    email_verify_token = NULL,
    email_verify_expires = NULL
WHERE id = :id
```

## Redirect yang Terjadi

- Token valid: `login.php`.
- Email sudah verified: `login.php`.
- Token invalid/expired: `auth/resend-verification.php?email=...`.

## Penjelasan Sederhana

Verifikasi email seperti mengklik tombol "Saya benar pemilik email ini". Tanpa klik itu, akun lokal belum boleh login.

