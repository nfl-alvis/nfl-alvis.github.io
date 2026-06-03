# Flow: Google OAuth

## Tujuan Flow

Flow ini menjelaskan proses login dengan Google dari awal sampai session user dibuat.

## File yang Terlibat

- `login.php`
- `auth/google-login.php`
- `config/google.php`
- `auth/google-callback.php`
- `includes/bootstrap.php`
- `config/database.php`

## Alur Lengkap

1. User klik tombol "Login dengan Google" di `login.php`.
2. Browser membuka `auth/google-login.php`.
3. Website membuat Google Client dengan `google_oauth_client()`.
4. Website mengambil `GOOGLE_CLIENT_ID` dari `.env`.
5. Website mengambil `GOOGLE_CLIENT_SECRET` dari `.env`.
6. Website mengambil `GOOGLE_REDIRECT_URI` dari `.env`.
7. Website membuat nilai random `state`.
8. `state` disimpan ke `$_SESSION['google_oauth_state']`.
9. `state` dipasang ke Google Client.
10. User diarahkan ke halaman Google.
11. User memilih akun Google.
12. Google mengirim callback ke website, yaitu ke `auth/google-callback.php`.
13. Website membaca `code` dan `state` dari Google.
14. Website mengambil `google_oauth_state` dari session.
15. Website membandingkan state dari Google dengan state di session.
16. Jika state tidak cocok, login dibatalkan.
17. Jika cocok, website menukar `code` menjadi access token.
18. Website mengambil data user Google.
19. Website mengecek apakah email Google valid.
20. Website mengecek apakah email Google sudah verified.
21. Website mencari user di database berdasarkan email.
22. Jika user belum ada, user dibuat dengan role `user`.
23. Jika user sudah ada, data Google disimpan ke akun tersebut.
24. Session login dibuat dengan `login_user($user)`.
25. User diarahkan ke halaman tujuan sesuai role.

## Data yang Diproses

Data dari `.env`:

- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI`

Nilai asli tidak ditulis dalam dokumentasi.

Data dari Google:

- Google ID
- nama
- email
- foto profil
- status email verified

Data database:

- `users.email`
- `users.google_id`
- `users.picture`
- `users.auth_provider`
- `users.email_verified`
- `users.role`

## Session yang Digunakan

Session pertama:

- `$_SESSION['google_oauth_state']`

Dibuat di `auth/google-login.php`, dibaca dan dihapus di `auth/google-callback.php`.

Session kedua:

- `$_SESSION['user_id']`

Dibuat setelah login Google sukses.

## Database yang Digunakan

Tabel:

- `users`

Jika user sudah ada:

```php
UPDATE users
SET google_id = :google_id,
    picture = :picture,
    auth_provider = :auth_provider,
    email_verified = 1
WHERE id = :id
```

Jika user belum ada:

```php
INSERT INTO users
    (name, email, password_hash, google_id, picture, auth_provider, email_verified, role, is_active, created_at, updated_at)
VALUES
    (...)
```

## Redirect yang Terjadi

- Dari login: user klik link ke `auth/google-login.php`.
- Dari `auth/google-login.php`: redirect ke Google.
- Dari Google: callback ke `auth/google-callback.php`.
- Jika gagal: redirect ke `login.php`.
- Jika sukses: redirect sesuai role.

## Penjelasan Sederhana

Google OAuth seperti login dengan kartu identitas dari Google. Website tidak meminta password Google. Website hanya meminta Google mengonfirmasi, "Benar, orang ini pemilik email tersebut." Setelah Google memberi data user, website membuat atau menemukan akun lokal, lalu membuat session login.

