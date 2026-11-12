<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
salon_admin_require();
salon_admin_redirect('dashboard.php');
