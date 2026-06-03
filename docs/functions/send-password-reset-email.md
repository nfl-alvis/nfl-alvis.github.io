# Function: send_password_reset_email()

## Lokasi Function

Function ini ada di file `config/mail.php`.

## Fungsi Utama

Function ini mengirim email berisi link reset password.

## Parameter

- `$toEmail`: alamat email tujuan.
- `$toName`: nama penerima.
- `$token`: token reset password.

## Return Value

Tidak mengembalikan nilai (`void`). Jika SMTP gagal, exception akan dilempar.

## Penjelasan Kode

Potongan penting:

```php
$link = configured_app_url('auth/reset-password.php?email=' . rawurlencode($toEmail) . '&token=' . rawurlencode($token));
```

Kode ini membuat link reset password yang berisi email dan token.

```php
send_app_mail(
    $toEmail,
    $toName,
    'Reset Password PusakaRasa',
    ...
);
```

Email dikirim melalui konfigurasi SMTP dari `.env`. Nilai SMTP tidak ditampilkan di dokumentasi ini.

## Contoh Pemakaian

Di `auth/forgot-password.php`:

```php
send_password_reset_email($user['email'], $user['name'], $token);
```

## File yang Memanggil Function Ini

- `auth/forgot-password.php`

