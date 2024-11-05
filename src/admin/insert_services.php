<?php
// Include database connection file
include 'db.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Collect form data and sanitize inputs
    $service_name = mysqli_real_escape_string($conn, $_POST['service_name']);
    $service_description = mysqli_real_escape_string($conn, $_POST['service_description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    
    // Handle image upload
    $target_dir = "uploads/"; // Directory to save the uploaded files
    $target_file = $target_dir . basename($_FILES["service_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is an actual image
    $check = getimagesize($_FILES["service_image"]["tmp_name"]);
    if($check === false) {
        die("File is not an image.");
    }

    // Move uploaded file to the target directory
    if (move_uploaded_file($_FILES["service_image"]["tmp_name"], $target_file)) {
        echo "The file ". htmlspecialchars(basename($_FILES["service_image"]["name"])) . " has been uploaded.";
    } else {
        die("Sorry, there was an error uploading your file.");
    }

    // Prepare SQL statement to insert data
    $sql = "INSERT INTO services (service_name, service_description, price, image_path) VALUES ('$service_name', '$service_description', '$price', '$target_file')";

    // Execute query and check if the service was added successfully
    if (mysqli_query($conn, $sql)) {
        echo "New service added successfully.";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }

    // Close database connection
    mysqli_close($conn);
}


<form action="insert_services.php" method="POST" enctype="multipart/form-data">
    <label for="service_name">Service Name:</label>
    <input type="text" id="service_name" name="service_name" required><br>

    <label for="service_desc">Service Description:</label>
    <textarea id="service_desc" name="service_desc" required></textarea><br>

    <label for="service_price">Service Price (Rands):</label>
    <input type="number" id="service_price" name="service_price" required><br>

    <label for="service_image">Service Image:</label>
    <input type="file" id="service_image" name="service_image" accept="image/*" required><br>

    <button type="submit" name="submit_service">Add Service</button>
</form>
