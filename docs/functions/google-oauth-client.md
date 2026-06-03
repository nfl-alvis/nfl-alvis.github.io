# Function: google_oauth_client()

## Lokasi Function

Function ini ada di file `config/google.php`.

## Fungsi Utama

Function ini membuat object Google OAuth Client. Object inilah yang dipakai aplikasi untuk mengarahkan user ke halaman login Google dan menukar authorization code menjadi access token.

## Parameter

Function ini tidak menerima parameter.

## Return Value

Mengembalikan object `Google\Client`.

## Penjelasan Kode

Potongan penting:

```php
function google_oauth_client(): Client
{
    static $client = null;

    if ($client instanceof Client) {
        return $client;
    }
```

Bagian ini memakai static variable. Artinya, selama satu request PHP berjalan, object Google Client cukup dibuat sekali. Jika function dipanggil lagi, object lama langsung dikembalikan.

```php
$client = new Client();
$client->setClientId(google_env_value('GOOGLE_CLIENT_ID'));
$client->setClientSecret(google_env_value('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(google_env_value('GOOGLE_REDIRECT_URI'));
```

Bagian ini membuat Google Client dan mengisi konfigurasi dari `.env`. Dokumentasi ini tidak menampilkan nilai asli `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, atau `GOOGLE_REDIRECT_URI`.

```php
$client->setAccessType('online');
$client->setPrompt('select_account');
$client->setIncludeGrantedScopes(true);
```

Bagian ini mengatur perilaku login Google. `select_account` membuat user memilih akun Google.

```php
$client->addScope([
    Oauth2::OPENID,
    Oauth2::USERINFO_EMAIL,
    Oauth2::USERINFO_PROFILE,
]);
```

Bagian ini meminta izin untuk membaca identitas dasar user: OpenID, email, dan profile.

## Contoh Pemakaian

Di `auth/google-login.php`:

```php
$client = google_oauth_client();
$client->setState($state);
header('Location: ' . $client->createAuthUrl());
```

Di `auth/google-callback.php`:

```php
$client = google_oauth_client();
$token = $client->fetchAccessTokenWithAuthCode($code);
```

## File yang Memanggil Function Ini

- `auth/google-login.php`
- `auth/google-callback.php`

