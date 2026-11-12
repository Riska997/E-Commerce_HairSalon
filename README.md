# Eve & Steve Salon

**Good Hair Style, Good Selfie**

Eve & Steve Salon is a responsive unisex salon website where visitors can explore hair and beauty services, create a customer account, request an appointment, and follow the status of their booking. It also includes a separate workspace for authorized salon staff to manage appointments and enquiries.

**[Visit the website](https://eveandstevesalon.cu.ma/)** · **[View the source code](https://github.com/Riska997/E-Commerce_HairSalon)**

> **Portfolio demonstration:** This website is a software engineering project, not an official salon booking or payment service. Please use fictional/test details when exploring it. Do not enter sensitive personal or payment information or rely on a submitted appointment as a real salon reservation.

## What can I do on the website?

| Feature | What it allows you to do |
| --- | --- |
| **Explore the salon** | Browse the homepage, featured services, About Us and contact information. |
| **View all services** | Explore 33 services across seven categories, with descriptions and indicative prices or price ranges. |
| **Create an account** | Register to request appointments and check their status. |
| **Sign in** | Return to your customer account with your email address and password. |
| **Request an appointment** | Select a service and preferred date and time. |
| **My Appointments** | View booking references, appointment details and status updates. |
| **Contact Us** | Send an enquiry through the website's contact form. |
| **Password recovery** | Request a password-reset link where email recovery is enabled and functioning. |

## Customer guide

### 1. Browse services without registering

1. Open the [salon homepage](https://eveandstevesalon.cu.ma/).
2. Select **Services** to see featured options, or **All Services** for the full catalogue.
3. Open any service category to view the available treatments, descriptions and prices.

You do **not** need an account just to browse services. Displayed prices are indicative; the application does not process payments.

### 2. Create a customer account

1. Select **Register**.
2. Enter your name, email address, phone number and a password.
3. Confirm the password and select **Create Account**.
4. After registration, use **Sign In** to access your account.

The password fields include a **Show/Hide** option so you can check what you typed.

**For this portfolio demo, use a test email/account and avoid submitting personal information you would not want stored.**

### 3. Request an appointment

1. Select **All Services** or **Book Now**.
2. Sign in if you have not already done so.
3. Choose your salon service.
4. Select your **preferred date** and **preferred time**.
5. Select **Submit Appointment Request**.
6. Note the appointment reference displayed on the confirmation page.

A new request starts as **Pending**. Submitting a request does **not** automatically confirm a booking.

### 4. Check an appointment's status

1. Sign in using your customer account.
2. Select **My Appointments**.
3. Find the appointment using its reference number, date and service.
4. Read its current status.

| Status | Meaning |
| --- | --- |
| **Pending** | The request has been submitted and is awaiting review. |
| **Confirmed** | An administrator has approved the request in the demonstration system. |
| **Cancelled** | The request has been declined or cancelled. |
| **Completed** | An administrator has marked the appointment as completed. |

Changes made by an administrator are reflected in the customer's appointment history. The application does not currently send automatic emails for appointment approvals.

### 5. Send an enquiry

1. Go to the **Contact Us** section on the homepage.
2. Enter the requested contact details and your message.
3. Select **Send Message**.

The message is saved for authorized salon staff to review. This form does not guarantee an immediate email reply.

### 6. Reset a forgotten password

If **Forgot your password?** is available on the sign-in page:

1. Select the link and enter the email address registered to your customer account.
2. Check your inbox for the password-reset email.
3. Open the link and choose a new password before it expires.
4. Return to **Sign In** using your new password.

Password-reset email delivery has been tested locally. **Availability on the live hosting account is still subject to final deployment testing.**

## Staff and Manager guide

The administration area is for **authorized salon personnel only**. Staff and Managers sign in using the normal **Sign In** page with their assigned credentials; members of the public cannot create administrator accounts.

| Page | What authorized users can do | Access |
| --- | --- | --- |
| **Dashboard** | See appointment totals, pending requests, service counts, unread messages and recent appointments. | Manager and Staff |
| **Appointments** | Review requests, filter by status, and confirm, cancel or complete them when that status change is permitted. | Manager and Staff |
| **Messages** | Read contact-form enquiries, mark them as responded to, or reopen them. | Manager and Staff |
| **Services** | Add services and edit existing service details or prices. | Manager only |
| **Customers** | Search and view registered customer information. | Manager only |
| **Staff** | Create Staff accounts and reset Staff passwords. | Manager only |
| **My Account** | Update your own profile and change your password. | Manager and Staff |

**Changing an appointment:** Open **Appointments**, find the request, choose an available action and select **Save**. The customer can then see the updated status in **My Appointments**. Completed and cancelled requests may be treated as final.

**Changing an admin password:** Open **My Account**, enter your current password and the new password, then save. Password changes are available when needed; a monthly change is not required.

The admin interface has been tested locally. Deployment and verification of the new admin features on the live hosting account are still in progress.

## Frequently asked questions

**Can I view services without signing in?**  
Yes. Sign-in is only needed to request an appointment or access your appointment history.

**Does selecting Book Now confirm an appointment?**  
No. It submits a request with a **Pending** status until an authorized administrator reviews it.

**Can I make a payment or buy hair products on the site?**  
No. Online checkout and payment processing are not implemented in this version.

**Why can't I access the administration pages?**  
They are restricted to authorized Manager and Staff accounts. A customer account does not grant administration access.

**Why is a password-reset email not arriving?**  
Check your spam folder, confirm you used your registered email, and note that live email delivery is still being verified.

## About this project

This portfolio project demonstrates a complete customer booking workflow and a protected administrative interface using **PHP, MySQL/MariaDB, HTML, CSS, JavaScript and Bootstrap**.

The source code is available on GitHub, but the interactive PHP website must run on a PHP-enabled hosting service; GitHub Pages cannot run the backend. Local setup also requires a compatible database and private configuration, which are intentionally not published in the repository.

**License:** See [LICENSE](LICENSE).
