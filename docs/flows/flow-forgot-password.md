# Flow: Forgot Password

## Tujuan Flow

Flow ini menjelaskan proses user meminta link reset password.

## File yang Terlibat

- `auth/forgot-password.php`
- `includes/bootstrap.php`
- `config/mail.php`
- `auth/reset-password.php`

## Alur Lengkap

1. User membuka `auth/forgot-password.php`.
2. User mengisi email.
3. Browser mengirim POST.
4. File memvalidasi format email.
5. File mencari user aktif berdasarkan email.
6. Jika user ada dan email sudah verified, aplikasi membuat token reset password.
7. Hash token disimpan ke database.
8. Token asli dikirim ke email user.
9. User selalu menerima pesan generik.
10. User diarahkan ke `login.php`.

## Data yang Diproses

Data dari form:

- `email`

Data database:

- `users.email`
- `users.is_active`
- `users.email_verified`
- `users.reset_token`
- `users.reset_expires`

## Session yang Digunakan

Session:

- `flash`

Tidak membuat session login.

## Database yang Digunakan

Tabel:

- `users`

Query user:

```php
SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1
```

Update token reset:

```php
UPDATE users
SET reset_token = :token_hash,
    reset_expires = DATE_ADD(NOW(), INTERVAL ... SECOND)
WHERE id = :id
```

## Redirect yang Terjadi

- User sudah login: redirect sesuai role.
- Email invalid: `auth/forgot-password.php`.
- Submit selesai: `login.php`.

## Penjelasan Sederhana

Forgot password seperti meminta kunci sementara. Aplikasi mengirim kunci ke email, tetapi tidak memberi tahu orang luar apakah email itu benar-benar terdaftar.

