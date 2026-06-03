<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

function favorite_json_response(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

if (!is_post()) {
    favorite_json_response(['ok' => false, 'message' => 'Method tidak didukung.'], 405);
}

$user = current_user();
if (!$user) {
    favorite_json_response(['ok' => false, 'message' => 'Silakan masuk terlebih dahulu.'], 401);
}

$action = trim((string) ($_POST['action'] ?? ''));
$userId = (int) $user['id'];

try {
    if ($action === 'toggle') {
        $productReference = trim((string) ($_POST['product_id'] ?? ''));

        if ($productReference === '') {
            favorite_json_response(['ok' => false, 'message' => 'Produk favorit tidak valid.'], 422);
        }

        $favorited = toggle_user_favorite($userId, $productReference);

        favorite_json_response([
            'ok' => true,
            'favorited' => $favorited,
            'favorites' => user_favorite_product_ids($userId),
        ]);
    }

    if ($action === 'clear') {
        clear_user_favorites($userId);

        favorite_json_response([
            'ok' => true,
            'favorites' => [],
        ]);
    }

    if ($action === 'import') {
        $legacyJson = (string) ($_POST['legacy_ids'] ?? '[]');
        $legacyIds = json_decode($legacyJson, true);

        if (!is_array($legacyIds)) {
            $legacyIds = [];
        }

        import_user_favorites($userId, $legacyIds);

        favorite_json_response([
            'ok' => true,
            'favorites' => user_favorite_product_ids($userId),
        ]);
    }

    favorite_json_response(['ok' => false, 'message' => 'Aksi tidak valid.'], 400);
} catch (Throwable $exception) {
    favorite_json_response([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
