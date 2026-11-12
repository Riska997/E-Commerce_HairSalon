<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require('Manager');
$pdo = db_connect();
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (strlen($search) > 100) { $search = substr($search, 0, 100); }
if ($search !== '') {
    $stmt = $pdo->prepare('SELECT c.CustomerID, c.Name, c.Email, c.ContactDetails, c.created_at,
                                 COUNT(a.AppointmentID) AS AppointmentCount
                          FROM customers c LEFT JOIN appointments a ON a.CustomerID = c.CustomerID
                          WHERE c.Name LIKE :qname OR c.Email LIKE :qemail
                          GROUP BY c.CustomerID, c.Name, c.Email, c.ContactDetails, c.created_at
                          ORDER BY c.created_at DESC LIMIT 100');
    $stmt->execute([':qname' => '%' . $search . '%', ':qemail' => '%' . $search . '%']);
} else {
    $stmt = $pdo->query('SELECT c.CustomerID, c.Name, c.Email, c.ContactDetails, c.created_at,
                               COUNT(a.AppointmentID) AS AppointmentCount
                        FROM customers c LEFT JOIN appointments a ON a.CustomerID = c.CustomerID
                        GROUP BY c.CustomerID, c.Name, c.Email, c.ContactDetails, c.created_at
                        ORDER BY c.created_at DESC LIMIT 100');
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
salon_admin_page_start('Customers', $admin);
?>
<div class="section-head"><div><h1>Customers</h1><p class="muted">Manager-only, read-only customer directory. Passwords are never displayed.</p></div>
<form method="get" class="inline"><label for="q">Find customer</label><input style="width:auto" id="q" name="q" maxlength="100" value="<?= salon_admin_h($search) ?>" placeholder="Name or email"><button type="submit" class="small">Search</button></form></div>
<div class="table-scroll"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Appointments</th><th>Joined</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr>
<td><?= (int)$row['CustomerID'] ?></td><td><?= salon_admin_h($row['Name']) ?></td><td><?= salon_admin_h($row['Email']) ?></td>
<td><?= salon_admin_h($row['ContactDetails']) ?></td><td><?= (int)$row['AppointmentCount'] ?></td><td><?= salon_admin_h($row['created_at']) ?></td>
</tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="empty">No customers found.</td></tr><?php endif; ?>
</tbody></table></div>
<?php salon_admin_page_end(); ?>
