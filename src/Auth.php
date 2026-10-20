<?php

namespace App;

class Auth
{
    // Fixed, valid bcrypt hash of a random string — used to burn CPU time on
    // "unknown email" attempts so login timing doesn't reveal whether an
    // account exists.
    private const DUMMY_HASH = '$2y$10$abcdefghijklmnopqrstuuVYVQ9x0m8n6y7X8Z1c2v3B4n5M6a7Su';

    private static ?array $cachedUser = null;
    private static bool $lookedUp = false;

    /** Attempt a login. Returns true on success (session is established). */
    public static function attempt(string $email, string $password): bool
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            password_verify($password, self::DUMMY_HASH);
            return false;
        }

        if (!$user['is_active']) {
            return false;
        }

        if ($user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            self::registerFailedAttempt((int) $user['id'], (int) $user['failed_login_attempts']);
            return false;
        }

        self::registerSuccess((int) $user['id']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];

        return true;
    }

    private static function registerFailedAttempt(int $userId, int $currentAttempts): void
    {
        $attempts = $currentAttempts + 1;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $stmt = db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?');
            $stmt->execute([LOGIN_LOCKOUT_MINUTES, $userId]);
        } else {
            $stmt = db()->prepare('UPDATE users SET failed_login_attempts = ? WHERE id = ?');
            $stmt->execute([$attempts, $userId]);
        }
    }

    private static function registerSuccess(int $userId): void
    {
        $stmt = db()->prepare('UPDATE users SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /** Current logged-in user row (fresh from DB, re-checks is_active), or null. */
    public static function user(): ?array
    {
        if (self::$lookedUp) {
            return self::$cachedUser;
        }
        self::$lookedUp = true;

        if (empty($_SESSION['user_id'])) {
            return self::$cachedUser = null;
        }

        $stmt = db()->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) {
            self::logout();
            return self::$cachedUser = null;
        }

        return self::$cachedUser = $user;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && $user['role'] === 'admin';
    }

    /** For classic pages: redirect to login if not authenticated. */
    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user === null) {
            Response::redirect(APP_URL . '/login.php');
        }
        return $user;
    }

    /** For classic pages: redirect to dashboard if not an admin. */
    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] !== 'admin') {
            Response::redirect(APP_URL . '/dashboard.php');
        }
        return $user;
    }
}
