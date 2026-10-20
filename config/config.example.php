<?php
/**
 * Copy this file to config.local.php and enter values for the server.
 * config.local.php is ignored by Git and must never be committed.
 */
return [
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'your_database_name',
    'DB_USER' => 'your_database_user',
    'DB_PASS' => 'replace_with_a_strong_database_password',
    'DB_CHARSET' => 'utf8mb4',

    // Recommended in production. Leave empty to detect the current domain
    // and installation subfolder automatically.
    'APP_URL' => 'https://tracker.example.com',
    'APP_NAME' => 'Order Status Tracker',
    'APP_ENV' => 'production',
    'APP_TIMEZONE' => 'Asia/Karachi',

    // Enable only when HTTPS is terminated by a trusted reverse proxy/CDN.
    'TRUST_PROXY_HEADERS' => false,
];
