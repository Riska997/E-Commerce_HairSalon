<?php

declare(strict_types=1);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../src/admin/auth.php';
require_once __DIR__ . '/../../src/inc/admin_header.php';
$admin = salon_admin_require('Manager');
$pdo = db_connect();
$categories = [
    'Barber & Grooming', 'Haircuts & Styling', 'Hair Colouring',
    'Hair Treatments & Relaxers', 'Braiding & Weaving',
    'Hair Extensions & Wigs', 'Manicures & Pedicures'
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    salon_admin_verify_csrf();
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $id = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
    $category = is_string($_POST['category'] ?? null) ? $_POST['category'] : '';
    $description = is_string($_POST['description'] ?? null) ? trim($_POST['description']) : '';
    $priceRaw = is_string($_POST['price'] ?? null) ? trim($_POST['price']) : '';
    $priceLabel = is_string($_POST['price_label'] ?? null) ? trim($_POST['price_label']) : '';
    $imagePath = is_string($_POST['image_path'] ?? null) ? trim($_POST['image_path']) : '';

    if (!in_array($action, ['add','update'], true)
        || ($action === 'update' && !$id)
        || $name === '' || strlen($name) > 100
        || !in_array($category, $categories, true)
        || strlen($description) > 4000
        || !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/', $priceRaw)
        || (float)$priceRaw > 999999.99
        || strlen($priceLabel) > 100
        || strlen($imagePath) > 255
        || ($imagePath !== '' && !preg_match('~^/?images/[a-zA-Z0-9/_ .-]+\.(?:png|jpg|jpeg|webp)$~i', $imagePath))
    ) {
        salon_admin_flash('Check the service name, category, price and image path.', 'error');
        salon_admin_redirect('services.php');
    }

    $params = [
        ':name' => $name, ':category' => $category,
        ':description' => $description, ':price' => $priceRaw,
        ':label' => $priceLabel !== '' ? $priceLabel : null,
        ':image' => $imagePath !== '' ? $imagePath : null,
    ];
    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare('INSERT INTO services (Name, Category, Description, Price, PriceLabel, ImagePath)
                    VALUES (:name, :category, :description, :price, :label, :image)');
        } else {
            $stmt = $pdo->prepare('UPDATE services SET Name = :name, Category = :category, Description = :description,
                      Price = :price, PriceLabel = :label, ImagePath = :image WHERE ServiceID = :id');
            $params[':id'] = $id;
        }
        $stmt->execute($params);
        salon_admin_flash($action === 'add' ? 'Service added.' : 'Service updated.');
    } catch (Throwable $error) {
        error_log('Admin service change: ' . $error->getMessage());
        salon_admin_flash('Unable to save the service. Check the database.', 'error');
    }
    salon_admin_redirect('services.php');
}

$editing = null;
$id = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE ServiceID = :id');
    $stmt->execute([':id' => $id]);
    $editing = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
$rows = $pdo->query('SELECT ServiceID, Name, Category, Description, Price, PriceLabel, ImagePath FROM services ORDER BY Category, Name')->fetchAll(PDO::FETCH_ASSOC);
salon_admin_page_start('Services', $admin);
?>
<div class="section-head"><div><h1>Salon Services</h1><p class="muted">Create or edit services without deleting previous booking records.</p></div></div>
<div class="two-column">
<section class="card">
<h2><?= $editing ? 'Edit service #' . (int)$editing['ServiceID'] : 'Add a service' ?></h2>
<form class="stack" method="post" action="/admin/services.php">
<input type="hidden" name="csrf_token" value="<?= salon_admin_h(salon_admin_csrf()) ?>">
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
<?php if ($editing): ?><input type="hidden" name="service_id" value="<?= (int)$editing['ServiceID'] ?>"><?php endif; ?>
<div><label for="name">Service name *</label><input id="name" name="name" maxlength="100" required value="<?= salon_admin_h($editing['Name'] ?? '') ?>"></div>
<div><label for="category">Category *</label><select id="category" name="category" required>
<option value="">Select a category</option>
<?php foreach ($categories as $cat): ?><option value="<?= salon_admin_h($cat) ?>" <?= ($editing['Category'] ?? '') === $cat ? 'selected' : '' ?>><?= salon_admin_h($cat) ?></option><?php endforeach; ?>
</select></div>
<div><label for="description">Description</label><textarea id="description" name="description" maxlength="4000"><?= salon_admin_h($editing['Description'] ?? '') ?></textarea></div>
<div class="field-grid">
<div><label for="price">Price (R) *</label><input id="price" name="price" type="number" step="0.01" min="0" max="999999.99" required value="<?= salon_admin_h($editing['Price'] ?? '') ?>"></div>
<div><label for="price_label">Display range (optional)</label><input id="price_label" name="price_label" maxlength="100" placeholder="e.g. R250–R350" value="<?= salon_admin_h($editing['PriceLabel'] ?? '') ?>"></div>
</div>
<div><label for="image_path">Image path (optional)</label><input id="image_path" name="image_path" maxlength="255" placeholder="/images/service-categories/example.webp" value="<?= salon_admin_h($editing['ImagePath'] ?? '') ?>"><p class="help">Use an already-uploaded image under /public/images. This form does not upload files.</p></div>
<div class="inline" style="display:flex;gap:.7rem;align-items:center"><button type="submit"><?= $editing ? 'Save changes' : 'Add service' ?></button><?php if ($editing): ?><a class="button outline" href="/admin/services.php">Cancel edit</a><?php endif; ?></div>
</form>
</section>
<section class="card"><h2>Service catalogue</h2><p class="muted"><?= count($rows) ?> services</p>
<div class="table-scroll"><table><thead><tr><th>Service</th><th>Price</th><th>Edit</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr>
<td><strong><?= salon_admin_h($row['Name']) ?></strong><div class="muted"><?= salon_admin_h($row['Category']) ?></div></td>
<td><?= $row['PriceLabel'] ? salon_admin_h($row['PriceLabel']) : 'R' . number_format((float)$row['Price'],2) ?></td>
<td><a href="/admin/services.php?edit=<?= (int)$row['ServiceID'] ?>">Edit</a></td>
</tr><?php endforeach; ?></tbody></table></div>
</section></div>
<?php salon_admin_page_end(); ?>
