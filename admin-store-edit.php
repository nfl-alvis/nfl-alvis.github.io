<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

$storeId = (int) ($_GET['id'] ?? 0);

redirect_to('admin-stores.php' . ($storeId > 0 ? '?edit=' . $storeId : ''));
