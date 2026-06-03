# Struktur Folder Project

Dokumen ini menjelaskan fungsi folder dan file penting di project PusakaRasa. Project ini adalah PHP native, jadi struktur foldernya sederhana dan file endpoint berada langsung di root project atau di folder `auth/`.

## Root Project

Root project berisi banyak file PHP seperti:

- `index.php`
- `login.php`
- `register.php`
- `logout.php`
- `katalog.php`
- `product.php`
- `store.php`
- `admin-dashboard.php`
- `store-dashboard.php`

Dalam project ini, file PHP di root biasanya adalah halaman atau proses yang langsung dibuka browser. Contohnya, ketika user membuka `/login.php`, server langsung menjalankan file `login.php`.

## Folder `auth/`

Folder `auth/` dipakai untuk proses autentikasi lanjutan.

Isi pentingnya:

- `google-login.php`: memulai proses login Google.
- `google-callback.php`: menerima callback dari Google setelah user memilih akun.
- `verify-email.php`: memproses link verifikasi email.
- `resend-verification.php`: mengirim ulang email verifikasi.
- `forgot-password.php`: meminta link reset password.
- `reset-password.php`: menyimpan password baru berdasarkan token reset.

Folder ini fokus pada proses auth yang bukan sekadar form login biasa.

## Folder `includes/`

Folder `includes/` berisi file yang dipakai bersama oleh banyak endpoint.

Isi pentingnya:

- `bootstrap.php`: file helper utama. Di dalamnya ada `session_start()`, helper redirect, helper login, role, query database, upload gambar, review, dashboard, dan layout.
- `admin-sidebar.php`: komponen sidebar untuk super admin.
- `store-sidebar.php`: komponen sidebar untuk store admin.

Bagi pemula, `includes/bootstrap.php` adalah file yang paling penting untuk dipelajari karena hampir semua file lain bergantung padanya.

## Folder `config/`

Folder `config/` berisi konfigurasi koneksi dan integrasi.

Isi pentingnya:

- `database.php`: membuat koneksi PDO ke database MySQL.
- `google.php`: membuat Google OAuth Client berdasarkan nilai dari `.env`.
- `mail.php`: mengirim email verifikasi dan reset password memakai PHPMailer.

Folder ini tidak boleh diakses langsung dari browser. `.htaccess` sudah memblokir akses ke folder `config`.

## Folder `database/`

Folder `database/` berisi file SQL untuk schema dan migration.

Isi pentingnya:

- `schema.sql`: definisi tabel utama dan seed data demo.
- `email_auth_migration.sql`: kolom tambahan untuk email verification dan reset password.
- `google_oauth_migration.sql`: kolom tambahan untuk Google OAuth.
- `store_operational_status_migration.sql`: kolom tambahan untuk jam operasional dan status toko.

File SQL ini biasanya dijalankan manual di database, bukan dipanggil langsung oleh PHP.

## File `indonesia.sql`

File ini berisi data wilayah Indonesia seperti provinsi, kabupaten/kota, kecamatan, dan desa. Di kode PHP, function `indonesia_provinces()` memakai tabel `provinces` jika tersedia. Jika tabel tidak tersedia, function tersebut bisa membaca nama provinsi dari file `indonesia.sql` sebagai fallback.

## Folder `assets/`

Folder `assets/` berisi asset frontend.

Subfolder penting:

- `assets/css/`: CSS utama aplikasi.
- `assets/css/app/`: CSS tambahan untuk halaman aplikasi, katalog, detail produk, auth, responsive, dan profile table.
- `assets/js/`: JavaScript frontend seperti `main.js`, `detail.js`, dan `carousel.js`.
- `assets/image/`: gambar produk, logo, background, dan ikon.

Folder ini tidak berisi logic backend.

## Folder `uploads/`

Folder `uploads/` berisi file yang diupload oleh user/admin.

Subfolder penting:

- `uploads/profiles/`: foto profil user.
- `uploads/products/`: gambar produk.
- `uploads/stores/`: gambar cover toko.

Path file upload disimpan di database, sedangkan file fisiknya berada di folder ini.

## Folder `vendor/`

Folder `vendor/` dibuat oleh Composer.

Library penting yang dipakai project:

- `google/apiclient`: untuk Google OAuth.
- `vlucas/phpdotenv`: untuk membaca `.env`.
- `phpmailer/phpmailer`: untuk mengirim email.

Folder `vendor` tidak diedit manual. Jika dependency hilang, jalankan `composer install`.

## File `.htaccess`

File `.htaccess` mengatur perilaku Apache.

Fungsi penting:

- Menjadikan `index.php` sebagai default page.
- Memblokir akses langsung ke `.env`, `config/`, `database/`, `includes/`, dan `vendor/`.
- Me-redirect URL lama seperti `login.html` ke `login.php`.

## File `composer.json`

File ini mendefinisikan dependency PHP project. Composer membaca file ini untuk menginstall library eksternal.

