<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Cache-Control: no-store, private');

function booking_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/* CUSTOMER AUTHENTICATION */

if (($_SESSION['user_role'] ?? '') === 'customer') {
    check_session_expiry();
}

$customerId = filter_var(
    $_SESSION['customer_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$isCustomer =
    ($_SESSION['user_role'] ?? '') === 'customer'
    && $customerId !== false
    && $customerId !== null;

/* CSRF PROTECTION */

if (empty($_SESSION['booking_csrf_token'])) {
    $_SESSION['booking_csrf_token'] =
        bin2hex(random_bytes(32));
}

/* INITIAL VALUES */

$timezone = new DateTimeZone('Africa/Johannesburg');

$today = (
    new DateTimeImmutable('now', $timezone)
)->format('Y-m-d');

$errors = [];
$groups = [];
$allServices = [];
$pdo = null;

$selectedService = is_string($_GET['service'] ?? null)
    ? $_GET['service']
    : '';

$date = '';
$time = '';

$confirmation = $_SESSION['booking_confirmation'] ?? null;
unset($_SESSION['booking_confirmation']);

/* CATEGORY DISPLAY ORDER */

$categoryOrder = [
    'Barber & Grooming',
    'Haircuts & Styling',
    'Hair Colouring',
    'Hair Treatments & Relaxers',
    'Braiding & Weaving',
    'Hair Extensions & Wigs',
    'Manicures & Pedicures',
];

/* Existing user-provided category photographs, optimised for the web. */
$categoryImages = [
    'Barber & Grooming' => '/images/service-categories/barber-grooming.webp',
    'Haircuts & Styling' => '/images/service-categories/haircuts-styling.webp',
    'Hair Colouring' => '/images/service-categories/hair-colouring.webp',
    'Hair Treatments & Relaxers' => '/images/service-categories/hair-treatments-relaxers.webp',
    'Braiding & Weaving' => '/images/service-categories/braiding-weaving.webp',
    'Hair Extensions & Wigs' => '/images/service-categories/hair-extensions-wigs.webp',
    'Manicures & Pedicures' => '/images/service-categories/manicures-pedicures.webp',
];

/* LOAD ALL 33 SERVICES */

try {
    $pdo = db_connect();

    $allServices = $pdo->query(
        'SELECT
            ServiceID,
            Category,
            Name,
            Description,
            Price,
            PriceLabel
         FROM services
         ORDER BY Category ASC, Name ASC'
    )->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allServices as $service) {
        $category = trim(
            (string) ($service['Category'] ?? '')
        ) ?: 'Other';

        $groups[$category][] = $service;
    }

    uksort(
        $groups,
        static function (
            string $a,
            string $b
        ) use ($categoryOrder): int {

            $aPos = array_search(
                $a,
                $categoryOrder,
                true
            );

            $bPos = array_search(
                $b,
                $categoryOrder,
                true
            );

            return (
                ($aPos === false ? 999 : $aPos)
                <=>
                ($bPos === false ? 999 : $bPos)
            ) ?: strcmp($a, $b);
        }
    );
} catch (Throwable $exception) {

    error_log(
        'Salon service catalogue error: ' .
            $exception->getMessage()
    );

    $errors[] =
        'The service catalogue is temporarily unavailable.';
}

/* HANDLE APPOINTMENT REQUEST */

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    if (!$isCustomer) {
        header(
            'Location: /login.php?next=booking.php',
            true,
            303
        );
        exit;
    }

    $selectedService = is_string(
        $_POST['serviceID'] ?? null
    )
        ? trim($_POST['serviceID'])
        : '';

    $date = is_string($_POST['date'] ?? null)
        ? trim($_POST['date'])
        : '';

    $time = is_string($_POST['time'] ?? null)
        ? trim($_POST['time'])
        : '';

    $token = is_string($_POST['csrf_token'] ?? null)
        ? $_POST['csrf_token']
        : '';

    /* VALIDATE CSRF */

    if (!hash_equals(
        (string) $_SESSION['booking_csrf_token'],
        $token
    )) {
        $errors[] =
            'Your form session expired. Refresh and try again.';
    }

    /* VALIDATE SERVICE */

    $serviceId = filter_var(
        $selectedService,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($serviceId === false || $serviceId === null) {
        $errors[] =
            'Please choose a valid salon service.';
    }

    /* VALIDATE DATE AND TIME */

    if (
        preg_match(
            '/^\d{4}-\d{2}-\d{2}$/D',
            $date
        ) !== 1 ||
        preg_match(
            '/^([01]\d|2[0-3]):[0-5]\d$/D',
            $time
        ) !== 1
    ) {

        $errors[] =
            'Please enter a valid appointment date and time.';
    } else {

        $appointmentAt =
            DateTimeImmutable::createFromFormat(
                '!Y-m-d H:i',
                "$date $time",
                $timezone
            );

        $issues = DateTimeImmutable::getLastErrors();

        if (
            $appointmentAt === false ||
            (
                $issues !== false &&
                (
                    $issues['warning_count'] > 0 ||
                    $issues['error_count'] > 0
                )
            ) ||
            $appointmentAt <=
            new DateTimeImmutable('now', $timezone)
        ) {

            $errors[] =
                'Please select a future appointment date and time.';
        }
    }

    /* SAVE APPOINTMENT */

    if ($errors === [] && $pdo instanceof PDO) {

        try {

            $pdo->beginTransaction();

            $find = $pdo->prepare(
                'SELECT
                    ServiceID,
                    Name,
                    Price,
                    PriceLabel
                 FROM services
                 WHERE ServiceID = :id
                 LIMIT 1'
            );

            $find->execute([
                ':id' => $serviceId
            ]);

            $service = $find->fetch(PDO::FETCH_ASSOC);

            if (!$service) {
                throw new InvalidArgumentException(
                    'This service is no longer available.'
                );
            }

            $insert = $pdo->prepare(
                "INSERT INTO appointments
                    (CustomerID, `Date`, `Time`, Status)
                 VALUES
                    (:customer, :date, :time, 'Pending')"
            );

            $insert->execute([
                ':customer' => (int) $customerId,
                ':date' => $date,
                ':time' => $time
            ]);

            $appointmentId = (int) $pdo->lastInsertId();

            $link = $pdo->prepare(
                'INSERT INTO appointment_service
                    (AppointmentID, ServiceID)
                 VALUES
                    (:appointment, :service)'
            );

            $link->execute([
                ':appointment' => $appointmentId,
                ':service' => $serviceId
            ]);

            $pdo->commit();

            /* PREPARE CONFIRMATION */

            $_SESSION['booking_confirmation'] = [
                'id' => $appointmentId,
                'service' => $service['Name'],
                'price' =>
                trim((string) (
                    $service['PriceLabel'] ?? ''
                )) ?: (
                    'R' . number_format(
                        (float) $service['Price'],
                        2
                    )
                ),
                'date' => $date,
                'time' => $time
            ];

            $_SESSION['booking_csrf_token'] =
                bin2hex(random_bytes(32));

            header(
                'Location: /booking.php#confirmation',
                true,
                303
            );
            exit;
        } catch (InvalidArgumentException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $exception->getMessage();
        } catch (Throwable $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Appointment request failed: ' .
                    $exception->getMessage()
            );

            $errors[] =
                'Your appointment could not be saved. Please try again.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en-ZA">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Browse Eve and Steve Salon services and request an appointment.">

    <title>
        Book an Appointment | Eve &amp; Steve Salon
    </title>

    <!-- EXTERNAL CSS ONLY -->
    <link rel="stylesheet" href="/css/draft.css">
    <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
    <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">

    <!-- LOGO TO BROWSER TAB -->
    <link rel="icon" type="image/png" href="/favicon.png">
</head>

<body class="booking-page">

    <!-- HEADER -->

    <header class="booking-header">

        <div class="booking-container booking-header-inner">

            <a href="/index.php" class="booking-brand">
    <img
        src="/images/logo.png"
        alt="Eve and Steve Salon logo"
        class="salon-header-logo"
    >

    <span class="site-name">
        Eve &amp; Steve Salon
    </span>
</a>

            <nav
                class="booking-nav"
                aria-label="Main navigation">

                <a href="/index.php">Home</a>

                <a href="/index.php#services">
                    Featured Services
                </a>

                <a href="#services">
                    All Services
                </a>

                <?php if ($isCustomer): ?>

                    <a href="/my-appointments.php">My Appointments</a>

                    <a href="/logout.php">
                        Sign Out
                    </a>

                <?php else: ?>

                    <a href="/login.php?next=booking.php">
                        Sign In
                    </a>

                    <a
                        class="booking-nav-highlight"
                        href="/register.php?next=booking.php">
                        Register
                    </a>

                <?php endif; ?>

            </nav>

        </div>

    </header>


    <!-- HERO -->

    <section class="booking-hero">

        <div class="booking-container">

            <h1>
                All Salon Services &amp; Booking
            </h1>

            <p>
                Hair, beauty and grooming for everyone.
                Explore our services and request your
                preferred appointment.
            </p>

        </div>

    </section>


    <!-- MAIN CONTENT -->

    <main class="booking-container booking-main">

        <p class="booking-back">

            <a href="/index.php">
                &larr; Back to Salon Homepage
            </a>

        </p>


        <!-- VALIDATION ERRORS -->

        <?php if ($errors !== []): ?>

            <div
                class="booking-message booking-error"
                role="alert">

                <strong>
                    Please correct the following:
                </strong>

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= booking_escape($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- APPOINTMENT CONFIRMATION -->

        <?php if (is_array($confirmation)): ?>

            <section
                id="confirmation"
                class="booking-message booking-success"
                role="status">

                <h2>
                    Appointment Request Received
                </h2>

                <p>
                    <strong>
                        Reference #<?= booking_escape(
                                        $confirmation['id'] ?? ''
                                    ) ?>
                    </strong>
                </p>

                <p>
                    <?= booking_escape(
                        $confirmation['service'] ?? ''
                    ) ?>

                    —

                    <?= booking_escape(
                        $confirmation['price'] ?? ''
                    ) ?>
                </p>

                <p>
                    <?= booking_escape(
                        $confirmation['date'] ?? ''
                    ) ?>

                    at

                    <?= booking_escape(
                        $confirmation['time'] ?? ''
                    ) ?>
                </p>

                <p>
                    <strong>
                        Status: Pending administrator approval.
                    </strong>
                </p>

                <p><a href="/my-appointments.php">View My Appointments</a></p>

            </section>

        <?php endif; ?>


        <div class="booking-layout">

            <!-- COMPLETE SERVICE CATALOGUE -->

            <section
                id="services"
                class="booking-catalogue"
                aria-labelledby="catalogue-heading">

                <h2 id="catalogue-heading">
                    All Salon Services
                </h2>

                <p class="booking-lead">
                    <?= count($allServices) ?> services
                    across <?= count($groups) ?> categories.

                    Select a category to view its
                    services and prices.
                </p>


                <?php if ($groups === []): ?>

                    <p>
                        No salon services are available
                        at the moment.
                    </p>

                <?php else: ?>

                    <?php foreach ($groups as $category => $services): ?>

                        <details
                            class="booking-category"
                            <?= $category === array_key_first($groups) ? 'open' : '' ?>>

                            <summary>

                                <?php if (isset($categoryImages[$category])): ?>
                                    <img
                                        class="booking-category-image"
                                        src="<?= booking_escape($categoryImages[$category]) ?>"
                                        alt="<?= booking_escape($category) ?> salon services"
                                        width="106"
                                        height="76"
                                        loading="lazy"
                                        decoding="async">
                                <?php endif; ?>

                                <span class="booking-category-copy">
                                    <span class="booking-category-title">
                                        <?= booking_escape($category) ?>
                                    </span>
                                    <span class="booking-count">
                                        <?= count($services) ?> services
                                    </span>
                                </span>

                            </summary>

                            <ul class="booking-service-list">

                                <?php foreach ($services as $service): ?>

                                    <?php
                                    $price = trim(
                                        (string) (
                                            $service['PriceLabel'] ?? ''
                                        )
                                    ) ?: (
                                        'R' .
                                        number_format(
                                            (float) $service['Price'],
                                            2
                                        )
                                    );
                                    ?>

                                    <li>

                                        <div class="booking-service-detail">

                                            <strong>
                                                <?= booking_escape(
                                                    $service['Name']
                                                ) ?>
                                            </strong>

                                            <?php if (
                                                trim((string) (
                                                    $service['Description'] ?? ''
                                                )) !== ''
                                            ): ?>

                                                <small>
                                                    <?= booking_escape(
                                                        $service['Description']
                                                    ) ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                        <div class="booking-service-action">

                                            <span>
                                                <?= booking_escape($price) ?>
                                            </span>

                                            <a
                                                href="<?= $isCustomer
                                                            ? '/booking.php?service=' .
                                                            (int) $service['ServiceID'] .
                                                            '#appointment'
                                                            : '/login.php?next=booking.php' ?>">
                                                Book
                                            </a>

                                        </div>

                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </details>

                    <?php endforeach; ?>

                <?php endif; ?>


                <p class="booking-note">
                    Prices shown as ranges or starting
                    amounts are estimates. The salon
                    will confirm final pricing.
                </p>

            </section>


            <!-- APPOINTMENT FORM / GUEST PROMPT -->

            <aside id="appointment" class="booking-aside">

                <section
                    class="booking-card"
                    aria-labelledby="appointment-heading">

                    <?php if (!$isCustomer): ?>

                        <h2 id="appointment-heading">
                            Ready to Make an Appointment?
                        </h2>

                        <p>
                            Explore every service as a guest.
                            Sign in or create an account when
                            you are ready to book.
                        </p>

                        <div class="booking-actions">

                            <a
                                class="booking-button"
                                href="/login.php?next=booking.php">
                                Customer Sign In
                            </a>

                            <a
                                class="booking-button booking-secondary"
                                href="/register.php?next=booking.php">
                                Create Account
                            </a>

                        </div>


                    <?php else: ?>

                        <h2 id="appointment-heading">
                            Request an Appointment
                        </h2>

                        <form
                            class="booking-form"
                            method="post"
                            action="/booking.php">

                            <input
                                type="hidden"
                                name="csrf_token"
                                value="<?= booking_escape(
                                            $_SESSION['booking_csrf_token']
                                        ) ?>">

                            <label for="serviceID">
                                Select Salon Service *
                            </label>

                            <select
                                id="serviceID"
                                name="serviceID"
                                required
                                <?= $groups === [] ? 'disabled' : '' ?>>

                                <option value="">
                                    Choose a service
                                </option>

                                <?php foreach ($groups as $category => $services): ?>

                                    <optgroup
                                        label="<?= booking_escape($category) ?>">

                                        <?php foreach ($services as $service): ?>

                                            <?php
                                            $price = trim(
                                                (string) (
                                                    $service['PriceLabel'] ?? ''
                                                )
                                            ) ?: (
                                                'R' .
                                                number_format(
                                                    (float) $service['Price'],
                                                    2
                                                )
                                            );
                                            ?>

                                            <option
                                                value="<?= (int) $service['ServiceID'] ?>"
                                                <?= $selectedService ===
                                                    (string) $service['ServiceID']
                                                    ? 'selected'
                                                    : '' ?>>
                                                <?= booking_escape($service['Name']) ?>
                                                —
                                                <?= booking_escape($price) ?>
                                            </option>

                                        <?php endforeach; ?>

                                    </optgroup>

                                <?php endforeach; ?>

                            </select>


                            <label for="date">
                                Preferred Date *
                            </label>

                            <input
                                type="date"
                                id="date"
                                name="date"
                                min="<?= booking_escape($today) ?>"
                                value="<?= booking_escape($date) ?>"
                                required>


                            <label for="time">
                                Preferred Time *
                            </label>

                            <input
                                type="time"
                                id="time"
                                name="time"
                                value="<?= booking_escape($time) ?>"
                                required>


                            <button
                                class="booking-button"
                                type="submit"
                                <?= $groups === [] ? 'disabled' : '' ?>>
                                Submit Appointment Request
                            </button>

                        </form>

                        <p class="booking-status-note">
                            Your request begins as
                            <strong>Pending</strong>.

                            The salon must accept it
                            before your appointment is confirmed.
                        </p>

                    <?php endif; ?>

                </section>

            </aside>

        </div>

    </main>


    <!-- SHARED FOOTER -->

    <?php require __DIR__ . '/../src/inc/footer.php'; ?>

</body>

</html>