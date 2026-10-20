<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\Status;
use App\Response;
use App\Validator;

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        Response::json(['statuses' => Status::all()]);
        break;

    case 'POST':
        $body = api_json_body();

        if (($body['action'] ?? '') === 'reorder') {
            $ids = is_array($body['ids'] ?? null) ? array_map('intval', $body['ids']) : [];
            if (empty($ids)) {
                Response::error('No ids supplied.', 400);
            }
            Status::reorder($ids);
            Response::json(['statuses' => Status::all()]);
        }

        [$name, $description, $value, $unit, $err] = validate_status_payload($body);
        if ($err) {
            Response::error($err, 422);
        }
        $newId = Status::create($name, $description, $value, $unit, (int) $currentUser['id']);
        Response::json(['id' => $newId], 201);
        break;

    case 'PUT':
        if ($id === null || !Status::find($id)) {
            Response::error('Status not found.', 404);
        }
        $body = api_json_body();
        [$name, $description, $value, $unit, $err] = validate_status_payload($body);
        if ($err) {
            Response::error($err, 422);
        }
        Status::update($id, $name, $description, $value, $unit);
        Response::json(['ok' => true]);
        break;

    case 'DELETE':
        if ($id === null || !Status::find($id)) {
            Response::error('Status not found.', 404);
        }
        Status::delete($id);
        Response::json(['ok' => true]);
        break;

    default:
        Response::error('Method not allowed.', 405);
}

/** @return array{0:?string,1:?string,2:?float,3:string,4:?string} */
function validate_status_payload(array $body): array
{
    $name = trim((string) ($body['name'] ?? ''));
    $description = trim((string) ($body['description'] ?? ''));
    $rawValue = $body['expected_duration_value'] ?? null;
    $unit = $body['expected_duration_unit'] ?? 'days';

    if (!Validator::required($name) || !Validator::maxLength($name, 150)) {
        return [null, null, null, 'instant', 'Status name is required (max 150 characters).'];
    }

    $value = ($rawValue !== null && $rawValue !== '') ? (float) $rawValue : null;

    // No value means no duration at all — normalize the unit rather than
    // trusting whatever the client sent, so display logic can key off value alone.
    if ($value === null) {
        $unit = 'instant';
    } elseif (!Validator::inArray($unit, ['days', 'weeks', 'months'])) {
        return [null, null, null, 'instant', 'Choose a unit (days/weeks/months) for the duration you entered.'];
    }

    return [$name, $description !== '' ? $description : null, $value, $unit, null];
}
