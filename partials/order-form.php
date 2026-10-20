<?php
/**
 * Shared order add/edit skeleton. Expects, set by the including page:
 *   $formMode   'add'|'edit'
 *   $order      array|null  (order row + ['steps' => [...]] for edit mode)
 *   $statuses   array       (predefined status library, App\Models\Status::all())
 *   $currentUser
 */
$order = $order ?? null;
$publicUrl = $order ? public_track_url($order['public_token']) : null;
?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-start justify-between gap-3">
        <div>
            <a href="<?= APP_URL ?>/dashboard.php" class="text-xs text-neutral-400 hover:text-ink">&larr; Back to orders</a>
            <h1 id="order-form-title" class="mt-1 text-xl font-medium text-ink"><?= $formMode === 'add' ? 'New Order' : 'Edit Order' ?></h1>
        </div>
        <div id="order-form-actions" class="<?= $formMode === 'edit' ? '' : 'hidden ' ?>flex flex-wrap justify-end gap-2">
            <a id="order-view-link" href="<?= h($publicUrl ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-sm">View</a>
            <button id="order-link-button" type="button" class="btn-secondary btn-sm" data-action="link-open"
                    data-order-id="<?= $formMode === 'edit' ? (int) $order['id'] : '' ?>" data-order-number="<?= h($order['order_number'] ?? '') ?>" data-url="<?= h($publicUrl ?? '') ?>">Link / QR</button>
            <button id="order-delete-button" type="button" class="btn-danger btn-sm" data-action="order-delete-redirect" data-order-id="<?= $formMode === 'edit' ? (int) $order['id'] : '' ?>">Delete</button>
        </div>
    </div>

    <div id="form-error" class="hidden mb-4 rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>

    <div class="card mb-6 p-5 sm:p-6">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="order_number">Order Number</label>
                <input class="input" type="text" id="order_number" maxlength="100" value="<?= h($order['order_number'] ?? '') ?>" placeholder="e.g. DEMO-1024">
            </div>
            <div>
                <label class="label" for="start_date">Start Date</label>
                <input class="input" type="date" id="start_date" value="<?= h($order['start_date'] ?? date('Y-m-d')) ?>">
            </div>
        </div>

        <label class="mt-5 flex cursor-pointer items-center gap-2">
            <input type="checkbox" id="advanced-toggle" class="h-4 w-4 rounded border-neutral-300 text-ink focus:ring-ink">
            <span class="text-sm text-ink">Enable Advanced Features</span>
        </label>
        <p class="mt-1 pl-6 text-xs text-neutral-400">Set a date or custom message on any step, and insert a one-off step for this order only.</p>
    </div>

    <div class="mb-3">
        <h2 class="text-sm font-medium text-ink">Timeline</h2>
        <p class="mt-0.5 text-xs text-neutral-400">
            This order automatically follows your Status Library's default sequence.
            Click a circle to mark it as the current status.
            <span id="advanced-hint" class="hidden">Click a pencil to set a date/message for a step, or a + to insert an exception.</span>
        </p>
        <?php if (!empty($defaultOrderDuration)): ?>
        <p class="mt-0.5 text-xs text-neutral-400">Typical order duration: <?= h($defaultOrderDuration) ?></p>
        <?php endif; ?>
    </div>

    <div id="steps-timeline" class="mb-6"></div>

    <button type="button" class="btn-primary w-full sm:w-auto" data-action="order-save">Save Order</button>
</div>

<script type="application/json" id="statuses-data"><?= json_encode($statuses, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<script type="application/json" id="order-data"><?= json_encode($order, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<div id="order-form-config" class="hidden"
     data-mode="<?= h($formMode) ?>"
     data-order-id="<?= $formMode === 'edit' ? (int) $order['id'] : '' ?>"></div>

<?php require __DIR__ . '/step-modal.php'; ?>
<?php require __DIR__ . '/qr-modal.php'; ?>
