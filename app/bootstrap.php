<?php

declare(strict_types=1);

define('BASE_PATH', __DIR__ . '/..');
define('APP_PATH', __DIR__);
define('VIEWS_PATH', BASE_PATH . '/views');

$config = require BASE_PATH . '/config.php';
$GLOBALS['config'] = $config;

date_default_timezone_set($config['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('meridiano_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once APP_PATH . '/helpers.php';
require_once APP_PATH . '/db.php';
require_once APP_PATH . '/auth.php';
require_once APP_PATH . '/icons.php';
