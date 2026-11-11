<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

header('Cache-Control: no-store, private');

function salon_login_escape($value): string
{
   return htmlspecialchars(
      (string) ($value ?? ''),
      ENT_QUOTES | ENT_SUBSTITUTE,
      'UTF-8'
   );
}

/*
=========================================
PRESERVE BOOKING DESTINATION
=========================================
*/

$requestedNext = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
   ? ($_POST['next'] ?? '')
   : ($_GET['next'] ?? '');

$allowedDestinations = ['booking.php', 'my-appointments.php'];
$next = is_string($requestedNext) && in_array($requestedNext, $allowedDestinations, true)
   ? $requestedNext
   : '';

$redirectTo = $next !== '' ? '/' . $next : '/index.php';

$registrationLink = '/register.php' .
   ($next !== '' ? '?next=' . rawurlencode($next) : '');


/*
=========================================
ALREADY SIGNED IN
=========================================
*/

if (($_SESSION['user_role'] ?? '') === 'customer') {

   check_session_expiry();

   $activeId = filter_var(
      $_SESSION['customer_id'] ?? null,
      FILTER_VALIDATE_INT,
      ['options' => ['min_range' => 1]]
   );

   if ($activeId !== false && $activeId !== null) {

      header('Location: ' . $redirectTo);
      exit;
   }
}


/*
=========================================
CSRF PROTECTION
=========================================
*/

if (
   empty($_SESSION['login_csrf_token']) ||
   !is_string($_SESSION['login_csrf_token'])
) {

   $_SESSION['login_csrf_token'] =
      bin2hex(random_bytes(32));
}

$error = '';
$email = '';


/*
=========================================
HANDLE LOGIN
=========================================
*/

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

   $email = is_string($_POST['email'] ?? null)
      ? strtolower(trim($_POST['email']))
      : '';

   $password = is_string($_POST['password'] ?? null)
      ? $_POST['password']
      : '';

   $submittedToken = is_string(
      $_POST['csrf_token'] ?? null
   )
      ? $_POST['csrf_token']
      : '';


   /*
     * Validate the form.
     */

   if (!hash_equals(
      $_SESSION['login_csrf_token'],
      $submittedToken
   )) {

      $error =
         'Your session expired. Please refresh and try again.';
   } elseif (
      !filter_var($email, FILTER_VALIDATE_EMAIL) ||
      strlen($email) > 100 ||
      $password === ''
   ) {

      $error =
         'Please enter a valid email address and password.';
   } else {

      /*
         * Authenticate the customer.
         */

      try {

         $statement = db_connect()->prepare(
            'SELECT
                    CustomerID,
                    Email,
                    Password
                 FROM customers
                 WHERE Email = :email
                 LIMIT 1'
         );

         $statement->execute([
            ':email' => $email
         ]);

         $customer = $statement->fetch(
            PDO::FETCH_ASSOC
         );


         if (
            !$customer ||
            !password_verify(
               $password,
               (string) $customer['Password']
            )
         ) {

            $error =
               'Incorrect email address or password.';
         } else {

            /*
                 * Start a fresh customer session.
                 */

            $_SESSION = [];

            session_regenerate_id(true);

            $_SESSION['customer_id'] =
               (int) $customer['CustomerID'];

            $_SESSION['user_id'] =
               (int) $customer['CustomerID'];

            $_SESSION['user_email'] =
               (string) $customer['Email'];

            $_SESSION['user_role'] = 'customer';

            $_SESSION['last_activity'] = time();


            /*
                 * Return to booking if the customer
                 * originally clicked Book Now.
                 */

            header(
               'Location: ' . $redirectTo,
               true,
               303
            );

            exit;
         }
      } catch (Throwable $exception) {

         error_log(
            'Customer login error: ' .
               $exception->getMessage()
         );

         $error =
            'Sign-in is temporarily unavailable. Please try again.';
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
      content="Sign in to your Eve and Steve Salon customer account.">

   <title>
      Customer Sign In | Eve &amp; Steve Salon
   </title>

   <!-- EXTERNAL STYLESHEETS -->

   <link rel="stylesheet" href="/css/draft.css">
   <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
   <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">

   <!-- LOGO TO BROWSER TAB -->
   <link rel="icon" type="image/png" href="/favicon.png">
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


         <!-- NAVIGATION -->

         <nav
            class="reg-nav"
            aria-label="Main navigation">

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

            <a
               href="<?= salon_login_escape($registrationLink) ?>">
               Register
            </a>

            <a
               class="reg-nav-button"
               href="/booking.php">
               Browse as Guest
            </a>

         </nav>

      </div>

   </header>


   <!-- ======================================
         WELCOME BANNER
    ======================================= -->

   <section
      class="reg-banner"
      aria-labelledby="welcome-title">

      <h1 id="welcome-title">
         Welcome Back
      </h1>

      <p>
         Sign in to Eve &amp; Steve Salon
      </p>

   </section>


   <!-- ======================================
         MAIN CONTENT
    ======================================= -->

   <main class="reg-wrap reg-main">

      <div class="reg-grid">

         <!-- INFORMATION PANEL -->

         <aside class="reg-side">

            <h2>
               Your Salon, Your Schedule
            </h2>

            <p>
               Sign in to request your next
               salon appointment.
            </p>

            <ul>

               <li>
                  Choose from all 33 salon services
               </li>

               <li>
                  Request your preferred date and time
               </li>

               <li>
                  Receive an appointment reference
               </li>

               <li>
                  Await confirmation from the salon
               </li>

            </ul>

            <p>
               Just exploring? Our full service
               catalogue is open to everyone.
            </p>

         </aside>


         <!-- CUSTOMER LOGIN FORM -->

         <section
            class="reg-card"
            aria-labelledby="signin-heading">

            <h2 id="signin-heading">
               Customer Sign In
            </h2>

            <p class="reg-muted">
               Enter the email address and password
               you used when registering.
            </p>


            <!-- ERROR MESSAGE -->

            <?php if ($error !== ''): ?>

               <div
                  class="reg-alert error"
                  role="alert">

                  <?= salon_login_escape($error) ?>

               </div>

            <?php endif; ?>


            <!-- LOGIN FORM -->

            <form
               class="reg-form"
               method="post"
               action="/login.php">

               <!-- CSRF TOKEN -->

               <input
                  type="hidden"
                  name="csrf_token"
                  value="<?= salon_login_escape($_SESSION['login_csrf_token']) ?>">

               <!-- RETURN DESTINATION -->

               <input
                  type="hidden"
                  name="next"
                  value="<?= salon_login_escape($next) ?>">


               <!-- EMAIL -->

               <div class="reg-field">

                  <label for="email">
                     Email Address *
                  </label>

                  <input
                     type="email"
                     id="email"
                     name="email"
                     maxlength="100"
                     autocomplete="email"
                     value="<?= salon_login_escape($email) ?>"
                     required>

               </div>


               <!-- PASSWORD -->

               <div class="reg-field">

                  <label for="password">
                     Password *
                  </label>

                  <input
                     type="password"
                     id="password"
                     name="password"
                     autocomplete="current-password"
                     required>

               </div>


               <!-- Password recovery link intentionally hidden until SMTP setup is tested. -->



               <!-- SIGN IN -->

               <button
                  class="reg-button"
                  type="submit">
                  Sign In
               </button>

            </form>


            <!-- ACCOUNT OPTIONS -->

            <div class="reg-actions">

               <a
                  class="reg-button alt"
                  href="<?= salon_login_escape($registrationLink) ?>">
                  Create an Account
               </a>

               <a
                  class="reg-button alt"
                  href="/booking.php">
                  Browse All Services
               </a>

            </div>


            <!-- HOMEPAGE -->

            <p class="reg-footer-note">

               <a href="/index.php">
                  &larr; Back to Homepage
               </a>

            </p>

         </section>

      </div>

   </main>


   <!-- ======================================
         SHARED FOOTER
    ======================================= -->

   <?php require_once __DIR__ . '/../src/inc/footer.php'; ?>

</body>

</html>