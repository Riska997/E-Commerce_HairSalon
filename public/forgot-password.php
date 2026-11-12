<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mail.php';

header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');

/*
=========================================
HTML ESCAPING
=========================================
*/

function reset_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/*
=========================================
CSRF PROTECTION
=========================================
*/

if (empty($_SESSION['password_reset_csrf'])) {
    $_SESSION['password_reset_csrf'] =
        bin2hex(random_bytes(32));
}

/*
=========================================
INITIAL VALUES
=========================================
*/

$errors = [];
$notice = '';
$completed = false;
$email = '';

$tokenInput = (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
)
    ? ($_POST['token'] ?? '')
    : ($_GET['token'] ?? '');

$token = is_string($tokenInput)
    ? $tokenInput
    : '';

$isReset = $token !== '';

$validFormat = (
    strlen($token) === 64 &&
    ctype_xdigit($token)
);

$tokenUsable = false;


/*
=========================================
PROCESS FORM SUBMISSIONS
=========================================
*/

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $csrf = is_string($_POST['csrf_token'] ?? null)
        ? $_POST['csrf_token']
        : '';

    $action = is_string($_POST['action'] ?? null)
        ? $_POST['action']
        : '';

    /*
    =====================================
    VALIDATE CSRF TOKEN
    =====================================
    */

    if (!hash_equals(
        (string) $_SESSION['password_reset_csrf'],
        $csrf
    )) {

        $errors[] =
            'Your form session expired. Refresh the page and try again.';

        /*
    =====================================
    REQUEST PASSWORD RESET
    =====================================
    */
    } elseif ($action === 'request') {

        $email = is_string($_POST['email'] ?? null)
            ? strtolower(trim($_POST['email']))
            : '';

        $appUrl = rtrim(
            (string) (
                getenv('APP_URL') ?:
                'http://127.0.0.1:8010'
            ),
            '/'
        );

        /*
         * Validate email and configuration.
         */

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 100
        ) {

            $errors[] =
                'Please enter a valid email address.';
        } elseif (
            !filter_var(
                getenv('MAIL_FROM') ?: '',
                FILTER_VALIDATE_EMAIL
            ) ||
            !(getenv('MAIL_HOST') ?: '') ||
            !(getenv('MAIL_USERNAME') ?: '') ||
            !(getenv('MAIL_PASSWORD') ?: '')
        ) {

            $errors[] =
                'Password reset emails are not configured on this server.';
        } elseif (
            !filter_var($appUrl, FILTER_VALIDATE_URL)
        ) {

            $errors[] =
                'The password reset service is not configured correctly.';

            /*
         * Basic request cooldown.
         */
        } elseif (
            time() -
            (int) ($_SESSION['last_reset_request'] ?? 0)
            < 60
        ) {

            $notice =
                'If an account exists for this address, reset instructions will be sent. Please wait a minute before requesting another link.';
        } else {

            $_SESSION['last_reset_request'] = time();

            $pdo = null;

            try {

                /*
                =============================
                FIND CUSTOMER ACCOUNT
                =============================
                */

                $pdo = db_connect();

                $find = $pdo->prepare(
                    'SELECT CustomerID
                     FROM customers
                     WHERE Email = :email
                     LIMIT 1'
                );

                $find->execute([
                    ':email' => $email
                ]);

                $customerId = $find->fetchColumn();

                /*
                =============================
                CREATE RESET TOKEN
                =============================
                */

                if ($customerId !== false) {

                    $rawToken = bin2hex(
                        random_bytes(32)
                    );

                    $tokenHash = hash(
                        'sha256',
                        $rawToken
                    );

                    // Token expires after 30 minutes.
                    $expiresAt = gmdate(
                        'Y-m-d H:i:s',
                        time() + 1800
                    );

                    $pdo->beginTransaction();

                    /*
                     * Invalidate previous reset links.
                     */

                    $delete = $pdo->prepare(
                        'DELETE FROM customer_password_resets
                         WHERE CustomerID = :id'
                    );

                    $delete->execute([
                        ':id' => (int) $customerId
                    ]);

                    /*
                     * Store only the token hash.
                     */

                    $create = $pdo->prepare(
                        'INSERT INTO customer_password_resets
                            (CustomerID, TokenHash, ExpiresAt)
                         VALUES
                            (:customer, :token, :expires)'
                    );

                    $create->execute([
                        ':customer' => (int) $customerId,
                        ':token' => $tokenHash,
                        ':expires' => $expiresAt
                    ]);

                    $pdo->commit();

                    /*
                    =============================
                    BUILD RESET LINK
                    =============================
                    */

                    $link = $appUrl .
                        '/forgot-password.php?token=' .
                        urlencode($rawToken);

                    /*
                    =============================
                    SEND EMAIL USING PHPMAILER
                    =============================
                    */

                    try {

                        send_customer_password_reset_email(
                            $email,
                            $link
                        );
                    } catch (Throwable $sendException) {

                        /*
                         * Remove the unusable reset
                         * token if email delivery fails.
                         */

                        try {

                            $remove = $pdo->prepare(
                                'DELETE FROM customer_password_resets
                                 WHERE TokenHash = :hash'
                            );

                            $remove->execute([
                                ':hash' => $tokenHash
                            ]);
                        } catch (Throwable $cleanupException) {

                            error_log(
                                'Password reset token cleanup failure: ' .
                                    $cleanupException->getMessage()
                            );
                        }

                        throw $sendException;
                    }
                } // End customer account check.

                /*
                 * Do not disclose whether the
                 * email belongs to an account.
                 */

                $notice =
                    'If an account exists for that email address, password reset instructions have been sent.';
            } catch (Throwable $exception) {

                if (
                    $pdo instanceof PDO &&
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }

                error_log(
                    'Password reset request error: ' .
                        $exception->getMessage()
                );

                $errors[] =
                    'Unable to process the reset request right now. Please try again later.';
            }
        }

        /*
    =====================================
    SET NEW PASSWORD
    =====================================
    */
    } elseif ($action === 'reset') {

        $password = is_string(
            $_POST['password'] ?? null
        )
            ? $_POST['password']
            : '';

        $confirmPassword = is_string(
            $_POST['confirm_password'] ?? null
        )
            ? $_POST['confirm_password']
            : '';

        /*
         * Validate the reset form.
         */

        if (!$validFormat) {

            $errors[] =
                'This password reset link is invalid.';
        } elseif (
            strlen($password) < 12 ||
            strlen($password) > 72
        ) {

            $errors[] =
                'Your new password must be between 12 and 72 bytes.';
        } elseif ($password !== $confirmPassword) {

            $errors[] =
                'The passwords do not match.';
        } else {

            $pdo = null;

            try {

                $pdo = db_connect();

                $pdo->beginTransaction();

                /*
                =============================
                VERIFY THE RESET TOKEN
                =============================
                */

                $find = $pdo->prepare(
                    'SELECT ResetID, CustomerID
                     FROM customer_password_resets
                     WHERE TokenHash = :hash
                       AND UsedAt IS NULL
                       AND ExpiresAt > UTC_TIMESTAMP()
                     LIMIT 1
                     FOR UPDATE'
                );

                $find->execute([
                    ':hash' => hash(
                        'sha256',
                        $token
                    )
                ]);

                $reset = $find->fetch(
                    PDO::FETCH_ASSOC
                );

                if (!$reset) {

                    $pdo->rollBack();

                    $errors[] =
                        'This reset link is expired, invalid or already used.';
                } else {

                    /*
                    =============================
                    UPDATE CUSTOMER PASSWORD
                    =============================
                    */

                    $change = $pdo->prepare(
                        'UPDATE customers
                         SET Password = :password
                         WHERE CustomerID = :customer'
                    );

                    $change->execute([
                        ':password' => password_hash(
                            $password,
                            PASSWORD_BCRYPT
                        ),
                        ':customer' => (int) $reset['CustomerID']
                    ]);

                    /*
                    =============================
                    MARK THE TOKEN AS USED
                    =============================
                    */

                    $useToken = $pdo->prepare(
                        'UPDATE customer_password_resets
                         SET UsedAt = UTC_TIMESTAMP()
                         WHERE ResetID = :id'
                    );

                    $useToken->execute([
                        ':id' => (int) $reset['ResetID']
                    ]);

                    $pdo->commit();

                    $completed = true;
                    $token = '';
                    $isReset = false;

                    // Rotate the form token.
                    $_SESSION['password_reset_csrf'] =
                        bin2hex(random_bytes(32));
                }
            } catch (Throwable $exception) {

                if (
                    $pdo instanceof PDO &&
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }

                error_log(
                    'Password reset update error: ' .
                        $exception->getMessage()
                );

                $errors[] =
                    'Unable to change your password. Please try again later.';
            }
        }
    } else {

        $errors[] =
            'Invalid request. Please try again.';
    }
}


/*
=========================================
VERIFY PASSWORD RESET LINK
=========================================
*/

if (
    $isReset &&
    $validFormat &&
    !$completed
) {

    try {

        $check = db_connect()->prepare(
            'SELECT 1
             FROM customer_password_resets
             WHERE TokenHash = :hash
               AND UsedAt IS NULL
               AND ExpiresAt > UTC_TIMESTAMP()
             LIMIT 1'
        );

        $check->execute([
            ':hash' => hash(
                'sha256',
                $token
            )
        ]);

        $tokenUsable = (bool) $check->fetchColumn();
    } catch (Throwable $exception) {

        error_log(
            'Password reset token check error: ' .
                $exception->getMessage()
        );

        $errors[] =
            'Unable to verify the reset link at the moment.';
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
        content="Recover your Eve and Steve Salon customer account password.">

    <title>
        Reset Password | Eve &amp; Steve Salon
    </title>

    <!-- EXTERNAL STYLESHEETS -->

    <link rel="stylesheet" href="/css/draft.css">
    <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
    <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">

    <!-- LOGO TO BROWSER TAB -->
    <link rel="icon" type="image/png" href="/favicon.png">
        <script src="/js/password-visibility.js?v=3" defer></script>
</head>

<body class="reg-page">

    <!-- ======================================
         WEBSITE HEADER
    ======================================= -->

    <header class="reg-header">

        <div class="reg-wrap reg-header-inner">

            <a
                class="reg-brand"
                href="/index.php">
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

            <nav
                class="reg-nav"
                aria-label="Main navigation">

                <a href="/index.php">
                    Home
                </a>

                <a href="/booking.php">
                    Services
                </a>

                <a
                    class="reg-nav-button"
                    href="/login.php">
                    Customer Sign In
                </a>

            </nav>

        </div>

    </header>


    <!-- ======================================
         ACCOUNT RECOVERY BANNER
    ======================================= -->

    <section
        class="reg-banner"
        aria-labelledby="recovery-title">

        <h1 id="recovery-title">
            Account Recovery
        </h1>

        <p>
            Securely reset your Eve &amp; Steve Salon password
        </p>

    </section>


    <!-- ======================================
         MAIN CONTENT
    ======================================= -->

    <main class="reg-wrap reg-main">

        <section
            class="reg-card reg-reset-shell"
            aria-labelledby="reset-heading">

            <h2 id="reset-heading">

                <?php if ($completed): ?>

                    Password Updated

                <?php elseif ($isReset): ?>

                    Choose a New Password

                <?php else: ?>

                    Forgot Your Password?

                <?php endif; ?>

            </h2>


            <!-- ERROR MESSAGES -->

            <?php foreach ($errors as $error): ?>

                <div
                    class="reg-alert error"
                    role="alert">

                    <?= reset_escape($error) ?>

                </div>

            <?php endforeach; ?>


            <!-- INFORMATION MESSAGE -->

            <?php if ($notice !== ''): ?>

                <div
                    class="reg-alert success"
                    role="status">

                    <?= reset_escape($notice) ?>

                </div>

            <?php endif; ?>


            <!-- ==================================
                 PASSWORD UPDATED
            =================================== -->

            <?php if ($completed): ?>

                <p>
                    Your password has been changed.
                    You can now sign in.
                </p>

                <a
                    class="reg-button"
                    href="/login.php">
                    Customer Sign In
                </a>


                <!-- ==================================
                 VALID RESET LINK
            =================================== -->

            <?php elseif ($isReset && $tokenUsable): ?>

                <p class="reg-muted">
                    Enter and confirm a new password
                    for your account.
                </p>

                <form
                    class="reg-form"
                    method="post"
                    action="/forgot-password.php">

                    <input
                        type="hidden"
                        name="action"
                        value="reset">

                    <input
                        type="hidden"
                        name="token"
                        value="<?= reset_escape($token) ?>">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= reset_escape($_SESSION['password_reset_csrf']) ?>">


                    <!-- NEW PASSWORD -->

                    <div class="reg-field">

                        <label for="password">
                            New Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            minlength="12"
                            maxlength="72"
                            autocomplete="new-password"
                            required>

                    </div>


                    <!-- CONFIRM PASSWORD -->

                    <div class="reg-field">

                        <label for="confirm_password">
                            Confirm New Password
                        </label>

                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="12"
                            maxlength="72"
                            autocomplete="new-password"
                            required>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        class="reg-button"
                        type="submit">
                        Reset Password
                    </button>

                </form>


                <!-- ==================================
                 INVALID OR EXPIRED RESET LINK
            =================================== -->

            <?php elseif ($isReset): ?>

                <p>
                    This reset link is invalid, expired,
                    or has already been used.
                </p>

                <a
                    class="reg-button"
                    href="/forgot-password.php">
                    Request a New Link
                </a>


                <!-- ==================================
                 REQUEST RESET EMAIL
            =================================== -->

            <?php else: ?>

                <p class="reg-muted">
                    Enter your registered email address
                    to request a password reset link.
                </p>

                <form
                    class="reg-form"
                    method="post"
                    action="/forgot-password.php">

                    <input
                        type="hidden"
                        name="action"
                        value="request">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= reset_escape($_SESSION['password_reset_csrf']) ?>">


                    <!-- EMAIL -->

                    <div class="reg-field">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="100"
                            autocomplete="email"
                            value="<?= reset_escape($email) ?>"
                            required>

                    </div>


                    <!-- SUBMIT -->

                    <button
                        class="reg-button"
                        type="submit">
                        Send Reset Link
                    </button>

                </form>

            <?php endif; ?>


            <!-- LOGIN LINK -->

            <p class="reg-footer-note">

                <a href="/login.php">
                    &larr; Back to Customer Sign In
                </a>

            </p>

        </section>

    </main>


    <!-- ======================================
         SHARED FOOTER
    ======================================= -->

    <?php
    require_once __DIR__ . '/../src/inc/footer.php';
    ?>

</body>

</html>