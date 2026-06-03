# Penjelasan File: auth/google-callback.php

## Fungsi Utama File

File ini menerima callback dari Google setelah user memilih akun Google. File ini memvalidasi state, mengambil data user Google, membuat atau update user di database, lalu membuat session login.

## Alur Kerja File

1. File memuat `includes/bootstrap.php`.
2. File memuat `config/google.php`.
3. Jika Google mengirim error, user kembali ke login.
4. File membaca `code` dan `state` dari query string.
5. File membaca `google_oauth_state` dari session.
6. State dari Google dibandingkan dengan state di session.
7. Jika state salah, login ditolak.
8. File menukar authorization code menjadi access token.
9. File mengambil profil Google.
10. Email Google harus valid dan verified.
11. File mencari user berdasarkan email.
12. Jika user sudah ada, data Google disimpan ke user tersebut.
13. Jika user belum ada, user baru dibuat dengan role `user`.
14. File membuat session login.
15. User diarahkan ke halaman sesuai role.

## Penjelasan Kode Per Bagian

Membaca callback:

```php
$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$sessionState = (string) ($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);
```

Setelah state dibaca, session state langsung dihapus agar tidak dipakai ulang.

Validasi state:

```php
if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    set_flash('error', 'Sesi Login Google tidak valid. Silakan coba lagi.');
    auth_redirect_to_root('login.php');
}
```

`hash_equals()` dipakai untuk perbandingan string yang lebih aman.

Menukar code menjadi token:

```php
$token = $client->fetchAccessTokenWithAuthCode($code);
```

Mengambil data Google:

```php
$oauth = new Oauth2($client);
$googleUser = $oauth->userinfo->get();
```

Mencari user:

```php
$existingUser = find_user_by_email($email);
```

Jika user sudah ada, update data Google:

```php
UPDATE users
SET google_id = :google_id,
    picture = :picture,
    auth_provider = :auth_provider,
    email_verified = 1,
    email_verify_token = NULL,
    email_verify_expires = NULL,
    updated_at = NOW()
WHERE id = :id
```

Jika user belum ada, insert user:

```php
INSERT INTO users
    (name, email, password_hash, google_id, picture, auth_provider, email_verified, role, is_active, created_at, updated_at)
VALUES
    (:name, :email, :password_hash, :google_id, :picture, :auth_provider, 1, :role, 1, NOW(), NOW())
```

Password user Google dibuat random dan di-hash.

Membuat session login:

```php
login_user($user);
```

## Function yang Digunakan

- `ensure_user_google_columns()`
- `google_oauth_client()`
- `find_user_by_email()`
- `find_user_by_id()`
- `login_user()`
- `nav_target_for_user()`
- `set_flash()`
- `redirect_to()`

## Variabel Penting

- `$code`: authorization code dari Google.
- `$state`: state dari Google.
- `$sessionState`: state yang sebelumnya dibuat website.
- `$token`: access token Google.
- `$googleUser`: data user dari Google.
- `$googleId`, `$name`, `$email`, `$picture`: data identitas Google.
- `$existingUser`: user di database jika email sudah ada.
- `$user`: user final yang akan diloginkan.

## Query Database

Query update user lama:

```php
UPDATE users
SET google_id = :google_id,
    picture = :picture,
    auth_provider = :auth_provider,
    email_verified = 1,
    email_verify_token = NULL,
    email_verify_expires = NULL,
    updated_at = NOW()
WHERE id = :id
```

Query insert user baru:

```php
INSERT INTO users (...)
VALUES (...)
```

## Session

Membaca dan menghapus:

- `$_SESSION['google_oauth_state']`

Membuat:

- `$_SESSION['user_id']` melalui `login_user($user)`.

## Redirect

- Google error: `login.php`.
- Code kosong: `login.php`.
- State tidak valid: `login.php`.
- Login sukses: dashboard sesuai role.
- Error lain: `login.php`.

## Hubungan dengan File Lain

- Lanjutan dari `auth/google-login.php`.
- Menggunakan konfigurasi dari `config/google.php`.
- Memakai helper user/session dari `includes/bootstrap.php`.

