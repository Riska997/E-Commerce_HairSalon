<?php
include 'components/connect.php';

session_start();
$user_id = $_SESSION['user_id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $update_profile = $conn->prepare("UPDATE `users` SET name = ?, email = ? WHERE id = ?");
    $update_profile->execute([$name, $email, $user_id]);

    // Password update logic
    $prev_pass = $_POST['prev_pass'];
    $old_pass = filter_input(INPUT_POST, 'old_pass', FILTER_SANITIZE_STRING);
    $new_pass = filter_input(INPUT_POST, 'new_pass', FILTER_SANITIZE_STRING);
    $cpass = filter_input(INPUT_POST, 'cpass', FILTER_SANITIZE_STRING);

    if ($old_pass === '' || $new_pass === '') {
        $message[] = 'Please enter a valid password!';
    } elseif ($old_pass !== $prev_pass) {
        $message[] = 'Old password not matched!';
    } elseif ($new_pass !== $cpass) {
        $message[] = 'Confirm password not matched!';
    } else {
        $hashed_new_pass = sha1($new_pass);
        $update_pass = $conn->prepare("UPDATE `users` SET password = ? WHERE id = ?");
        $update_pass->execute([$hashed_new_pass, $user_id]);
        $message[] = 'Password updated successfully!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <?php include 'components/user_header.php'; ?>

    <section class="form-container">
        <form action="" method="post">
            <h3>Update Now</h3>
            <input type="hidden" name="prev_pass" value="<?= htmlspecialchars($fetch_profile['password']); ?>">
            <input type="text" name="name" required placeholder="Enter your username" maxlength="20" class="box" value="<?= htmlspecialchars($fetch_profile['name']); ?>">
            <input type="email" name="email" required placeholder="Enter your email" maxlength="50" class="box" value="<?= htmlspecialchars($fetch_profile['email']); ?>">
            <input type="password" name="old_pass" placeholder="Enter your old password" maxlength="20" class="box">
            <input type="password" name="new_pass" placeholder="Enter your new password" maxlength="20" class="box">
            <input type="password" name="cpass" placeholder="Confirm your new password" maxlength="20" class="box">
            <input type="submit" value="Update Now" class="btn" name="submit">
        </form>
    </section>

    <?php include 'components/footer.php'; ?>
    <script src="js/script.js"></script>
</body>
</html>
