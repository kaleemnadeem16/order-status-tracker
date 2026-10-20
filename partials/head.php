<?php
/**
 * Shared page shell. Expects (set by the including page before require):
 *   $pageTitle   string
 *   $currentUser array|null  (from Auth::user())
 *   $activeNav   string|null one of: dashboard|statuses|users
 */
use App\Csrf;

$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$currentUser = $currentUser ?? null;
$bodyClass = $bodyClass ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> · <?= htmlspecialchars(APP_NAME) ?></title>
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES) ?>">
    <meta name="app-url" content="<?= htmlspecialchars(APP_URL, ENT_QUOTES) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;1,300;1,400&amp;family=Inter:wght@300;400;500;600&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
    <link rel="icon" href="<?= APP_URL ?>/images/wordmark.svg">
</head>
<body class="min-h-screen flex flex-col <?= h($bodyClass) ?>">
<?php if ($currentUser): ?>
<header class="border-b border-neutral-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="flex h-16 items-center justify-between">
            <a href="<?= APP_URL ?>/dashboard.php" class="flex items-center gap-2">
                <img src="<?= APP_URL ?>/images/wordmark.svg" alt="<?= htmlspecialchars(APP_NAME) ?>" class="h-7 w-auto">
            </a>

            <nav class="hidden sm:flex items-center gap-1">
                <a href="<?= APP_URL ?>/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'nav-link-active' : 'nav-link' ?>">Orders</a>
                <a href="<?= APP_URL ?>/statuses.php" class="<?= $activeNav === 'statuses' ? 'nav-link-active' : 'nav-link' ?>">Status Library</a>
                <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="<?= APP_URL ?>/users.php" class="<?= $activeNav === 'users' ? 'nav-link-active' : 'nav-link' ?>">Users</a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/account.php" class="<?= $activeNav === 'account' ? 'nav-link-active' : 'nav-link' ?>">My Account</a>
                <form method="post" action="<?= APP_URL ?>/logout.php"><?= Csrf::field() ?><button type="submit" class="nav-link">Log out</button></form>
            </nav>

            <button id="nav-toggle" type="button" class="sm:hidden rounded-sm p-2 text-ink hover:bg-neutral-100" aria-label="Menu">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
            </button>
        </div>

        <nav id="nav-menu" class="hidden sm:hidden flex flex-col gap-1 pb-4">
            <a href="<?= APP_URL ?>/dashboard.php" class="<?= $activeNav === 'dashboard' ? 'nav-link-active' : 'nav-link' ?>">Orders</a>
            <a href="<?= APP_URL ?>/statuses.php" class="<?= $activeNav === 'statuses' ? 'nav-link-active' : 'nav-link' ?>">Status Library</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= APP_URL ?>/users.php" class="<?= $activeNav === 'users' ? 'nav-link-active' : 'nav-link' ?>">Users</a>
            <?php endif; ?>
            <a href="<?= APP_URL ?>/account.php" class="<?= $activeNav === 'account' ? 'nav-link-active' : 'nav-link' ?>">My Account</a>
            <form method="post" action="<?= APP_URL ?>/logout.php"><?= Csrf::field() ?><button type="submit" class="nav-link">Log out</button></form>
        </nav>
    </div>
</header>
<?php endif; ?>
<main class="flex-1 w-full">
