<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

require_role(ROLE_SUPER_ADMIN);

$userId = (int) ($_GET['id'] ?? 0);

redirect_to('admin-users.php' . ($userId > 0 ? '?edit=' . $userId : ''));
