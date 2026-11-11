<?php

declare(strict_types=1);

/**
 * Eve & Steve Salon
 * Customer Registration
 *
 * File: public/register.php
 */

require_once __DIR__ . '/../config/db.php';

function reg_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

header('Cache-Control: no-store, private');

/*
 * Preserve the booking destination.
 */

$incomingNext = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['next'] ?? '')
    : ($_GET['next'] ?? '');

$allowedDestinations = ['booking.php', 'my-appointments.php'];
$next = is_string($incomingNext) && in_array($incomingNext, $allowedDestinations, true)
    ? $incomingNext
    : '';

$signInLink = '/login.php' .
    ($next !== '' ? '?next=' . rawurlencode($next) : '');

/*
 * Redirect customers who are already logged in.
 */

if (
    ($_SESSION['user_role'] ?? null) === 'customer' &&
    !empty($_SESSION['customer_id'])
) {
    header(
        'Location: ' .
            ($next !== '' ? '/' . $next : '/index.php')
    );
    exit;
}

/*
 * Registration security token.
 */

if (empty($_SESSION['register_csrf_token'])) {
    $_SESSION['register_csrf_token'] =
        bin2hex(random_bytes(32));
}

$errors = [];

$name = '';
$email = '';
$phone = '';

$registered = !empty($_SESSION['customer_registered']);

unset($_SESSION['customer_registered']);

/*
==================================================
HANDLE REGISTRATION
==================================================
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = is_string($_POST['name'] ?? null)
        ? trim($_POST['name'])
        : '';

    $email = is_string($_POST['email'] ?? null)
        ? strtolower(trim($_POST['email']))
        : '';

    $phone = is_string($_POST['phone'] ?? null)
        ? trim($_POST['phone'])
        : '';

    $password = is_string($_POST['password'] ?? null)
        ? $_POST['password']
        : '';

    $confirm = is_string(
        $_POST['confirm_password'] ?? null
    )
        ? $_POST['confirm_password']
        : '';

    $token = is_string($_POST['csrf_token'] ?? null)
        ? $_POST['csrf_token']
        : '';

    /*
     * Validate the submitted information.
     */

    if (!hash_equals(
        (string) $_SESSION['register_csrf_token'],
        $token
    )) {
        $errors[] =
            'Your form session expired. Refresh and try again.';
    }

    if ($name === '' || strlen($name) > 100) {
        $errors[] =
            'Enter your full name (maximum 100 bytes).';
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($email) > 100
    ) {
        $errors[] =
            'Enter a valid email address.';
    }

    if (preg_match('/^0[0-9]{9}$/D', $phone) !== 1) {
        $errors[] =
            'Enter exactly 10 South African phone digits, starting with 0.';
    }

    if (
        strlen($password) < 12 ||
        strlen($password) > 72
    ) {
        $errors[] =
            'Your password must be between 12 and 72 bytes.';
    }

    if ($password !== $confirm) {
        $errors[] =
            'The passwords do not match.';
    }

    /*
     * Save a valid customer account.
     */

    if ($errors === []) {

        try {

            $pdo = db_connect();

            $existing = $pdo->prepare(
                'SELECT CustomerID
                 FROM customers
                 WHERE Email = :email
                 LIMIT 1'
            );

            $existing->execute([
                ':email' => $email
            ]);

            if ($existing->fetchColumn()) {

                $errors[] =
                    'An account already exists with that email. Please sign in.';
            } else {

                $statement = $pdo->prepare(
                    'INSERT INTO customers
                        (Name, Email, ContactDetails, Password)
                     VALUES
                        (:name, :email, :phone, :password)'
                );

                $statement->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':password' => password_hash(
                        $password,
                        PASSWORD_BCRYPT
                    )
                ]);

                $_SESSION['register_csrf_token'] =
                    bin2hex(random_bytes(32));

                $_SESSION['customer_registered'] = true;

                header(
                    'Location: /register.php' .
                        ($next !== '' ? '?next=' . rawurlencode($next) : ''),
                    true,
                    303
                );

                exit;
            }
        } catch (PDOException $exception) {

            if (
                (int) ($exception->errorInfo[1] ?? 0)
                === 1062
            ) {

                $errors[] =
                    'An account already exists with that email. Please sign in.';
            } else {

                error_log(
                    'Salon registration error: ' .
                        $exception->getMessage()
                );

                $errors[] =
                    'Registration is unavailable. Please try again.';
            }
        } catch (Throwable $exception) {

            error_log(
                'Salon registration error: ' .
                    $exception->getMessage()
            );

            $errors[] =
                'Registration is unavailable. Please try again.';
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

    <title>
        Create an Account | Eve &amp; Steve Salon
    </title>


    <!-- Shared website styles -->
    <link rel="stylesheet" href="/css/draft.css">
    <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
    <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">

    <!-- LOGO TO BROWSER TAB -->
    <link rel="icon" type="image/png" href="/favicon.png">
</head>

<body class="reg-page">

    <!-- ==========================================
     WEBSITE HEADER
========================================== -->

    <header class="reg-header">

        <div class="reg-wrap reg-header-inner">

            <a class="reg-brand" href="/index.php">
    <img
        src="/images/logo.png"
        alt="Eve and Steve Salon logo"
        class="salon-header-logo"
    >

    <span class="salon-brand-copy">
        <strong>Eve &amp; Steve Salon</strong>
        <small>Good Hair Style, Good Selfie</small>
    </span>
</a>

            <nav class="reg-nav" aria-label="Main navigation">

                <a
                    class="reg-hide-mobile"
                    href="/index.php">
                    Home
                </a>

                <a
                    class="reg-hide-mobile"
                    href="/booking.php">
                    Services
                </a>

                <a href="<?= reg_escape($signInLink) ?>">
                    Sign In
                </a>

                <a
                    class="reg-nav-button"
                    href="/booking.php">
                    Browse as Guest
                </a>

            </nav>

        </div>

    </header>


    <!-- ==========================================
     WELCOME BANNER
========================================== -->

    <section class="reg-banner">

        <h1>
            Welcome to Eve &amp; Steve Salon
        </h1>

        <p>
            Hair, beauty and grooming for everyone
        </p>

    </section>


    <!-- ==========================================
     MAIN CONTENT
========================================== -->

    <main class="reg-wrap reg-main">

        <div class="reg-grid">

            <!-- SIDE INFORMATION PANEL -->

            <aside class="reg-side">

                <h2>
                    Look Good. Feel Great.
                </h2>

                <p>
                    Create an account to request
                    your preferred appointment.
                </p>

                <ul>
                    <li>Explore all 33 salon services</li>
                    <li>Book hair and grooming appointments</li>
                    <li>Receive your booking reference</li>
                    <li>Check your appointment status</li>
                </ul>

                <p>
                    Not ready to register?
                    You can browse the services as a guest.
                </p>

            </aside>


            <!-- REGISTRATION FORM CARD -->

            <section
                class="reg-card"
                aria-labelledby="register-heading">

                <h2 id="register-heading">
                    Create a Customer Account
                </h2>

                <p class="reg-muted">
                    Register to request appointments
                    at Eve &amp; Steve Salon.
                </p>


                <!-- SUCCESS MESSAGE -->

                <?php if ($registered): ?>

                    <div
                        class="reg-alert success"
                        role="status">

                        Your account was created successfully.

                        Sign in to continue
                        <?= $next ? 'with your appointment' : '' ?>.

                    </div>

                    <a
                        class="reg-button"
                        href="<?= reg_escape($signInLink) ?>">
                        Continue to Sign In
                    </a>


                <?php else: ?>


                    <!-- VALIDATION ERRORS -->

                    <?php if ($errors): ?>

                        <div
                            class="reg-alert error"
                            role="alert">

                            <strong>
                                Please correct the following:
                            </strong>

                            <ul>

                                <?php foreach ($errors as $error): ?>

                                    <li>
                                        <?= reg_escape($error) ?>
                                    </li>

                                <?php endforeach; ?>

                            </ul>

                        </div>

                    <?php endif; ?>


                    <!-- REGISTRATION FORM -->

                    <form
                        class="reg-form"
                        method="post"
                        action="/register.php">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= reg_escape($_SESSION['register_csrf_token']) ?>">

                        <input
                            type="hidden"
                            name="next"
                            value="<?= reg_escape($next) ?>">


                        <!-- FULL NAME -->

                        <div class="reg-field">

                            <label for="name">
                                Full Name *
                            </label>

                            <input
                                id="name"
                                name="name"
                                type="text"
                                autocomplete="name"
                                maxlength="100"
                                value="<?= reg_escape($name) ?>"
                                required>

                        </div>


                        <!-- EMAIL -->

                        <div class="reg-field">

                            <label for="email">
                                Email Address *
                            </label>

                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                maxlength="100"
                                value="<?= reg_escape($email) ?>"
                                required>

                        </div>


                        <!-- PHONE NUMBER -->

                        <div class="reg-field">

                            <label for="phone">
                                Phone Number *
                            </label>

                            <input
                                id="phone"
                                name="phone"
                                type="tel"
                                inputmode="numeric"
                                autocomplete="tel"
                                placeholder="0712345678"
                                minlength="10"
                                maxlength="10"
                                pattern="0[0-9]{9}"
                                title="Exactly 10 digits starting with 0"
                                oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"
                                value="<?= reg_escape($phone) ?>"
                                required>

                            <small>
                                Exactly 10 South African digits,
                                starting with 0.
                            </small>

                        </div>


                        <!-- PASSWORD -->

                        <div class="reg-field">

                            <label for="password">
                                Password *
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                minlength="12"
                                maxlength="72"
                                required>

                            <small>
                                At least 12 characters
                                (maximum 72 bytes).
                            </small>

                        </div>


                        <!-- CONFIRM PASSWORD -->

                        <div class="reg-field">

                            <label for="confirm_password">
                                Confirm Password *
                            </label>

                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                autocomplete="new-password"
                                minlength="12"
                                maxlength="72"
                                required>

                        </div>


                        <!-- SUBMIT BUTTON -->

                        <button
                            class="reg-button"
                            type="submit">
                            Create Account
                        </button>

                    </form>

                <?php endif; ?>


                <!-- ADDITIONAL NAVIGATION -->

                <div class="reg-actions">

                    <a
                        class="reg-button alt"
                        href="<?= reg_escape($signInLink) ?>">
                        Already registered? Sign In
                    </a>

                    <a
                        class="reg-button alt"
                        href="/booking.php">
                        Browse All Services
                    </a>

                </div>

                <p class="reg-footer-note">

                    <a href="/index.php">
                        &larr; Back to Homepage
                    </a>

                </p>

            </section>

        </div>

    </main>


    <!-- ==========================================
     SHARED WEBSITE FOOTER
========================================== -->

    <?php require __DIR__ . '/../src/inc/footer.php'; ?>

</body>

</html>