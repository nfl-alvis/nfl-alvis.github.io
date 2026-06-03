# Dokumentasi Project PusakaRasa

Dokumentasi ini dibuat untuk membantu pemula memahami project PHP native ini dari sisi struktur, database, session, keamanan, function penting, file penting, dan alur fitur autentikasi.

Project ini tidak memakai framework seperti Laravel. Setiap file PHP seperti `login.php`, `register.php`, `katalog.php`, atau `auth/google-callback.php` berperan sebagai endpoint yang dapat dibuka langsung oleh browser. Hampir semua endpoint memuat `includes/bootstrap.php`, lalu memakai function dari file tersebut untuk session, login, role, query database, redirect, dan render halaman.

## Daftar Dokumentasi

### Dokumentasi Umum

- [Struktur Folder](struktur-folder.md)
- [Database](database.md)
- [Session](session.md)
- [Keamanan](keamanan.md)

### Dokumentasi Function

- [Function: google_oauth_client()](functions/google-oauth-client.md)
- [Function: google_env_value()](functions/google-env-value.md)
- [Function: redirect_to()](functions/redirect-to.md)
- [Function: db()](functions/db.md)
- [Function: current_user()](functions/current-user.md)
- [Function: login_user()](functions/login-user.md)
- [Function: logout_user()](functions/logout-user.md)
- [Function: require_login()](functions/require-login.md)
- [Function: require_role()](functions/require-role.md)
- [Function: authenticate_user()](functions/authenticate-user.md)
- [Function: create_user()](functions/create-user.md)
- [Function: create_email_verification_token()](functions/create-email-verification-token.md)
- [Function: verify_user_email_token()](functions/verify-user-email-token.md)
- [Function: create_password_reset_token()](functions/create-password-reset-token.md)
- [Function: reset_user_password_with_token()](functions/reset-user-password-with-token.md)
- [Function: send_verification_email()](functions/send-verification-email.md)
- [Function: send_password_reset_email()](functions/send-password-reset-email.md)
- [Function Lainnya](functions/function-lainnya.md)

### Dokumentasi File

- [File: login.php](files/auth-login.md)
- [File: register.php](files/auth-register.md)
- [File: auth/verify-email.php](files/auth-verify-email.md)
- [File: auth/resend-verification.php](files/auth-resend-verification.md)
- [File: auth/forgot-password.php](files/auth-forgot-password.md)
- [File: auth/reset-password.php](files/auth-reset-password.md)
- [File: auth/google-login.php](files/auth-google-login.md)
- [File: auth/google-callback.php](files/auth-google-callback.md)
- [File: logout.php](files/auth-logout.md)
- [File: includes/bootstrap.php](files/includes-bootstrap.md)
- [File: includes/bootstrap.php - daftar function](files/includes-functions.md)
- [File: config/database.php](files/config-database.md)
- [File: config/google.php](files/config-google.md)
- [File: config/mail.php](files/config-mail.md)
- [File Lainnya](files/file-lainnya.md)

### Dokumentasi Flow

- [Flow Login Manual](flows/flow-login-manual.md)
- [Flow Register Manual](flows/flow-register-manual.md)
- [Flow Verifikasi Email](flows/flow-verifikasi-email.md)
- [Flow Forgot Password](flows/flow-forgot-password.md)
- [Flow Reset Password](flows/flow-reset-password.md)
- [Flow Google OAuth](flows/flow-google-oauth.md)
- [Flow Logout](flows/flow-logout.md)
- [Flow Proteksi User](flows/flow-proteksi-user.md)
- [Flow Proteksi Admin](flows/flow-proteksi-admin.md)

## Urutan Membaca yang Disarankan

1. Mulai dari [Struktur Folder](struktur-folder.md) agar paham letak file penting.
2. Baca [Database](database.md), karena hampir semua fitur membaca atau menulis tabel `users`, `stores`, dan `products`.
3. Baca [Session](session.md), karena login dan flash message bergantung pada `$_SESSION`.
4. Baca [Keamanan](keamanan.md), supaya paham role, password hashing, prepared statement, dan bagian yang perlu diperbaiki.
5. Baca file utama [includes/bootstrap.php](files/includes-bootstrap.md), karena file ini adalah pusat helper aplikasi.
6. Lanjutkan ke dokumentasi file auth: login, register, verifikasi email, forgot password, reset password, Google OAuth, dan logout.
7. Terakhir, baca folder `flows/` untuk melihat alur fitur dari sudut pandang user.

## Catatan Penting

Dokumentasi ini tidak menampilkan isi secret dari `.env`. Nilai seperti `GOOGLE_CLIENT_SECRET`, `MAIL_PASSWORD`, dan konfigurasi sensitif lain hanya dijelaskan sebagai "berasal dari konfigurasi `.env`".

