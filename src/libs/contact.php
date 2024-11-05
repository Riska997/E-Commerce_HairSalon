<?php
include 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email_address = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    
    if (!$name || !$email_address || !$phone || !$message) {
        echo "Invalid input! All fields are required and email must be valid.";
        exit;
    }

    if (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $phone)) {
        echo "Invalid phone number format.";
        exit;
    }

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO messages (name, email, number, message) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email_address, $phone, $message])) {
        // Send the email
        $to = 'yourname@yourdomain.com';
        $email_subject = "Website Contact Form: $name";
        $email_body = "You have received a new message from your website contact form.\n\n".
                      "Here are the details:\n\nName: $name\nEmail: $email_address\nPhone: $phone\nMessage:\n$message";
        
        $headers = "From: noreply@yourdomain.com\n";
        $headers .= "Reply-To: $email_address"; 

        if (mail($to, $email_subject, $email_body, $headers)) {
            echo "Message sent successfully!";
        } else {
            echo "Message sending failed.";
        }
    } else {
        echo "Failed to save message in database.";
    }
} else {
    echo "Form submission error.";
}
?>
