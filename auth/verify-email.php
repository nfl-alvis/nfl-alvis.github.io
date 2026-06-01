<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$email = strtolower(trim((string) ($_GET['email'] ?? '')));
$token = trim((string) ($_GET['token'] ?? ''));

if ($email !== '') {
    $user = find_user_by_email($email);

    if ($user && (int) ($user['email_verified'] ?? 0) === 1) {
        set_flash('success', 'Email akun Anda sudah terverifikasi. Silakan masuk.');
        redirect_to('login.php');
    }
}

if (verify_user_email_token($email, $token)) {
    set_flash('success', 'Email berhasil diverifikasi. Silakan masuk.');
    redirect_to('login.php');
}

set_flash('error', 'Link verifikasi tidak valid atau sudah kedaluwarsa. Silakan kirim ulang verifikasi.');
redirect_to('auth/resend-verification.php?' . http_build_query(['email' => $email]));
