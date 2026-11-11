<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Cache-Control: no-store, private');

function history_escape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

if (($_SESSION['user_role'] ?? '') === 'customer') {
    check_session_expiry();
}

$customerId = filter_var(
    $_SESSION['customer_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (
    ($_SESSION['user_role'] ?? '') !== 'customer' ||
    $customerId === false ||
    $customerId === null
) {
    header('Location: /login.php?next=my-appointments.php', true, 303);
    exit;
}

$appointments = [];
$error = '';

try {
    $pdo = db_connect();
    $stmt = $pdo->prepare(
        'SELECT a.AppointmentID,
                a.`Date` AS AppointmentDate,
                a.`Time` AS AppointmentTime,
                a.Status,
                s.Name AS ServiceName,
                s.Price,
                s.PriceLabel
         FROM appointments AS a
         LEFT JOIN appointment_service AS aps ON aps.AppointmentID = a.AppointmentID
         LEFT JOIN services AS s ON s.ServiceID = aps.ServiceID
         WHERE a.CustomerID = :customer
         ORDER BY a.`Date` DESC, a.`Time` DESC, a.AppointmentID DESC
         LIMIT 150'
    );
    $stmt->execute([':customer' => (int) $customerId]);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = (int) $row['AppointmentID'];
        if (!isset($appointments[$id])) {
            $appointments[$id] = [
                'id' => $id,
                'date' => (string) $row['AppointmentDate'],
                'time' => (string) $row['AppointmentTime'],
                'status' => (string) $row['Status'],
                'services' => []
            ];
        }
        if (!empty($row['ServiceName'])) {
            $priceLabel = trim((string) ($row['PriceLabel'] ?? ''));
            $price = $priceLabel !== ''
                ? $priceLabel
                : 'R' . number_format((float) $row['Price'], 2);
            $appointments[$id]['services'][] = [
                'name' => (string) $row['ServiceName'],
                'price' => $price
            ];
        }
    }
} catch (Throwable $exception) {
    error_log('Customer appointment history error: ' . $exception->getMessage());
    $error = 'Your appointments could not be loaded right now. Please try again.';
}

?>
<!DOCTYPE html>
<html lang="en-ZA">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View your Eve and Steve Salon appointment requests and their current status.">
    <title>My Appointments | Eve &amp; Steve Salon</title>
    <link rel="stylesheet" href="/css/draft.css">
    <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
    <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">
    <link rel="icon" type="image/png" href="/favicon.png">
</head>
<body class="booking-page appointments-page">
    <header class="booking-header">
        <div class="booking-container booking-header-inner">
            <a href="/index.php" class="booking-brand">
                <img src="/images/logo.png" alt="Eve and Steve Salon logo" class="salon-header-logo">
                <span class="site-name">Eve &amp; Steve Salon</span>
            </a>
            <nav class="booking-nav" aria-label="Main navigation">
                <a href="/index.php">Home</a>
                <a href="/booking.php#services">All Services</a>
                <a href="/my-appointments.php" aria-current="page">My Appointments</a>
                <a href="/logout.php">Sign Out</a>
            </nav>
        </div>
    </header>
    <section class="booking-hero">
        <div class="booking-container">
            <h1>My Appointments</h1>
            <p>Review your appointment requests and their latest recorded status.</p>
        </div>
    </section>
    <main class="appointments-container appointments-main">
        <p class="appointments-lead">Appointments begin as <strong>Pending</strong>. The salon must confirm them before the requested time is approved.</p>
        <?php if ($error !== ''): ?>
            <div class="booking-message booking-error" role="alert"><?= history_escape($error) ?></div>
        <?php elseif ($appointments === []): ?>
            <div class="appointments-empty">
                <h2>You have not requested an appointment yet.</h2>
                <p>Explore the salon services and choose a suitable date and time.</p>
                <a class="booking-button" href="/booking.php#services">Browse All Services</a>
            </div>
        <?php else: ?>
            <div class="appointments-list">
                <?php foreach ($appointments as $appointment): ?>
                    <?php
                    $rawStatus = $appointment['status'];
                    $statusClass = in_array($rawStatus, ['Pending', 'Confirmed', 'Booked', 'Cancelled', 'Completed'], true)
                        ? strtolower($rawStatus)
                        : 'pending';
                    $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $appointment['date']);
                    $formattedDate = $dateObject ? $dateObject->format('j F Y') : $appointment['date'];
                    ?>
                    <article class="appointment-item">
                        <div>
                            <h2>Appointment #<?= $appointment['id'] ?></h2>
                            <p><strong>Preferred date:</strong> <?= history_escape($formattedDate) ?></p>
                            <p><strong>Preferred time:</strong> <?= history_escape(substr($appointment['time'], 0, 5)) ?></p>
                            <?php foreach ($appointment['services'] as $service): ?>
                                <p><strong>Service:</strong> <?= history_escape($service['name']) ?> — <?= history_escape($service['price']) ?></p>
                            <?php endforeach; ?>
                        </div>
                        <span class="status-chip status-<?= history_escape($statusClass) ?>">
                            <?= history_escape($rawStatus) ?>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
            <p class="appointments-more"><a class="booking-button" href="/booking.php#services">Request Another Appointment</a></p>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../src/inc/footer.php'; ?>
</body>
</html>
