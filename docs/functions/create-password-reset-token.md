# Function: create_password_reset_token()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini membuat token reset password.

## Parameter

- `$userId`: ID user.
- `$ttlSeconds`: masa berlaku token dalam detik, default 3600 detik atau 1 jam.

## Return Value

Mengembalikan token asli yang dikirim ke email user.

## Penjelasan Kode

Potongan penting:

```php
$token = make_auth_token();
```

Membuat token random.

```php
SET reset_token = :token_hash,
    reset_expires = DATE_ADD(NOW(), INTERVAL ... SECOND)
```

Database hanya menyimpan hash token dan waktu kedaluwarsa.

```php
return $token;
```

Token asli dikembalikan agar bisa dikirim ke user lewat email.

## Contoh Pemakaian

Di `auth/forgot-password.php`:

```php
$token = create_password_reset_token((int) $user['id']);
send_password_reset_email($user['email'], $user['name'], $token);
```

## File yang Memanggil Function Ini

- `auth/forgot-password.php`

