<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Models\Setting;
use App\Models\Status;

$currentUser = Auth::requireLogin();
$statuses = Status::all();
$defaultOrderDuration = Setting::get('default_order_duration');

$pageTitle = 'Status Library';
$activeNav = 'statuses';
$extraScripts = ['assets/js/statuses.js'];
require __DIR__ . '/partials/head.php';
?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-medium text-ink">Status Library</h1>
            <p class="mt-0.5 text-sm text-neutral-500">Reusable statuses staff can pick from when building an order's timeline.</p>
        </div>
        <button type="button" class="btn-primary shrink-0" data-action="status-add-open">+ Add Status</button>
    </div>

    <div class="card mb-6 p-4 sm:p-5">
        <label class="label" for="default-duration-input">Typical Order Duration</label>
        <div class="flex gap-2">
            <input class="input" type="text" id="default-duration-input" maxlength="100"
                   placeholder="e.g. 16 weeks" value="<?= h($defaultOrderDuration ?? '') ?>">
            <button type="button" class="btn-secondary shrink-0" data-action="save-default-duration">Save</button>
        </div>
        <p class="mt-2 text-xs text-neutral-400">A reference note only — shown to staff and customers. It doesn't affect any dates, which are still calculated from each step's own duration.</p>
    </div>

    <div id="status-list" class="space-y-2"></div>
</div>

<script type="application/json" id="statuses-data"><?= json_encode($statuses, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

<?php require __DIR__ . '/partials/status-modal.php'; ?>
<?php require __DIR__ . '/partials/foot.php'; ?>
