# Function: current_user()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini mengambil data user yang sedang login berdasarkan `$_SESSION['user_id']`.

## Parameter

Tidak ada parameter.

## Return Value

Mengembalikan:

- array data user jika login valid.
- `null` jika tidak login atau user tidak ditemukan.

## Penjelasan Kode

Potongan penting:

```php
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $user = null;
    return null;
}
```

Jika tidak ada `user_id` di session, berarti user belum login.

```php
$stmt = db()->prepare(
    'SELECT u.*, s.name AS store_name, s.slug AS store_slug
     FROM users u
     LEFT JOIN stores s ON s.id = u.store_id
     WHERE u.id = :id LIMIT 1'
);
```

Query ini mengambil user dan informasi toko jika user adalah store admin.

```php
if (!$user) {
    unset($_SESSION['user_id']);
}
```

Jika session menunjuk ke user yang sudah tidak ada, session dibersihkan.

## Contoh Pemakaian

```php
$user = current_user();
if (!$user) {
    redirect_to('login.php');
}
```

## File yang Memanggil Function Ini

- `login.php`
- `edit-profile.php`
- `store-dashboard.php`
- `store-profile.php`
- `store-products.php`
- `store-add-product.php`
- `product.php`
- `render_layout()` di `includes/bootstrap.php`

