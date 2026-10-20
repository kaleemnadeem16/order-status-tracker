<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\Order;
use App\Response;
use App\Validator;

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $order = Order::find($id);
            if (!$order || !Order::canAccess($order, $currentUser)) {
                Response::error('Order not found.', 404);
            }
            $order['steps'] = Order::steps($id);
            Response::json(['order' => $order]);
        }

        $search = isset($_GET['search']) ? trim((string) $_GET['search']) : null;
        $orders = Order::listForUser((int) $currentUser['id'], $currentUser['role'] === 'admin', $search);
        Response::json(['orders' => $orders]);
        break;

    case 'POST':
        $body = api_json_body();
        [$orderNumber, $startDate, $advancedEnabled, $steps, $err] = validate_order_payload($body);
        if ($err) {
            Response::error($err, 422);
        }
        if (Order::orderNumberExists($orderNumber)) {
            Response::error('An order with this order number already exists.', 422);
        }

        $newId = Order::create((int) $currentUser['id'], $orderNumber, $startDate, $advancedEnabled, $steps);
        $created = Order::find($newId);
        Response::json([
            'id' => $newId,
            'token' => $created['public_token'],
            'url' => public_track_url($created['public_token']),
            'steps' => Order::steps($newId),
        ], 201);
        break;

    case 'PUT':
        if ($id === null) {
            Response::error('Missing order id.', 400);
        }
        $order = Order::find($id);
        if (!$order || !Order::canAccess($order, $currentUser)) {
            Response::error('Order not found.', 404);
        }

        $body = api_json_body();
        [$orderNumber, $startDate, $advancedEnabled, $steps, $err] = validate_order_payload($body);
        if ($err) {
            Response::error($err, 422);
        }
        if (Order::orderNumberExists($orderNumber, $id)) {
            Response::error('An order with this order number already exists.', 422);
        }

        Order::update($id, $orderNumber, $startDate, $advancedEnabled, $steps);
        Response::json(['ok' => true]);
        break;

    case 'DELETE':
        if ($id === null) {
            Response::error('Missing order id.', 400);
        }
        $order = Order::find($id);
        if (!$order || !Order::canAccess($order, $currentUser)) {
            Response::error('Order not found.', 404);
        }
        Order::delete($id);
        Response::json(['ok' => true]);
        break;

    default:
        Response::error('Method not allowed.', 405);
}

/** @return array{0:?string,1:?string,2:bool,3:array,4:?string} */
function validate_order_payload(array $body): array
{
    $orderNumber = trim((string) ($body['order_number'] ?? ''));
    $startDate = trim((string) ($body['start_date'] ?? ''));
    $advancedEnabled = !empty($body['advanced_enabled']);
    $steps = is_array($body['steps'] ?? null) ? $body['steps'] : [];

    if (!Validator::required($orderNumber) || !Validator::maxLength($orderNumber, 100)) {
        return [null, null, false, [], 'Order number is required (max 100 characters).'];
    }
    if (!Validator::date($startDate)) {
        return [null, null, false, [], 'A valid start date is required.'];
    }
    if (empty($steps)) {
        return [null, null, false, [], 'Add at least one status to the timeline.'];
    }

    $lastDate = null;
    $lastDateName = null;
    foreach ($steps as $i => $step) {
        if (!Validator::required($step['name'] ?? '')) {
            return [null, null, false, [], 'Every timeline step needs a name (step ' . ($i + 1) . ').'];
        }
        if (!Validator::maxLength($step['name'], 150)) {
            return [null, null, false, [], 'Step name is too long (step ' . ($i + 1) . ').'];
        }
        $actualDate = trim((string) ($step['actual_date'] ?? ''));
        if ($actualDate !== '') {
            if (!Validator::date($actualDate)) {
                return [null, null, false, [], 'Invalid date for step "' . $step['name'] . '".'];
            }
            if ($actualDate < $startDate) {
                return [null, null, false, [], 'The date for "' . $step['name'] . '" can\'t be before the order start date.'];
            }
            // The timeline is linear — dates must stay in step order. Steps
            // with no date set are simply skipped in this check.
            if ($lastDate !== null && $actualDate < $lastDate) {
                return [null, null, false, [], 'The date for "' . $step['name'] . '" can\'t be before "' . $lastDateName . '"\'s date.'];
            }
            $lastDate = $actualDate;
            $lastDateName = $step['name'];
        }
    }

    return [$orderNumber, $startDate, $advancedEnabled, $steps, null];
}
