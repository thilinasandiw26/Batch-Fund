<?php
/**
 * config.php  (Member 1 – Database connection & application integration)
 * Central configuration. Edit the values below to match your environment.
 */

// ---------- Database ----------
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'batchfund');
define('DB_USER', 'root');      // XAMPP default
define('DB_PASS', '');          // XAMPP default is an empty password

// ---------- Application ----------
define('APP_NAME', 'BatchFund');
define('BATCH_NAME', 'Batch Fund & Committee Management');
define('CURRENCY', 'Rs.');
define('EXPENSE_APPROVAL_REQUIRED', true);   // true: new expenses stay "pending" until approved
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024); // 3 MB per file
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('SESSION_IDLE_SECONDS', 7200);        // auto logout after 2 hours of inactivity
define('PAYMENT_INSTRUCTIONS',
    "Pay by bank transfer to: Batch Fund Account, Account No. 0000-0000-0000, Example Bank.\n" .
    "Use your registration number as the transfer remark, then upload the slip/receipt in BatchFund.");

// ---------- SMTP e-mail (Member 5) ----------
// Leave SMTP_HOST empty to fall back to PHP's mail() function (rarely works on XAMPP).
// Gmail example: host smtp.gmail.com, port 587, secure 'tls', user = your address, pass = an App Password.
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');   // 'tls' (STARTTLS, port 587), 'ssl' (port 465) or '' (none)
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', 'noreply@batchfund.local');
define('MAIL_FROM_NAME', 'BatchFund Committee');

date_default_timezone_set('Asia/Colombo');

// ---------- Security headers ----------
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/** Start a hardened PHP session. */
function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('batchfund_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/** Shared PDO connection (prepared statements, exceptions, no emulation). */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            error_log('BatchFund DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Check config.php and that MySQL is running.');
        }
    }
    return $pdo;
}
