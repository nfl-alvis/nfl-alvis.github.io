# Flow: Reset Password

## Tujuan Flow

Flow ini menjelaskan cara user mengganti password memakai link reset password.

## File yang Terlibat

- `auth/forgot-password.php`
- `config/mail.php`
- `auth/reset-password.php`
- `includes/bootstrap.php`

## Alur Lengkap

1. User menerima email reset password.
2. User klik link menuju `auth/reset-password.php?email=...&token=...`.
3. File membaca email dan token dari URL.
4. File mengecek token ke database.
5. Jika token invalid atau expired, user diarahkan ke forgot password.
6. Jika token valid, form password baru ditampilkan.
7. User mengisi password baru dan konfirmasi password.
8. Browser mengirim POST ke `auth/reset-password.php`.
9. File membaca email dan token dari hidden input.
10. File memastikan password dan konfirmasi tidak kosong.
11. File memastikan password sama dengan konfirmasi.
12. File memanggil `reset_user_password_with_token()`.
13. Password baru di-hash.
14. Hash password disimpan di database.
15. Token reset dihapus.
16. User diarahkan ke login.

## Data yang Diproses

Data dari URL/form:

- `email`
- `token`
- `password`
- `password_confirm`

Data database:

- `reset_token`
- `reset_expires`
- `password_hash`

## Session yang Digunakan

Session:

- `flash`

Tidak membuat session login.

## Database yang Digunakan

Tabel:

- `users`

Validasi token:

```php
SELECT *
FROM users
WHERE email = :email
  AND reset_token = :token_hash
  AND reset_expires >= NOW()
  AND email_verified = 1
  AND is_active = 1
LIMIT 1
```

Update password:

```php
UPDATE users
SET password_hash = :password_hash,
    reset_token = NULL,
    reset_expires = NULL
WHERE id = :id
```

## Redirect yang Terjadi

- Token invalid: `auth/forgot-password.php`.
- Password kosong/tidak sama: kembali ke halaman reset dengan email dan token.
- Reset sukses: `login.php`.

## Penjelasan Sederhana

Reset password seperti memakai tiket sekali jalan. Tiket hanya berlaku satu jam. Setelah password diganti, tiket langsung disobek agar tidak bisa dipakai lagi.

