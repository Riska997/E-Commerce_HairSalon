<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require();
$pdo = db_connect();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    salon_admin_verify_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $currentPassword = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    if (!password_verify($currentPassword, (string)$admin['Password'])) {
        salon_admin_flash('Your current password is incorrect.', 'error');
        salon_admin_redirect('account.php');
    }

    if ($action === 'profile') {
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
            salon_admin_flash('Enter a valid name and email.', 'error');
            salon_admin_redirect('account.php');
        }
        try {
            $customerCheck = $pdo->prepare('SELECT CustomerID FROM customers WHERE Email = :email LIMIT 1');
            $customerCheck->execute([':email' => $email]);
            if ($customerCheck->fetchColumn()) {
                salon_admin_flash('That email belongs to a customer. Choose a private admin email.', 'error');
                salon_admin_redirect('account.php');
            }
            $stmt = $pdo->prepare('UPDATE admins SET Name = :name, Email = :email WHERE AdminID = :id');
            $stmt->execute([':name' => $name, ':email' => $email, ':id' => $admin['AdminID']]);
            $_SESSION['user_email'] = $email;
            salon_admin_flash('Profile updated.');
        } catch (Throwable $error) {
            error_log('Admin profile update: ' . $error->getMessage());
            salon_admin_flash('Unable to update your profile. Email may already be in use.', 'error');
        }
    } elseif ($action === 'password') {
        $newPassword = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        if (!salon_admin_password_valid($newPassword) || $newPassword !== $confirm || $newPassword === $currentPassword) {
            salon_admin_flash('Use a new, matching password between 12 and 72 bytes.', 'error');
            salon_admin_redirect('account.php');
        }
        $stmt = $pdo->prepare('UPDATE admins SET Password = :hash WHERE AdminID = :id');
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt->execute([':hash' => $newHash, ':id' => $admin['AdminID']]);
        session_regenerate_id(true);
        $_SESSION['admin_password_fingerprint'] = hash('sha256', $newHash);
        salon_admin_flash('Password changed. Other administrator sessions for this account are invalidated.');
    } else {
        salon_admin_flash('Invalid account update.', 'error');
    }
    salon_admin_redirect('account.php');
}
salon_admin_page_start('My Account', $admin);
?>
<div class="section-head"><div><h1>My Account</h1><p class="muted">Update your details or change your password whenever you wish. No monthly forced reset.</p></div></div>
<div class="two-column">
<section class="card"><h2>Profile</h2>
<form method="post" class="stack">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>"><input type="hidden" name="action" value="profile">
<div><label for="name">Name</label><input id="name" name="name" maxlength="100" value="<?= salon_admin_h($admin['Name']) ?>" required></div>
<div><label for="email">Private sign-in email</label><input id="email" name="email" type="email" maxlength="100" value="<?= salon_admin_h($admin['Email']) ?>" required></div>
<div><label for="profile-password">Current password</label><input id="profile-password" name="current_password" type="password" autocomplete="current-password" required></div>
<button type="submit">Save profile</button>
</form></section>
<section class="card"><h2>Change password</h2>
<form method="post" class="stack">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>"><input type="hidden" name="action" value="password">
<div><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
<div><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<div><label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirm_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<button type="submit">Change password</button>
<p class="help">Use a strong, unique password. Consider a password manager. Change it when you choose or if you suspect compromise.</p>
</form></section>
</div>
<?php salon_admin_page_end(); ?>
