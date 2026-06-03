# Function: login_user()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini membuat user dianggap login dengan menyimpan ID user ke session.

## Parameter

- `$user`: array data user dari database.

## Return Value

Tidak mengembalikan nilai (`void`).

## Penjelasan Kode

Potongan kode:

```php
function login_user(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
}
```

Kode ini menyimpan `id` user ke session. Setelah itu, `current_user()` bisa membaca user tersebut di request berikutnya.

## Contoh Pemakaian

Login manual:

```php
login_user($user);
set_flash('success', 'Berhasil masuk ke akun Anda.');
redirect_to(nav_target_for_user($user));
```

Login Google:

```php
login_user($user);
set_flash('success', 'Berhasil masuk dengan Google.');
auth_redirect_to_root(nav_target_for_user($user));
```

## File yang Memanggil Function Ini

- `login.php`
- `auth/google-callback.php`

