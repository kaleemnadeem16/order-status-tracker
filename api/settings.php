<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\Setting;
use App\Response;
use App\Validator;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        Response::json(['default_order_duration' => Setting::get('default_order_duration')]);
        break;

    case 'PUT':
        $body = api_json_body();
        $value = trim((string) ($body['default_order_duration'] ?? ''));

        if ($value !== '' && !Validator::maxLength($value, 100)) {
            Response::error('Keep it under 100 characters.', 422);
        }

        Setting::set('default_order_duration', $value !== '' ? $value : null);
        Response::json(['ok' => true]);
        break;

    default:
        Response::error('Method not allowed.', 405);
}
