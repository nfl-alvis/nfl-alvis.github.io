# Session

Session adalah tempat PHP menyimpan data sementara per user/browser. Project ini memulai session di `includes/bootstrap.php`.

Potongan kode:

```php
session_start();
```

Artinya, setiap file yang memuat `includes/bootstrap.php` bisa memakai `$_SESSION`.

## Session `user_id`

### Isinya Apa

Berisi ID user yang sedang login.

Contoh:

```php
$_SESSION['user_id'] = $user['id'];
```

### Dibuat di File Mana

Dibuat oleh function `login_user()` di `includes/bootstrap.php`.

Function ini dipanggil oleh:

- `login.php`
- `auth/google-callback.php`

### Digunakan di File Mana

Digunakan oleh `current_user()` di `includes/bootstrap.php`.

Karena hampir semua halaman memanggil `current_user()` secara langsung atau tidak langsung, maka session ini berpengaruh ke banyak file:

- `katalog.php`
- `product.php`
- `favorites.php`
- `edit-profile.php`
- `admin-dashboard.php`
- `admin-users.php`
- `admin-stores.php`
- `admin-products.php`
- `store-dashboard.php`
- `store-profile.php`
- `store-products.php`
- `store-add-product.php`

### Kapan Dihapus

Dihapus oleh `logout_user()`:

```php
unset($_SESSION['user_id']);
```

Selain itu, jika `current_user()` tidak menemukan user di database, session `user_id` juga dihapus:

```php
if (!$user) {
    unset($_SESSION['user_id']);
}
```

### Penjelasan Sederhana

Bayangkan `user_id` seperti tiket masuk. Saat login sukses, user diberi tiket. Saat membuka halaman yang butuh login, aplikasi melihat tiket itu dan mengecek ke database siapa pemiliknya.

## Session `flash`

### Isinya Apa

Berisi pesan sementara setelah suatu aksi, misalnya:

- login sukses
- password salah
- produk berhasil disimpan
- akses ditolak

Strukturnya:

```php
$_SESSION['flash'] = [
    'type' => $type,
    'message' => $message,
];
```

### Dibuat di File Mana

Dibuat oleh function `set_flash()` di `includes/bootstrap.php`.

Function ini dipakai di banyak file, misalnya:

- `login.php`
- `register.php`
- `logout.php`
- `auth/verify-email.php`
- `auth/forgot-password.php`
- `auth/reset-password.php`
- `product.php`
- halaman admin
- halaman store admin

### Digunakan di File Mana

Dibaca oleh `get_flash()` di `includes/bootstrap.php`, lalu ditampilkan oleh `render_layout()`.

Potongan kode:

```php
$flash = get_flash();
```

### Kapan Dihapus

Dihapus langsung setelah dibaca:

```php
$flash = $_SESSION['flash'];
unset($_SESSION['flash']);
```

### Penjelasan Sederhana

Flash message seperti sticky note sementara. Halaman sebelumnya menempelkan pesan, halaman berikutnya membaca pesan itu, lalu sticky note dibuang.

## Session `visitor_key`

### Isinya Apa

Berisi string random untuk mengenali visitor yang sama dalam satu session.

Potongan kode:

```php
$_SESSION['visitor_key'] = bin2hex(random_bytes(16));
```

### Dibuat di File Mana

Dibuat oleh function `session_key()` di `includes/bootstrap.php`.

### Digunakan di File Mana

Digunakan oleh:

- `track_store_visit()`
- `track_product_view()`

Function tersebut dipanggil saat user membuka:

- `store.php?slug=...`
- `product.php?slug=...`

### Kapan Dihapus

Tidak ada kode khusus yang menghapus `visitor_key`. Session ini hilang ketika session browser/server berakhir.

### Penjelasan Sederhana

`visitor_key` seperti nomor antrian anonim. Aplikasi tidak perlu tahu nama visitor untuk menghitung kunjungan, cukup tahu bahwa ini visitor yang sama pada hari yang sama.

## Session `google_oauth_state`

### Isinya Apa

Berisi nilai random untuk melindungi proses Google OAuth dari request palsu.

Potongan kode:

```php
$state = bin2hex(random_bytes(32));
$_SESSION['google_oauth_state'] = $state;
$client->setState($state);
```

### Dibuat di File Mana

Dibuat di:

- `auth/google-login.php`

### Digunakan di File Mana

Digunakan di:

- `auth/google-callback.php`

Callback membandingkan `state` dari Google dengan nilai di session:

```php
if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    set_flash('error', 'Sesi Login Google tidak valid. Silakan coba lagi.');
    auth_redirect_to_root('login.php');
}
```

### Kapan Dihapus

Dihapus di `auth/google-callback.php` setelah dibaca:

```php
unset($_SESSION['google_oauth_state']);
```

Juga dihapus jika Google login gagal dimulai.

### Penjelasan Sederhana

`google_oauth_state` seperti kode rahasia satu kali. Website memberikan kode itu ke Google. Saat Google kembali, website mengecek apakah kodenya sama.

## Catatan Session yang Tidak Ada

Tidak ditemukan session CSRF token khusus untuk form POST biasa. Form login, register, forgot password, reset password, edit profile, dan admin action belum memakai token CSRF sendiri.

Saran:

- Buat `$_SESSION['csrf_token']`.
- Tambahkan hidden input `csrf_token` di setiap form POST.
- Validasi token sebelum memproses POST.

