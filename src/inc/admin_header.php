<?php

declare(strict_types=1);

/** Shared admin layout; intentionally independent of customer CSS. */
function salon_admin_page_start(string $title, array $admin): void
{
    $isManager = ($admin['Role'] ?? '') === 'Manager';
    $nav = [
        ['dashboard.php', 'Dashboard'],
        ['appointments.php', 'Appointments'],
        ['messages.php', 'Messages'],
    ];
    if ($isManager) {
        $nav[] = ['services.php', 'Services'];
        $nav[] = ['customers.php', 'Customers'];
        $nav[] = ['staff.php', 'Staff'];
    }
    $nav[] = ['account.php', 'My Account'];
    $current = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    ?>
<!doctype html>
<html lang="en-ZA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <meta name="referrer" content="same-origin">
    <title><?= salon_admin_h($title) ?> | Eve &amp; Steve Salon Admin</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/css/admin.css">
    <script src="/js/admin-password-visibility.js" defer></script>
</head>
<body class="salon-admin">
<a class="skip-link" href="#admin-content">Skip to content</a>
<header class="admin-topbar">
    <div class="admin-bar-inner">
        <a class="admin-brand" href="/admin/dashboard.php" aria-label="Eve and Steve Salon Admin Dashboard">
            <img src="/images/logo.png" alt="" width="46" height="46">
            <span>Eve &amp; Steve Salon <small>Administration</small></span>
        </a>
        <div class="admin-account">
            <span><?= salon_admin_h($admin['Name']) ?> <small>(<?= salon_admin_h($admin['Role']) ?>)</small></span>
            <form action="/admin/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>">
                <button type="submit" class="button outline small">Sign out</button>
            </form>
        </div>
    </div>
    <nav class="admin-nav" aria-label="Administration">
        <div class="admin-bar-inner admin-links">
            <?php foreach ($nav as [$url, $label]): ?>
                <a href="/admin/<?= salon_admin_h($url) ?>" <?= $current === $url ? 'aria-current="page"' : '' ?>><?= salon_admin_h($label) ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
</header>
<main class="admin-main" id="admin-content">
    <?php $flash = salon_admin_take_flash(); ?>
    <?php if ($flash !== null): ?>
        <div class="notice <?= ($flash['type'] ?? '') === 'error' ? 'error' : 'success' ?>" role="status">
            <?= salon_admin_h($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>
    <?php
}

function salon_admin_page_end(): void
{
    ?>
</main>
<footer class="admin-footer">Eve &amp; Steve Salon — secure staff workspace</footer>
</body>
</html>
<?php
}
