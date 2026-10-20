<?php

require_once __DIR__ . '/_bootstrap.php';

use App\Models\User;
use App\Response;
use App\Validator;

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

// Everyone logged in may change their OWN password via action=change-password.
// Everything else on this endpoint is admin-only staff management.
if ($method === 'POST') {
    $body = api_json_body();
    if (($body['action'] ?? '') === 'change-password') {
        $current = (string) ($body['current_password'] ?? '');
        $new = (string) ($body['new_password'] ?? '');
        if (!User::verifyPassword((int) $currentUser['id'], $current)) {
            Response::error('Current password is incorrect.', 422);
        }
        if (strlen($new) < 10) {
            Response::error('New password must be at least 10 characters.', 422);
        }
        User::setPassword((int) $currentUser['id'], $new);
        Response::json(['ok' => true]);
    }
}

if ($currentUser['role'] !== 'admin') {
    Response::error('Admins only.', 403);
}

switch ($method) {
    case 'GET':
        Response::json(['users' => User::all()]);
        break;

    case 'POST':
        $body = api_json_body();
        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $role = ($body['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';

        if (!Validator::required($name) || !Validator::maxLength($name, 100)) {
            Response::error('Name is required.', 422);
        }
        if (!Validator::email($email)) {
            Response::error('A valid email is required.', 422);
        }
        if (User::emailExists($email)) {
            Response::error('A user with this email already exists.', 422);
        }
        if (strlen($password) < 10) {
            Response::error('Password must be at least 10 characters.', 422);
        }

        $newId = User::create($name, $email, $password, $role, (int) $currentUser['id']);
        Response::json(['id' => $newId], 201);
        break;

    case 'PUT':
        if ($id === null || !User::find($id)) {
            Response::error('User not found.', 404);
        }
        $body = api_json_body();

        if (($body['action'] ?? '') === 'set-active') {
            $active = !empty($body['is_active']);
            if (!$active && $id === (int) $currentUser['id']) {
                Response::error('You cannot deactivate your own account.', 422);
            }
            $target = User::find($id);
            if (!$active && $target['role'] === 'admin' && User::activeAdminCount() <= 1) {
                Response::error('At least one active admin must remain.', 422);
            }
            User::setActive($id, $active);
            Response::json(['ok' => true]);
        }

        if (($body['action'] ?? '') === 'reset-password') {
            $new = (string) ($body['new_password'] ?? '');
            if (strlen($new) < 10) {
                Response::error('Password must be at least 10 characters.', 422);
            }
            User::setPassword($id, $new);
            Response::json(['ok' => true]);
        }

        $name = trim((string) ($body['name'] ?? ''));
        $email = trim((string) ($body['email'] ?? ''));
        $role = ($body['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';

        if (!Validator::required($name) || !Validator::maxLength($name, 100)) {
            Response::error('Name is required.', 422);
        }
        if (!Validator::email($email)) {
            Response::error('A valid email is required.', 422);
        }
        if (User::emailExists($email, $id)) {
            Response::error('A user with this email already exists.', 422);
        }
        if ($role !== 'admin' && $id === (int) $currentUser['id'] && User::activeAdminCount() <= 1) {
            Response::error('At least one active admin must remain.', 422);
        }

        User::update($id, $name, $email, $role);
        Response::json(['ok' => true]);
        break;

    default:
        Response::error('Method not allowed.', 405);
}
