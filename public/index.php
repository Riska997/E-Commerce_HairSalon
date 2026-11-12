<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (($_SESSION['user_role'] ?? '') === 'customer') {
    check_session_expiry();
}

$isCustomer =
    ($_SESSION['user_role'] ?? '') === 'customer'
    && !empty($_SESSION['customer_id']);


function home_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/* ==========================================
   CONTACT FORM CSRF TOKEN
========================================== */

if (empty($_SESSION['contact_csrf_token'])) {
    $_SESSION['contact_csrf_token'] =
        bin2hex(random_bytes(32));
}

$contactErrors = [];

$contactSuccess =
    $_SESSION['contact_success'] ?? '';

unset($_SESSION['contact_success']);

$contactName = '';
$contactEmail = '';
$contactPhone = '';
$contactMessage = '';

/* ==========================================
   HANDLE CONTACT FORM
========================================== */

if (
    ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' &&
    ($_POST['form_type'] ?? '') === 'contact'
) {

    $contactName = is_string($_POST['name'] ?? null)
        ? trim($_POST['name'])
        : '';

    $contactEmail = is_string($_POST['email'] ?? null)
        ? strtolower(trim($_POST['email']))
        : '';

    $contactPhone = is_string($_POST['phone'] ?? null)
        ? trim($_POST['phone'])
        : '';

    $contactMessage = is_string($_POST['message'] ?? null)
        ? trim($_POST['message'])
        : '';

    $submittedToken = is_string(
        $_POST['csrf_token'] ?? null
    )
        ? $_POST['csrf_token']
        : '';

    if (!hash_equals(
        (string) $_SESSION['contact_csrf_token'],
        $submittedToken
    )) {

        $contactErrors[] =
            'Your form session expired. Refresh the page and try again.';
    }

    if (
        $contactName === '' ||
        strlen($contactName) > 100
    ) {

        $contactErrors[] =
            'Please enter your name (maximum 100 characters).';
    }

    if (
        !filter_var(
            $contactEmail,
            FILTER_VALIDATE_EMAIL
        ) ||
        strlen($contactEmail) > 100
    ) {

        $contactErrors[] =
            'Please enter a valid email address.';
    }

    if (
        preg_match(
            '/^0[0-9]{9}$/D',
            $contactPhone
        ) !== 1
    ) {

        $contactErrors[] =
            'Please enter a valid 10-digit South African phone number.';
    }

    if (
        $contactMessage === '' ||
        strlen($contactMessage) > 3000
    ) {

        $contactErrors[] =
            'Please enter a message (maximum 3000 characters).';
    }

    /* SAVE MESSAGE TO DATABASE */

    if ($contactErrors === []) {

        try {

            $pdo = db_connect();

            $statement = $pdo->prepare(
                'INSERT INTO messages
                    (CustomerName, Email, Phone, Message)
                 VALUES
                    (:name, :email, :phone, :message)'
            );

            $statement->execute([
                ':name' => $contactName,
                ':email' => $contactEmail,
                ':phone' => $contactPhone,
                ':message' => $contactMessage
            ]);

            $_SESSION['contact_success'] =
                'Your message has been received. Thank you for contacting us.';

            $_SESSION['contact_csrf_token'] =
                bin2hex(random_bytes(32));

            header(
                'Location: /index.php#contact',
                true,
                303
            );

            exit;
        } catch (Throwable $exception) {

            error_log(
                'Salon contact form error: ' .
                    $exception->getMessage()
            );

            $contactErrors[] =
                'We could not save your message. Please try again later.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en-ZA">

<head>

    <meta charset="UTF-8">

    <meta
        http-equiv="X-UA-Compatible"
        content="IE=edge">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Eve and Steve Salon offers haircuts, styling, hair colouring, braiding, grooming, beauty services and salon appointments.">

    <title>
        Eve &amp; Steve Salon | Hair, Beauty &amp; Grooming
    </title>

    <!-- FONTS -->

    <link
        href="https://fonts.googleapis.com/css?family=Lobster|Roboto:400,700"
        rel="stylesheet">

    <link
        href="https://fonts.googleapis.com/css?family=Great+Vibes"
        rel="stylesheet">

    <!-- ICONS -->

    <link
        rel="stylesheet"
        href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- EXISTING EXTERNAL CSS -->

    <link rel="stylesheet" href="/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="/css/magnific-popup.css">
    <link rel="stylesheet" href="/css/animate.min.css">
    <link rel="stylesheet" href="/css/draft.css">
    <link rel="stylesheet" href="/css/footer.css?v=<?= (int) filemtime(__DIR__ . '/css/footer.css') ?>">
    <link rel="stylesheet" href="/css/main.css?v=<?= (int) filemtime(__DIR__ . '/css/main.css') ?>">

    <!-- LOGO TO BROWSER TAB -->
    <link rel="icon" type="image/png" href="/favicon.png">
</head>

<body id="page-top">

    <div class="loader"></div>


    <!-- ======================================
         HEADER AND NAVIGATION
    ======================================= -->

    <!-- EVE & STEVE SALON - HOMEPAGE HEADER -->

    <div class="booking-page home-booking-shell">

        <header class="booking-header">

            <div class="booking-container booking-header-inner">

                <a href="/index.php" class="booking-brand">

                    <img
                        src="/images/logo.png"
                        alt="Eve and Steve Salon logo"
                        class="salon-header-logo">

                    <span class="site-name">
                        Eve &amp; Steve Salon
                    </span>

                </a>

                <nav
                    class="booking-nav"
                    aria-label="Main navigation">

                    <a href="/index.php">Home</a>

                    <a href="#services">Services</a>

                    <a href="#about">About</a>

                    <a href="#contact">Contact</a>

                    <a href="/booking.php">All Services</a>

                    <?php if ($isCustomer): ?>

                        <a href="/my-appointments.php">My Appointments</a>

                        <a href="/logout.php">
                            Sign Out
                        </a>

                    <?php else: ?>

                        <a href="/login.php">
                            Sign In
                        </a>

                        <a
                            class="booking-nav-highlight"
                            href="/register.php">
                            Register
                        </a>

                    <?php endif; ?>

                </nav>

            </div>

        </header>

    </div>


    <!-- ======================================
         HOMEPAGE HERO CAROUSEL
    ======================================= -->

    <header id="home">

        <div
            id="carouselExampleIndicators"
            class="carousel slide"
            data-ride="carousel">

            <!-- CAROUSEL INDICATORS -->

            <ol class="carousel-indicators">

                <li
                    data-target="#carouselExampleIndicators"
                    data-slide-to="0"
                    class="active"></li>

                <li
                    data-target="#carouselExampleIndicators"
                    data-slide-to="1"></li>

            </ol>


            <div class="carousel-inner" role="listbox">

                <!-- FIRST SLIDE -->

                <div class="carousel-item active">

                    <div class="home-content-box">

                        <div class="home-content-box-inner text-center">

                            <div class="home-heading animated fadeInDown">

                                <h3>
                                    Welcome to Eve &amp; Steve Salon
                                </h3>

                                <h3>
                                    Relax, Refresh, Renew
                                </h3>

                            </div>

                            <div
                                class="home-btn wow fadeInUp"
                                data-wow-delay="0.3s">

                                <a
                                    class="btn btn-lg btn-general btn-greenish"
                                    href="/booking.php"
                                    role="button">
                                    Book Now
                                </a>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- SECOND SLIDE -->

                <div class="carousel-item second-slide">

                    <div class="home-content-box">

                        <div class="home-content-box-inner text-center">

                            <div class="home-heading animated fadeInDown">

                                <h3>
                                    Experience the Best in Beauty
                                </h3>

                                <h3>
                                    Quality Service Awaits You
                                </h3>

                            </div>

                            <div
                                class="home-btn wow fadeInUp"
                                data-wow-delay="0.3s">

                                <a
                                    class="btn btn-lg btn-general btn-greenish"
                                    href="/booking.php"
                                    role="button"
                                    title="Book Your Appointment">
                                    Book Now
                                </a>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </header>


    <!-- ======================================
         FEATURED SALON SERVICES
    ======================================= -->

    <section id="services" class="services">

        <div class="container">

            <div class="row mb-5">

                <div class="col-md-12 text-center mb-5">

                    <div
                        class="heading wow fadeInUp"
                        data-wow-delay="0.3s">

                        <h1>Services</h1>

                        <div class="bord-bot"></div>

                    </div>

                </div>

            </div>


            <div class="row text-center">

                <!-- HAIRCUTS AND STYLING -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.3s">

                        <img
                            src="/images/service-categories/haircuts-styling.webp"
                            alt="Haircuts and styling"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Haircuts and Styling
                            </h2>

                            <p>
                                Haircuts and styling for men,
                                women, and children.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- HAIR COLOURING -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.6s">

                        <img
                            src="/images/service-categories/hair-colouring.webp"
                            alt="Hair colouring"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Hair Coloring
                            </h2>

                            <p>
                                Highlights, lowlights,
                                and color correction.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- HAIR TREATMENTS -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.9s">

                        <img
                            src="/images/service-categories/hair-treatments-relaxers.webp"
                            alt="Hair treatments"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Hair Treatments
                            </h2>

                            <p>
                                Deep conditioning
                                and scalp treatments.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- HAIR EXTENSIONS -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.9s">

                        <img
                            src="/images/service-categories/hair-extensions-wigs.webp"
                            alt="Hair extensions"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Hair Extensions
                            </h2>

                            <p>
                                High-quality,
                                non-chemically processed
                                hair extensions.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- BRAIDING AND WEAVING -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.9s">

                        <img
                            src="/images/service-categories/braiding-weaving.webp"
                            alt="Braiding and weaving"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Braiding and Weaving
                            </h2>

                            <p>
                                Various braiding styles
                                and weaving techniques.
                            </p>

                        </div>

                    </div>

                </div>


                <!-- MANICURES AND PEDICURES -->

                <div class="col-md-4 col-sm-6">

                    <div
                        class="service-cont wow fadeInUp"
                        data-wow-delay="0.9s">

                        <img
                            src="/images/service-categories/manicures-pedicures.webp"
                            alt="Manicures and pedicures"
                            class="img-fluid">

                        <div class="service-desc">

                            <h2>
                                Manicures and Pedicures
                            </h2>

                            <p>
                                Nail services including
                                gel and acrylic nails.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- ======================================
         ABOUT THE SALON
    ======================================= -->

    <section id="about" class="about">

        <div class="container">

            <div class="row mb-5">

                <div class="col-md-12 text-center">

                    <div
                        class="heading wow fadeInUp"
                        data-wow-delay="0.3s">

                        <h1>About Us</h1>

                        <div class="bord-bot"></div>

                    </div>

                </div>

            </div>


            <div class="row">

                <div class="col-md-4 my-auto">

                    <div
                        class="body-cont mb-5 wow fadeInUp"
                        data-wow-delay="0.6s">

                        <h3>
                            Good Hair Style, Good Selfie
                        </h3>

                        <div class="bord-bottom"></div>

                        <p>
                            Salon Eve &amp; Steve was established in
                            1997 at Westgate Mall, Mitchell's Plain,
                            from very humble beginnings.

                            We are a hair salon with a modern style
                            and a hip, funky and upbeat atmosphere.

                            We treat our clients with respect,
                            ensuring quality service and individual
                            attention.
                        </p>

                        <a
                            href="#contact"
                            class="btn btn-general btn-white js-scroll-trigger">
                            ENQUIRIES
                        </a>

                    </div>

                </div>


                <div class="col-md-8 m-auto text-center">

                    <div
                        class="body-img-1 wow fadeInUp"
                        data-wow-delay="0.9s">

                        <img
                            src="\images\equipment.png"
                            alt="Salon equipment and interior"
                            class="img-fluid">

                    </div>

                </div>

            </div>

        </div>

    </section>


    <!-- ======================================
         TESTIMONIALS SECTION COMMENTED OUT
         NOT DELETED
    ======================================= -->

    <!--

    <section id="testimonials" class="testimonials">

        <div class="container">

            <div class="row">

                <div class="col-md-12">

                    <div
                        id="testimonial-slider"
                        class="owl-carousel owl-theme"
                    >

                        <div class="testimonial">

                            <div class="pic">
                                <img src="/images/Thanda.jpg" alt="Customer">
                            </div>

                            <p class="description">
                                I have been a loyal customer at
                                Eve & Steve Hair Salon for over
                                five years, and I cannot recommend
                                them enough!

                                The staff is incredibly professional
                                and friendly, and they always go
                                the extra mile to ensure I leave
                                feeling fabulous.
                            </p>

                            <h3 class="title">
                                Thandiwe M.
                            </h3>

                        </div>


                        <div class="testimonial">

                            <div class="pic">
                                <img src="/images/Daniels.jpg" alt="Customer">
                            </div>

                            <p class="description">
                                Eve & Steve Hair Salon is hands
                                down the best salon in
                                Mitchells Plain!

                                I went in for a balayage treatment,
                                and the results were stunning.

                                The stylist was attentive and
                                listened carefully to what I wanted.
                            </p>

                            <h3 class="title">
                                Daniel Matthews
                            </h3>

                        </div>


                        <div class="testimonial">

                            <div class="pic">
                                <img src="/images/Kofis.jpg" alt="Customer">
                            </div>

                            <p class="description">
                                I recently visited Eve & Steve
                                Hair Salon, and while the service
                                was good, I felt the wait time
                                was a bit long.

                                Overall, it was a decent experience,
                                but I think there is room
                                for improvement.
                            </p>

                            <h3 class="title">
                                Kofi Mensah
                            </h3>

                        </div>


                        <div class="testimonial">

                            <div class="pic">
                                <img src="/images/Lebo.jpg" alt="Customer">
                            </div>

                            <p class="description">
                                I went to Eve & Steve Hair Salon
                                for a haircut, and it was okay.

                                The salon was clean, and the
                                staff was polite.

                                It is a good place if you
                                are looking for a reliable salon.
                            </p>

                            <h3 class="title">
                                Lebo Dlamini
                            </h3>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </section>

    -->


    <!-- ======================================
         CONTACT US
    ======================================= -->

    <section id="contact" class="contact mb-5">

        <div class="container">

            <div class="row mb-5">

                <div class="col-md-12 text-center">

                    <div class="heading">

                        <h1>
                            Contact Us
                        </h1>

                        <div class="bord-bot"></div>

                        <p class="desc">
                            We would love to hear from you!
                        </p>

                    </div>

                </div>

            </div>


            <!-- CONTACT FORM FEEDBACK -->

            <?php if ($contactSuccess !== ''): ?>

                <div
                    class="alert alert-success"
                    role="status">
                    <?= home_escape($contactSuccess) ?>
                </div>

            <?php endif; ?>


            <?php if ($contactErrors !== []): ?>

                <div
                    class="alert alert-danger"
                    role="alert">

                    <ul>

                        <?php foreach ($contactErrors as $error): ?>

                            <li>
                                <?= home_escape($error) ?>
                            </li>

                        <?php endforeach; ?>

                    </ul>

                </div>

            <?php endif; ?>


            <!-- CONTACT FORM -->

            <div class="row">

                <div class="col-lg-12">

                    <form
                        id="contactForm"
                        method="post"
                        action="/index.php#contact">

                        <!-- FORM TYPE -->

                        <input
                            type="hidden"
                            name="form_type"
                            value="contact">

                        <!-- CSRF TOKEN -->

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= home_escape($_SESSION['contact_csrf_token']) ?>">


                        <div class="row">

                            <div class="col-md-6">

                                <!-- NAME -->

                                <div class="form-group">

                                    <label
                                        class="sr-only"
                                        for="name">
                                        Your Name
                                    </label>

                                    <input
                                        class="form-control"
                                        id="name"
                                        name="name"
                                        type="text"
                                        placeholder="Your Name *"
                                        maxlength="100"
                                        autocomplete="name"
                                        value="<?= home_escape($contactName) ?>"
                                        required>

                                </div>


                                <!-- EMAIL -->

                                <div class="form-group">

                                    <label
                                        class="sr-only"
                                        for="email">
                                        Your Email
                                    </label>

                                    <input
                                        class="form-control"
                                        id="email"
                                        name="email"
                                        type="email"
                                        placeholder="Your Email *"
                                        maxlength="100"
                                        autocomplete="email"
                                        value="<?= home_escape($contactEmail) ?>"
                                        required>

                                </div>


                                <!-- PHONE -->

                                <div class="form-group">

                                    <label
                                        class="sr-only"
                                        for="phone">
                                        Your Phone
                                    </label>

                                    <input
                                        class="form-control"
                                        id="phone"
                                        name="phone"
                                        type="tel"
                                        placeholder="Your Phone *"
                                        pattern="0[0-9]{9}"
                                        maxlength="10"
                                        autocomplete="tel"
                                        inputmode="tel"
                                        value="<?= home_escape($contactPhone) ?>"
                                        required>

                                </div>

                            </div>


                            <div class="col-md-6">

                                <!-- MESSAGE -->

                                <div class="form-group">

                                    <label
                                        class="sr-only"
                                        for="message">
                                        Your Message
                                    </label>

                                    <textarea
                                        class="form-control"
                                        id="message"
                                        name="message"
                                        maxlength="3000"
                                        placeholder="Your Message *"
                                        required><?= home_escape($contactMessage) ?></textarea>

                                </div>

                            </div>


                            <!-- SEND MESSAGE -->

                            <div class="col-lg-12 text-center">

                                <button
                                    id="sendMessageButton"
                                    class="btn btn-general btn-greenish btn-xl text-uppercase"
                                    type="submit">
                                    Send Message
                                </button>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </section>


    <!-- ======================================
         BOOKING CALL TO ACTION
    ======================================= -->

    <section id="booking" class="booking">

        <div class="book-container">

            <h2>
                Book Your Style
            </h2>

            <p>
                Select your preferred date and services
                to experience a premium salon treatment.
            </p>

            <p>
                Book now to secure your spot!
            </p>

            <a
                href="/booking.php"
                class="btn btn-general btn-white">
                Get Started
            </a>

        </div>

    </section>


    <!-- ======================================
         SHARED FOOTER
    ======================================= -->

    <?php
    require __DIR__ . '/../src/inc/footer.php';
    ?>


    <!-- BACK TO TOP -->

    <a
        href="#page-top"
        id="back-to-top"
        class="btn btn-sm btn-green btn-back-to-top js-scroll-trigger"
        title="Back to top"
        aria-label="Back to top">

        <i class="fa fa-angle-double-up"></i>

    </a>


    <!-- ======================================
         JAVASCRIPT
    ======================================= -->

    <script src="/js/jquery.min.js"></script>
    <script src="/js/popper.min.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/jquery.easing.min.js"></script>
    <script src="/js/jquery.magnific-popup.min.js"></script>
    <script src="/js/owl.carousel.min.js"></script>
    <script src="/js/wow.min.js"></script>

    <script>
        // Initialise homepage effects.
        // Avoid the old script.js admin-only selectors.

        window.addEventListener('load', function() {

            const loader = document.querySelector('.loader');

            if (loader) {
                loader.style.display = 'none';
            }

        });

        if (typeof WOW !== 'undefined') {
            new WOW().init();
        }

        // Back-to-top functionality.

        const backToTop = document.getElementById('back-to-top');

        window.addEventListener('scroll', function() {

            if (backToTop) {

                backToTop.style.display =
                    window.scrollY > 200 ?
                    'inline-flex' :
                    'none';

            }

        });

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }
    </script>

</body>

</html>