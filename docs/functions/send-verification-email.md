# Function: send_verification_email()

## Lokasi Function

Function ini ada di file `config/mail.php`.

## Fungsi Utama

Function ini mengirim email verifikasi akun ke user.

## Parameter

- `$toEmail`: alamat email tujuan.
- `$toName`: nama penerima.
- `$token`: token verifikasi email.

## Return Value

Tidak mengembalikan nilai (`void`). Jika pengiriman gagal, PHPMailer akan melempar exception.

## Penjelasan Kode

Potongan penting:

```php
$link = configured_app_url('auth/verify-email.php?email=' . rawurlencode($toEmail) . '&token=' . rawurlencode($token));
```

Kode ini membuat link verifikasi yang mengarah ke `auth/verify-email.php`.

```php
send_app_mail(
    $toEmail,
    $toName,
    'Verifikasi Email PusakaRasa',
    ...
);
```

Function ini tidak mengirim email langsung. Ia membuat isi email lalu menyerahkannya ke `send_app_mail()`.

## Contoh Pemakaian

Di `register.php`:

```php
send_verification_email($email, $name, $token);
```

## File yang Memanggil Function Ini

- `register.php`
- `auth/resend-verification.php`

