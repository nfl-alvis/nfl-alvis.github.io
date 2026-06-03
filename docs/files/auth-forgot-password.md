# Penjelasan File: auth/forgot-password.php

## Fungsi Utama File

File ini menangani permintaan reset password. User memasukkan email, lalu sistem mengirim link reset password jika email terdaftar dan sudah verified.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memuat `config/mail.php`.
3. Jika user sudah login, user diarahkan ke dashboard sesuai role.
4. Saat POST, email divalidasi.
5. File mencari user aktif berdasarkan email.
6. Jika user ada dan email sudah verified, token reset password dibuat.
7. Email reset password dikirim.
8. User diarahkan ke login dengan pesan generik.

## Penjelasan Kode Per Bagian

Membaca email:

```php
$email = strtolower(trim((string) ($_POST['email'] ?? '')));
```

Validasi email:

```php
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Masukkan email yang valid.');
    redirect_to('auth/forgot-password.php');
}
```

Mencari user:

```php
$user = find_user_by_email($email, true);
```

Parameter `true` berarti hanya mencari user aktif.

Membuat dan mengirim token:

```php
$token = create_password_reset_token((int) $user['id']);
send_password_reset_email($user['email'], $user['name'], $token);
```

Pesan sukses dibuat generik:

```php
set_flash('success', 'Jika email terdaftar dan sudah terverifikasi, link reset password akan dikirim.');
redirect_to('login.php');
```

## Function yang Digunakan

- `is_logged_in()`
- `nav_target_for_user()`
- `current_user()`
- `find_user_by_email()`
- `create_password_reset_token()`
- `send_password_reset_email()`
- `set_flash()`
- `redirect_to()`
- `render_layout()`

## Variabel Penting

- `$email`: email dari form.
- `$user`: user aktif berdasarkan email.
- `$token`: token reset password.

## Query Database

Query user:

```php
SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1
```

Query update token reset:

```php
UPDATE users
SET reset_token = :token_hash,
    reset_expires = DATE_ADD(NOW(), INTERVAL ... SECOND),
    updated_at = NOW()
WHERE id = :id
```

## Session

Memakai flash message. Tidak membuat session login.

## Redirect

- User sudah login: redirect sesuai role.
- Email invalid: `auth/forgot-password.php`.
- Setelah submit valid/generik: `login.php`.

## Hubungan dengan File Lain

- Link reset password yang dikirim mengarah ke `auth/reset-password.php`.
- Email dikirim oleh `config/mail.php`.

