# Penjelasan File: config/mail.php

## Fungsi Utama File

File ini mengatur pengiriman email verifikasi dan reset password memakai PHPMailer.

## Alur Kerja File

1. File memuat Composer autoload.
2. File membaca konfigurasi email dari `.env`.
3. File membuat URL aplikasi berdasarkan `APP_URL`.
4. File membuat object PHPMailer.
5. File mengirim email SMTP.
6. File menyediakan function khusus untuk email verifikasi dan reset password.

## Penjelasan Kode Per Bagian

Memuat library:

```php
require_once __DIR__ . '/../vendor/autoload.php';
```

Membaca `.env`:

```php
Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
```

Nilai seperti `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dan `APP_URL` berasal dari `.env`. Nilai aslinya tidak ditampilkan.

Membuat PHPMailer:

```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = mail_env_value('MAIL_HOST');
$mail->Port = (int) mail_env_value('MAIL_PORT');
```

Mengatur pengirim dan penerima:

```php
$mail->setFrom(mail_env_value('MAIL_FROM_ADDRESS'), mail_env_value('MAIL_FROM_NAME'));
$mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
```

Mengirim email:

```php
$mail->send();
```

## Function yang Digunakan

File ini mendefinisikan:

- `mail_env_value()`
- `configured_app_url()`
- `send_app_mail()`
- `send_verification_email()`
- `send_password_reset_email()`

## Variabel Penting

- `$mail`: object PHPMailer.
- `$toEmail`: email tujuan.
- `$toName`: nama penerima.
- `$subject`: subject email.
- `$htmlBody`: isi HTML email.
- `$textBody`: isi text email.
- `$token`: token verifikasi/reset.

## Query Database

Tidak ada query database. File ini hanya mengirim email.

## Session

Tidak memakai session.

## Redirect

Tidak ada redirect di file ini. File pemanggil yang menentukan redirect jika email gagal atau sukses.

## Hubungan dengan File Lain

Dipakai oleh:

- `register.php`
- `auth/resend-verification.php`
- `auth/forgot-password.php`

