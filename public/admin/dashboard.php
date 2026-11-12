<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require();
$pdo = db_connect();
$stats = [
    'Appointments' => (int)$pdo->query('SELECT COUNT(*) FROM appointments')->fetchColumn(),
    'Pending requests' => (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE Status = 'Pending'")->fetchColumn(),
    'Services' => (int)$pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'Unread messages' => (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE Status = 'Pending'")->fetchColumn(),
];
$stmt = $pdo->query(
    'SELECT a.AppointmentID, a.Date, a.Time, a.Status, c.Name AS CustomerName,
            GROUP_CONCAT(s.Name ORDER BY s.Name SEPARATOR ", ") AS Services
     FROM appointments a
     JOIN customers c ON c.CustomerID = a.CustomerID
     LEFT JOIN appointment_service aps ON aps.AppointmentID = a.AppointmentID
     LEFT JOIN services s ON s.ServiceID = aps.ServiceID
     GROUP BY a.AppointmentID, a.Date, a.Time, a.Status, a.created_at, c.Name
     ORDER BY a.created_at DESC, a.AppointmentID DESC LIMIT 10'
);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
salon_admin_page_start('Dashboard', $admin);
?>
<div class="section-head"><div><h1>Dashboard</h1><p class="muted">An overview of your salon operations.</p></div></div>
<div class="grid">
<?php foreach ($stats as $label => $count): ?>
    <div class="card stat"><strong><?= $count ?></strong><span><?= salon_admin_h($label) ?></span></div>
<?php endforeach; ?>
</div>
<div class="section-head"><h2>Recent appointments</h2><a class="button" href="/admin/appointments.php">Manage appointments</a></div>
<div class="table-scroll"><table>
<thead><tr><th>Reference</th><th>Customer</th><th>Service</th><th>Requested</th><th>Status</th></tr></thead>
<tbody>
<?php foreach ($appointments as $row): ?>
<tr>
<td>#<?= (int)$row['AppointmentID'] ?></td>
<td><?= salon_admin_h($row['CustomerName']) ?></td>
<td><?= salon_admin_h($row['Services'] ?: 'Not specified') ?></td>
<td><?= salon_admin_h($row['Date']) ?> <?= salon_admin_h(substr((string)$row['Time'], 0, 5)) ?></td>
<td><span class="badge <?= strtolower(salon_admin_h($row['Status'])) ?>"><?= salon_admin_h($row['Status']) ?></span></td>
</tr>
<?php endforeach; ?>
<?php if (!$appointments): ?><tr><td colspan="5" class="empty">No appointments yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php salon_admin_page_end(); ?>
