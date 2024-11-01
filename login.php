php
<?php
session_start();
require 'includes/db.php';

$loginMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $loginMessage = user_login($email, $password);

    if (empty($loginMessage)) {
        header("Location: customer_dashboard.php");
        exit;
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="login">
    <h2>Login</h2>
    <form action="login.php" method="POST">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit" name="login">Login</button>
    </form>
    <p><?php echo $loginMessage; ?></p>
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</body>
</html>