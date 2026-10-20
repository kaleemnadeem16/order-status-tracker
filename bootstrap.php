<?php
/**
 * Shared bootstrap for every page and API endpoint: config, autoloading,
 * DB connection, and a hardened session.
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/helpers.php';

$isHttps = app_request_is_https() || str_starts_with(strtolower(APP_URL), 'https://');

ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isHttps ? '1' : '0');
ini_set('session.use_only_cookies', '1');

session_name(SESSION_NAME);
session_start();
