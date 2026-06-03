# Penjelasan File: auth/google-login.php

## Fungsi Utama File

File ini memulai proses login Google OAuth. File ini belum membuat user login, hanya mengarahkan user ke halaman Google.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memuat `config/google.php`.
3. Jika user sudah login, user diarahkan ke dashboard sesuai role.
4. File membuat Google OAuth Client.
5. File membuat random `state`.
6. `state` disimpan ke session.
7. `state` dipasang ke Google Client.
8. User diarahkan ke URL login Google.
9. Jika konfigurasi gagal, user kembali ke login.

## Penjelasan Kode Per Bagian

Memuat helper:

```php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/google.php';
```

Membuat Google Client:

```php
$client = google_oauth_client();
```

Membuat state:

```php
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$client->setState($state);
```

`state` dipakai untuk memastikan callback dari Google benar-benar lanjutan dari request login yang dibuat website ini.

Redirect ke Google:

```php
header('Location: ' . $client->createAuthUrl());
exit;
```

## Function yang Digunakan

- `is_logged_in()`
- `current_user()`
- `nav_target_for_user()`
- `google_oauth_client()`
- `set_flash()`
- `redirect_to()`

## Variabel Penting

- `$client`: object Google Client.
- `$state`: nilai random anti-CSRF OAuth.

## Query Database

Tidak ada query database di file ini.

## Session

Membuat session:

- `$_SESSION['google_oauth_state']`

Session ini akan dibaca oleh `auth/google-callback.php`.

## Redirect

- User sudah login: dashboard sesuai role.
- OAuth sukses dimulai: redirect ke URL Google.
- Konfigurasi Google gagal: `login.php`.

## Hubungan dengan File Lain

- Memakai Google Client dari `config/google.php`.
- Callback dari Google akan masuk ke `auth/google-callback.php`.

