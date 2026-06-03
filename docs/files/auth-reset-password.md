# Penjelasan File: auth/reset-password.php

## Fungsi Utama File

File ini memvalidasi token reset password dan menyimpan password baru.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. Jika user sudah login, user diarahkan ke dashboard sesuai role.
3. File membaca email dan token dari GET atau POST.
4. File mengecek apakah token valid dan belum expired.
5. Jika token tidak valid, user diarahkan ke forgot password.
6. Saat POST, password dan konfirmasi password divalidasi.
7. Password baru di-hash dan disimpan.
8. Token reset dihapus dari database.
9. User diarahkan ke login.

## Penjelasan Kode Per Bagian

Membaca email dan token:

```php
$email = strtolower(trim((string) ($_POST['email'] ?? $_GET['email'] ?? '')));
$token = trim((string) ($_POST['token'] ?? $_GET['token'] ?? ''));
```

Token bisa datang dari query string saat halaman pertama dibuka, atau dari hidden input saat form disubmit.

Validasi token:

```php
$resetUser = find_user_by_reset_token($email, $token);

if (!$resetUser) {
    set_flash('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
    redirect_to('auth/forgot-password.php');
}
```

Mengecek password:

```php
if ($password !== $passwordConfirm) {
    set_flash('error', 'Konfirmasi kata sandi tidak sesuai.');
    redirect_to('auth/reset-password.php?' . http_build_query(['email' => $email, 'token' => $token]));
}
```

Menyimpan password baru:

```php
if (!reset_user_password_with_token($email, $token, $password)) {
    set_flash('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
    redirect_to('auth/forgot-password.php');
}
```

## Function yang Digunakan

- `is_logged_in()`
- `nav_target_for_user()`
- `current_user()`
- `find_user_by_reset_token()`
- `reset_user_password_with_token()`
- `set_flash()`
- `redirect_to()`
- `render_layout()`
- `e()`

## Variabel Penting

- `$email`: email dari link/form.
- `$token`: token reset password.
- `$resetUser`: user pemilik token.
- `$password`: password baru.
- `$passwordConfirm`: konfirmasi password baru.

## Query Database

Validasi token:

```php
SELECT *
FROM users
WHERE email = :email
  AND reset_token = :token_hash
  AND reset_expires IS NOT NULL
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
    reset_expires = NULL,
    updated_at = NOW()
WHERE id = :id
```

## Session

Memakai flash message. Tidak membuat session login.

## Redirect

- User sudah login: redirect sesuai role.
- Token invalid: `auth/forgot-password.php`.
- Password kosong/tidak sama: kembali ke `auth/reset-password.php?email=...&token=...`.
- Reset sukses: `login.php`.

## Hubungan dengan File Lain

- Link menuju file ini dibuat oleh `send_password_reset_email()` di `config/mail.php`.
- Token dibuat di `auth/forgot-password.php`.

