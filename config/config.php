<?php
/**
 * Environment-neutral application configuration.
 *
 * Secrets belong in environment variables or config/config.local.php. The
 * local file is intentionally ignored by Git; copy config.example.php to
 * create it on each server.
 */

$appLocalConfig = [];
$appLocalConfigPath = __DIR__ . '/config.local.php';
if (is_file($appLocalConfigPath)) {
    $loadedConfig = require $appLocalConfigPath;
    if (!is_array($loadedConfig)) {
        throw new RuntimeException('config/config.local.php must return an array.');
    }
    $appLocalConfig = $loadedConfig;
}

/** Environment variables take precedence over the ignored local file. */
function app_config_value(string $key, $default = null)
{
    global $appLocalConfig;

    $environmentValue = getenv($key);
    if ($environmentValue !== false && $environmentValue !== '') {
        return $environmentValue;
    }

    return array_key_exists($key, $appLocalConfig) ? $appLocalConfig[$key] : $default;
}

function app_config_bool(string $key, bool $default = false): bool
{
    $value = app_config_value($key, $default);
    if (is_bool($value)) {
        return $value;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

/** Detect HTTPS directly, or from a trusted reverse proxy when enabled. */
function app_request_is_https(): bool
{
    if ((!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }

    if (app_config_bool('TRUST_PROXY_HEADERS')) {
        $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        return $forwardedProto === 'https';
    }

    return false;
}

/** Detect whether the app is at the domain root or inside a subfolder. */
function app_detect_base_path(): string
{
    $configuredPath = app_config_value('APP_BASE_PATH');
    if ($configuredPath !== null && $configuredPath !== '') {
        $path = '/' . trim(str_replace('\\', '/', (string) $configuredPath), '/');
        return $path === '/' ? '' : $path;
    }

    $documentRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $applicationRoot = realpath(dirname(__DIR__));
    if ($documentRoot && $applicationRoot) {
        $documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
        $applicationRoot = rtrim(str_replace('\\', '/', $applicationRoot), '/');
        if (strncasecmp($applicationRoot, $documentRoot, strlen($documentRoot)) === 0) {
            $relative = substr($applicationRoot, strlen($documentRoot));
            if ($relative === '' || str_starts_with($relative, '/')) {
                return $relative === '' ? '' : '/' . trim($relative, '/');
            }
        }
    }

    $scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (basename($scriptDirectory) === 'api') {
        $scriptDirectory = dirname($scriptDirectory);
    }
    return $scriptDirectory === '/' || $scriptDirectory === '.' ? '' : '/' . trim($scriptDirectory, '/');
}

function app_detect_url(): string
{
    $scheme = app_request_is_https() ? 'https' : 'http';
    $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');

    // Never reflect an arbitrary Host header into customer tracking links.
    if (!preg_match('/\A(?:[A-Za-z0-9.-]+|\[[A-Fa-f0-9:]+\])(?::[0-9]{1,5})?\z/', $host)) {
        $host = 'localhost';
    }

    return $scheme . '://' . $host . app_detect_base_path();
}

// Database values have no production credentials in source control.
define('DB_HOST', (string) app_config_value('DB_HOST', '127.0.0.1'));
define('DB_PORT', (string) app_config_value('DB_PORT', '3306'));
define('DB_NAME', (string) app_config_value('DB_NAME', ''));
define('DB_USER', (string) app_config_value('DB_USER', ''));
define('DB_PASS', (string) app_config_value('DB_PASS', ''));
define('DB_CHARSET', (string) app_config_value('DB_CHARSET', 'utf8mb4'));

$configuredAppUrl = trim((string) app_config_value('APP_URL', ''));
define('APP_URL', rtrim($configuredAppUrl !== '' ? $configuredAppUrl : app_detect_url(), '/'));
define('APP_NAME', (string) app_config_value('APP_NAME', 'Order Status Tracker'));
define('APP_ENV', (string) app_config_value('APP_ENV', 'production'));

define('SESSION_NAME', (string) app_config_value('SESSION_NAME', 'tracker_session'));
define('LOGIN_MAX_ATTEMPTS', (int) app_config_value('LOGIN_MAX_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_MINUTES', (int) app_config_value('LOGIN_LOCKOUT_MINUTES', 15));

date_default_timezone_set((string) app_config_value('APP_TIMEZONE', 'Asia/Karachi'));
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
