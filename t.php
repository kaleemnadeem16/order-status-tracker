<?php

require_once __DIR__ . '/bootstrap.php';

use App\Models\Order;
use App\Models\Setting;

// Keep this page off search engines entirely — it's only ever reachable via
// the unguessable token link or its QR code.
header('X-Robots-Tag: noindex, nofollow, noarchive');

App\RateLimit::enforce((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$token = is_string($_GET['k'] ?? null) ? $_GET['k'] : '';

$order = $token !== '' ? Order::findByToken($token) : null;
if (!$order) { http_response_code(404); }
$steps = $order ? Order::steps((int) $order['id']) : [];
$defaultOrderDuration = Setting::get('default_order_duration');

/** Shared shape for both the JS-rendered timeline and the no-JS fallback list. */
function step_view_model(array $step): array
{
    $done = (bool) $step['is_completed'];

    // Only a completed step ever shows a date — a future actual_date that
    // hasn't been reached yet stays invisible to the customer (no "Expected"
    // line); it's just a staff-side schedule until then. Never falls back to
    // completed_at — only what staff explicitly set in actual_date.
    if ($done) {
        $dateLine = $step['actual_date'] ? 'Completed ' . format_date($step['actual_date']) : 'Completed';
    } else {
        $dateLine = null;
    }

    $durationValue = $step['expected_duration_value'] !== null ? (float) $step['expected_duration_value'] : null;

    return [
        'name' => $step['name'],
        'done' => $done,
        'dateLine' => $dateLine,
        'duration' => format_duration($durationValue, $step['expected_duration_unit']),
        'description' => $step['description'],
        'message' => $step['custom_message'],
    ];
}

$stepViews = array_map('step_view_model', $steps);

$pageTitle = $order ? 'Order #' . $order['order_number'] : 'Not found';
$currentUser = null;
$bodyClass = 'bg-white';
$extraScripts = ['assets/js/timeline-render.js', 'assets/js/public-timeline.js'];
require __DIR__ . '/partials/head.php';
?>
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 sm:py-14">
    <div class="mb-10 flex flex-col items-center border-b border-black/10 pb-8 text-center sm:mb-12">
        <img src="<?= APP_URL ?>/images/wordmark.svg" alt="<?= htmlspecialchars(APP_NAME) ?>" class="mb-8 h-8 w-auto sm:h-9">
        <?php if ($order): ?>
        <p class="mb-3 font-mono text-[9px] uppercase tracking-[0.26em] text-neutral-500">Private client portal</p>
        <h1 class="font-serif text-3xl font-light leading-none text-ink sm:text-4xl">Order <span class="italic">#<?= h($order['order_number']) ?></span></h1>
        <p class="mt-4 font-mono text-[10px] uppercase tracking-[0.16em] text-neutral-500">Started <?= h(format_date($order['start_date'])) ?></p>
        <?php if (!empty($defaultOrderDuration)): ?>
        <p class="mt-0.5 text-xs text-neutral-400">Typical order duration: <?= h($defaultOrderDuration) ?></p>
        <?php endif; ?>
        <?php else: ?>
        <h1 class="text-lg font-medium text-ink">Tracking link not found</h1>
        <p class="mt-1 text-sm text-neutral-500">This link may have been regenerated or is no longer valid. Please contact us for an updated link.</p>
        <?php endif; ?>
    </div>

    <?php if ($order && $stepViews): ?>
    <div id="timeline-container"></div>
    <script type="application/json" id="timeline-data"><?= json_encode($stepViews, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>

    <noscript>
        <ol class="space-y-4">
            <?php foreach ($stepViews as $sv): ?>
            <li class="border-b border-neutral-100 pb-3">
                <p class="font-medium <?= $sv['done'] ? 'text-ink' : 'text-neutral-500' ?>"><?= h($sv['name']) ?></p>
                <?php if ($sv['duration']): ?><p class="text-xs text-neutral-400"><?= h($sv['duration']) ?></p><?php endif; ?>
                <?php if ($sv['dateLine']): ?><p class="text-xs text-emerald-600"><?= h($sv['dateLine']) ?></p><?php endif; ?>
                <?php if ($sv['description']): ?><p class="mt-1 text-sm text-neutral-500"><?= h($sv['description']) ?></p><?php endif; ?>
                <?php if ($sv['message']): ?><p class="mt-1 text-sm text-ink"><?= h($sv['message']) ?></p><?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </noscript>
    <?php elseif ($order): ?>
    <p class="text-center text-sm text-neutral-400">No timeline steps have been added to this order yet.</p>
    <?php endif; ?>

    <div class="mt-12 text-center font-mono text-[9px] uppercase tracking-[0.22em] text-neutral-400">
        <?= htmlspecialchars(APP_NAME) ?> &middot; Private order tracking
    </div>
</div>
<?php require __DIR__ . '/partials/foot.php'; ?>
