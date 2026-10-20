<?php

namespace App;

class Validator
{
    public static function required($value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }

    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function date(string $value): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return $d !== false && $d->format('Y-m-d') === $value;
    }

    public static function inArray($value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }

    public static function maxLength(string $value, int $max): bool
    {
        return mb_strlen($value) <= $max;
    }
}
