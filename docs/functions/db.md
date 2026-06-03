# Function: db()

## Lokasi Function

Function ini ada di file `config/database.php`.

## Fungsi Utama

Function ini membuat dan mengembalikan koneksi PDO ke database MySQL.

## Parameter

Tidak ada parameter.

## Return Value

Mengembalikan object `PDO`.

## Penjelasan Kode

Potongan penting:

```php
static $pdo = null;

if ($pdo instanceof PDO) {
    return $pdo;
}
```

Kode ini membuat koneksi hanya sekali per request. Jika sudah ada koneksi, function mengembalikan koneksi yang sama.

```php
$host = '127.0.0.1';
$port = 3306;
$database = 'pusakarasa';
$username = 'root';
$password = '';
```

Bagian ini adalah konfigurasi database. Berbeda dari Google dan email, konfigurasi database masih ditulis langsung di file PHP.

```php
$pdo = new PDO($dsn, $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
```

`PDO::ERRMODE_EXCEPTION` membuat error database menjadi exception. `PDO::FETCH_ASSOC` membuat hasil query berupa associative array.

## Contoh Pemakaian

```php
$stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
```

## File yang Memanggil Function Ini

Hampir semua logic database memanggil `db()` melalui `includes/bootstrap.php`, file admin, file store admin, dan file auth.

