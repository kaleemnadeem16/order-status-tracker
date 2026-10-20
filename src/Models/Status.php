<?php

namespace App\Models;

class Status
{
    public const UNITS = ['instant', 'days', 'weeks', 'months'];

    public static function all(): array
    {
        return db()->query('SELECT * FROM statuses ORDER BY sort_order ASC, id ASC')->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM statuses WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, ?string $description, ?float $durationValue, string $durationUnit, int $createdBy): int
    {
        $nextSort = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) + 10 FROM statuses')->fetchColumn();
        $stmt = db()->prepare(
            'INSERT INTO statuses (name, description, expected_duration_value, expected_duration_unit, sort_order, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $description, $durationValue, $durationUnit, $nextSort, $createdBy]);
        return (int) db()->lastInsertId();
    }

    public static function update(int $id, string $name, ?string $description, ?float $durationValue, string $durationUnit): void
    {
        $stmt = db()->prepare(
            'UPDATE statuses SET name = ?, description = ?, expected_duration_value = ?, expected_duration_unit = ? WHERE id = ?'
        );
        $stmt->execute([$name, $description, $durationValue, $durationUnit, $id]);
    }

    public static function reorder(array $orderedIds): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        $sort = 10;
        $stmt = $pdo->prepare('UPDATE statuses SET sort_order = ? WHERE id = ?');
        foreach ($orderedIds as $id) {
            $stmt->execute([$sort, (int) $id]);
            $sort += 10;
        }
        $pdo->commit();
    }

    public static function delete(int $id): void
    {
        // order_statuses.status_id is ON DELETE SET NULL, so past orders keep their
        // snapshotted step data even after the template is removed.
        $stmt = db()->prepare('DELETE FROM statuses WHERE id = ?');
        $stmt->execute([$id]);
    }
}
