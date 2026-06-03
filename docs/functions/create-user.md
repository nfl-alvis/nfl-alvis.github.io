# Function: create_user()

## Lokasi Function

Function ini ada di file `includes/bootstrap.php`.

## Fungsi Utama

Function ini membuat user baru di tabel `users`.

## Parameter

- `$name`: nama user.
- `$email`: email user.
- `$password`: password awal user.
- `$role`: role user, default `ROLE_USER`.
- `$storeId`: ID toko jika user adalah store admin.
- `$emailVerified`: status email sudah verified atau belum.

## Return Value

Mengembalikan integer ID user baru.

## Penjelasan Kode

Potongan penting:

```php
ensure_user_auth_columns();
```

Function ini memastikan kolom auth seperti `email_verified`, `reset_token`, dan `google_id` sudah tersedia.

```php
'email' => strtolower(trim($email)),
'password_hash' => password_hash($password, PASSWORD_BCRYPT),
```

Email dinormalisasi menjadi huruf kecil. Password di-hash sebelum disimpan.

```php
'role' => $role,
'store_id' => $storeId,
```

Role dan toko bisa ditentukan dari pemanggil. Register biasa memakai role `user`, sedangkan super admin bisa membuat `store_admin`.

## Contoh Pemakaian

Register manual:

```php
$userId = create_user($name, $email, $password, ROLE_USER, null, false);
```

Buat store admin:

```php
create_user($name, $email, $password, ROLE_STORE_ADMIN, $storeId);
```

## File yang Memanggil Function Ini

- `register.php`
- `admin-store-admin-create.php`

