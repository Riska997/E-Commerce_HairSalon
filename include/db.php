<?php
session_start();

// Database connection function
function db_connect() {
    $host = 'localhost';
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

try {
    $pdo = db_connect();
    $pdo->beginTransaction();

    // Attempt to add the foreign key
    $pdo->exec("ALTER TABLE appointments
                ADD CONSTRAINT fk_payment
                FOREIGN KEY (PaymentID) REFERENCES payments(PaymentID) 
                ON DELETE SET NULL;");

    // Commit transaction if successful
    $pdo->commit();
} catch (PDOException $e) {
    // Rollback transaction in case of error
    $pdo->rollBack();
    echo "Error adding foreign key: " . $e->getMessage();
}

// Enhanced insert_service function with error handling and feedback
function insert_service($service_name, $service_desc, $service_price, $image_path) {
    $pdo = db_connect();
    $sql = "INSERT INTO services (name, description, price, ImagePath) VALUES (:name, :description, :price, :image)";
    
    try {
        // Validate service price is numeric
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

// Fetch all services function
function get_all_services() {
    $pdo = db_connect();
    $sql = "SELECT * FROM services";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Sanitize user input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle file uploads and return full URL path or error message
function handle_file_upload($file) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validate image file
    $check = getimagesize($file["tmp_name"]);
    if (!$check) return "File is not a valid image.";

    if (file_exists($target_file)) return "File already exists.";
    if ($file["size"] > 5000000) return "File is too large.";
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) return "Only JPG, JPEG, PNG & GIF files are allowed.";

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $_SERVER['HTTP_HOST'] . "/" . $target_file; // Return full URL
    } else {
        return "Error uploading your file.";
    }
}

// User login function to match `customers` table
function user_login($email, $password) {
    $pdo = db_connect();
    $sql = "SELECT * FROM customers WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['Password'])) {
        $_SESSION['user_id'] = $user['CustomerID'];
        $_SESSION['user_email'] = $user['Email'];
        $_SESSION['last_activity'] = time(); // Track last activity for session expiry

        header("Location: dashboard.php");
        exit;
    } else {
        return "Invalid email or password.";
    }
}

// User logout function
function user_logout() {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Session expiry check
function check_session_expiry() {
    $session_lifetime = 1800; // Session expiry set to 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_lifetime)) {
        user_logout();
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Handle form submission for service insertion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_service'])) {
    $service_name = sanitize_input($_POST['service_name']);
    $service_desc = sanitize_input($_POST['service_desc']);
    $service_price = sanitize_input($_POST['service_price']);

    // Validate inputs and file upload
    $image_path = handle_file_upload($_FILES['service_image']);
    if (is_string($image_path) && strpos($image_path, "Error") === false && !empty($service_name) && !empty($service_desc) && !empty($service_price)) {
        $feedback = insert_service($service_name, $service_desc, $service_price, $image_path);
        echo $feedback;
    } else {
        echo "Please complete all fields and upload a valid image. Error: " . $image_path;
    }
}

