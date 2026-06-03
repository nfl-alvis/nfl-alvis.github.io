# PROJECT_GUIDE.md

Panduan ini membaca project berdasarkan urutan eksekusi request, bukan berdasarkan urutan folder. Aplikasi ini adalah aplikasi PHP native untuk katalog kuliner "PusakaRasa". Tidak ada framework routing; setiap file PHP di root atau folder `auth/` adalah endpoint HTTP langsung.

## Gambaran Besar Sistem

Alur utama aplikasi:

1. Browser membuka endpoint PHP, misalnya `index.php`, `login.php`, `katalog.php`, atau `admin-dashboard.php`.
2. Hampir semua endpoint memanggil `includes/bootstrap.php`.
3. `includes/bootstrap.php` memulai session, memuat koneksi database dari `config/database.php`, lalu menyediakan helper untuk auth, role, query data, upload file, dan layout HTML.
4. Endpoint membaca input dari `$_GET`, `$_POST`, atau `$_FILES`.
5. Endpoint memanggil helper/query di `includes/bootstrap.php` atau query PDO langsung dengan `db()`.
6. Database MySQL menyimpan user, toko, produk, gambar produk, statistik kunjungan, review, dan balasan review.
7. Endpoint mengirim response dengan `render_layout()` atau redirect dengan flash message.

## Diagram Alur Request

```text
Browser
  -> file endpoint PHP
  -> includes/bootstrap.php
       -> session_start()
       -> config/database.php
       -> helper auth/query/render
  -> endpoint validasi GET/POST/FILES
  -> db() / PDO / helper database
  -> MySQL database
  -> data dikembalikan ke endpoint
  -> render_layout() atau redirect_to()
  -> HTML response / Location redirect
```

Untuk request yang butuh login:

```text
Browser
  -> endpoint protected
  -> require_login() atau require_role()
  -> current_user()
  -> SELECT users LEFT JOIN stores
  -> jika tidak valid: redirect login/index
  -> jika valid: lanjut query bisnis
  -> render response
```

## Alur Eksekusi Dari Entry Point

### File: index.php

#### Fungsi

Entry point utama halaman beranda. File ini menampilkan hero, produk populer, ringkasan jumlah toko, dan toko pilihan.

#### Kapan Dieksekusi

Dieksekusi saat browser membuka `/` atau `/index.php`. Apache diarahkan ke `index.php` karena `.htaccess` memakai `DirectoryIndex index.php index.html`.

#### Dipanggil Oleh

Browser atau link internal dari navbar, footer, redirect logout, dan redirect error role.

#### Memanggil

- `includes/bootstrap.php`
- `find_popular_products()`
- `find_stores()`
- `find_popular_stores()`
- `render_layout()`
- `render_product_card()`
- `base_path()`
- `is_logged_in()`

#### Penjelasan Kode

- Baris 5 memuat `includes/bootstrap.php`. Ini membuat session, koneksi database, helper auth, helper query, dan helper render tersedia.
- Baris 7 mengambil 4 produk paling populer berdasarkan data `product_views`, rating, dan status aktif.
- Baris 8 mengambil semua toko aktif untuk menghitung highlight jumlah toko.
- Baris 9 mengambil 3 toko populer berdasarkan kunjungan dan jumlah produk.
- Baris 10 membuat placeholder gambar toko dalam format SVG data URI jika toko tidak punya cover.
- Baris 12 memanggil `render_layout()`. Semua HTML halaman dibungkus oleh layout umum dari bootstrap.
- Baris 14-26 merender hero beranda. Tombol "Jelajahi Katalog" mengarah ke `katalog.php` jika user sudah login, atau `login.php` jika belum login.
- Baris 28-41 menampilkan angka highlight dari hasil query produk dan toko.
- Baris 43-54 merender produk populer dengan `render_product_card()`.
- Baris 56-88 merender kartu toko pilihan dan membuat link ke `store.php?slug=...` serta WhatsApp toko.

#### Ringkasan

`index.php` adalah pintu masuk publik aplikasi. Ia membaca data populer dari database melalui helper bootstrap lalu mengirim HTML beranda.

### File: includes/bootstrap.php

#### Fungsi

File inti aplikasi. Semua mekanisme penting terkumpul di sini: session, auth, role, flash message, query katalog, query toko, query user, upload gambar, review, statistik dashboard, dan render layout.

#### Kapan Dieksekusi

Dieksekusi hampir di semua endpoint saat file melakukan `require_once __DIR__ . '/includes/bootstrap.php'` atau dari folder `auth/` dengan path relatif ke atas.

#### Dipanggil Oleh

`index.php`, `login.php`, `register.php`, `logout.php`, `katalog.php`, `product.php`, `store.php`, `favorites.php`, `edit-profile.php`, semua file admin, semua file store admin, `auth/*.php`, `tentang.php`, `rendang.php`, `lupassword.php`, dan `Tambah_Makanan_Page.php`.

#### Memanggil

- `config/database.php`
- Database MySQL melalui `db()`
- File upload target di `uploads/profiles`, `uploads/products`, `uploads/stores`
- Asset CSS/JS saat `render_layout()`

#### Penjelasan Kode

- Baris 5 menjalankan `session_start()`, sehingga `$_SESSION` bisa dipakai untuk login, flash message, favorit client-side, visitor key, dan Google OAuth state.
- Baris 7 memuat `config/database.php` agar fungsi `db()` tersedia.
- Baris 9-11 mendefinisikan role: `user`, `store_admin`, dan `super_admin`.
- Baris 13-44 berisi helper dasar: nama aplikasi, path relatif, redirect, cek POST, dan escaping HTML dengan `htmlspecialchars`.
- Baris 46-61 mengelola flash message. Pesan disimpan di session lalu dihapus saat dibaca.
- Baris 63-91 mengambil user login dari `$_SESSION['user_id']`, join ke tabel `stores`, lalu cache hasilnya di static variable selama request.
- Baris 93-121 menentukan URL dan inisial foto profil.
- Baris 123-146 menyimpan/menghapus session login dan mengecek role.
- Baris 148-175 menentukan izin review: store admin hanya boleh membalas/menghapus review produk milik tokonya; super admin boleh menghapus review.
- Baris 177-192 adalah guard authorization. `require_login()` redirect ke login, sedangkan `require_role()` redirect ke beranda jika role tidak cocok.
- Baris 194-205 menentukan halaman dashboard setelah login: super admin ke `admin-dashboard.php`, store admin ke `store-dashboard.php`, user biasa ke `katalog.php`.
- Baris 207-227 memformat angka pendek dan harga rupiah.
- Baris 229-415 mengelola jam operasional toko: parsing JSON/teks lama, normalisasi jam, cek buka hari ini, display, dan render input jam operasional.
- Baris 417-453 memformat harga dan rating bintang.
- Baris 455-487 membuat visitor key session dan mencatat kunjungan toko atau view produk dengan `INSERT IGNORE`, sehingga satu session dihitung sekali per hari.
- Baris 489-503 membuat slug dan token pencarian.
- Baris 505-617 membaca produk populer, produk katalog dengan filter, dan daftar region produk.
- Baris 618-680 membaca provinsi dari tabel `provinces`; jika tabel belum ada, fallback membaca `indonesia.sql`.
- Baris 682-737 membaca detail produk dan review beserta balasan review.
- Baris 739-762 memastikan tabel `review_replies` ada.
- Baris 764-847 membaca daftar toko, toko populer, dan detail toko beserta produk aktifnya.
- Baris 849-1075 berisi user auth: cari user, autentikasi password, buat user, token verifikasi email, token reset password, dan update password.
- Baris 1077-1112 menyimpan upload foto profil.
- Baris 1114-1231 memastikan kolom migrasi user/toko tersedia saat runtime.
- Baris 1233-1274 update profil user yang sedang login.
- Baris 1276-1513 mengelola upload gambar produk dan tabel `product_images`, termasuk mengganti daftar gambar dan menjaga minimal satu gambar saat edit.
- Baris 1515-1556 menyimpan upload gambar toko.
- Baris 1558-1708 menangani review: insert review, balas review, hapus balasan, hapus review, dan hitung ulang rating produk.
- Baris 1710-1803 membaca data dashboard: produk toko, statistik toko, statistik super admin, semua user, semua toko, semua produk.
- Baris 1804-1819 membuat pagination array di memori.
- Baris 1821-1864 merender kartu produk yang dipakai beranda, katalog, dan favorit.
- Baris 1866-2057 merender layout HTML: head, CSS, header, navbar, flash message, footer, script umum, dan slot konten halaman.

#### Ringkasan

`includes/bootstrap.php` adalah pusat aplikasi. Jika ingin memahami project, file ini adalah file paling penting setelah entry point.

### File: config/database.php

#### Fungsi

Menyediakan fungsi `db()` untuk koneksi PDO ke MySQL database `pusakarasa`.

#### Kapan Dieksekusi

Dieksekusi saat `includes/bootstrap.php` dimuat.

#### Dipanggil Oleh

`includes/bootstrap.php`.

#### Memanggil

- Class `PDO`
- Database MySQL di `127.0.0.1:3306`, database `pusakarasa`, user `root`, password kosong.

#### Penjelasan Kode

- Baris 5 mendefinisikan `db(): PDO`.
- Baris 7 membuat static `$pdo`, supaya satu request hanya membuat satu koneksi.
- Baris 9-10 langsung mengembalikan koneksi lama jika sudah ada.
- Baris 13-17 mengatur host, port, nama database, username, dan password.
- Baris 19 membuat DSN MySQL dengan charset `utf8mb4`.
- Baris 21-24 membuat object PDO dengan error mode exception dan fetch mode associative array.
- Baris 26 mengembalikan koneksi.

#### Ringkasan

Semua operasi database di project lewat `db()`. Tidak ada ORM; semua query memakai PDO langsung.

### File: .htaccess

#### Fungsi

Mengatur entry file default, mencegah folder internal diakses langsung, dan membuat redirect kompatibilitas dari beberapa URL lama.

#### Kapan Dieksekusi

Dieksekusi oleh Apache sebelum PHP ketika request masuk ke folder project.

#### Dipanggil Oleh

Apache web server.

#### Memanggil

Tidak memanggil file PHP secara langsung, tetapi rewrite rule mengarahkan beberapa URL ke file PHP.

#### Penjelasan Kode

- Baris 1 menjadikan `index.php` sebagai halaman default.
- Baris 2 mematikan directory listing.
- Baris 4-8 memberi header `X-Robots-Tag: noindex` untuk asset statis.
- Baris 10-12 menolak akses file dotfile seperti `.env`.
- Baris 14 mengaktifkan rewrite engine.
- Baris 16 menolak request ke path yang mengandung dotfile.
- Baris 17 menolak akses langsung ke `config`, `database`, `includes`, dan `vendor`.
- Baris 18 menolak akses langsung ke file dependency/package.
- Baris 20-27 me-redirect URL lama seperti `login.html`, `rendang.php`, dan `Tambah_Makanan_Page.php`.

#### Ringkasan

`.htaccess` adalah lapisan proteksi dan kompatibilitas sebelum request masuk ke PHP.

## Alur Authentication

### File: login.php

#### Fungsi

Menampilkan form login dan memproses login email/password.

#### Kapan Dieksekusi

Saat user membuka `/login.php` atau saat endpoint protected mengarahkan user yang belum login ke login.

#### Dipanggil Oleh

Browser, navbar, `require_login()`, dan redirect dari auth error.

#### Memanggil

- `includes/bootstrap.php`
- `is_logged_in()`
- `nav_target_for_user()`
- `authenticate_user()`
- `user_needs_email_verification()`
- `login_user()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7-9 jika sudah login, user langsung diarahkan ke dashboard sesuai role.
- Baris 11-18 saat POST, email dan password wajib diisi.
- Baris 20-24 memanggil `authenticate_user()`, yang membaca user aktif dan memverifikasi `password_hash`.
- Baris 26-29 menolak user lokal yang belum verifikasi email.
- Baris 31 menyimpan `user_id` ke session.
- Baris 32-33 membuat flash success dan redirect ke `admin-dashboard.php`, `store-dashboard.php`, atau `katalog.php`.
- Baris 36-72 merender form login, tombol Google login, lupa password, resend verification, dan link register.

#### Ringkasan

`login.php` adalah pintu masuk session. Role user menentukan halaman tujuan setelah login.

### File: register.php

#### Fungsi

Mendaftarkan user biasa, membuat token verifikasi email, lalu mengirim email verifikasi.

#### Kapan Dieksekusi

Saat user membuka atau submit `/register.php`.

#### Dipanggil Oleh

Browser melalui link daftar.

#### Memanggil

- `includes/bootstrap.php`
- `config/mail.php`
- `create_user()`
- `create_email_verification_token()`
- `send_verification_email()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 6 memuat helper email.
- Baris 8-10 user yang sudah login diarahkan ke dashboard sesuai role.
- Baris 12-21 validasi semua field wajib ada.
- Baris 23-26 validasi format email.
- Baris 28-31 memastikan konfirmasi password sama.
- Baris 33-39 membuat user `ROLE_USER` dengan `emailVerified=false`, lalu membuat token verifikasi.
- Baris 41-46 mengirim email verifikasi; jika gagal, user diarahkan ke resend verification.
- Baris 48-49 redirect ke login setelah register berhasil.
- Baris 52-90 merender form register.

#### Ringkasan

`register.php` selalu membuat akun user biasa. Admin toko dibuat oleh super admin lewat endpoint lain.

### File: config/mail.php

#### Fungsi

Mengatur pengiriman email verifikasi dan reset password memakai PHPMailer dan variabel `.env`.

#### Kapan Dieksekusi

Saat `register.php`, `auth/resend-verification.php`, atau `auth/forgot-password.php` perlu mengirim email.

#### Dipanggil Oleh

`register.php`, `auth/resend-verification.php`, `auth/forgot-password.php`.

#### Memanggil

- `vendor/autoload.php`
- `Dotenv`
- `PHPMailer`
- `.env`

#### Penjelasan Kode

- Baris 5-8 memuat Dotenv, PHPMailer, dan Composer autoload.
- Baris 10-27 membaca nilai environment dan melempar error jika konfigurasi wajib kosong.
- Baris 29-32 membuat URL absolut berdasarkan `APP_URL`.
- Baris 34-65 membuat object PHPMailer, mengatur SMTP, auth, encryption, from, tujuan, subject, HTML body, text body, lalu `send()`.
- Baris 67-80 membuat email verifikasi berisi link ke `auth/verify-email.php`.
- Baris 82-95 membuat email reset password berisi link ke `auth/reset-password.php`.

#### Ringkasan

`config/mail.php` adalah adapter email aplikasi. Nilai sensitifnya berasal dari `.env`.

### File: auth/verify-email.php

#### Fungsi

Memvalidasi link verifikasi email.

#### Kapan Dieksekusi

Saat user klik link email verifikasi dari register atau resend verification.

#### Dipanggil Oleh

Link email.

#### Memanggil

- `includes/bootstrap.php`
- `find_user_by_email()`
- `verify_user_email_token()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7-8 membaca `email` dan `token` dari query string.
- Baris 10-17 jika email sudah verified, user langsung diarahkan ke login.
- Baris 19-22 memverifikasi hash token, expiry, lalu update `email_verified=1`.
- Baris 24-25 jika gagal, redirect ke resend verification dengan email terisi.

#### Ringkasan

Endpoint ini mengubah user lokal dari belum verified menjadi verified.

### File: auth/resend-verification.php

#### Fungsi

Mengirim ulang link verifikasi email.

#### Kapan Dieksekusi

Saat user membuka link "Kirim ulang verifikasi" dari login atau diarahkan dari link verifikasi gagal.

#### Dipanggil Oleh

Browser dan `auth/verify-email.php`.

#### Memanggil

- `includes/bootstrap.php`
- `config/mail.php`
- `find_user_by_email()`
- `user_needs_email_verification()`
- `create_email_verification_token()`
- `send_verification_email()`

#### Penjelasan Kode

- Baris 5-6 memuat bootstrap dan mail helper.
- Baris 8-10 user yang sudah login diarahkan ke dashboard.
- Baris 12 membaca email awal dari query string.
- Baris 14-20 saat POST, email wajib valid.
- Baris 22 mengambil user aktif berdasarkan email.
- Baris 24-31 jika user ada dan belum verified, token baru dibuat dan email dikirim.
- Baris 34-35 selalu memberi pesan generik lalu redirect login agar tidak membocorkan apakah email terdaftar.
- Baris 38-64 merender form resend.

#### Ringkasan

Endpoint ini aman dari enumerasi email karena response suksesnya dibuat generik.

### File: auth/forgot-password.php

#### Fungsi

Membuat token reset password dan mengirim link reset password.

#### Kapan Dieksekusi

Saat user membuka link "Lupa password?" dari login.

#### Dipanggil Oleh

Browser dari `login.php`.

#### Memanggil

- `includes/bootstrap.php`
- `config/mail.php`
- `find_user_by_email()`
- `create_password_reset_token()`
- `send_password_reset_email()`

#### Penjelasan Kode

- Baris 5-6 memuat bootstrap dan mail helper.
- Baris 8-10 user login diarahkan ke dashboard.
- Baris 12-18 saat POST, email wajib valid.
- Baris 20 mencari user aktif.
- Baris 22-30 hanya user verified yang dibuatkan token dan dikirimi email.
- Baris 32-33 response selalu generik lalu redirect login.
- Baris 36-62 merender form forgot password.

#### Ringkasan

`auth/forgot-password.php` memulai proses reset password tanpa membocorkan status email.

### File: auth/reset-password.php

#### Fungsi

Memvalidasi token reset password dan menyimpan password baru.

#### Kapan Dieksekusi

Saat user klik link reset password dari email.

#### Dipanggil Oleh

Link email reset password.

#### Memanggil

- `includes/bootstrap.php`
- `find_user_by_reset_token()`
- `reset_user_password_with_token()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7-9 user yang sudah login diarahkan ke dashboard.
- Baris 11-13 membaca email dan token dari GET/POST lalu validasi ke database.
- Baris 15-18 token invalid atau expired diarahkan kembali ke forgot password.
- Baris 20-32 saat POST, password baru dan konfirmasi wajib ada dan sama.
- Baris 34-37 menyimpan hash password baru dan menghapus token reset.
- Baris 39-40 redirect ke login.
- Baris 43-74 merender form password baru.

#### Ringkasan

Endpoint ini adalah tahap akhir reset password.

### File: auth/google-login.php

#### Fungsi

Memulai OAuth login Google.

#### Kapan Dieksekusi

Saat user klik tombol "Login dengan Google" di `login.php`.

#### Dipanggil Oleh

Browser dari `login.php`.

#### Memanggil

- `includes/bootstrap.php`
- `config/google.php`
- `google_oauth_client()`

#### Penjelasan Kode

- Baris 5-6 memuat bootstrap dan Google config.
- Baris 8-11 membuat wrapper redirect.
- Baris 13-15 user yang sudah login diarahkan ke dashboard.
- Baris 17-24 membuat Google client, membuat random OAuth `state`, menyimpannya ke session, lalu redirect ke URL consent Google.
- Baris 25-29 jika konfigurasi error, state dihapus dan user kembali ke login.

#### Ringkasan

File ini tidak login langsung; tugasnya hanya mengirim user ke Google dengan state anti-CSRF.

### File: config/google.php

#### Fungsi

Membuat Google OAuth client dari environment variable.

#### Kapan Dieksekusi

Saat login Google dimulai atau callback Google diproses.

#### Dipanggil Oleh

`auth/google-login.php`, `auth/google-callback.php`.

#### Memanggil

- `vendor/autoload.php`
- `Dotenv`
- `Google\Client`
- `Google\Service\Oauth2`
- `.env`

#### Penjelasan Kode

- Baris 5-9 memuat Dotenv, Google Client, Oauth2 service, dan Composer autoload.
- Baris 11-28 membaca environment dan error jika nilai kosong.
- Baris 30-52 membuat singleton Google client.
- Baris 38-41 mengisi client id, secret, dan redirect URI.
- Baris 42-44 mengatur akses online, prompt akun, dan include scope.
- Baris 45-49 meminta scope OpenID, email, dan profile.

#### Ringkasan

`config/google.php` adalah konfigurasi resmi OAuth Google untuk project.

### File: auth/google-callback.php

#### Fungsi

Menerima callback Google, menukar authorization code dengan token, mengambil data profile, lalu membuat atau menghubungkan user.

#### Kapan Dieksekusi

Saat Google mengarahkan user kembali ke `GOOGLE_REDIRECT_URI`.

#### Dipanggil Oleh

Google OAuth redirect.

#### Memanggil

- `includes/bootstrap.php`
- `config/google.php`
- `ensure_user_google_columns()`
- `google_oauth_client()`
- `find_user_by_email()`
- `find_user_by_id()`
- `login_user()`

#### Penjelasan Kode

- Baris 7-8 memuat bootstrap dan Google config.
- Baris 15-19 menangani callback error dari Google.
- Baris 21-24 membaca `code`, `state`, dan state session, lalu menghapus state session.
- Baris 26-34 menolak request tanpa code atau state tidak cocok.
- Baris 36-38 memastikan kolom Google di tabel users tersedia.
- Baris 39-46 menukar code dengan access token.
- Baris 48-55 mengambil profile Google: id, name, email, picture, dan status verified.
- Baris 57-63 menolak data Google tidak lengkap atau email Google belum verified.
- Baris 69 mencari user berdasarkan email.
- Baris 71-95 jika user sudah ada, sistem update `google_id`, `picture`, `auth_provider=google`, dan `email_verified=1`.
- Baris 96-114 jika user belum ada, sistem membuat user baru role `user` dengan password random hash.
- Baris 116-122 login user dan redirect sesuai role.
- Baris 123-126 jika error, kembali ke login.

#### Ringkasan

Callback Google mengubah identitas Google menjadi session aplikasi.

### File: logout.php

#### Fungsi

Mengakhiri session login.

#### Kapan Dieksekusi

Saat user klik link keluar.

#### Dipanggil Oleh

Navbar, dropdown profil, admin sidebar, store sidebar.

#### Memanggil

- `includes/bootstrap.php`
- `logout_user()`
- `set_flash()`
- `redirect_to()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 menghapus `$_SESSION['user_id']`.
- Baris 8 menyimpan flash sukses.
- Baris 9 redirect ke beranda.

#### Ringkasan

`logout.php` membersihkan session user lalu kembali ke halaman publik.

### File: lupassword.php

#### Fungsi

Alias lama untuk halaman forgot password.

#### Kapan Dieksekusi

Jika user membuka `/lupassword.php`.

#### Dipanggil Oleh

Browser atau link lama.

#### Memanggil

- `includes/bootstrap.php`
- `redirect_to('auth/forgot-password.php')`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 redirect ke endpoint reset password yang baru.

#### Ringkasan

File ini hanya menjaga kompatibilitas URL lama.

## Alur Katalog, Toko, Produk, Review

### File: katalog.php

#### Fungsi

Menampilkan katalog produk dengan filter pencarian, kategori, dan daerah.

#### Kapan Dieksekusi

Saat user login membuka `/katalog.php`.

#### Dipanggil Oleh

Navbar, beranda, favorit, halaman toko, redirect setelah login user biasa.

#### Memanggil

- `includes/bootstrap.php`
- `require_login()`
- `find_products()`
- `product_regions()`
- `render_layout()`
- `render_product_card()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 mewajibkan login.
- Baris 9-11 membaca filter dari query string.
- Baris 12 mengambil produk aktif dari toko aktif berdasarkan filter.
- Baris 13 mengambil daftar region untuk dropdown filter.
- Baris 15-84 merender toolbar filter dan grid produk.
- Baris 78-80 setiap produk dirender dengan `render_product_card()` yang link ke `product.php?slug=...`.

#### Ringkasan

`katalog.php` adalah halaman listing produk yang hanya bisa dibuka user login.

### File: product.php

#### Fungsi

Menampilkan detail produk, mencatat kunjungan/view, menampilkan review, menerima review user, dan menerima balasan/hapus review dari admin yang berwenang.

#### Kapan Dieksekusi

Saat user membuka `/product.php?slug=...` atau submit form review/balasan/hapus review.

#### Dipanggil Oleh

Kartu produk di beranda, katalog, favorit, dan halaman toko.

#### Memanggil

- `includes/bootstrap.php`
- `require_login()`
- `find_product_by_slug()`
- `user_can_reply_product_reviews()`
- `user_can_delete_product_reviews()`
- `save_review_reply()`
- `delete_review_reply()`
- `delete_product_review()`
- `delete_product_review_by_manager()`
- `submit_product_review()`
- `track_store_visit()`
- `track_product_view()`
- `find_reviews_by_product()`
- `product_image_paths()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 mewajibkan login.
- Baris 9-15 membaca slug, mencari produk aktif dan toko aktif, lalu redirect ke katalog jika tidak ditemukan.
- Baris 17-109 menangani POST.
- Baris 18-22 memastikan user masih login saat submit.
- Baris 24-27 menghitung izin user untuk membalas atau menghapus review.
- Baris 29-55 memproses action `reply_review`. Hanya store admin pemilik toko produk yang boleh membalas.
- Baris 57-72 memproses action `delete_review_reply`.
- Baris 74-91 memproses action `delete_review`. User bisa hapus review sendiri; super admin/store admin pemilik toko bisa hapus review produk.
- Baris 93-96 menolak action POST yang tidak dikenal.
- Baris 98-108 memvalidasi rating 1-5 dan teks review, insert review, hitung ulang rating, lalu redirect.
- Baris 111 mencatat kunjungan toko.
- Baris 112 mencatat view produk.
- Baris 113 mengambil review beserta balasan.
- Baris 114 mengambil semua gambar produk.
- Baris 116-521 merender detail produk, galeri, info toko, form review, daftar review, action menu review, dan JavaScript galeri/filter review.

#### Ringkasan

`product.php` adalah alur request paling lengkap: membaca produk, menulis statistik, menulis review, membaca ulang data, lalu mengirim response detail.

### File: store.php

#### Fungsi

Menampilkan direktori toko atau detail satu toko beserta produk aktifnya.

#### Kapan Dieksekusi

Saat user membuka `/store.php` untuk direktori atau `/store.php?slug=...` untuk detail toko.

#### Dipanggil Oleh

Navbar, beranda, kartu toko, detail produk, store sidebar.

#### Memanggil

- `includes/bootstrap.php`
- `find_store_by_slug()`
- `find_stores()`
- `require_login()`
- `track_store_visit()`
- `parse_operating_schedule()`
- `operating_hours_display()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7-12 membaca slug, mengambil toko detail jika slug ada, atau daftar toko jika tidak ada.
- Baris 14-17 jika slug ada tapi toko tidak ditemukan, redirect ke daftar toko.
- Baris 19-22 halaman detail toko mewajibkan login dan mencatat kunjungan toko.
- Baris 24-258 merender dua mode: detail toko jika `$store` ada, atau direktori toko jika tidak.
- Baris 27-30 di mode detail, produk toko bisa difilter kategori `Makanan` atau `Minuman`.
- Baris 31-43 menghitung rating rata-rata dan jumlah visitor toko.
- Baris 45-52 menyiapkan jam operasional, cover image, status buka, dan URL filter.
- Baris 54-216 merender detail toko, kontak, jam operasional, deskripsi, dan produk toko.
- Baris 218-257 merender direktori toko dan form pencarian.

#### Ringkasan

`store.php` punya dua mode. Direktori toko publik bisa dilihat tanpa login, tetapi detail toko mewajibkan login.

### File: favorites.php

#### Fungsi

Menampilkan halaman produk favorit user.

#### Kapan Dieksekusi

Saat user login membuka `/favorites.php`.

#### Dipanggil Oleh

Navbar, dropdown profil, footer.

#### Memanggil

- `includes/bootstrap.php`
- `require_login()`
- `find_products()`
- `render_layout()`
- `render_product_card()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 mewajibkan login.
- Baris 9 mengambil semua produk aktif sebagai sumber kartu.
- Baris 11-90 merender halaman favorit.
- Halaman ini menampilkan semua kartu, lalu JavaScript frontend menyaring berdasarkan favorit yang disimpan client-side dari tombol hati.

#### Ringkasan

Favorite di project ini bukan tabel database; halaman mengambil produk aktif lalu frontend menentukan item favorit.

### File: edit-profile.php

#### Fungsi

Menampilkan dan memproses dashboard profil user yang sedang login.

#### Kapan Dieksekusi

Saat user login membuka `/edit-profile.php` atau submit perubahan profil.

#### Dipanggil Oleh

Dropdown profil dan navbar mobile.

#### Memanggil

- `includes/bootstrap.php`
- `require_login()`
- `current_user()`
- `save_uploaded_profile_image()`
- `update_current_user_profile()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 mewajibkan login.
- Baris 9-12 mengambil user aktif, redirect jika tidak ada.
- Baris 14-49 menangani POST update profil.
- Baris 15-19 membaca nama, email, password baru, konfirmasi, dan gambar saat ini.
- Baris 21-29 validasi nama/email wajib dan konfirmasi password.
- Baris 31-39 upload foto profil dan update data user.
- Baris 40-48 memberi flash sukses/error dan redirect balik.
- Baris 51-281 merender dashboard profil, ringkasan akun, form foto, form email/nama/password, preview gambar, dan validasi password di frontend.

#### Ringkasan

`edit-profile.php` mengubah data akun sendiri, termasuk foto profil dan password.

### File: tentang.php

#### Fungsi

Menampilkan halaman statis "Tentang Kami".

#### Kapan Dieksekusi

Saat user membuka `/tentang.php`.

#### Dipanggil Oleh

Navbar dan link internal.

#### Memanggil

- `includes/bootstrap.php`
- `render_layout()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 memanggil `render_layout()` dengan judul "Tentang Kami".
- Baris 9-16 menampilkan hero.
- Baris 18-32 menampilkan gambar dan visi misi.
- Baris 34-58 menampilkan nilai PusakaRasa.
- Baris 60 mengaktifkan class halaman dan CSS khusus tentang.

#### Ringkasan

Halaman ini tidak membaca database; hanya memakai layout umum.

### File: cs.php

#### Fungsi

Menampilkan halaman customer service.

#### Kapan Dieksekusi

Saat user membuka `/cs.php`.

#### Dipanggil Oleh

Footer dari `render_layout()` dan link support.

#### Memanggil

- CSS `assets/css/cs.css`, `base.css`, `component.css`
- JS `assets/js/main.js`, `assets/js/carousel.js`

#### Penjelasan Kode

- Baris 1-21 langsung HTML, tidak memuat `bootstrap.php`.
- Baris 24-51 membuat navbar manual.
- Baris 55-61 membuat hero customer service.
- Baris 65-103 membuat kartu kontak WhatsApp, email, dan Instagram.
- Baris 107-137 membuat footer manual.
- Baris 140-141 memuat JS.

#### Ringkasan

`cs.php` adalah halaman statis lama yang berdiri sendiri dan tidak memakai sistem auth/layout bootstrap.

### File: rendang.php

#### Fungsi

Alias lama untuk detail produk Rendang.

#### Kapan Dieksekusi

Saat user membuka `/rendang.php`.

#### Dipanggil Oleh

Browser atau URL lama. `.htaccess` juga punya redirect untuk `rendang.php`.

#### Memanggil

- `includes/bootstrap.php`
- `redirect_to('product.php?slug=rendang')`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 redirect ke halaman detail produk Rendang.

#### Ringkasan

File ini hanya kompatibilitas URL lama.

## Alur Super Admin

### File: admin-dashboard.php

#### Fungsi

Dashboard statistik platform untuk super admin.

#### Kapan Dieksekusi

Saat user dengan role `super_admin` membuka `/admin-dashboard.php`.

#### Dipanggil Oleh

Redirect setelah login super admin dan sidebar admin.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `require_role(ROLE_SUPER_ADMIN)`
- `super_admin_stats()`
- `db()`
- `render_admin_sidebar()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-6 memuat bootstrap dan sidebar admin.
- Baris 8 memastikan hanya super admin yang bisa masuk.
- Baris 10 membaca total users, stores, products aktif, dan product views.
- Baris 12-17 membuat label 30 hari terakhir.
- Baris 19-37 mengambil data pertumbuhan toko, produk, dan distribusi role.
- Baris 39-60 membentuk data Chart.js.
- Baris 62-181 merender dashboard, sidebar, kartu statistik, chart pertumbuhan toko, produk, dan role.

#### Ringkasan

`admin-dashboard.php` adalah pusat monitoring platform untuk super admin.

### File: includes/admin-sidebar.php

#### Fungsi

Merender sidebar navigasi super admin.

#### Kapan Dieksekusi

Saat halaman super admin memanggil `render_admin_sidebar()`.

#### Dipanggil Oleh

`admin-dashboard.php`, `admin-users.php`, `admin-stores.php`, `admin-products.php`, `admin-add-product.php`, `admin-store-create.php`, `admin-store-admin-create.php`.

#### Memanggil

- `base_path()`
- `e()`

#### Penjelasan Kode

- Baris 5 mendefinisikan fungsi `render_admin_sidebar()`.
- Baris 7-8 menyiapkan nama dan email admin.
- Baris 9-16 mendefinisikan daftar menu.
- Baris 18-25 merender brand sidebar.
- Baris 27-53 merender link menu admin, link beranda/katalog, dan logout.
- Baris 55-63 merender ringkasan user di footer sidebar.

#### Ringkasan

File ini hanya komponen UI untuk menu super admin.

### File: admin-users.php

#### Fungsi

Mengelola user: list, filter, edit role/toko/status, dan hapus user.

#### Kapan Dieksekusi

Saat super admin membuka `/admin-users.php`, membuka modal edit `?edit=id`, atau submit edit/delete user.

#### Dipanggil Oleh

Sidebar admin dan redirect dari `admin-store-admin-create.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `require_role(ROLE_SUPER_ADMIN)`
- `all_users()`
- `all_stores_with_admins()`
- `paginate_array()`
- `recalculate_product_rating()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi role.
- Baris 10-13 membuat URL listing tanpa parameter `edit`.
- Baris 15-38 memproses POST `edit_user`, update tabel `users`.
- Baris 40-83 memproses POST `delete_user`. User aktif tidak boleh menghapus dirinya sendiri.
- Baris 54-82 saat delete memakai transaction, hapus user, lalu recalculates rating produk yang pernah direview user tersebut.
- Baris 85-110 membaca filter, sort, pagination, lalu memfilter `all_users()` di PHP.
- Baris 111-129 membaca daftar toko dan user yang sedang diedit.
- Baris 131-324 merender tabel user, pagination, dan modal edit.

#### Ringkasan

`admin-users.php` adalah manajemen akun dan role platform.

### File: admin-user-edit.php

#### Fungsi

Alias kecil untuk membuka modal edit user di `admin-users.php`.

#### Kapan Dieksekusi

Saat ada URL lama `/admin-user-edit.php?id=...`.

#### Dipanggil Oleh

Browser atau link lama.

#### Memanggil

- `includes/bootstrap.php`
- `require_role(ROLE_SUPER_ADMIN)`
- `redirect_to()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 memastikan super admin.
- Baris 9 membaca id user.
- Baris 11 redirect ke `admin-users.php?edit=id`.

#### Ringkasan

File ini menjaga kompatibilitas route edit lama.

### File: admin-stores.php

#### Fungsi

Mengelola toko: list, filter, edit data toko, status publik, upload cover, dan hapus toko beserta data turunannya.

#### Kapan Dieksekusi

Saat super admin membuka `/admin-stores.php`, membuka modal edit `?edit=id`, atau submit edit/delete toko.

#### Dipanggil Oleh

Sidebar admin dan redirect dari `admin-store-create.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `ensure_store_operational_columns()`
- `operating_schedule_from_post()`
- `operating_schedule_is_open_today()`
- `save_uploaded_store_image()`
- `all_stores_with_admins()`
- `paginate_array()`
- `render_operating_hours_selects()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-9 memuat dependency, proteksi role, dan memastikan kolom operasional toko.
- Baris 11-14 membuat URL listing tanpa `edit`.
- Baris 16-48 memproses edit toko. Data toko diupdate, slug dibuat ulang, WhatsApp dibersihkan jadi angka, jam operasional disimpan, status buka dihitung otomatis, dan cover image diproses.
- Baris 50-106 memproses delete toko.
- Baris 58-95 delete toko memakai transaction dan membersihkan `product_images`, `reviews`, `product_views`, `store_visits`, `products`, lalu melepas `users.store_id`.
- Baris 108-130 memfilter, sort, dan paginate toko.
- Baris 131-143 mengambil toko yang sedang diedit.
- Baris 145-377 merender tabel toko dan modal edit.

#### Ringkasan

`admin-stores.php` adalah manajemen toko paling lengkap untuk super admin.

### File: admin-store-create.php

#### Fungsi

Membuat toko baru.

#### Kapan Dieksekusi

Saat super admin membuka atau submit `/admin-store-create.php`.

#### Dipanggil Oleh

Sidebar admin dan tombol "Tambah Toko Baru" dari `admin-stores.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `ensure_store_operational_columns()`
- `save_uploaded_store_image()`
- `operating_schedule_from_post()`
- `operating_schedule_is_open_today()`
- `render_province_options()`
- `render_operating_hours_selects()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi super admin.
- Baris 10 menyiapkan path cover yang baru diupload untuk cleanup jika insert gagal.
- Baris 12-53 memproses POST create store.
- Baris 14 memastikan kolom operasional.
- Baris 15-18 membaca nama, upload cover, jam operasional, dan path upload.
- Baris 19-36 insert ke tabel `stores`.
- Baris 37-38 redirect ke manajemen toko jika sukses.
- Baris 39-52 jika error, file upload yang baru dibuat dihapus dan user kembali ke form.
- Baris 55-349 merender form toko baru, pilihan provinsi, jam operasional, upload cover, preview toko, dan JavaScript preview.

#### Ringkasan

Endpoint ini menambah toko baru dan langsung membuatnya tersedia untuk dipilih oleh store admin atau produk.

### File: admin-store-edit.php

#### Fungsi

Alias kecil untuk membuka modal edit toko di `admin-stores.php`.

#### Kapan Dieksekusi

Saat ada URL lama `/admin-store-edit.php?id=...`.

#### Dipanggil Oleh

Browser atau link lama.

#### Memanggil

- `includes/bootstrap.php`
- `require_role(ROLE_SUPER_ADMIN)`
- `redirect_to()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 memastikan super admin.
- Baris 9 membaca id toko.
- Baris 11 redirect ke `admin-stores.php?edit=id`.

#### Ringkasan

File ini menjaga route edit toko lama.

### File: admin-store-admin-create.php

#### Fungsi

Membuat akun store admin dan menghubungkannya ke toko.

#### Kapan Dieksekusi

Saat super admin membuka atau submit `/admin-store-admin-create.php`.

#### Dipanggil Oleh

Sidebar admin.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `all_stores_with_admins()`
- `create_user()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi super admin.
- Baris 10 membaca daftar toko.
- Baris 12-27 saat POST, memanggil `create_user()` dengan role `ROLE_STORE_ADMIN` dan `store_id` dari form.
- Baris 21-22 redirect ke `admin-users.php` jika sukses.
- Baris 29-107 merender form pembuatan store admin dan men-disable submit jika belum ada toko.

#### Ringkasan

Endpoint ini adalah cara resmi membuat admin toko.

### File: admin-products.php

#### Fungsi

Mengelola semua produk platform: list, filter, edit produk lintas toko, dan hapus produk permanen.

#### Kapan Dieksekusi

Saat super admin membuka `/admin-products.php`, membuka modal edit `?edit=id`, atau submit edit/delete produk.

#### Dipanggil Oleh

Sidebar admin, redirect dari `admin-add-product.php`, dan alias `admin-product-edit.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `all_products_for_admin()`
- `all_stores_with_admins()`
- `edited_product_image_paths()`
- `replace_product_images()`
- `normalize_price_display()`
- `paginate_array()`
- `render_product_image_delete_controls()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi super admin.
- Baris 10-12 membuat URL listing tanpa `edit`.
- Baris 14-69 memproses edit produk.
- Baris 18-29 membaca upload gambar, mengambil produk lama, lalu menghitung daftar gambar final.
- Baris 31-57 transaction update tabel `products` dan replace isi `product_images`.
- Baris 71-109 memproses delete produk permanen. Data `product_images`, `reviews`, `product_views`, lalu `products` dihapus dalam transaction.
- Baris 111-134 membaca filter, sort, pagination, dan daftar toko.
- Baris 135-152 mengambil produk yang sedang diedit.
- Baris 154-405 merender tabel produk, pagination, dan modal edit.

#### Ringkasan

`admin-products.php` adalah kontrol penuh produk oleh super admin.

### File: admin-add-product.php

#### Fungsi

Menambahkan produk baru ke toko mana pun.

#### Kapan Dieksekusi

Saat super admin membuka atau submit `/admin-add-product.php`.

#### Dipanggil Oleh

`admin-products.php` dan sidebar admin.

#### Memanggil

- `includes/bootstrap.php`
- `includes/admin-sidebar.php`
- `all_stores_with_admins()`
- `save_uploaded_product_images()`
- `ensure_product_images_table()`
- `replace_product_images()`
- `normalize_price_display()`
- `render_province_options()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi super admin.
- Baris 10 membaca daftar toko untuk dropdown.
- Baris 12-53 memproses POST tambah produk.
- Baris 14-16 membaca nama dan menyimpan multi-upload gambar produk; gambar pertama menjadi `products.image_path`.
- Baris 18-20 memastikan tabel gambar produk dan mulai transaction.
- Baris 21-39 insert ke tabel `products`.
- Baris 40 memasukkan semua gambar ke `product_images`.
- Baris 41 commit.
- Baris 43-44 redirect ke manajemen produk.
- Baris 45-52 rollback dan kembali ke form jika error.
- Baris 55-289 merender form produk, upload multi-gambar, preview kartu katalog, dan JavaScript preview.

#### Ringkasan

Endpoint ini membuat produk baru lintas toko untuk super admin.

### File: admin-product-edit.php

#### Fungsi

Alias kecil untuk membuka modal edit produk di `admin-products.php`.

#### Kapan Dieksekusi

Saat ada URL lama `/admin-product-edit.php?id=...`.

#### Dipanggil Oleh

Browser atau link lama.

#### Memanggil

- `includes/bootstrap.php`
- `require_role(ROLE_SUPER_ADMIN)`
- `redirect_to()`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 memastikan super admin.
- Baris 9 membaca id produk.
- Baris 11 redirect ke `admin-products.php?edit=id`.

#### Ringkasan

File ini menjaga route edit produk lama.

### File: dashboard_admin.php

#### Fungsi

Alias lama dashboard admin.

#### Kapan Dieksekusi

Saat user membuka `/dashboard_admin.php`.

#### Dipanggil Oleh

Browser atau URL lama.

#### Memanggil

- `includes/bootstrap.php`
- `redirect_to('admin-dashboard.php')`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 redirect ke dashboard super admin baru.

#### Ringkasan

File ini hanya redirect kompatibilitas.

## Alur Store Admin

### File: store-dashboard.php

#### Fungsi

Dashboard statistik untuk toko milik store admin.

#### Kapan Dieksekusi

Saat user role `store_admin` membuka `/store-dashboard.php`.

#### Dipanggil Oleh

Redirect setelah login store admin dan store sidebar.

#### Memanggil

- `includes/bootstrap.php`
- `includes/store-sidebar.php`
- `require_role(ROLE_STORE_ADMIN)`
- `find_store_products()`
- `store_dashboard_stats()`
- `db()`
- `render_store_sidebar()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi role.
- Baris 10-16 membaca `store_id` dari user login; jika kosong, redirect ke beranda.
- Baris 18-20 mengambil data toko.
- Baris 22-24 mengambil daftar produk dan statistik toko.
- Baris 26-42 mengambil tren kunjungan toko dan view produk 30 hari terakhir.
- Baris 44-75 membentuk data chart.
- Baris 77-271 merender dashboard, sidebar, statistik, chart, ringkasan toko, produk paling dilihat, dan daftar produk.

#### Ringkasan

Store admin hanya melihat statistik toko yang terhubung ke `users.store_id`.

### File: includes/store-sidebar.php

#### Fungsi

Merender sidebar navigasi store admin.

#### Kapan Dieksekusi

Saat halaman store admin memanggil `render_store_sidebar()`.

#### Dipanggil Oleh

`store-dashboard.php`, `store-profile.php`, `store-products.php`, `store-add-product.php`.

#### Memanggil

- `base_path()`
- `e()`

#### Penjelasan Kode

- Baris 5 mendefinisikan fungsi `render_store_sidebar()`.
- Baris 7-9 menyiapkan data user dan slug toko.
- Baris 10-14 mendefinisikan menu dashboard, profil toko, dan produk saya.
- Baris 16-23 merender brand sidebar.
- Baris 25-51 merender menu toko, link halaman publik toko, katalog, dan logout.
- Baris 53-61 merender user footer.

#### Ringkasan

File ini hanya komponen UI navigasi store admin.

### File: store-profile.php

#### Fungsi

Mengubah profil toko milik store admin.

#### Kapan Dieksekusi

Saat store admin membuka atau submit `/store-profile.php`.

#### Dipanggil Oleh

Store sidebar.

#### Memanggil

- `includes/bootstrap.php`
- `includes/store-sidebar.php`
- `require_role(ROLE_STORE_ADMIN)`
- `ensure_store_operational_columns()`
- `operating_schedule_from_post()`
- `operating_schedule_is_open_today()`
- `render_province_options()`
- `render_operating_hours_selects()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-9 memuat dependency, proteksi role, dan kolom operasional.
- Baris 11-17 membaca `store_id`; jika tidak ada, redirect.
- Baris 19-21 mengambil toko milik user.
- Baris 23-56 memproses POST update toko.
- Baris 25 membaca jam operasional dari form.
- Baris 26-49 update tabel `stores` hanya untuk id toko milik user.
- Baris 50-55 redirect balik dengan flash.
- Baris 58-250 merender preview toko, tips, form data toko, jam operasional, deskripsi, dan counter karakter.

#### Ringkasan

Store admin bisa mengubah data publik tokonya, tetapi tidak bisa mengganti cover image atau status publik aktif/nonaktif dari halaman ini.

### File: store-products.php

#### Fungsi

Mengelola produk milik toko store admin: list, filter, edit, nonaktifkan produk.

#### Kapan Dieksekusi

Saat store admin membuka `/store-products.php`, membuka modal edit `?edit=id`, atau submit edit/delete produk.

#### Dipanggil Oleh

Store sidebar dan redirect dari `store-add-product.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/store-sidebar.php`
- `require_role(ROLE_STORE_ADMIN)`
- `find_store_products()`
- `edited_product_image_paths()`
- `replace_product_images()`
- `normalize_price_display()`
- `paginate_array()`
- `render_product_image_delete_controls()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi role.
- Baris 10-16 membaca `store_id` dari user login.
- Baris 18-20 mengambil data toko.
- Baris 22-25 menyiapkan URL listing tanpa `edit`.
- Baris 27-34 jika `?edit=id`, produk hanya diambil jika `store_id` sesuai toko user.
- Baris 36-134 menangani POST.
- Baris 37-65 action `delete_product` tidak menghapus permanen; hanya update `products.is_active=0` untuk produk toko tersebut.
- Baris 67-130 action `edit_product` membaca produk milik toko, mengolah gambar final, update produk, dan replace `product_images` dalam transaction.
- Baris 136-174 mengambil semua produk toko, hitung statistik, filter, sort, dan pagination.
- Baris 176-486 merender tabel produk, filter, pagination, modal edit, dan validasi minimal satu gambar.

#### Ringkasan

`store-products.php` menerapkan authorization kepemilikan lewat `WHERE id = :id AND store_id = :store_id`.

### File: store-add-product.php

#### Fungsi

Menambahkan produk baru untuk toko milik store admin.

#### Kapan Dieksekusi

Saat store admin membuka atau submit `/store-add-product.php`.

#### Dipanggil Oleh

Store sidebar dan tombol tambah produk dari `store-products.php`.

#### Memanggil

- `includes/bootstrap.php`
- `includes/store-sidebar.php`
- `require_role(ROLE_STORE_ADMIN)`
- `save_uploaded_product_images()`
- `ensure_product_images_table()`
- `replace_product_images()`
- `normalize_price_display()`
- `render_province_options()`
- `render_layout()`

#### Penjelasan Kode

- Baris 5-8 memuat dependency dan memproteksi role store admin.
- Baris 10-16 membaca `store_id` dari user login.
- Baris 18-20 mengambil toko.
- Baris 22-62 memproses POST tambah produk.
- Baris 24-27 membaca nama dan menyimpan gambar; gambar pertama menjadi `image_path`.
- Baris 28-30 memastikan tabel gambar produk dan mulai transaction.
- Baris 31-48 insert produk dengan `store_id` dari user login, bukan dari form.
- Baris 49 menyimpan semua gambar ke `product_images`.
- Baris 50 commit, baris 52-53 redirect ke produk toko.
- Baris 54-61 rollback jika gagal.
- Baris 64-289 merender form produk dan preview kartu.

#### Ringkasan

Store admin bisa menambah produk hanya untuk tokonya sendiri.

### File: Tambah_Makanan_Page.php

#### Fungsi

Alias lama untuk halaman tambah produk store admin.

#### Kapan Dieksekusi

Saat user membuka `/Tambah_Makanan_Page.php`.

#### Dipanggil Oleh

Browser atau URL lama. `.htaccess` juga mengarahkan URL ini.

#### Memanggil

- `includes/bootstrap.php`
- `redirect_to('store-add-product.php')`

#### Penjelasan Kode

- Baris 5 memuat bootstrap.
- Baris 7 redirect ke `store-add-product.php`.

#### Ringkasan

File ini hanya kompatibilitas URL lama.

## Struktur Database

### File: database/schema.sql

#### Fungsi

Mendefinisikan schema utama aplikasi dan seed data demo.

#### Kapan Dieksekusi

Dieksekusi manual saat setup database, biasanya lewat MySQL client/phpMyAdmin.

#### Dipanggil Oleh

Tidak dipanggil otomatis oleh PHP. Beberapa helper bootstrap juga punya migrasi runtime untuk kolom/tabel tertentu.

#### Memanggil

Database MySQL.

#### Penjelasan Kode

- Baris 1-16 membuat tabel `stores`.
- Baris 18-38 membuat tabel `users`.
- Baris 40-61 membuat tabel `products`.
- Baris 63-70 membuat tabel `product_images`.
- Baris 72-80 membuat tabel `store_visits`.
- Baris 82-92 membuat tabel `product_views`.
- Baris 94-105 membuat tabel `reviews`.
- Baris 107-117 membuat tabel `review_replies`.
- Baris 119-136 menambahkan kolom migrasi agar schema lama kompatibel.
- Baris 138-148 seed beberapa toko demo.
- Baris 150-165 reset/seed tiga user demo.
- Baris 167-189 seed beberapa produk demo.
- Baris 191-198 membuat data awal `product_images` dari `products.image_path`.
- Baris 200-208 menghitung ulang rating produk dari tabel review.

#### Ringkasan

`database/schema.sql` adalah sumber schema utama project.

### Tabel Utama

| Tabel | Fungsi | Relasi Penting |
|---|---|---|
| `stores` | Data toko kuliner | Dipakai oleh `users.store_id`, `products.store_id`, `store_visits.store_id`, `product_views.store_id` |
| `users` | Akun user, store admin, super admin | `store_id` nullable ke `stores.id` |
| `products` | Produk katalog | `store_id` ke `stores.id` |
| `product_images` | Multi gambar produk | `product_id` ke `products.id` |
| `store_visits` | Statistik kunjungan toko per session per hari | `store_id` ke `stores.id` |
| `product_views` | Statistik view produk per session per hari | `product_id` ke `products.id`, `store_id` ke `stores.id` |
| `reviews` | Ulasan user untuk produk | `product_id` ke `products.id`, `user_id` ke `users.id` |
| `review_replies` | Balasan admin untuk review | `review_id` unique ke `reviews.id`, `admin_user_id` ke `users.id` |
| `provinces` | Data provinsi Indonesia | Dari `indonesia.sql`, dipakai dropdown region |
| `regencies`, `districts`, `villages` | Data wilayah detail | Seed dari `indonesia.sql`, belum banyak dipakai langsung oleh UI |

### File: database/email_auth_migration.sql

#### Fungsi

Menambahkan kolom untuk foto profil, auth provider, verifikasi email, dan reset password.

#### Kapan Dieksekusi

Manual jika database lama belum punya kolom auth email.

#### Dipanggil Oleh

Tidak otomatis dipanggil, tetapi logika sejenis ada di `ensure_user_auth_columns()`.

#### Memanggil

Database MySQL.

#### Penjelasan Kode

- Baris 1-9 menambahkan kolom `profile_image`, `google_id`, `picture`, `auth_provider`, `email_verified`, token verifikasi, dan token reset.
- Baris 11-15 menandai user lokal lama sebagai verified jika belum punya token verifikasi.

#### Ringkasan

Migration ini menjaga database lama kompatibel dengan sistem email verification dan reset password.

### File: database/google_oauth_migration.sql

#### Fungsi

Menambahkan kolom Google OAuth ke tabel users.

#### Kapan Dieksekusi

Manual jika database lama belum mendukung Google OAuth.

#### Dipanggil Oleh

Tidak otomatis dipanggil, tetapi `ensure_user_google_columns()` memanggil `ensure_user_auth_columns()` yang melakukan hal serupa.

#### Memanggil

Database MySQL.

#### Penjelasan Kode

- Baris 1 menambahkan `google_id`.
- Baris 2 menambahkan `picture`.
- Baris 3 menambahkan `auth_provider`.

#### Ringkasan

Migration kecil untuk dukungan login Google.

### File: database/store_operational_status_migration.sql

#### Fungsi

Menambahkan jam operasional dan status toko.

#### Kapan Dieksekusi

Manual jika database lama belum punya kolom operasional toko.

#### Dipanggil Oleh

Tidak otomatis dipanggil, tetapi `ensure_store_operational_columns()` melakukan hal serupa saat runtime.

#### Memanggil

Database MySQL.

#### Penjelasan Kode

- Baris 1-4 menambah/memodifikasi `stores.operating_hours`.
- Baris 6-7 menambah `stores.is_open`.
- Baris 9-10 menambah `stores.is_active`.

#### Ringkasan

Migration ini mendukung status buka/tutup dan publikasi toko.

### File: indonesia.sql

#### Fungsi

Dataset wilayah Indonesia: provinsi, kabupaten/kota, kecamatan, desa.

#### Kapan Dieksekusi

Manual saat setup database jika ingin dropdown region memakai tabel `provinces`.

#### Dipanggil Oleh

Tidak di-include sebagai SQL otomatis. Namun `indonesia_provinces()` di `includes/bootstrap.php` bisa membaca file ini sebagai fallback jika tabel `provinces` tidak tersedia.

#### Memanggil

Database MySQL jika diimport manual.

#### Penjelasan Kode

- Awal file membuat tabel `provinces`, `regencies`, `districts`, dan `villages`.
- Setelah definisi tabel, file mengisi ribuan baris data wilayah.
- Di runtime, project hanya butuh nama provinsi untuk dropdown `render_province_options()`.

#### Ringkasan

`indonesia.sql` adalah data referensi wilayah, bukan alur bisnis utama.

## Daftar Endpoint

| Endpoint | Method | Akses | Fungsi |
|---|---|---|---|
| `/index.php` | GET | Publik | Beranda |
| `/login.php` | GET/POST | Publik | Login lokal |
| `/register.php` | GET/POST | Publik | Register user biasa |
| `/logout.php` | GET | Login | Logout |
| `/auth/google-login.php` | GET | Publik | Mulai OAuth Google |
| `/auth/google-callback.php` | GET | Publik dari Google | Callback OAuth Google |
| `/auth/verify-email.php` | GET | Publik dari email | Verifikasi email |
| `/auth/resend-verification.php` | GET/POST | Publik | Kirim ulang verifikasi |
| `/auth/forgot-password.php` | GET/POST | Publik | Minta link reset password |
| `/auth/reset-password.php` | GET/POST | Publik dari email | Reset password |
| `/katalog.php` | GET | Login | Katalog produk |
| `/product.php?slug=...` | GET/POST | Login | Detail produk, review, balasan review |
| `/store.php` | GET | Publik | Direktori toko |
| `/store.php?slug=...` | GET | Login | Detail toko |
| `/favorites.php` | GET | Login | Halaman favorit |
| `/edit-profile.php` | GET/POST | Login | Profil user |
| `/tentang.php` | GET | Publik | Halaman tentang |
| `/cs.php` | GET | Publik | Customer service |
| `/admin-dashboard.php` | GET | Super admin | Dashboard platform |
| `/admin-users.php` | GET/POST | Super admin | Manajemen user |
| `/admin-stores.php` | GET/POST | Super admin | Manajemen toko |
| `/admin-store-create.php` | GET/POST | Super admin | Tambah toko |
| `/admin-store-admin-create.php` | GET/POST | Super admin | Tambah store admin |
| `/admin-products.php` | GET/POST | Super admin | Manajemen produk platform |
| `/admin-add-product.php` | GET/POST | Super admin | Tambah produk platform |
| `/store-dashboard.php` | GET | Store admin | Dashboard toko |
| `/store-profile.php` | GET/POST | Store admin | Edit profil toko |
| `/store-products.php` | GET/POST | Store admin | Manajemen produk toko |
| `/store-add-product.php` | GET/POST | Store admin | Tambah produk toko |
| `/rendang.php` | GET | Login setelah redirect | Alias detail Rendang |
| `/lupassword.php` | GET | Publik | Alias forgot password |
| `/dashboard_admin.php` | GET | Super admin setelah redirect | Alias admin dashboard |
| `/admin-user-edit.php?id=...` | GET | Super admin | Alias modal edit user |
| `/admin-store-edit.php?id=...` | GET | Super admin | Alias modal edit toko |
| `/admin-product-edit.php?id=...` | GET | Super admin | Alias modal edit produk |
| `/Tambah_Makanan_Page.php` | GET | Store admin setelah redirect | Alias tambah produk toko |

## Authentication & Authorization

Role yang dipakai:

| Role | Nilai | Akses |
|---|---|---|
| User biasa | `user` | Katalog, detail produk, review, toko detail, favorit, profil |
| Store admin | `store_admin` | Semua akses user plus dashboard toko, profil toko, produk toko, balas/hapus review produk toko |
| Super admin | `super_admin` | Semua akses admin platform, manajemen user/toko/produk, hapus review |

Mekanisme login:

1. Login lokal memakai `login.php`.
2. Password diverifikasi dengan `password_verify()`.
3. User lokal wajib `email_verified=1`.
4. Login sukses menyimpan `$_SESSION['user_id']`.
5. `current_user()` membaca ulang user dari database setiap request.

Mekanisme Google:

1. `auth/google-login.php` membuat `google_oauth_state` di session.
2. User diarahkan ke Google.
3. `auth/google-callback.php` memvalidasi state.
4. Email Google harus valid dan verified.
5. User lama dihubungkan berdasarkan email, atau user baru dibuat dengan role `user`.

Authorization penting:

- `require_login()` melindungi halaman yang butuh session.
- `require_role(ROLE_SUPER_ADMIN)` melindungi halaman super admin.
- `require_role(ROLE_STORE_ADMIN)` melindungi halaman store admin.
- Store admin dibatasi oleh `users.store_id`.
- Edit/hapus produk store admin selalu memakai `WHERE store_id = :store_id`.
- Balasan review hanya boleh oleh store admin yang tokonya memiliki produk tersebut.
- Super admin bisa menghapus review produk mana pun.

## Business Flow

### Flow User Biasa

1. User membuka `index.php`.
2. Jika ingin katalog, user diarahkan login/register.
3. User register lewat `register.php`.
4. User klik link `auth/verify-email.php`.
5. User login lewat `login.php`.
6. User masuk ke `katalog.php`.
7. User membuka `product.php?slug=...`.
8. Sistem mencatat `store_visits` dan `product_views`.
9. User submit review.
10. Review masuk ke tabel `reviews`.
11. `recalculate_product_rating()` update `products.rating` dan `products.review_count`.
12. Halaman detail reload dan menampilkan review baru.

### Flow Store Admin

1. Super admin membuat toko di `admin-store-create.php`.
2. Super admin membuat akun store admin di `admin-store-admin-create.php` dan memilih toko.
3. Store admin login.
4. Store admin masuk ke `store-dashboard.php`.
5. Store admin update profil toko di `store-profile.php`.
6. Store admin tambah produk di `store-add-product.php`.
7. Store admin kelola produk di `store-products.php`.
8. Jika ada review di produk tokonya, store admin bisa membalas dari `product.php`.

### Flow Super Admin

1. Super admin login.
2. Super admin masuk ke `admin-dashboard.php`.
3. Super admin memantau statistik users, stores, products, dan views.
4. Super admin mengelola user di `admin-users.php`.
5. Super admin mengelola toko di `admin-stores.php`.
6. Super admin mengelola produk platform di `admin-products.php`.
7. Super admin bisa menghapus review produk mana pun dari `product.php`.

## File Pendukung Dependency

### File: composer.json

#### Fungsi

Mendefinisikan dependency PHP project.

#### Kapan Dieksekusi

Tidak dieksekusi saat request. Dipakai saat `composer install` atau `composer update`.

#### Dipanggil Oleh

Composer.

#### Memanggil

Package:

- `google/apiclient`
- `vlucas/phpdotenv`
- `phpmailer/phpmailer`

#### Penjelasan Kode

- Baris 1-7 berisi daftar package yang diperlukan untuk Google OAuth, membaca `.env`, dan mengirim email SMTP.

#### Ringkasan

`composer.json` menjelaskan library eksternal yang dipakai auth Google dan email.

## Catatan Penting Untuk Pemula

- Project ini tidak punya router pusat seperti Laravel. Route adalah file PHP.
- `includes/bootstrap.php` adalah file yang harus dipahami paling dalam.
- `db()` adalah satu-satunya akses database standar.
- Banyak endpoint memakai pola POST-Redirect-GET: proses POST, set flash, redirect, lalu halaman GET menampilkan hasil.
- Beberapa migration juga dijalankan defensif dari PHP lewat fungsi `ensure_*`, jadi aplikasi mencoba menambal schema lama saat runtime.
- Favorite disimpan di frontend, bukan database.
- Upload gambar disimpan sebagai file fisik di `uploads/`, sedangkan path-nya disimpan di database.
