<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session if it hasn't started yet
}

// Database connection function
function db_connect() {
    $host = '127.0.0.1'; // Using 127.0.0.1 instead of localhost for consistency
    $port = '3306';
    $db = 'salon_db';
    $user = 'root';
    $pass = '';

    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection error: " . $e->getMessage());
    }
}

/* Sanitize user input
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}*/

// Register user function
function user_register($name, $email, $hashed_password) {
    $pdo = db_connect();
    $sql = "INSERT INTO customers (Name, Email, Password) VALUES (:name, :email, :password)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);

    try {
        $stmt->execute();
        return "Registration successful!";
    } catch (PDOException $e) {
        return "Error: " . $e->getMessage();
    }
}

// Login user function
function user_login($email, $password) {
    $pdo = db_connect();

    // Check customers table
    $sql = "SELECT * FROM customers WHERE Email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['CustomerID'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['last_activity'] = time(); // Set session activity time
        header("Location: customer_dashboard.php");
        exit;
    }

    // Check admins table
    $sql = "SELECT * FROM admins WHERE Email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['Password'])) {
        $_SESSION['user_id'] = $admin['AdminID'];
        $_SESSION['user_email'] = $admin['Email'];
        $_SESSION['user_role'] = $admin['Role'];
        $_SESSION['last_activity'] = time(); // Set session activity time
        header("Location: admin_dashboard.php");
        exit;
    }

    return "Invalid email or password.";
}

// Logout user function
function user_logout() {
    // Clear session data
    session_unset();
    session_destroy();
    // Redirect to login page
    header("Location: login.php");
    exit;
}

// Check if the session has expired
function check_session_expiry() {
    $session_lifetime = 1800; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
        user_logout();
    } else {
        $_SESSION['last_activity'] = time(); // Update last activity time
    }
}

// Handle file uploads for service images
function handle_file_upload($file) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image file
    if (!getimagesize($file["tmp_name"])) return "Error: File is not a valid image.";
    if (file_exists($target_file)) return "Error: File already exists.";
    if ($file["size"] > 5000000) return "Error: File is too large.";
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) return "Error: Only JPG, JPEG, PNG & GIF files are allowed.";

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file; // Return relative path
    } else {
        return "Error uploading your file.";
    }
}

// Insert a new service into the database with image handling
function insert_service($service_name, $service_desc, $service_price, $image_path) {
    $pdo = db_connect();
    $sql = "INSERT INTO services (name, description, price, ImagePath) VALUES (:name, :description, :price, :image)";

    try {
        // Validate that service price is numeric
        if (!is_numeric($service_price)) {
            throw new Exception("Service price must be a valid number.");
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $service_name);
        $stmt->bindParam(':description', $service_desc);
        $stmt->bindParam(':price', $service_price);
        $stmt->bindParam(':image', $image_path);

        $stmt->execute();
        return "Service added successfully!";
    } catch (Exception $e) {
        return "Error adding service: " . $e->getMessage();
    }
}

// Fetch all services from the database
function get_all_services() {
    $pdo = db_connect();
    $sql = "SELECT * FROM services";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Handle POST request for submitting a service
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_service'])) {
    $service_name = sanitize_input($_POST['service_name']);
    $service_desc = sanitize_input($_POST['service_desc']);
    $service_price = sanitize_input($_POST['service_price']);
    $image_path = handle_file_upload($_FILES['service_image']);

    if (is_string($image_path) && strpos($image_path, "Error") === false && !empty($service_name) && !empty($service_desc) && !empty($service_price)) {
        $feedback = insert_service($service_name, $service_desc, $service_price, $image_path);
        echo $feedback;
    } else {
        echo "Please complete all fields and upload a valid image. Error: " . $image_path;
    }
}

