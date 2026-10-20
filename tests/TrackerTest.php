<?php
use PHPUnit\Framework\TestCase;
use App\Csrf;
use App\Validator;
use App\Models\Order;
use App\Models\Status;

final class TrackerTest extends TestCase
{
    public function testValidatorsRejectInvalidInput(): void
    {
        self::assertFalse(Validator::date('2026-02-30'));
        self::assertTrue(Validator::date('2026-10-21'));
        self::assertFalse(Validator::email('invalid'));
        self::assertTrue(Validator::email('demo@example.com'));
        self::assertFalse(Validator::required('   '));
        self::assertTrue(Validator::maxLength('é', 1));
        self::assertFalse(Validator::inArray('1', [1]));
    }

    public function testCsrfRequiresExactSessionToken(): void
    {
        $_SESSION = [];
        self::assertFalse(Csrf::verify(null));
        self::assertFalse(Csrf::verify('untrusted'));
        $token = Csrf::token();
        self::assertSame($token, Csrf::token());
        self::assertTrue(Csrf::verify($token));
        self::assertFalse(Csrf::verify($token . 'x'));
    }

    public function testOrderTokensOwnershipAndTimeline(): void
    {
        $email = bin2hex(random_bytes(8)) . '@example.com';
        db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
            ->execute(['Test', $email, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT), 'staff']);
        $userId = (int) db()->lastInsertId();
        try {
            $id = Order::create($userId, 'TEST-' . bin2hex(random_bytes(8)), '2026-10-21', false,
                [['name' => 'Received'], ['name' => 'Shipped', 'expected_duration_value' => 2, 'expected_duration_unit' => 'weeks']]);
            $order = Order::find($id);
            $token = $order['public_token'];
            self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
            self::assertSame($id, (int) Order::findByToken($token)['id']);
            self::assertNull(Order::findByToken(str_repeat('!', 43)));
            self::assertNull(Order::findByToken(str_repeat('a', 42)));
            $caseChanged = preg_replace_callback('/[A-Za-z]/', fn($m) => ctype_lower($m[0]) ? strtoupper($m[0]) : strtolower($m[0]), $token, 1);
            self::assertNotSame($token, $caseChanged);
            self::assertNull(Order::findByToken($caseChanged));
            self::assertTrue(Order::canAccess($order, ['id' => $userId, 'role' => 'staff']));
            self::assertFalse(Order::canAccess($order, ['id' => $userId + 1, 'role' => 'staff']));
            self::assertTrue(Order::canAccess($order, ['id' => $userId + 1, 'role' => 'admin']));
            $steps = Order::steps($id);
            self::assertSame(['Received', 'Shipped'], array_column($steps, 'name'));
            self::assertSame('2026-11-04', $steps[1]['expected_date']);
            Order::markCurrent($id, (int) $steps[1]['id']);
            self::assertSame([1,1], array_map('intval', array_column(Order::steps($id), 'is_completed')));
            $newToken = Order::regenerateToken($id);
            self::assertNotSame($token, $newToken);
            self::assertNull(Order::findByToken($token));
            self::assertSame($id, (int) Order::findByToken($newToken)['id']);
        } finally { db()->prepare('DELETE FROM users WHERE id=?')->execute([$userId]); }
    }

    public function testStatusOrdering(): void
    {
        db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
            ->execute(['Test', bin2hex(random_bytes(8)).'@example.com', 'not-a-login-hash', 'admin']);
        $adminId = (int) db()->lastInsertId();
        $id1 = Status::create('Test first', null, null, 'instant', $adminId);
        $id2 = Status::create('Test second', null, null, 'instant', $adminId);
        try {
            Status::reorder([$id2, $id1]);
            self::assertSame(10, (int) Status::find($id2)['sort_order']);
            self::assertSame(20, (int) Status::find($id1)['sort_order']);
        } finally { Status::delete($id1); Status::delete($id2); db()->prepare('DELETE FROM users WHERE id=?')->execute([$adminId]); }
    }
}
