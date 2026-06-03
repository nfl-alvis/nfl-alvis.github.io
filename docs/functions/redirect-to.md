# Function: redirect_to()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini mengarahkan user ke halaman lain memakai HTTP header `Location`, lalu menghentikan eksekusi PHP.

## Parameter

- `$path`: path tujuan redirect, misalnya `login.php`, `index.php`, atau `auth/forgot-password.php`.

## Return Value

Function ini bertipe `never`, artinya tidak pernah kembali ke pemanggil karena selalu `exit`.

## Penjelasan Kode

Potongan kode:

```php
function redirect_to(string $path): never
{
    header('Location: ' . base_path($path));
    exit;
}
```

`base_path($path)` dipakai supaya redirect tetap benar meskipun file dipanggil dari root atau folder `auth/`.

Setelah header dikirim, `exit` wajib dipanggil agar kode setelah redirect tidak tetap berjalan.

## Contoh Pemakaian

Jika user belum login:

```php
set_flash('error', 'Silakan masuk terlebih dahulu.');
redirect_to('login.php');
```

Jika login berhasil:

```php
login_user($user);
redirect_to(nav_target_for_user($user));
```

## File yang Memanggil Function Ini

Banyak file memakai function ini, antara lain:

- `login.php`
- `register.php`
- `logout.php`
- `auth/verify-email.php`
- `auth/forgot-password.php`
- `auth/reset-password.php`
- `auth/google-login.php`
- `auth/google-callback.php`
- `product.php`
- file admin
- file store admin

