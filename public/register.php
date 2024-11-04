php
<?php
session_start();
require 'includes/db.php';

$registerMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $registerMessage = user_register($name, $email, $hashed_password);
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="register">
    <h2>Register</h2>
    <form action="register.php" method="POST">
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" required>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <button type="submit" name="register">Register</button>
    </form>
    <p><?php echo $registerMessage; ?></p>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>