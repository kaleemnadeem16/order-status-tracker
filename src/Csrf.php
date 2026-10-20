<?php

namespace App;

class Csrf
{
    private const SESSION_KEY = 'csrf_token';

    /** Get (or lazily create) the CSRF token for this session. */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /** Verify a token supplied by the client (form field or header). Constant-time compare. */
    public static function verify(?string $token): bool
    {
        if (!$token || empty($_SESSION[self::SESSION_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::SESSION_KEY], $token);
    }

    /** Render a hidden input for classic HTML forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }
}
