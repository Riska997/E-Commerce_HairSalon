<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require('Manager');
$pdo = db_connect();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    salon_admin_verify_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $managerPassword = is_string($_POST['manager_password'] ?? null) ? $_POST['manager_password'] : '';

    if (!password_verify($managerPassword, (string)$admin['Password'])) {
        salon_admin_flash('Your Manager password was incorrect.', 'error');
        salon_admin_redirect('staff.php');
    }

    if ($action === 'create') {
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        if ($name === '' || strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 100 || !salon_admin_password_valid($password) || $password !== $confirm) {
            salon_admin_flash('Check the staff details. Password must be 12–72 bytes and confirmed.', 'error');
            salon_admin_redirect('staff.php');
        }
        try {
            $customerCheck = $pdo->prepare('SELECT CustomerID FROM customers WHERE Email = :email LIMIT 1');
            $customerCheck->execute([':email' => $email]);
            if ($customerCheck->fetchColumn()) {
                salon_admin_flash('That address is registered to a customer. Use a separate staff email.', 'error');
                salon_admin_redirect('staff.php');
            }
            $stmt = $pdo->prepare("INSERT INTO admins (Name, Email, Role, Password) VALUES (:name, :email, 'Staff', :hash)");
            $stmt->execute([':name' => $name, ':email' => $email,
                            ':hash' => password_hash($password, PASSWORD_DEFAULT)]);
            salon_admin_flash('Staff account created. Give credentials to the staff member privately; ask them to change the password immediately.');
        } catch (Throwable $error) {
            error_log('Admin staff creation: ' . $error->getMessage());
            salon_admin_flash('Unable to create account. Check whether the email already exists.', 'error');
        }
    } elseif ($action === 'reset') {
        $id = filter_var($_POST['admin_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';
        if (!$id || !salon_admin_password_valid($password) || $password !== $confirm) {
            salon_admin_flash('Enter and confirm a new staff password (12–72 bytes).', 'error');
        } else {
            $stmt = $pdo->prepare("UPDATE admins SET Password = :hash WHERE AdminID = :id AND Role = 'Staff'");
            $stmt->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);
            salon_admin_flash($stmt->rowCount() ? 'Staff password replaced. Inform the staff member securely.' : 'Staff account not found.', $stmt->rowCount() ? 'success' : 'error');
        }
    } else {
        salon_admin_flash('Invalid request.', 'error');
    }
    salon_admin_redirect('staff.php');
}
$staff = $pdo->query('SELECT AdminID, Name, Email, Role, created_at FROM admins ORDER BY Role, Name')->fetchAll(PDO::FETCH_ASSOC);
salon_admin_page_start('Staff Accounts', $admin);
?>
<div class="section-head"><div><h1>Staff Accounts</h1><p class="muted">Only Managers can create or reset staff accounts. Public visitors cannot register as staff.</p></div></div>
<div class="two-column">
<section class="card"><h2>Add a staff member</h2>
<form method="post" class="stack">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>"><input type="hidden" name="action" value="create">
<div><label for="name">Full name</label><input id="name" name="name" maxlength="100" required></div>
<div><label for="email">Private staff email</label><input id="email" name="email" type="email" maxlength="100" autocomplete="off" required></div>
<div><label for="new-password">Temporary password</label><input id="new-password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<div><label for="confirm-password">Confirm temporary password</label><input id="confirm-password" name="confirm_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<div><label for="manager-password">Your Manager password to authorize</label><input id="manager-password" name="manager_password" type="password" autocomplete="current-password" required></div>
<button type="submit">Create Staff Account</button>
<p class="help">Use a random unique temporary password. Provide it privately and require the staff member to change it on first use.</p>
</form></section>
<section class="card"><h2>Existing administrators</h2><div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>
<?php foreach ($staff as $row): ?><tr><td><?= salon_admin_h($row['Name']) ?></td><td><?= salon_admin_h($row['Email']) ?></td><td><?= salon_admin_h($row['Role']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><p class="help">Resetting a staff password below changes the stored hash immediately. Manager passwords cannot be reset from this page.</p></section>
</div>
<section class="card" style="margin-top:1rem"><h2>Reset staff member's password</h2>
<form method="post" class="stack">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>"><input type="hidden" name="action" value="reset">
<div><label for="staff-id">Staff member</label><select id="staff-id" name="admin_id" required><option value="">Select staff member</option>
<?php foreach ($staff as $row): if ($row['Role'] !== 'Staff') { continue; } ?><option value="<?= (int)$row['AdminID'] ?>"><?= salon_admin_h($row['Name'] . ' — ' . $row['Email']) ?></option><?php endforeach; ?>
</select></div>
<div class="field-grid"><div><label for="reset-password">New temporary password</label><input id="reset-password" name="password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div>
<div><label for="reset-confirm">Confirm password</label><input id="reset-confirm" name="confirm_password" type="password" minlength="12" maxlength="72" autocomplete="new-password" required></div></div>
<div><label for="reset-manager-password">Your Manager password</label><input id="reset-manager-password" name="manager_password" type="password" autocomplete="current-password" required></div>
<div><button type="submit">Reset Staff Password</button></div>
</form></section>
<?php salon_admin_page_end(); ?>
