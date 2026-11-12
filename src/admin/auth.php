<?php

declare(strict_types=1);

/** Eve & Steve Salon administrative access layer.
 * All callers must load config/db.php before this file.
 */

function salon_admin_h(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function salon_admin_csrf(): string
{
    if (empty($_SESSION['salon_admin_csrf']) || !is_string($_SESSION['salon_admin_csrf'])) {
        $_SESSION['salon_admin_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['salon_admin_csrf'];
}

function salon_admin_verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals(salon_admin_csrf(), $token)) {
        http_response_code(403);
        exit('Security check failed. Refresh the page and try again.');
    }
}

function salon_admin_flash(string $message, string $type = 'success'): void
{
    $_SESSION['salon_admin_flash'] = [
        'message' => $message,
        'type' => in_array($type, ['success', 'error'], true) ? $type : 'error'
    ];
}

function salon_admin_take_flash(): ?array
{
    $flash = $_SESSION['salon_admin_flash'] ?? null;
    unset($_SESSION['salon_admin_flash']);
    return is_array($flash) ? $flash : null;
}

function salon_admin_password_valid(string $password): bool
{
    // Bcrypt's effective limit is 72 bytes; do not silently truncate passwords.
    return strlen($password) >= 12 && strlen($password) <= 72;
}

function salon_admin_email_hash(string $email): string
{
    return hash('sha256', strtolower(trim($email)));
}

function salon_admin_ip_hash(): string
{
    // Use REMOTE_ADDR only; untrusted proxy headers are deliberately ignored.
    return hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

/** Called only after the normal customer check has failed.
 * This always gives the browser the same generic error for invalid credentials.
 * Requires database/migrations/004_admin_login_attempts.sql to be applied.
 */
function salon_admin_attempt_login(string $email, string $password): bool
{
    $pdo = db_connect();
    $emailHash = salon_admin_email_hash($email);
    $ipHash = salon_admin_ip_hash();

    $limit = $pdo->prepare(
        'SELECT COUNT(*) FROM admin_login_attempts
         WHERE CreatedAt >= (NOW() - INTERVAL 15 MINUTE)
           AND (EmailHash = :eh OR IPHash = :ih)'
    );
    $limit->execute([':eh' => $emailHash, ':ih' => $ipHash]);
    if ((int)$limit->fetchColumn() >= 10) {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT AdminID, Name, Email, Role, Password
         FROM admins WHERE Email = :email LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $verified = $admin
        && is_string($admin['Password'] ?? null)
        && password_verify($password, $admin['Password'])
        && in_array($admin['Role'] ?? null, ['Manager', 'Staff'], true);

    if (!$verified) {
        $record = $pdo->prepare(
            'INSERT INTO admin_login_attempts (EmailHash, IPHash)
             VALUES (:eh, :ih)'
        );
        $record->execute([':eh' => $emailHash, ':ih' => $ipHash]);
        return false;
    }

    $clear = $pdo->prepare(
        'DELETE FROM admin_login_attempts WHERE EmailHash = :eh AND IPHash = :ih'
    );
    $clear->execute([':eh' => $emailHash, ':ih' => $ipHash]);

    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['user_role'] = 'admin';
    $_SESSION['admin_id'] = (int)$admin['AdminID'];
    $_SESSION['admin_password_fingerprint'] = hash('sha256', (string)$admin['Password']);
    $_SESSION['user_email'] = (string)$admin['Email'];
    $_SESSION['last_activity'] = time();
    $_SESSION['admin_last_regeneration'] = time();

    header('Location: /admin/dashboard.php', true, 303);
    exit;
}

/** Validates the role from the database on EVERY admin page request. */
function salon_admin_require(?string $requiredRole = null): array
{
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    $id = filter_var(
        $_SESSION['admin_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (($_SESSION['user_role'] ?? null) !== 'admin' || $id === false || $id === null) {
        header('Location: /login.php', true, 303);
        exit;
    }

    $now = time();
    $last = (int)($_SESSION['last_activity'] ?? 0);
    if ($last <= 0 || ($now - $last) > 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /login.php?expired=1', true, 303);
        exit;
    }

    $statement = db_connect()->prepare(
        'SELECT AdminID, Name, Email, Role, Password FROM admins WHERE AdminID = :id LIMIT 1'
    );
    $statement->execute([':id' => $id]);
    $admin = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !in_array($admin['Role'], ['Manager', 'Staff'], true)
        || !is_string($admin['Password']) || $admin['Password'] === ''
        || !hash_equals((string)($_SESSION['admin_password_fingerprint'] ?? ''), hash('sha256', (string)$admin['Password']))) {
        $_SESSION = [];
        session_regenerate_id(true);
        header('Location: /login.php', true, 303);
        exit;
    }

    if ($requiredRole !== null && $admin['Role'] !== $requiredRole) {
        http_response_code(403);
        exit('Access denied. Manager permission is required.');
    }

    $_SESSION['last_activity'] = $now;
    if ($now - (int)($_SESSION['admin_last_regeneration'] ?? 0) >= 900) {
        session_regenerate_id(true);
        $_SESSION['admin_last_regeneration'] = $now;
    }
    return $admin;
}

function salon_admin_redirect(string $page): never
{
    // All redirect destinations are internal, fixed admin paths.
    header('Location: /admin/' . $page, true, 303);
    exit;
}
