<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Google\Client;
use Google\Service\Oauth2;

require_once __DIR__ . '/../vendor/autoload.php';

function google_env_value(string $key): string
{
    static $loaded = false;

    if (!$loaded) {
        Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
        $loaded = true;
    }

    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    $value = is_string($value) ? trim($value) : '';

    if ($value === '') {
        throw new RuntimeException('Konfigurasi Google OAuth belum lengkap.');
    }

    return $value;
}

function google_oauth_client(): Client
{
    static $client = null;

    if ($client instanceof Client) {
        return $client;
    }

    $client = new Client();
    $client->setClientId(google_env_value('GOOGLE_CLIENT_ID'));
    $client->setClientSecret(google_env_value('GOOGLE_CLIENT_SECRET'));
    $client->setRedirectUri(google_env_value('GOOGLE_REDIRECT_URI'));
    $client->setAccessType('online');
    $client->setPrompt('select_account');
    $client->setIncludeGrantedScopes(true);
    $client->addScope([
        Oauth2::OPENID,
        Oauth2::USERINFO_EMAIL,
        Oauth2::USERINFO_PROFILE,
    ]);

    return $client;
}
