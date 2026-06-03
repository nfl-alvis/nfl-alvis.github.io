# Flow: Register Manual

## Tujuan Flow

Flow ini menjelaskan proses pendaftaran user baru melalui `register.php`.

## File yang Terlibat

- `register.php`
- `includes/bootstrap.php`
- `config/database.php`
- `config/mail.php`
- `auth/verify-email.php`

## Alur Lengkap

1. User membuka `register.php`.
2. File memuat `includes/bootstrap.php` dan `config/mail.php`.
3. Jika user sudah login, user diarahkan ke halaman sesuai role.
4. User mengisi nama, email, password, dan konfirmasi password.
5. Browser mengirim POST ke `register.php`.
6. File memastikan semua field tidak kosong.
7. File memastikan format email valid.
8. File memastikan password dan konfirmasi password sama.
9. File memanggil `create_user()` dengan role `ROLE_USER`.
10. User disimpan ke tabel `users` dengan `email_verified = 0`.
11. File memanggil `create_email_verification_token()`.
12. Token asli dikirim lewat email, sedangkan hash token disimpan di database.
13. File memanggil `send_verification_email()`.
14. User diarahkan ke `login.php` dengan pesan untuk cek email.
15. User harus klik link verifikasi sebelum bisa login manual.

## Data yang Diproses

Data dari form:

- `name`
- `email`
- `password`
- `password_confirm`

Data yang disimpan:

- nama
- email
- hash password
- role `user`
- status email belum verified
- hash token verifikasi
- expiry token verifikasi

## Session yang Digunakan

Tidak membuat session login.

Session yang dipakai:

- `flash` untuk pesan sukses/error.

## Database yang Digunakan

Tabel:

- `users`

Insert user:

```php
INSERT INTO users (...)
VALUES (...)
```

Update token:

```php
UPDATE users
SET email_verify_token = :token_hash,
    email_verify_expires = DATE_ADD(NOW(), INTERVAL ... SECOND)
WHERE id = :id
```

## Redirect yang Terjadi

- Sudah login: redirect sesuai role.
- Validasi gagal: `register.php`.
- Email verifikasi gagal dikirim: `auth/resend-verification.php?email=...`.
- Register sukses: `login.php`.

## Penjelasan Sederhana

Register seperti membuat kartu anggota baru. Tapi kartu itu belum aktif sampai user membuka link verifikasi yang dikirim ke email.

