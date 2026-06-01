<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../config/google.php';

function auth_redirect_to_root(string $path): never
{
    redirect_to($path);
}

if (is_logged_in()) {
    auth_redirect_to_root(nav_target_for_user(current_user()));
}

try {
    $client = google_oauth_client();
    $state = bin2hex(random_bytes(32));
    $_SESSION['google_oauth_state'] = $state;
    $client->setState($state);

    header('Location: ' . $client->createAuthUrl());
    exit;
} catch (Throwable $exception) {
    unset($_SESSION['google_oauth_state']);
    set_flash('error', 'Login dengan Google belum dapat digunakan. Periksa konfigurasi OAuth.');
    auth_redirect_to_root('login.php');
}
