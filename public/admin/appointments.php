<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require();
$pdo = db_connect();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    salon_admin_verify_csrf();
    $id = filter_var($_POST['appointment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $status = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
    $allowed = [
        'Pending' => ['Confirmed', 'Cancelled'],
        'Booked' => ['Confirmed', 'Cancelled', 'Completed'],
        'Confirmed' => ['Completed', 'Cancelled'],
        'Completed' => [],
        'Cancelled' => [],
    ];
    if (!$id || !in_array($status, ['Confirmed', 'Completed', 'Cancelled'], true)) {
        salon_admin_flash('Select a valid appointment and status.', 'error');
        salon_admin_redirect('appointments.php');
    }
    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT Status FROM appointments WHERE AppointmentID = :id FOR UPDATE');
        $lock->execute([':id' => $id]);
        $oldStatus = $lock->fetchColumn();
        if (!is_string($oldStatus) || !in_array($status, $allowed[$oldStatus] ?? [], true)) {
            $pdo->rollBack();
            salon_admin_flash('That status change is not permitted. Refresh and try again.', 'error');
            salon_admin_redirect('appointments.php');
        }
        $update = $pdo->prepare('UPDATE appointments SET Status = :status WHERE AppointmentID = :id');
        $update->execute([':status' => $status, ':id' => $id]);
        $pdo->commit();
        salon_admin_flash('Appointment #' . $id . ' updated to ' . $status . '.');
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Admin appointment update: ' . $error->getMessage());
        salon_admin_flash('Unable to update the appointment.', 'error');
    }
    salon_admin_redirect('appointments.php');
}

$filter = is_string($_GET['status'] ?? null) ? $_GET['status'] : 'All';
$known = ['All', 'Pending', 'Confirmed', 'Completed', 'Cancelled', 'Booked'];
if (!in_array($filter, $known, true)) { $filter = 'All'; }
$sql = 'SELECT a.AppointmentID, a.Date, a.Time, a.Status, a.created_at,
               c.Name AS CustomerName, c.Email, c.ContactDetails,
               GROUP_CONCAT(s.Name ORDER BY s.Name SEPARATOR ", ") AS Services
        FROM appointments a
        JOIN customers c ON c.CustomerID = a.CustomerID
        LEFT JOIN appointment_service aps ON aps.AppointmentID = a.AppointmentID
        LEFT JOIN services s ON s.ServiceID = aps.ServiceID';
$params = [];
if ($filter !== 'All') {
    $sql .= ' WHERE a.Status = :status';
    $params[':status'] = $filter;
}
$sql .= ' GROUP BY a.AppointmentID, a.Date, a.Time, a.Status, a.created_at,
                  c.Name, c.Email, c.ContactDetails
          ORDER BY a.created_at DESC, a.AppointmentID DESC LIMIT 150';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$changes = [
    'Pending' => ['Confirmed' => 'Confirm', 'Cancelled' => 'Decline'],
    'Booked' => ['Confirmed' => 'Confirm', 'Cancelled' => 'Decline', 'Completed' => 'Complete'],
    'Confirmed' => ['Completed' => 'Complete', 'Cancelled' => 'Cancel'],
];
salon_admin_page_start('Appointments', $admin);
?>
<div class="section-head"><div><h1>Appointments</h1><p class="muted">Approve, decline, or complete customer requests. Latest 150 are shown.</p></div>
<form method="get" class="inline"><label for="status-filter">Status</label><select id="status-filter" name="status" style="width:auto">
<?php foreach ($known as $item): ?><option value="<?= salon_admin_h($item) ?>" <?= $filter === $item ? 'selected' : '' ?>><?= salon_admin_h($item) ?></option><?php endforeach; ?>
</select><button type="submit" class="button small">Filter</button></form></div>
<div class="table-scroll"><table>
<thead><tr><th>Reference</th><th>Customer</th><th>Services</th><th>Preferred time</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($rows as $row): ?>
<tr>
<td>#<?= (int)$row['AppointmentID'] ?></td>
<td><?= salon_admin_h($row['CustomerName']) ?><div class="muted"><?= salon_admin_h($row['Email']) ?></div><div class="muted"><?= salon_admin_h($row['ContactDetails']) ?></div></td>
<td><?= salon_admin_h($row['Services'] ?: 'Not specified') ?></td>
<td><?= salon_admin_h($row['Date']) ?><br><?= salon_admin_h(substr((string)$row['Time'],0,5)) ?></td>
<td><span class="badge <?= strtolower(salon_admin_h($row['Status'])) ?>"><?= salon_admin_h($row['Status']) ?></span></td>
<td>
<?php if (isset($changes[$row['Status']])): ?>
<form method="post" class="inline">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>">
<input type="hidden" name="appointment_id" value="<?= (int)$row['AppointmentID'] ?>">
<select name="status" aria-label="Change appointment #<?= (int)$row['AppointmentID'] ?> status" style="width:auto" required>
<option value="">Choose action</option>
<?php foreach ($changes[$row['Status']] as $value => $label): ?><option value="<?= salon_admin_h($value) ?>"><?= salon_admin_h($label) ?></option><?php endforeach; ?>
</select><button type="submit" class="small">Save</button></form>
<?php else: ?><span class="muted">Final status</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">No appointments match this filter.</td></tr><?php endif; ?>
</tbody></table></div>
<?php salon_admin_page_end(); ?>
