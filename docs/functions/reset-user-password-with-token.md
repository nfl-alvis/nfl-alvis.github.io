# Function: reset_user_password_with_token()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini mengganti password user jika token reset password valid.

## Parameter

- `$email`: email user.
- `$token`: token reset password dari link email.
- `$password`: password baru.

## Return Value

Mengembalikan boolean:

- `true` jika password berhasil diganti.
- `false` jika token tidak valid.

## Penjelasan Kode

Potongan penting:

```php
$user = find_user_by_reset_token($email, $token);
```

Function ini mencari user berdasarkan email dan token reset yang masih berlaku.

```php
if (!$user) {
    return false;
}
```

Jika token salah atau expired, password tidak diganti.

```php
SET password_hash = :password_hash,
    reset_token = NULL,
    reset_expires = NULL
```

Password baru disimpan sebagai hash, lalu token reset dihapus agar tidak bisa dipakai lagi.

## Contoh Pemakaian

Di `auth/reset-password.php`:

```php
if (!reset_user_password_with_token($email, $token, $password)) {
    set_flash('error', 'Link reset password tidak valid atau sudah kedaluwarsa.');
    redirect_to('auth/forgot-password.php');
}
```

## File yang Memanggil Function Ini

- `auth/reset-password.php`

