<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Status;
use App\Response;

$currentUser = Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$order = Order::find($id);
if (!$order || !Order::canAccess($order, $currentUser)) {
    Response::redirect(APP_URL . '/dashboard.php');
}
$order['steps'] = Order::steps($id);

$formMode = 'edit';
$statuses = Status::all();
$defaultOrderDuration = Setting::get('default_order_duration');

$pageTitle = 'Edit Order #' . $order['order_number'];
$activeNav = 'dashboard';
$extraScripts = ['assets/js/qrcode.min.js', 'assets/js/qr-modal.js', 'assets/js/timeline-render.js', 'assets/js/timeline-builder.js'];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/order-form.php';
require __DIR__ . '/partials/foot.php';
