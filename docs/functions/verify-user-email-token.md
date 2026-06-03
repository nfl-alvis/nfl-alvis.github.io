# Function: verify_user_email_token()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini mengecek apakah token verifikasi email valid, belum expired, dan cocok dengan email user.

## Parameter

- `$email`: email user.
- `$token`: token asli dari link email.

## Return Value

Mengembalikan boolean:

- `true` jika token valid dan user berhasil diverifikasi.
- `false` jika token salah atau expired.

## Penjelasan Kode

Potongan penting:

```php
if ($email === '' || $token === '') {
    return false;
}
```

Email dan token wajib ada.

```php
WHERE email = :email
  AND email_verify_token = :token_hash
  AND email_verify_expires IS NOT NULL
  AND email_verify_expires >= NOW()
```

Query ini mencari user dengan email yang sama, hash token yang sama, dan token belum kedaluwarsa.

```php
SET email_verified = 1,
    email_verify_token = NULL,
    email_verify_expires = NULL
```

Jika valid, user ditandai verified dan token dihapus.

## Contoh Pemakaian

Di `auth/verify-email.php`:

```php
if (verify_user_email_token($email, $token)) {
    set_flash('success', 'Email berhasil diverifikasi. Silakan masuk.');
    redirect_to('login.php');
}
```

## File yang Memanggil Function Ini

- `auth/verify-email.php`

