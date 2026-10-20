<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Models\Order;

$currentUser = Auth::requireLogin();

$search = trim((string) ($_GET['search'] ?? ''));
$orders = Order::listForUser((int) $currentUser['id'], $currentUser['role'] === 'admin', $search !== '' ? $search : null);

$pageTitle = 'Orders';
$activeNav = 'dashboard';
$extraScripts = ['assets/js/qrcode.min.js', 'assets/js/qr-modal.js', 'assets/js/dashboard.js'];
require __DIR__ . '/partials/head.php';
?>
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-medium text-ink">Orders</h1>
            <p class="mt-0.5 text-sm text-neutral-500"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?><?= $currentUser['role'] === 'admin' ? ' · all staff' : '' ?></p>
        </div>
        <a href="<?= APP_URL ?>/order-add.php" class="btn-primary">+ New Order</a>
    </div>

    <form method="get" class="mb-6 flex gap-2">
        <input type="search" name="search" value="<?= h($search) ?>" placeholder="Search order number&hellip;" class="input">
        <button class="btn-secondary shrink-0" type="submit">Search</button>
        <?php if ($search !== ''): ?>
        <a href="<?= APP_URL ?>/dashboard.php" class="btn-secondary shrink-0">Clear</a>
        <?php endif; ?>
    </form>

    <?php if (empty($orders)): ?>
    <div class="card p-10 text-center">
        <p class="text-sm text-neutral-500"><?= $search !== '' ? 'No orders match your search.' : 'No orders yet. Create your first one to get started.' ?></p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($orders as $order):
            $steps = Order::steps((int) $order['id']);
            $total = count($steps);
            $completed = (int) $order['completed_steps'];
            $currentName = $order['current_status_name'];
            $selectedStepId = null;
            foreach ($steps as $step) {
                if ($step['is_completed']) {
                    $selectedStepId = $step['id'];
                }
            }
            if ($selectedStepId === null && $steps) {
                $selectedStepId = $steps[0]['id'];
            }
            $url = public_track_url($order['public_token']);
        ?>
        <div class="card p-4 sm:p-5" data-order-card data-order-id="<?= (int) $order['id'] ?>">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-medium text-ink">#<?= h($order['order_number']) ?></h3>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <span class="text-xs text-neutral-400">by <?= h($order['owner_name']) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="mt-0.5 text-sm text-neutral-500">
                        Started <?= h(format_date($order['start_date'])) ?> &middot;
                        <span id="progress-text-<?= (int) $order['id'] ?>"><?= $completed ?>/<?= $total ?> steps</span>
                    </p>
                    <span id="status-badge-<?= (int) $order['id'] ?>" class="<?= $completed > 0 ? 'badge-done' : 'badge-pending' ?> mt-2">
                        <?= h($currentName ?: 'Not started') ?>
                    </span>
                </div>

                <div class="flex flex-wrap gap-2 sm:shrink-0">
                    <a href="<?= h($url) ?>" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-sm">View</a>
                    <a href="<?= APP_URL ?>/order-edit.php?id=<?= (int) $order['id'] ?>" class="btn-secondary btn-sm">Edit</a>
                    <button type="button" class="btn-secondary btn-sm" data-action="link-open"
                            data-order-id="<?= (int) $order['id'] ?>" data-order-number="<?= h($order['order_number']) ?>" data-url="<?= h($url) ?>">Link / QR</button>
                    <button type="button" class="btn-danger btn-sm" data-action="order-delete" data-order-id="<?= (int) $order['id'] ?>">Delete</button>
                </div>
            </div>

            <?php if ($steps): ?>
            <div class="mt-4 flex flex-col gap-2 border-t border-neutral-100 pt-4 sm:flex-row sm:items-center">
                <label class="text-xs text-neutral-500 sm:whitespace-nowrap" for="quick-select-<?= (int) $order['id'] ?>">Quick update:</label>
                <select id="quick-select-<?= (int) $order['id'] ?>" class="input select-input sm:max-w-xs">
                    <?php foreach ($steps as $step): ?>
                    <option value="<?= (int) $step['id'] ?>" <?= (int) $step['id'] === (int) $selectedStepId ? 'selected' : '' ?>>
                        <?= h($step['name']) ?><?= $step['is_completed'] ? ' ✓' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-primary h-[42px] sm:shrink-0" data-action="order-quick-update" data-order-id="<?= (int) $order['id'] ?>">Update</button>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/qr-modal.php'; ?>
<?php require __DIR__ . '/partials/foot.php'; ?>
