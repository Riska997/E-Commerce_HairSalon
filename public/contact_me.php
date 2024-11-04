<?php
// Check if the form is submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check for empty fields and validate the email
    if (empty($_POST['name']) || 
        empty($_POST['email']) || 
        empty($_POST['phone']) || 
        empty($_POST['message']) || 
        !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) 
    {
        echo "Invalid input! All fields are required and email must be valid.";
        return false;
    }

    // Sanitize input fields
    $name = strip_tags(htmlspecialchars($_POST['name']));
    $email_address = strip_tags(htmlspecialchars($_POST['email']));
    $phone = strip_tags(htmlspecialchars($_POST['phone']));
    $message = strip_tags(htmlspecialchars($_POST['message']));

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9\-\(\)\/\+\s]*$/', $phone)) {
        echo "Invalid phone number format.";
        return false;
    }

    // Create the email content
    $to = 'yourname@yourdomain.com'; // Update with your email address
    $email_subject = "Website Contact Form: $name";
    $email_body = "You have received a new message from your website contact form.\n\n" .
                  "Here are the details:\n\n" .
                  "Name: $name\n\n" .
                  "Email: $email_address\n\n" .
                  "Phone: $phone\n\n" .
                  "Message:\n$message";
    
    $headers = "From: noreply@yourdomain.com\n"; // Update this to a valid sender email
    $headers .= "Reply-To: $email_address"; 

    // Send the email using the mail() function
    if (mail($to, $email_subject, $email_body, $headers)) {
        echo "Message sent successfully!";
        return true;
    } else {
        echo "Message sending failed.";
        return false;
    }
} else {
    // In case the form is not submitted via POST
    echo "Form submission error.";
    return false;
}
?>
