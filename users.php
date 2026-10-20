<?php

require_once __DIR__ . '/bootstrap.php';

use App\Auth;
use App\Models\User;

$currentUser = Auth::requireAdmin();
$users = User::all();

$pageTitle = 'Users';
$activeNav = 'users';
$extraScripts = ['assets/js/users.js'];
require __DIR__ . '/partials/head.php';
?>
<div class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-medium text-ink">Users</h1>
            <p class="mt-0.5 text-sm text-neutral-500">Staff accounts with access to this tool.</p>
        </div>
        <button type="button" class="btn-primary shrink-0" data-action="user-add-open">+ Add User</button>
    </div>

    <div id="user-list" class="space-y-2"></div>
</div>

<script type="application/json" id="users-data"><?= json_encode($users, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<div id="users-config" class="hidden" data-current-user-id="<?= (int) $currentUser['id'] ?>"></div>

<?php require __DIR__ . '/partials/user-modal.php'; ?>
<?php require __DIR__ . '/partials/foot.php'; ?>
