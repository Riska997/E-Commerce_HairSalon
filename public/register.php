<?php

require '../config/db.php';

$registerMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $registerMessage = user_register($name, $email, $hashed_password);
}

function sanitize_input($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../public/css/main.css">
</head>

<body class="register">
    <h1>Sign Up</h1>
    <form method="POST" action=""> 
        <div>
            <label for="name">Name:</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div>
            <label for="phone">Phone Number:</label>
            <input type="tel" name="phone" id="phone" pattern="[0-9]{10}" placeholder="1234567890" required>
        </div>

        <div>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>

        <button type="submit" name="register">Register</button>
    </form>
    <p><?php echo $registerMessage; ?></p>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</body>

</html>
