<?php

namespace App\Models;

class Order
{
    /** Orders list for the dashboard. Admins see everyone's; staff see only their own. */
    public static function listForUser(int $userId, bool $isAdmin, ?string $search = null): array
    {
        self::syncDateCompletion();

        $sql = 'SELECT o.*, u.name AS owner_name,
                       (SELECT COUNT(*) FROM order_statuses os WHERE os.order_id = o.id) AS total_steps,
                       (SELECT COUNT(*) FROM order_statuses os WHERE os.order_id = o.id AND os.is_completed = 1) AS completed_steps,
                       (SELECT name FROM order_statuses os WHERE os.order_id = o.id AND os.is_completed = 1 ORDER BY os.sort_order DESC LIMIT 1) AS current_status_name
                FROM orders o
                JOIN users u ON u.id = o.user_id
                WHERE 1 = 1';
        $params = [];

        if (!$isAdmin) {
            $sql .= ' AND o.user_id = ?';
            $params[] = $userId;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND o.order_number LIKE ?';
            $params[] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY o.created_at DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/\A[A-Za-z0-9_-]{43}\z/', $token)) {
            return null;
        }
        $stmt = db()->prepare('SELECT * FROM orders WHERE BINARY public_token = ?');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function steps(int $orderId): array
    {
        self::syncDateCompletion($orderId);

        $stmt = db()->prepare('SELECT * FROM order_statuses WHERE order_id = ? ORDER BY sort_order ASC, id ASC');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    /**
     * A step with an `actual_date` reached (today, Pakistan time, or earlier)
     * auto-completes itself plus every step before it — same prefix-complete
     * rule as markCurrent(), just triggered by the calendar instead of a
     * click. Never un-completes anything, so it's safe to call on every read.
     * Pass $orderId to scope to one order; omit to sweep every order (used
     * before the dashboard listing, which doesn't go through steps()).
     */
    public static function syncDateCompletion(?int $orderId = null): void
    {
        $today = (new \DateTime('now', new \DateTimeZone('Asia/Karachi')))->format('Y-m-d');

        $sql = 'UPDATE order_statuses os
                JOIN (
                    SELECT order_id, MAX(sort_order) AS max_sort
                    FROM order_statuses
                    WHERE actual_date IS NOT NULL AND actual_date <= ?
                    GROUP BY order_id
                ) reached ON reached.order_id = os.order_id
                SET os.is_completed = 1, os.completed_at = COALESCE(os.completed_at, NOW())
                WHERE os.sort_order <= reached.max_sort AND os.is_completed = 0';
        $params = [$today];

        if ($orderId !== null) {
            $sql .= ' AND os.order_id = ?';
            $params[] = $orderId;
        }

        db()->prepare($sql)->execute($params);
    }

    public static function canAccess(array $order, array $user): bool
    {
        return $user['role'] === 'admin' || (int) $order['user_id'] === (int) $user['id'];
    }

    public static function orderNumberExists(string $orderNumber, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = ? AND id != ? LIMIT 1');
            $stmt->execute([$orderNumber, $excludeId]);
        } else {
            $stmt = db()->prepare('SELECT id FROM orders WHERE order_number = ? LIMIT 1');
            $stmt->execute([$orderNumber]);
        }
        return (bool) $stmt->fetch();
    }

    /** Create an order plus its initial timeline steps in one transaction. */
    public static function create(int $userId, string $orderNumber, string $startDate, bool $advancedEnabled, array $steps): int
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $token = self::generateToken();
            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, order_number, start_date, advanced_enabled, public_token) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $orderNumber, $startDate, $advancedEnabled ? 1 : 0, $token]);
            $orderId = (int) $pdo->lastInsertId();

            self::writeSteps($orderId, $startDate, $steps, []);

            // A new order can already have a current step selected in the
            // form. Persist that initial progress in the same transaction.
            $lastCompletedSort = null;
            foreach ($steps as $index => $step) {
                if (!empty($step['is_completed'])) {
                    $lastCompletedSort = 10 + ($index * 10);
                }
            }
            if ($lastCompletedSort !== null) {
                $pdo->prepare(
                    'UPDATE order_statuses SET is_completed = 1, completed_at = NOW()
                     WHERE order_id = ? AND sort_order <= ?'
                )->execute([$orderId, $lastCompletedSort]);
            }

            $pdo->commit();
            return $orderId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Update order fields and reconcile its steps (update existing / insert new / delete removed). */
    public static function update(int $orderId, string $orderNumber, string $startDate, bool $advancedEnabled, array $steps): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE orders SET order_number = ?, start_date = ?, advanced_enabled = ? WHERE id = ?');
            $stmt->execute([$orderNumber, $startDate, $advancedEnabled ? 1 : 0, $orderId]);

            $existingStmt = $pdo->prepare('SELECT id FROM order_statuses WHERE order_id = ?');
            $existingStmt->execute([$orderId]);
            $existingIds = array_map('intval', array_column($existingStmt->fetchAll(), 'id'));

            self::writeSteps($orderId, $startDate, $steps, $existingIds);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Upserts steps in the given order, recomputing expected_date as a running
     * total of each step's own expected duration from the order's start date.
     * Existing steps not present in $steps are deleted. is_completed/completed_at
     * are never touched here — only markCurrent() and syncDateCompletion() change those.
     */
    private static function writeSteps(int $orderId, string $startDate, array $steps, array $existingIds): void
    {
        $pdo = db();

        $updateStmt = $pdo->prepare(
            'UPDATE order_statuses SET status_id=?, name=?, description=?, expected_duration_value=?, expected_duration_unit=?,
                sort_order=?, expected_date=?, custom_message=?, actual_date=? WHERE id=? AND order_id=?'
        );
        $insertStmt = $pdo->prepare(
            'INSERT INTO order_statuses
                (order_id, status_id, name, description, expected_duration_value, expected_duration_unit, sort_order, expected_date, custom_message, actual_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $cumulativeDays = 0.0;
        $sort = 10;
        $keptIds = [];

        foreach ($steps as $step) {
            $unit = in_array($step['expected_duration_unit'] ?? 'instant', Status::UNITS, true)
                ? ($step['expected_duration_unit'] ?? 'instant')
                : 'instant';
            $value = (isset($step['expected_duration_value']) && $step['expected_duration_value'] !== '' && $step['expected_duration_value'] !== null)
                ? (float) $step['expected_duration_value']
                : null;

            $cumulativeDays += self::durationToDays($value, $unit);
            $expectedDate = date('Y-m-d', strtotime($startDate . sprintf(' +%d days', (int) round($cumulativeDays))));

            $statusId = (isset($step['status_id']) && $step['status_id'] !== '') ? (int) $step['status_id'] : null;
            $description = ($step['description'] ?? '') !== '' ? $step['description'] : null;
            $customMessage = ($step['custom_message'] ?? '') !== '' ? $step['custom_message'] : null;
            $actualDate = ($step['actual_date'] ?? '') !== '' ? $step['actual_date'] : null;

            $id = isset($step['id']) ? (int) $step['id'] : null;

            if ($id !== null && in_array($id, $existingIds, true)) {
                $updateStmt->execute([
                    $statusId, $step['name'], $description, $value, $unit,
                    $sort, $expectedDate, $customMessage, $actualDate,
                    $id, $orderId,
                ]);
                $keptIds[] = $id;
            } else {
                $insertStmt->execute([
                    $orderId, $statusId, $step['name'], $description, $value, $unit,
                    $sort, $expectedDate, $customMessage, $actualDate,
                ]);
            }
            $sort += 10;
        }

        $toDelete = array_diff($existingIds, $keptIds);
        if ($toDelete) {
            $in = implode(',', array_fill(0, count($toDelete), '?'));
            $delStmt = $pdo->prepare("DELETE FROM order_statuses WHERE order_id = ? AND id IN ($in)");
            $delStmt->execute(array_merge([$orderId], array_values($toDelete)));
        }
    }

    /** Mark a step (and every step before it) complete; uncomplete everything after it. */
    public static function markCurrent(int $orderId, int $orderStatusId): void
    {
        $stmt = db()->prepare('SELECT sort_order FROM order_statuses WHERE id = ? AND order_id = ?');
        $stmt->execute([$orderStatusId, $orderId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new \RuntimeException('Step not found');
        }
        $sortOrder = $row['sort_order'];

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE order_statuses SET is_completed = 1, completed_at = COALESCE(completed_at, NOW()) WHERE order_id = ? AND sort_order <= ?')
                ->execute([$orderId, $sortOrder]);
            $pdo->prepare('UPDATE order_statuses SET is_completed = 0, completed_at = NULL WHERE order_id = ? AND sort_order > ?')
                ->execute([$orderId, $sortOrder]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function regenerateToken(int $orderId): string
    {
        $token = self::generateToken();
        $stmt = db()->prepare('UPDATE orders SET public_token = ? WHERE id = ?');
        $stmt->execute([$token, $orderId]);
        return $token;
    }

    public static function delete(int $orderId): void
    {
        $stmt = db()->prepare('DELETE FROM orders WHERE id = ?');
        $stmt->execute([$orderId]);
    }

    private static function durationToDays(?float $value, string $unit): float
    {
        if ($value === null) {
            return 0.0;
        }
        return match ($unit) {
            'days' => $value,
            'weeks' => $value * 7,
            'months' => $value * 30,
            default => 0.0, // instant
        };
    }

    private static function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
