# Function: logout_user()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini menghapus session login user.

## Parameter

Tidak ada parameter.

## Return Value

Tidak mengembalikan nilai (`void`).

## Penjelasan Kode

Potongan kode:

```php
function logout_user(): void
{
    unset($_SESSION['user_id']);
}
```

Session `user_id` adalah tanda bahwa user sedang login. Jika session ini dihapus, user dianggap logout.

## Contoh Pemakaian

Di `logout.php`:

```php
logout_user();
set_flash('success', 'Anda telah keluar dari sesi.');
redirect_to('index.php');
```

## File yang Memanggil Function Ini

- `logout.php`

