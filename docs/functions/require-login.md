# Function: require_login()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini melindungi halaman yang hanya boleh dibuka oleh user login.

## Parameter

Tidak ada parameter.

## Return Value

Tidak mengembalikan nilai (`void`). Jika user belum login, function melakukan redirect dan eksekusi berhenti.

## Penjelasan Kode

Potongan kode:

```php
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('error', 'Silakan masuk terlebih dahulu.');
        redirect_to('login.php');
    }
}
```

`is_logged_in()` memanggil `current_user()`. Jika tidak ada user, sistem membuat flash error lalu mengarahkan ke login.

## Contoh Pemakaian

Di `katalog.php`:

```php
require_login();
```

Artinya, katalog hanya bisa dibuka setelah login.

## File yang Memanggil Function Ini

- `katalog.php`
- `product.php`
- `favorites.php`
- `edit-profile.php`
- `store.php` untuk detail toko
- `require_role()` juga memanggil function ini

