# Function: require_role()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini melindungi halaman berdasarkan role. Contohnya, halaman super admin hanya boleh dibuka oleh user role `super_admin`.

## Parameter

- `...$roles`: satu atau lebih role yang diizinkan.

Contoh:

```php
require_role(ROLE_SUPER_ADMIN);
require_role(ROLE_STORE_ADMIN);
```

## Return Value

Tidak mengembalikan nilai (`void`). Jika akses ditolak, function redirect.

## Penjelasan Kode

Potongan kode:

```php
require_login();
```

Pertama, function memastikan user sudah login.

```php
if (!has_role(...$roles)) {
    set_flash('error', 'Anda tidak memiliki akses ke halaman tersebut.');
    redirect_to('index.php');
}
```

Jika role user tidak termasuk role yang diizinkan, user diarahkan ke beranda.

## Contoh Pemakaian

Di halaman super admin:

```php
require_role(ROLE_SUPER_ADMIN);
```

Di halaman store admin:

```php
require_role(ROLE_STORE_ADMIN);
```

## File yang Memanggil Function Ini

- `admin-dashboard.php`
- `admin-users.php`
- `admin-stores.php`
- `admin-products.php`
- `admin-add-product.php`
- `admin-store-create.php`
- `admin-store-admin-create.php`
- `store-dashboard.php`
- `store-profile.php`
- `store-products.php`
- `store-add-product.php`

