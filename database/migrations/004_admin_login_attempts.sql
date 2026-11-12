-- Eve & Steve Salon — ADMIN LOGIN RATE LIMITING
-- Run once in the CORRECT local MySQL database (salon_db) before installing shared admin login.
-- For GoogieHost, run on the correct hosted database (kwcagurv_salon_db).
-- Does not delete or modify customer, service, appointment or admin records.
-- Existing 'admins' table must already have AdminID, Name, Email, Role, Password.
CREATE TABLE IF NOT EXISTS admin_login_attempts (
    AttemptID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    EmailHash CHAR(64) NOT NULL,
    IPHash CHAR(64) NOT NULL,
    CreatedAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_attempt_email_time (EmailHash, CreatedAt),
    INDEX idx_admin_attempt_ip_time (IPHash, CreatedAt)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
