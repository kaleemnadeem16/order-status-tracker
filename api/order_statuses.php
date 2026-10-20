<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\Order;
use App\Response;

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    Response::error('Method not allowed.', 405);
}

$body = api_json_body();
$orderId = isset($body['order_id']) ? (int) $body['order_id'] : 0;
$stepId = isset($body['order_status_id']) ? (int) $body['order_status_id'] : 0;
$action = $body['action'] ?? '';

if ($action !== 'mark-current' || $orderId <= 0 || $stepId <= 0) {
    Response::error('Invalid request.', 400);
}

$order = Order::find($orderId);
if (!$order || !Order::canAccess($order, $currentUser)) {
    Response::error('Order not found.', 404);
}

try {
    Order::markCurrent($orderId, $stepId);
} catch (\RuntimeException $e) {
    Response::error($e->getMessage(), 404);
}

Response::json(['steps' => Order::steps($orderId)]);
