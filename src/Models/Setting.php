<?php

namespace App\Models;

class Setting
{
    public static function get(string $key): ?string
    {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return $value !== false ? $value : null;
    }

    public static function set(string $key, ?string $value): void
    {
        $stmt = db()->prepare(
            'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        $stmt->execute([$key, $value]);
    }
}
