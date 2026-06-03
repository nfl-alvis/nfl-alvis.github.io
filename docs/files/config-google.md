# Penjelasan File: config/google.php

## Fungsi Utama File

File ini mengatur Google OAuth Client. Nilai client id, client secret, dan redirect URI diambil dari `.env`.

## Alur Kerja File

1. File memuat Composer autoload.
2. File mendefinisikan `google_env_value()`.
3. File mendefinisikan `google_oauth_client()`.
4. Saat OAuth dimulai, function membaca konfigurasi dari `.env`.
5. Function membuat Google Client dan scope.

## Penjelasan Kode Per Bagian

Autoload:

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

Autoload dibutuhkan untuk class Google dan Dotenv.

Membaca `.env`:

```php
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
```

Konfigurasi Google berasal dari `.env`. Nilai aslinya tidak ditampilkan di dokumentasi ini.

Membuat client:

```php
$client = new Client();
$client->setClientId(google_env_value('GOOGLE_CLIENT_ID'));
$client->setClientSecret(google_env_value('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(google_env_value('GOOGLE_REDIRECT_URI'));
```

Scope:

```php
$client->addScope([
    Oauth2::OPENID,
    Oauth2::USERINFO_EMAIL,
    Oauth2::USERINFO_PROFILE,
]);
```

Scope ini meminta identitas, email, dan profil user.

## Function yang Digunakan

File ini mendefinisikan:

- `google_env_value()`
- `google_oauth_client()`

## Variabel Penting

- `$key`: nama env.
- `$value`: nilai env.
- `$client`: object Google Client.

## Query Database

Tidak ada query database.

## Session

Tidak memakai session langsung. Session OAuth state dibuat di `auth/google-login.php`.

## Redirect

Tidak ada redirect langsung di file ini.

## Hubungan dengan File Lain

Dipakai oleh:

- `auth/google-login.php`
- `auth/google-callback.php`

