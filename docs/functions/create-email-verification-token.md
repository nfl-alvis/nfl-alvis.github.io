# Function: create_email_verification_token()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini membuat token verifikasi email untuk user baru atau user yang meminta kirim ulang verifikasi.

## Parameter

- `$userId`: ID user.
- `$ttlSeconds`: masa berlaku token dalam detik, default 86400 detik atau 24 jam.

## Return Value

Mengembalikan token asli dalam bentuk string. Token inilah yang dikirim lewat email.

## Penjelasan Kode

Potongan penting:

```php
$token = make_auth_token();
```

Membuat token random.

```php
SET email_verify_token = :token_hash,
    email_verify_expires = DATE_ADD(NOW(), INTERVAL ... SECOND)
```

Database menyimpan hash token dan waktu kedaluwarsa. Token asli tidak disimpan.

```php
'token_hash' => auth_token_hash($token),
```

Token diubah menjadi SHA-256 sebelum disimpan.

## Contoh Pemakaian

Di `register.php`:

```php
$token = create_email_verification_token($userId);
send_verification_email($email, $name, $token);
```

## File yang Memanggil Function Ini

- `register.php`
- `auth/resend-verification.php`

