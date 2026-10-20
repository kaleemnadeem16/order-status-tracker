<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\Order;
use App\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$body = api_json_body();
$orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;

$order = Order::find($orderId);
if (!$order || !Order::canAccess($order, $currentUser)) {
    Response::error('Order not found.', 404);
}

$token = Order::regenerateToken($orderId);
Response::json([
    'token' => $token,
    'url' => public_track_url($token),
]);
