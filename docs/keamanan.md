# Keamanan Project

Dokumen ini menjelaskan keamanan yang sudah ada di project dan bagian yang masih bisa diperbaiki.

## Password Hashing

Project tidak menyimpan password asli di database. Saat user dibuat atau password diubah, aplikasi memakai `password_hash()`.

Contoh di `create_user()`:

```php
'password_hash' => password_hash($password, PASSWORD_BCRYPT),
```

Contoh di reset password:

```php
'password_hash' => password_hash($password, PASSWORD_BCRYPT),
```

Artinya, database hanya menyimpan hash. Jika database bocor, password asli tidak langsung terlihat.

## Password Verify

Saat login manual, aplikasi memakai `password_verify()`.

Potongan kode:

```php
if (!password_verify($password, (string) $user['password_hash'])) {
    return null;
}
```

Artinya, password dari form dibandingkan dengan hash di database memakai cara yang benar.

## Prepared Statement

Banyak query memakai PDO prepared statement.

Contoh:

```php
$stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
```

Ini lebih aman daripada menyambung string SQL langsung karena input user tidak langsung masuk mentah ke SQL.

## Escape Output HTML

Project punya function `e()`:

```php
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
```

Function ini dipakai untuk menampilkan data ke HTML agar karakter berbahaya seperti `<script>` tidak dieksekusi sebagai HTML.

## Proteksi Halaman User

Halaman yang butuh login memanggil `require_login()`.

Contoh:

```php
require_login();
```

Jika belum login, user diarahkan ke `login.php`.

Digunakan di:

- `katalog.php`
- `product.php`
- `favorites.php`
- `edit-profile.php`

## Proteksi Halaman Admin

Role super admin diproteksi dengan:

```php
require_role(ROLE_SUPER_ADMIN);
```

Role store admin diproteksi dengan:

```php
require_role(ROLE_STORE_ADMIN);
```

Jika user login tetapi role tidak cocok, user diarahkan ke `index.php`.

## Role User

Project memakai tiga role:

- `user`: user biasa.
- `store_admin`: admin toko.
- `super_admin`: admin platform.

Role disimpan di tabel `users.role`.

## Pembatasan Store Admin

Store admin tidak bebas mengedit semua toko atau produk. Kode mengambil `store_id` dari user login:

```php
$storeId = (int) ($user['store_id'] ?? 0);
```

Lalu query edit produk memakai kondisi:

```php
WHERE id = :id AND store_id = :store_id
```

Ini penting agar store admin hanya mengubah produk milik tokonya sendiri.

## Token Verifikasi Email dan Reset Password

Token dibuat random:

```php
$token = bin2hex(random_bytes(32));
```

Token tidak disimpan langsung. Yang disimpan adalah hash token:

```php
return hash('sha256', $token);
```

Ini lebih aman karena jika database bocor, token asli tidak langsung bisa dipakai.

## Google OAuth Secret

Google OAuth membaca konfigurasi dari `.env`:

```php
$client->setClientId(google_env_value('GOOGLE_CLIENT_ID'));
$client->setClientSecret(google_env_value('GOOGLE_CLIENT_SECRET'));
$client->setRedirectUri(google_env_value('GOOGLE_REDIRECT_URI'));
```

Dokumentasi ini tidak menampilkan nilai secret. Nilainya berasal dari konfigurasi `.env`.

## Proteksi File Sensitif

`.htaccess` memblokir akses ke folder penting:

```apache
RewriteRule ^(config|database|includes|vendor)(/|$) - [F,L]
```

Ini membantu mencegah browser membaca file konfigurasi, database script, helper, atau dependency.

## Validasi Input

Validasi input sudah ada di beberapa tempat:

- Login mewajibkan email dan password.
- Register memvalidasi format email dan konfirmasi password.
- Reset password memvalidasi password dan konfirmasi.
- Review memvalidasi rating 1 sampai 5 dan teks tidak kosong.
- Upload gambar memvalidasi MIME type dan ukuran produk maksimal 5MB.
- Nomor WhatsApp dibersihkan dengan regex angka.

## Bagian yang Kurang Aman

### 1. Belum Ada CSRF Token untuk Form Biasa

Tidak ditemukan CSRF token untuk form POST seperti login, register, edit profile, admin delete, atau edit produk.

Dampaknya: jika user sedang login, ada risiko form action dipicu dari halaman lain.

Saran:

- Buat token di session.
- Tambahkan hidden input pada setiap form POST.
- Validasi token sebelum memproses request.

### 2. Password Minimal Belum Konsisten di Backend

Form register menampilkan placeholder "Minimal 6 karakter", tetapi validasi backend tidak terlihat memeriksa panjang minimal password.

Saran:

```php
if (strlen($password) < 6) {
    set_flash('error', 'Kata sandi minimal 6 karakter.');
    redirect_to('register.php');
}
```

### 3. Session ID Belum Diregenerasi Setelah Login

Saat login sukses, kode menyimpan `user_id`, tetapi tidak terlihat memanggil `session_regenerate_id(true)`.

Saran:

```php
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
```

Ini mengurangi risiko session fixation.

### 4. Beberapa Action Hapus Memakai POST Tetapi Tanpa CSRF

Delete user, delete toko, delete produk, delete review sudah memakai POST dan confirm JavaScript. Namun confirm JavaScript bukan proteksi keamanan backend.

Saran: tetap tambahkan CSRF token.

### 5. Konfigurasi Database Hardcoded

`config/database.php` menyimpan host, database, username, dan password langsung di kode.

Saran: pindahkan ke `.env`, seperti konfigurasi Google dan email.

## Kesimpulan

Keamanan dasar project sudah cukup baik untuk password, query, role, token email, dan OAuth state. Bagian terbesar yang perlu ditingkatkan adalah CSRF token, regenerasi session ID setelah login, validasi password backend, dan pemindahan konfigurasi database ke `.env`.

