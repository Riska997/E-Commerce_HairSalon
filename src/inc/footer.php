<?php
/* Shared customer footer: keep outside public/ because pages require it from src/inc/. */
?>
<footer class="site-footer" id="footer">
    <div class="footer-container">
        <div class="footer-left">
            <h2 class="footer-title">Eve &amp; Steve Salon</h2>
            <p class="footer-tagline">Good Hair Style, Good Selfie</p>
            <address>
                Westgate Mall, Mitchell's Plain, 7785<br>
                <a href="tel:+27213765645">021 376 5645</a>
            </address>
            <a class="location-link" href="https://www.bing.com/maps?osid=7e46068f-78b9-4d12-8042-8d307bc0110e&amp;cp=-34.040741~18.607997&amp;lvl=15.24&amp;pi=0&amp;imgid=95b2b68f-59db-4909-92ed-cbb4d9584362&amp;v=2&amp;sV=2&amp;form=S00027" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px;">
                <svg xmlns="http://w3.org" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
                <span>View location on map</span>
            </a>
            <div class="footer-social">
                <a href="https://www.facebook.com/pages/Salon%20Eve%20%26%20Steve%20-%20Westgate%20Mall/323360621043360/" target="_blank" rel="noopener noreferrer" aria-label="Visit Eve and Steve Salon on Facebook" style="display: inline-flex; align-items: center;">
                    <svg xmlns="http://w3.org" viewBox="0 0 24 24" width="24" height="24" fill="#1877F2" aria-hidden="true">
                        <path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/>
                    </svg>
                </a>
            </div>
        </div>
        <nav class="footer-right" aria-label="Footer navigation">
            <h3>Explore</h3>
            <ul class="footer-navbar">
                <li><a href="/index.php">Home</a></li>
                <li><a href="/index.php#services">Featured Services</a></li>
                <li><a href="/booking.php#services">All Services</a></li>
                <li><a href="/index.php#about">About Us</a></li>
                <li><a href="/index.php#contact">Contact</a></li>
                <li><a href="/my-appointments.php">My Appointments</a></li>
                <?php if (($_SESSION['user_role'] ?? '') === 'customer' && !empty($_SESSION['customer_id'])): ?>
                    <li><a href="/logout.php">Sign Out</a></li>
                <?php else: ?>
                    <li><a href="/login.php">Sign In</a></li>
                    <li><a href="/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <div class="footer-bottom">&copy; <?= date('Y') ?> Eve &amp; Steve Salon. All rights reserved.</div>
</footer>
