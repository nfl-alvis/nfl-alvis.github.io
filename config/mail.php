<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

function mail_env_value(string $key, bool $required = true): string
{
    static $loaded = false;

    if (!$loaded) {
        Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
        $loaded = true;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    $value = is_string($value) ? trim($value) : '';

    if ($required && $value === '') {
        throw new RuntimeException('Konfigurasi email belum lengkap.');
    }

    return $value;
}

function configured_app_url(string $path = ''): string
{
    return rtrim(mail_env_value('APP_URL'), '/') . '/' . ltrim($path, '/');
}

function send_app_mail(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody): void
{
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = mail_env_value('MAIL_HOST');
    $mail->Port = (int) mail_env_value('MAIL_PORT');

    $username = mail_env_value('MAIL_USERNAME', false);
    $password = mail_env_value('MAIL_PASSWORD', false);

    if ($username !== '' || $password !== '') {
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
    }

    $encryption = strtolower(mail_env_value('MAIL_ENCRYPTION', false));
    if ($encryption === 'ssl' || $encryption === 'smtps' || ($encryption === '' && (int) $mail->Port === 465)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($encryption === 'tls' || ($encryption === '' && (int) $mail->Port === 587)) {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    $mail->CharSet = 'UTF-8';
    $mail->setFrom(mail_env_value('MAIL_FROM_ADDRESS'), mail_env_value('MAIL_FROM_NAME'));
    $mail->addAddress($toEmail, $toName !== '' ? $toName : $toEmail);
    $mail->Subject = $subject;
    $mail->isHTML(true);
    $mail->Body = $htmlBody;
    $mail->AltBody = $textBody;
    $mail->send();
}

function send_verification_email(string $toEmail, string $toName, string $token): void
{
    $link = configured_app_url('auth/verify-email.php?email=' . rawurlencode($toEmail) . '&token=' . rawurlencode($token));
    $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    send_app_mail(
        $toEmail,
        $toName,
        'Verifikasi Email PusakaRasa',
        '<p>Halo ' . $safeName . ',</p><p>Klik tautan berikut untuk memverifikasi email akun PusakaRasa Anda:</p><p><a href="' . $safeLink . '">Verifikasi Email</a></p><p>Tautan ini berlaku selama 24 jam.</p>',
        "Halo {$toName},\n\nBuka tautan berikut untuk memverifikasi email akun PusakaRasa Anda:\n{$link}\n\nTautan ini berlaku selama 24 jam."
    );
}

function send_password_reset_email(string $toEmail, string $toName, string $token): void
{
    $link = configured_app_url('auth/reset-password.php?email=' . rawurlencode($toEmail) . '&token=' . rawurlencode($token));
    $safeName = htmlspecialchars($toName !== '' ? $toName : $toEmail, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

    send_app_mail(
        $toEmail,
        $toName,
        'Reset Password PusakaRasa',
        '<p>Halo ' . $safeName . ',</p><p>Klik tautan berikut untuk mengatur ulang kata sandi akun PusakaRasa Anda:</p><p><a href="' . $safeLink . '">Reset Password</a></p><p>Tautan ini berlaku selama 1 jam.</p>',
        "Halo {$toName},\n\nBuka tautan berikut untuk mengatur ulang kata sandi akun PusakaRasa Anda:\n{$link}\n\nTautan ini berlaku selama 1 jam."
    );
}
