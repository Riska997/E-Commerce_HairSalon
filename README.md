# Eve & Steve Salon — Appointment Booking and Administration

A responsive, database-driven unisex salon website built with **PHP, MySQL/MariaDB, HTML, CSS, JavaScript and Bootstrap**. This project demonstrates customer-facing booking workflows and a protected, role-based administrative workspace.

> **Portfolio demonstration:** This is an educational software project, not an official production booking service. Do not submit sensitive personal, financial or payment information. Payment processing and product checkout are not implemented.

## Live website and source

- **Live demonstration:** https://eveandstevesalon.cu.ma/
- **Source code:** https://github.com/Riska997/E-Commerce_HairSalon
- **Local development:** `http://127.0.0.1:8010/`

The live site already supports the customer-facing features. **The new admin functionality has been tested locally but must be deployed and verified on GoogieHost.** Gmail SMTP email delivery has been tested from the developer's computer; hosted password-reset email delivery must be tested separately.

## Main features

### Customer experience
- Responsive homepage with salon carousel, service highlights, About and Contact sections.
- Seven service categories and **33** database-backed salon services, with descriptions and prices or price ranges.
- Customer registration, sign-in and sign-out.
- Password visibility controls on login and registration.
- Appointment requests with preferred date, time and service; new requests begin as **Pending**.
- Customer appointment reference and booking-status history.
- Contact form with database storage.
- Password-reset flow with expiring, single-use tokens and SMTP email delivery (tested locally; verify on the deployed host before enabling publicly).

### Administration (locally tested)
- Shared `/login.php` page for customers and authorized staff; no public admin-registration route.
- Protected `/admin/` pages using server-side role and session checks.
- Dashboard metrics and recent appointments.
- Appointment review, confirmation, cancellation and completion; customers see updated statuses.
- Manager-only service management, customer directory and staff-account administration.
- Message review and status updates.
- Account settings including email/profile updates and password changes.
- CSRF protection and database-backed administrative login-attempt throttling.

**Roles:** `Manager` can access all administrative features; `Staff` has access to the limited pages authorized by the application. Neither role is granted by public registration.

## Technologies

| Layer | Technologies |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap |
| Backend | PHP 8+, PDO |
| Database | MySQL / MariaDB |
| Authentication | PHP sessions, `password_hash()`, `password_verify()` |
| Email | PHPMailer with authenticated SMTP (Gmail tested locally) |
| Configuration | `vlucas/phpdotenv` and a private `.env` |
| Tools | VS Code, XAMPP, Composer, Git, GitHub |
| Deployment | GoogieHost / DirectAdmin, PHP and MariaDB |

## Project structure

```text
E-Commerce_HairSalon/
├── config/
│   ├── db.php                  # private, ignored by Git
│   └── mail.php                # private, ignored by Git
├── database/
│   └── migrations/            # apply to the appropriate database, do not web-publish
├── public/                    # local PHP document root
│   ├── index.php
│   ├── booking.php
│   ├── register.php
│   ├── login.php
│   ├── logout.php
│   ├── forgot-password.php
│   ├── my-appointments.php
│   ├── favicon.png
│   ├── admin/                  # nine role-protected admin pages
│   ├── css/                    # public and admin styling
│   ├── js/                     # client scripts
│   └── images/                 # logo, carousel, service photos and other assets
├── src/
│   ├── admin/auth.php          # server-side access control
│   └── inc/
│       ├── admin_header.php
│       └── footer.php
├── scripts/                    # local CLI helpers; not web-deployed
├── vendor/                     # Composer-installed libraries; ignored by Git
├── .env                        # private; ignored by Git
├── .env.example                # placeholders only
├── .gitignore
├── composer.json
└── composer.lock
```

**Do not deploy the repository-root `.htaccess` file.** It was written for an older `public/` rewrite layout and has rules incompatible with the current GoogieHost site. For deployment, use the instructions in `GOOGIEHOST_UPLOAD_GUIDE.md`.

## Local setup

1. Install PHP 8+, MySQL/MariaDB (such as XAMPP), Composer and Git.
2. Create the local `salon_db` database and apply the appropriate schema/migrations. Do **not** run the old `config/SalonDBConnection.session.sql` against an existing populated database: it describes an outdated schema.
3. Run `composer install` in the repository root to create `vendor/`.
4. Copy `.env.example` to `.env` and set your **own local** database and email configuration. Never commit `.env` or passwords.
5. Ensure `config/db.php` exists and implements the project’s `db_connect()` and session helpers. It is deliberately not in the public repository because it contains environment-specific configuration.
6. Start the local PHP server from the repository root:

   ```powershell
   php -S 127.0.0.1:8010 -t public
   ```

7. Open `http://127.0.0.1:8010/`.

### Database requirements

The application uses the tables `customers`, `services`, `appointments`, `appointment_service`, `messages`, `admins`, `customer_password_resets` and `admin_login_attempts`, with `payments` retained in the existing schema. Before installing admin authentication on a populated database, apply **`database/migrations/004_admin_login_attempts.sql`** if its table is missing. Do not recreate or overwrite populated customer tables.

To create the first Manager account locally, use the CLI helper **if installed**:

```powershell
php .\scripts\create-manager-sql.php
```

Paste its generated SQL into the *correct* local or hosted phpMyAdmin database. Do not commit or share the temporary password or generated SQL output. The helper is not part of the public Git repository release and should not be uploaded to the website.

## Deployment

The live server is configured with `public_html/` as its document root:

- **Inside `public_html`:** the **contents** of local `public/` (not the `public` folder itself).
- **Outside `public_html`:** private `.env`, `config/`, `src/` and Composer `vendor/`.
- **Database:** created and managed through DirectAdmin/phpMyAdmin; migrations run there, not from a public URL.

Keep the hosted `config/db.php`, `.env` and existing working `vendor/` intact during feature deployment. Back up website files and export the hosted database before changes. See the separate upload guide for every required path, upload sequence and verification step.

## Security and demo boundaries

- Do not publish `.env`, passwords, password-reset links/tokens, SQL database exports, backups, CLI helpers or SMTP test scripts.
- All admin endpoints must verify the authenticated role **on the server**, even when the shared login entry point is not advertised.
- Use HTTPS and secure session cookie settings for production hosting.
- Password reset is for customer accounts; administrative password changes occur through authorized account-management functions.
- Do not run the old repository-root `.htaccess` on the current host.
- Avoid using personal information on the public demo; use test data where possible.
- This project does not send automated appointment confirmation emails, collect online payments or implement product checkout.

## Current verification status

| Component | Status |
|---|---|
| Customer homepage, services, signup, login and booking | Working in local tests and demonstrated live |
| Customer booking history and status updates | Tested locally |
| Admin dashboard, status management and account login | Tested locally; hosted deployment pending |
| Gmail SMTP authentication and test message | Successful locally |
| Password-reset email delivery | Successful locally; full token/reuse testing still to be confirmed |
| Hosted SMTP and admin workflow | Pending deployment testing |

## Further development

Potential upgrades include email notifications on appointment confirmation, multifactor authentication for administrators, more granular staff permissions, accessibility enhancements, automated tests and an appointment-availability calendar.

## License

See the repository's existing `LICENSE` file.
