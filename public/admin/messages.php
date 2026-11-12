<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require();
$pdo = db_connect();
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    salon_admin_verify_csrf();
    $id = filter_var($_POST['message_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
    if ($id && in_array($status, ['Pending', 'Responded'], true)) {
        $stmt = $pdo->prepare('UPDATE messages SET Status = :status WHERE MessageID = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
        salon_admin_flash('Message status updated.');
    } else {
        salon_admin_flash('Invalid message update.', 'error');
    }
    salon_admin_redirect('messages.php');
}
$rows = $pdo->query('SELECT MessageID, CustomerName, Email, Phone, Message, Status, created_at FROM messages ORDER BY created_at DESC, MessageID DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
salon_admin_page_start('Messages', $admin);
?>
<div class="section-head"><div><h1>Customer Messages</h1><p class="muted">Messages submitted through the salon contact form. Latest 100 shown.</p></div></div>
<div class="table-scroll"><table><thead><tr><th>Received</th><th>Contact</th><th>Message</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr>
<td><?= salon_admin_h($row['created_at']) ?></td>
<td><strong><?= salon_admin_h($row['CustomerName']) ?></strong><div><?= salon_admin_h($row['Email']) ?></div><div><?= salon_admin_h($row['Phone']) ?></div></td>
<td style="min-width:230px;max-width:480px;white-space:pre-wrap;overflow-wrap:anywhere"><?= salon_admin_h($row['Message']) ?></td>
<td><span class="badge"><?= salon_admin_h($row['Status']) ?></span></td>
<td><form method="post" class="inline">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>"><input type="hidden" name="message_id" value="<?= (int)$row['MessageID'] ?>">
<input type="hidden" name="status" value="<?= $row['Status'] === 'Pending' ? 'Responded' : 'Pending' ?>">
<button type="submit" class="small"><?= $row['Status'] === 'Pending' ? 'Mark responded' : 'Reopen' ?></button></form></td>
</tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="empty">No messages received.</td></tr><?php endif; ?>
</tbody></table></div>
<?php salon_admin_page_end(); ?>
