# Function: google_env_value()

## Lokasi Function

Function ini ada di file `config/google.php`.

## Fungsi Utama

Function ini membaca nilai konfigurasi Google OAuth dari environment variable atau file `.env`.

## Parameter

- `$key`: nama konfigurasi yang ingin dibaca, misalnya `GOOGLE_CLIENT_ID`.

## Return Value

Mengembalikan string berisi nilai konfigurasi. Jika nilai kosong, function melempar `RuntimeException`.

## Penjelasan Kode

Potongan penting:

```php
static $loaded = false;

if (!$loaded) {
    Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
    $loaded = true;
}
```

Bagian ini memastikan `.env` hanya diload sekali dalam satu request.

```php
$value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
$value = is_string($value) ? trim($value) : '';
```

Aplikasi mencoba membaca nilai dari `$_ENV`, `$_SERVER`, atau `getenv()`.

```php
if ($value === '') {
    throw new RuntimeException('Konfigurasi Google OAuth belum lengkap.');
}
```

Jika nilai kosong, aplikasi menghentikan proses Google OAuth dan menampilkan flash error.

## Contoh Pemakaian

```php
$client->setClientId(google_env_value('GOOGLE_CLIENT_ID'));
$client->setClientSecret(google_env_value('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(google_env_value('GOOGLE_REDIRECT_URI'));
```

## File yang Memanggil Function Ini

- `config/google.php`, terutama dari `google_oauth_client()`.

