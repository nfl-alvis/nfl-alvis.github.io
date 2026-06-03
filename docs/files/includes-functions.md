# Penjelasan File: includes/bootstrap.php - Daftar Function

## Fungsi Utama File

File ini menjelaskan daftar function penting di `includes/bootstrap.php` secara ringkas.

## Alur Kerja File

`includes/bootstrap.php` tidak berjalan sebagai halaman sendiri. File ini dimuat oleh endpoint lain. Setelah dimuat, semua function di dalamnya siap dipakai.

## Penjelasan Kode Per Bagian

### Helper dasar

- `app_name()`: nama aplikasi.
- `base_path()`: membuat URL relatif.
- `redirect_to()`: redirect dan exit.
- `is_post()`: cek method POST.
- `e()`: escape output HTML.

### Flash message

- `set_flash()`: menyimpan pesan sementara.
- `get_flash()`: membaca dan menghapus pesan sementara.

### Auth dan role

- `current_user()`: membaca user login dari session.
- `login_user()`: membuat session login.
- `logout_user()`: menghapus session login.
- `is_logged_in()`: cek login.
- `has_role()`: cek role.
- `require_login()`: proteksi halaman login.
- `require_role()`: proteksi halaman role tertentu.
- `nav_target_for_user()`: menentukan halaman tujuan berdasarkan role.

### Produk dan toko

- `find_popular_products()`: produk populer.
- `find_products()`: produk katalog.
- `find_product_by_slug()`: detail produk.
- `find_stores()`: daftar toko.
- `find_store_by_slug()`: detail toko.
- `find_store_products()`: produk milik toko.

### Auth email dan reset password

- `authenticate_user()`: login manual.
- `create_user()`: membuat user.
- `make_auth_token()`: token random.
- `auth_token_hash()`: hash token.
- `create_email_verification_token()`: token verifikasi email.
- `verify_user_email_token()`: validasi token verifikasi.
- `create_password_reset_token()`: token reset password.
- `find_user_by_reset_token()`: cari user berdasarkan token reset.
- `reset_user_password_with_token()`: update password.

### Upload

- `save_uploaded_profile_image()`
- `save_uploaded_product_images()`
- `save_uploaded_store_image()`

### Review

- `submit_product_review()`
- `save_review_reply()`
- `delete_review_reply()`
- `delete_product_review()`
- `delete_product_review_by_manager()`
- `recalculate_product_rating()`

### Render

- `render_product_card()`
- `render_layout()`

## Function yang Digunakan

File ini sendiri adalah daftar function, jadi tidak membahas satu function saja.

## Variabel Penting

Variabel penting bergantung pada function masing-masing. Yang paling sering muncul:

- `$user`
- `$product`
- `$store`
- `$filters`
- `$token`
- `$stmt`

## Query Database

Function di file ini melakukan query ke tabel:

- `users`
- `stores`
- `products`
- `product_images`
- `reviews`
- `review_replies`
- `store_visits`
- `product_views`
- `provinces`

## Session

Session utama:

- `user_id`
- `flash`
- `visitor_key`

## Redirect

Redirect utama terjadi lewat:

- `redirect_to()`
- `require_login()`
- `require_role()`

## Hubungan dengan File Lain

Semua endpoint utama bergantung pada file ini.

