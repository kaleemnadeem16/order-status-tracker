<?php

require_once __DIR__ . '/../bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Response;

header('Content-Type: application/json; charset=utf-8');

// CSRF check on every state-changing request. The token is sent back either
// as a header (fetch calls) or as a JSON body field.
$method = $_SERVER['REQUEST_METHOD'];

function api_json_body(): array
{
    static $body = null;
    if ($body === null) {
        $raw = file_get_contents('php://input');
        $decoded = $raw !== '' ? json_decode($raw, true) : [];
        $body = is_array($decoded) ? $decoded : [];
    }
    return $body;
}

if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
    $body = api_json_body();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($body['csrf_token'] ?? null);
    if (!Csrf::verify(is_string($token) ? $token : null)) {
        Response::error('Invalid or missing CSRF token.', 403);
    }
}

// Every endpoint in this folder requires a logged-in user except the public
// tracking lookup, which lives outside /api entirely (t.php).
$currentUser = Auth::user();
if ($currentUser === null) {
    Response::error('Authentication required.', 401);
}
