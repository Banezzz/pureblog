<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_setup_redirect();

start_admin_session();
require_admin_login();

header('Location: ' . admin_url('settings-site.php'), true, 302);
exit;
