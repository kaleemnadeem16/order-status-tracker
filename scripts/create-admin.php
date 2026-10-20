<?php
/** Create the first administrator without storing a password in source. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['email:', 'name::']);
$email = strtolower(trim((string) ($options['email'] ?? '')));
$name = trim((string) ($options['name'] ?? 'Admin'));

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php scripts/create-admin.php --email=you@example.com [--name=\"Admin Name\"]\n");
    exit(1);
}
if ($name === '' || mb_strlen($name) > 100) {
    fwrite(STDERR, "The administrator name must be between 1 and 100 characters.\n");
    exit(1);
}

$existing = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$existing->execute([$email]);
if ($existing->fetch()) {
    fwrite(STDERR, "A user with that email already exists; no changes were made.\n");
    exit(1);
}

$temporaryPassword = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
$stmt = db()->prepare(
    'INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)'
);
$stmt->execute([$name, $email, password_hash($temporaryPassword, PASSWORD_DEFAULT), 'admin']);

echo "Administrator created successfully.\n";
echo "Email: {$email}\n";
echo "Temporary password: {$temporaryPassword}\n";
echo "Sign in and change this password immediately. It will not be shown again.\n";
