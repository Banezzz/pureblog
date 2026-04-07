<?php

declare(strict_types=1);

require_once __DIR__ . '/../functions.php';
require_setup_redirect();

start_admin_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ' . base_path() . '/admin/dashboard.php');
    exit;
}

verify_csrf();

// Capture custom admin path before destroying session
$customPath = custom_admin_path();
$loginUrl = $customPath !== ''
    ? base_path() . '/' . $customPath . '/'
    : base_path() . '/admin/index.php';

$_SESSION = [];
session_destroy();

header('Location: ' . $loginUrl);
exit;
