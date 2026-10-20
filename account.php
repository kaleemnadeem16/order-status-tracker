<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;

$currentUser = Auth::requireLogin();

$pageTitle = 'My Account';
$activeNav = 'account';
$extraScripts = ['assets/js/account.js'];
require __DIR__ . '/partials/head.php';
?>
<div class="mx-auto max-w-md px-4 py-8 sm:px-6">
    <h1 class="mb-1 text-xl font-medium text-ink">My Account</h1>
    <p class="mb-6 text-sm text-neutral-500"><?= h($currentUser['name']) ?> &middot; <?= h($currentUser['email']) ?> &middot; <?= $currentUser['role'] === 'admin' ? 'Admin' : 'Staff' ?></p>

    <div class="card p-5 sm:p-6">
        <h2 class="mb-4 text-sm font-medium text-ink">Change Password</h2>

        <div id="account-error" class="mb-4 hidden rounded-sm border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"></div>
        <div id="account-success" class="mb-4 hidden rounded-sm border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700"></div>

        <div class="mb-4">
            <label class="label" for="current-password">Current Password</label>
            <input class="input" type="password" id="current-password" autocomplete="current-password">
        </div>
        <div class="mb-5">
            <label class="label" for="new-password">New Password</label>
            <input class="input" type="password" id="new-password" autocomplete="new-password">
            <p class="mt-1 text-xs text-neutral-400">At least 10 characters.</p>
        </div>

        <button type="button" class="btn-primary" data-action="account-change-password">Update Password</button>
    </div>
</div>
<?php require __DIR__ . '/partials/foot.php'; ?>
