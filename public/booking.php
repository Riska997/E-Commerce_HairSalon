<?php
// Database connection
require '../config/db.php';

// Check if the user is logged in
if (!isset($_SESSION['customer_id'])) {
    die("Please login to book an appointment.");
}

$services = []; 

// Fetch available services from the database
$stmt = $pdo->query("SELECT * FROM services");
while ($service = $stmt->fetch()) {
    $services[] = $service;
}

// Handle form submission (booking an appointment)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerID = $_SESSION['customer_id'];
    $date = $_POST['date'];
    $time = $_POST['time'];
    $serviceID = $_POST['serviceID'];

    // Insert booking into the appointments table
    $stmt = $pdo->prepare("INSERT INTO appointments (CustomerID, Date, Time) VALUES (?, ?, ?)");
    if ($stmt->execute([$customerID, $date, $time])) {
        $appointmentID = $pdo->lastInsertId(); 

        // Link selected service to the appointment
        $stmt = $pdo->prepare("INSERT INTO appointment_service (AppointmentID, ServiceID) VALUES (?, ?)");
        if ($stmt->execute([$appointmentID, $serviceID])) {
            // Success message after successful booking
            $success = "Appointment booked successfully!";
            $serviceDetails = $pdo->query("SELECT * FROM services WHERE ServiceID = $serviceID")->fetch();
        } else {
            $error = "Error linking the service to the appointment.";
        }
    } else {
        $error = "Error booking the appointment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment</title>
</head>
<body>
    <h2>Book an Appointment</h2>

    <?php if (!empty($success)): ?>
        <!-- Display success message and appointment details -->
        <p style="color:green;"><?php echo $success; ?></p>
        <p>Your appointment is confirmed for <?php echo $date; ?> at <?php echo $time; ?>.</p>
        <p>Service: <?php echo $serviceDetails['Name']; ?> - $<?php echo $serviceDetails['Price']; ?></p>
    <?php elseif (!empty($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php else: ?>
        <!-- Booking form -->
        <form method="POST" action="">
            <label for="date">Appointment Date:</label>
            <input type="date" name="date" required><br><br>
            
            <label for="time">Appointment Time:</label>
            <input type="time" name="time" required><br><br>
            
            <label for="serviceID">Select Service:</label>
            <select name="serviceID" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo $service['ServiceID']; ?>">
                        <?php echo $service['Name']; ?> - $<?php echo $service['Price']; ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <button type="submit">Book Now</button>
        </form>
    <?php endif; ?>
</body>
</html>
