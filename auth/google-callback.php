<?php

declare(strict_types=1);

use Google\Service\Oauth2;

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/google.php';

function auth_redirect_to_root(string $path): never
{
    redirect_to($path);
}

if (isset($_GET['error'])) {
    unset($_SESSION['google_oauth_state']);
    set_flash('error', 'Login dengan Google dibatalkan atau gagal.');
    auth_redirect_to_root('login.php');
}

$code = trim((string) ($_GET['code'] ?? ''));
$state = trim((string) ($_GET['state'] ?? ''));
$sessionState = (string) ($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);

if ($code === '') {
    set_flash('error', 'Kode otorisasi Google tidak ditemukan.');
    auth_redirect_to_root('login.php');
}

if ($state === '' || $sessionState === '' || !hash_equals($sessionState, $state)) {
    set_flash('error', 'Sesi Login Google tidak valid. Silakan coba lagi.');
    auth_redirect_to_root('login.php');
}

try {
    ensure_user_google_columns();

    $client = google_oauth_client();
    $token = $client->fetchAccessTokenWithAuthCode($code);

    if (!is_array($token) || isset($token['error'])) {
        throw new RuntimeException('Token Google tidak valid.');
    }

    $client->setAccessToken($token);

    $oauth = new Oauth2($client);
    $googleUser = $oauth->userinfo->get();

    $googleId = trim((string) $googleUser->getId());
    $name = trim((string) $googleUser->getName());
    $email = strtolower(trim((string) $googleUser->getEmail()));
    $picture = trim((string) $googleUser->getPicture());
    $verifiedEmail = $googleUser->getVerifiedEmail();

    if ($googleId === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Data akun Google tidak lengkap.');
    }

    if ($verifiedEmail === false) {
        throw new RuntimeException('Email Google belum terverifikasi.');
    }

    if ($name === '') {
        $name = strstr($email, '@', true) ?: $email;
    }

    $existingUser = find_user_by_email($email);

    if ($existingUser) {
        if ((int) ($existingUser['is_active'] ?? 0) !== 1) {
            set_flash('error', 'Akun Anda sedang nonaktif. Hubungi admin untuk bantuan.');
            auth_redirect_to_root('login.php');
        }

        $stmt = db()->prepare(
            'UPDATE users
             SET google_id = :google_id,
                 picture = :picture,
                 auth_provider = :auth_provider,
                 email_verified = 1,
                 email_verify_token = NULL,
                 email_verify_expires = NULL,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'google_id' => $googleId,
            'picture' => $picture !== '' ? $picture : null,
            'auth_provider' => 'google',
            'id' => $existingUser['id'],
        ]);

        $user = find_user_by_id((int) $existingUser['id']);
    } else {
        $stmt = db()->prepare(
            'INSERT INTO users
                (name, email, password_hash, profile_image, google_id, picture, auth_provider, email_verified, email_verify_token, email_verify_expires, reset_token, reset_expires, role, store_id, is_active, created_at, updated_at)
             VALUES
                (:name, :email, :password_hash, NULL, :google_id, :picture, :auth_provider, 1, NULL, NULL, NULL, NULL, :role, NULL, 1, NOW(), NOW())'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT),
            'google_id' => $googleId,
            'picture' => $picture !== '' ? $picture : null,
            'auth_provider' => 'google',
            'role' => ROLE_USER,
        ]);

        $user = find_user_by_id((int) db()->lastInsertId());
    }

    if (!$user) {
        throw new RuntimeException('User Google gagal disiapkan.');
    }

    login_user($user);
    set_flash('success', 'Berhasil masuk dengan Google.');
    auth_redirect_to_root(nav_target_for_user($user));
} catch (Throwable $exception) {
    set_flash('error', 'Login dengan Google gagal. Silakan coba lagi.');
    auth_redirect_to_root('login.php');
}
