<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Response;

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !App\Csrf::verify(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
    http_response_code(403);
    exit('Invalid logout request.');
}
Auth::logout();
Response::redirect(APP_URL . '/login.php');
