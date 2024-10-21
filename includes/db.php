<?php
// Start a session to manage user login and other session variables
session_start();

// Database connection
function db_connect() {
    // Define your connection parameters
    $host = 'localhost';   // Database host
    $db   = 'your_db_name';  // Database name
    $user = 'your_db_user';  // Database user
    $pass = 'your_db_pass';  // Database password
    
    // Create a new PDO instance for MySQL connection
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Could not connect to the database: " . $e->getMessage());
    }
}

// Insert new service into the database
function insert_service($service_name, $service_desc, $service_price, $image_path) {
    $pdo = db_connect();  // Connect to the database
    
    // SQL query to insert a new service
    $sql = "INSERT INTO services (name, description, price, image) VALUES (:name, :description, :price, :image)";
    
    // Prepare the SQL query
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters to the query
    $stmt->bindParam(':name', $service_name);
    $stmt->bindParam(':description', $service_desc);
    $stmt->bindParam(':price', $service_price);
    $stmt->bindParam(':image', $image_path);
    
    // Execute the query
    if ($stmt->execute()) {
        return true;  // Insert was successful
    } else {
        return false; // Insert failed
    }
}

// Fetch all services from the database
function get_all_services() {
    $pdo = db_connect();  // Connect to the database
    
    // SQL query to retrieve all services
    $sql = "SELECT * FROM services";
    
    // Prepare and execute the query
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    // Fetch all rows
    return $stmt->fetchAll();
}

// Function to sanitize user input
function sanitize_input($data) {
    $data = trim($data);           // Remove unnecessary spaces
    $data = stripslashes($data);   // Remove backslashes
    $data = htmlspecialchars($data); // Convert special characters to HTML entities
    return $data;
}

// Function to handle file uploads
function handle_file_upload($file) {
    $target_dir = "uploads/";   // Directory where the file will be saved
    $target_file = $target_dir . basename($file["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is an actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }

    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }

    // Allow certain file formats (jpg, jpeg, png, gif)
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif") {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        return false;
    } else {
        // Try to upload the file
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return $target_file; // Return the file path if successful
        } else {
            echo "Sorry, there was an error uploading your file.";
            return false;
        }
    }
}

// Handle the service insertion form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_service'])) {
    // Sanitize and validate input data
    $service_name = sanitize_input($_POST['service_name']);
    $service_desc = sanitize_input($_POST['service_desc']);
    $service_price = sanitize_input($_POST['service_price']);

    // Handle file upload and get the file path
    $image_path = handle_file_upload($_FILES['service_image']);

    // Check if the image upload was successful and all inputs are valid
    if ($image_path && !empty($service_name) && !empty($service_desc) && !empty($service_price)) {
        // Insert the service into the database
        if (insert_service($service_name, $service_desc, $service_price, $image_path)) {
            echo "Service added successfully!";
        } else {
            echo "Error adding service.";
        }
    } else {
        echo "Please complete all fields and upload a valid image.";
    }
}

// User login function (example)
function user_login($email, $password) {
    $pdo = db_connect();
    
    // SQL query to check the user's credentials
    $sql = "SELECT * FROM users WHERE email = :email";
    
    // Prepare the query
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    // Fetch user data
    $user = $stmt->fetch();
    
    // Verify the password (assuming it was hashed using password_hash())
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables upon successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        
        // Redirect to the dashboard or another page
        header("Location: dashboard.php");
        exit;
    } else {
        echo "Invalid email or password.";
    }
}

// User logout function
function user_logout() {
    // Unset all session variables
    session_unset();
    
    // Destroy the session
    session_destroy();
    
    // Redirect to the homepage or login page
    header("Location: index.php");
    exit;
}
?>
