<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Csrf;
use App\Response;

if (Auth::user()) {
    Response::redirect(APP_URL . '/dashboard.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email !== '' && $password !== '' && Auth::attempt($email, $password)) {
            Response::redirect(APP_URL . '/dashboard.php');
        }
        $error = 'Invalid email or password, or this account is temporarily locked.';
    }
}

$pageTitle = 'Log in';
$currentUser = null;
require __DIR__ . '/partials/head.php';
?>
<div class="flex min-h-[calc(100vh-1px)] items-center justify-center px-4 py-12">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex justify-center">
            <img src="<?= APP_URL ?>/images/wordmark.svg" alt="<?= htmlspecialchars(APP_NAME) ?>" class="h-10 w-auto">
        </div>

        <div class="card p-6 sm:p-8">
            <h1 class="mb-1 text-lg font-medium text-ink">Sign in</h1>
            <p class="mb-6 text-sm text-neutral-500">Order tracking &middot; staff access only</p>

            <?php if ($error): ?>
            <div class="mb-4 rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <?= Csrf::field() ?>
                <div class="mb-4">
                    <label class="label" for="email">Email</label>
                    <input class="input" type="email" id="email" name="email" autocomplete="username" required autofocus>
                </div>
                <div class="mb-6">
                    <label class="label" for="password">Password</label>
                    <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
                </div>
                <button type="submit" class="btn-primary w-full">Sign in</button>
            </form>
        </div>
    </div>
</div>
<?php require __DIR__ . '/partials/foot.php'; ?>
