<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Models\Setting;
use App\Models\Status;

$currentUser = Auth::requireLogin();

$formMode = 'add';
$order = null;
$statuses = Status::all();
$defaultOrderDuration = Setting::get('default_order_duration');

$pageTitle = 'New Order';
$activeNav = 'dashboard';
$extraScripts = ['assets/js/qrcode.min.js', 'assets/js/qr-modal.js', 'assets/js/timeline-render.js', 'assets/js/timeline-builder.js'];
require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/order-form.php';
require __DIR__ . '/partials/foot.php';
