<?php
session_start(); // Start the session at the beginning
require '../../config/db.php';

$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
    header('location: ../public/login.php');
    exit();
}

if (isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Prevent the currently logged-in admin from deleting their own account
    if ($delete_id != $admin_id) {
        $delete_admins = $conn->prepare("DELETE FROM `admins` WHERE id = ?");
        if ($delete_admins->execute([$delete_id])) {
            header('location:admin_accounts.php');
            exit();
        } else {
            $loginMessage = 'Error deleting the account. Please try again.';
        }
    } else {
        $loginMessage = 'You cannot delete your own account while logged in.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Accounts</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/main.css">
</head>
<body>

<?php include '../inc/admin_header.php'; ?>

<section class="accounts">
   <h1 class="heading">Admin Accounts</h1>
   <div class="box-container">
      <div class="box">
         <p>Add New Admin</p>
         <a href="register_admin.php" class="option-btn">Register Admin</a>
      </div>

      <?php
      $select_accounts = $conn->prepare("SELECT * FROM `admins`");
      $select_accounts->execute();
      if ($select_accounts->rowCount() > 0) {
         while ($fetch_accounts = $select_accounts->fetch(PDO::FETCH_ASSOC)) {
      ?>
      <div class="box">
         <p>Admin ID: <span><?= htmlspecialchars($fetch_accounts['id']); ?></span></p>
         <p>Admin Name: <span><?= htmlspecialchars($fetch_accounts['name']); ?></span></p>
         <div class="flex-btn">
            <a href="admin_accounts.php?delete=<?= $fetch_accounts['id']; ?>" onclick="return confirm('Delete this account?')" class="delete-btn">Delete</a>
            <?php if ($fetch_accounts['id'] == $admin_id) : ?>
               <a href="update_profile.php" class="option-btn">Update</a>
            <?php endif; ?>
         </div>
      </div>
      <?php
         }
      } else {
         echo '<p class="empty">No accounts available!</p>';
      }
      ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
