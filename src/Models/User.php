<?php

namespace App\Models;

class User
{
    public static function all(): array
    {
        return db()->query(
            'SELECT id, name, email, role, is_active, last_login_at, created_at FROM users ORDER BY created_at ASC'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, name, email, role, is_active, last_login_at, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $excludeId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
        }
        return (bool) $stmt->fetch();
    }

    public static function create(string $name, string $email, string $password, string $role, int $createdBy): int
    {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password_hash, role, created_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $createdBy]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, string $name, string $email, string $role): void
    {
        $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
        $stmt->execute([$name, $email, $role, $id]);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = db()->prepare('UPDATE users SET is_active = ? WHERE id = ?');
        $stmt->execute([$active ? 1 : 0, $id]);
    }

    public static function setPassword(int $id, string $password): void
    {
        $stmt = db()->prepare('UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public static function verifyPassword(int $id, string $password): bool
    {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row && password_verify($password, $row['password_hash']);
    }

    public static function activeAdminCount(): int
    {
        return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")->fetchColumn();
    }
}
