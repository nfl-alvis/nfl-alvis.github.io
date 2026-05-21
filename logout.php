<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

logout_user();
set_flash('success', 'Anda telah keluar dari sesi.');
redirect_to('index.php');
