<?php
include '../components/connect.php';
session_start();

// Check if the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header('location:login.php');
    exit;
}

// Fetch all appointments
$appointments = [];
$stmt = $pdo->query("SELECT a.AppointmentID, c.Name AS CustomerName, c.Email, s.Name AS ServiceName, a.Date, a.Time, a.Status 
                     FROM appointments a
                     JOIN customers c ON a.CustomerID = c.CustomerID
                     JOIN appointment_service as aps ON a.AppointmentID = aps.AppointmentID
                     JOIN services s ON aps.ServiceID = s.ServiceID");
while ($appointment = $stmt->fetch()) {
    $appointments[] = $appointment;
}

// Fetch all services for management
$services = [];
$serviceStmt = $pdo->query("SELECT * FROM services");
while ($service = $serviceStmt->fetch()) {
    $services[] = $service;
}

// Handle appointment status updates
if (isset($_POST['update_status'])) {
    $appointmentID = $_POST['appointment_id'];
    $newStatus = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE appointments SET Status = ? WHERE AppointmentID = ?");
    $stmt->execute([$newStatus, $appointmentID]);
    header("Location: admin_dashboard.php"); // Redirect to refresh
}

// Handle service updates
if (isset($_POST['manage_service'])) {
    $serviceID = $_POST['service_id'];
    $serviceName = $_POST['service_name'];
    $servicePrice = $_POST['service_price'];
    
    if ($_POST['action'] === 'edit') {
        // Update service
        $stmt = $pdo->prepare("UPDATE services SET Name = ?, Price = ? WHERE ServiceID = ?");
        $stmt->execute([$serviceName, $servicePrice, $serviceID]);
    } elseif ($_POST['action'] === 'delete') {
        // Delete service
        $stmt = $pdo->prepare("DELETE FROM services WHERE ServiceID = ?");
        $stmt->execute([$serviceID]);
    }
    header("Location: admin_dashboard.php"); // Redirect to refresh
}

// Adding a new service
if (isset($_POST['add_service'])) {
    $newServiceName = $_POST['new_service_name'];
    $newServicePrice = $_POST['new_service_price'];

    $stmt = $pdo->prepare("INSERT INTO services (Name, Price) VALUES (?, ?)");
    $stmt->execute([$newServiceName, $newServicePrice]);
    header("Location: admin_dashboard.php"); // Redirect to refresh
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="dashboard">
    <h1 class="heading">Admin Dashboard</h1>

    <h2>Manage Appointments</h2>
    <table>
        <thead>
            <tr>
                <th>Appointment ID</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?php echo $appointment['AppointmentID']; ?></td>
                    <td><?php echo $appointment['CustomerName']; ?></td>
                    <td><?php echo $appointment['Email']; ?></td>
                    <td><?php echo $appointment['ServiceName']; ?></td>
                    <td><?php echo $appointment['Date']; ?></td>
                    <td><?php echo $appointment['Time']; ?></td>
                    <td><?php echo $appointment['Status']; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['AppointmentID']; ?>">
                            <select name="status">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="completed">Completed</option>
                                <option value="canceled">Canceled</option>
                            </select>
                            <button type="submit" name="update_status">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Manage Services</h2>
    <table>
        <thead>
            <tr>
                <th>Service ID</th>
                <th>Service Name</th>
                <th>Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($services as $service): ?>
                <tr>
                    <td><?php echo $service['ServiceID']; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="service_id" value="<?php echo $service['ServiceID']; ?>">
                            <input type="text" name="service_name" value="<?php echo $service['Name']; ?>" required>
                            <input type="number" name="service_price" value="<?php echo $service['Price']; ?>" required>
                            <input type="hidden" name="action" value="edit">
                            <button type="submit" name="manage_service">Edit</button>
                        </form>
                    </td>
                    <td>$<?php echo $service['Price']; ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="service_id" value="<?php echo $service['ServiceID']; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" name="manage_service">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Add New Service</h3>
    <form method="POST" action="">
        <input type="text" name="new_service_name" placeholder="Service Name" required>
        <input type="number" name="new_service_price" placeholder="Service Price" required>
        <button type="submit" name="add_service">Add Service</button>
    </form>
</section>

<script src="../js/admin_script.js"></script>

</body>
</html>
