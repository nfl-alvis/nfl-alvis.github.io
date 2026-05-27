<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

$productId = (int) ($_GET['id'] ?? 0);

redirect_to('admin-products.php' . ($productId > 0 ? '?edit=' . $productId : ''));
