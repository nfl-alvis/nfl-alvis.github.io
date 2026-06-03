# Function: authenticate_user()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini memeriksa email dan password login manual.

## Parameter

- `$email`: email yang dikirim dari form login.
- `$password`: password yang dikirim dari form login.

## Return Value

Mengembalikan:

- array user jika email ditemukan, akun aktif, dan password cocok.
- `null` jika gagal.

## Penjelasan Kode

Potongan kode:

```php
$user = find_user_by_email($email, true);
```

Bagian ini mencari user berdasarkan email dan hanya mengambil user aktif.

```php
if (!$user) {
    return null;
}
```

Jika user tidak ditemukan, login gagal.

```php
if (!password_verify($password, (string) $user['password_hash'])) {
    return null;
}
```

Password dari form dibandingkan dengan hash password di database. Password asli tidak pernah dibandingkan sebagai teks biasa.

```php
return $user;
```

Jika semua cocok, data user dikembalikan.

## Contoh Pemakaian

Di `login.php`:

```php
$user = authenticate_user($email, $password);
if (!$user) {
    set_flash('error', 'Email atau kata sandi tidak cocok.');
    redirect_to('login.php');
}
```

## File yang Memanggil Function Ini

- `login.php`

