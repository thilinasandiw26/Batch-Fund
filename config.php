<?php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'batchfund');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'BatchFund');
define('BATCH_NAME', 'Batch Fund Management System');
define('CURRENCY', 'Rs.');
define('EXPENSE_APPROVAL_REQUIRED', true);
define('MAX_UPLOAD_BYTES', 3 * 1024 * 1024);
define('EVENT_IMAGE_LIMIT', 6);              // images per special event
define('EVENT_IMAGE_SIZE', 800);             // event images are cropped to a square and shrunk to at most this many px
define('EVENT_IMAGE_RAW_MAX', 15 * 1024 * 1024); // largest ORIGINAL a coordinator may pick (it is resized in the browser before upload)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('SESSION_IDLE_SECONDS', 7200);
define('PAYMENT_INSTRUCTIONS',
    "Pay by bank transfer to: Batch Fund Account, \n\n 122352572458 \nBOC \nMoratuwa Branch.\n\n" .
    "Use your registration number as the transfer remark, then upload the slip/receipt in BatchFund.");

define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('MAIL_FROM', 'noreply@batchfund.local');
define('MAIL_FROM_NAME', 'BatchFund Committee');

date_default_timezone_set('Asia/Colombo');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

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

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
    'mysql:host=' . DB_HOST .
    ';port=' . DB_PORT .
    ';dbname=' . DB_NAME .
    ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);
        } catch (PDOException $e) {
            error_log('BatchFund DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection failed. Check config.php and that MySQL is running.');
        }
        ensure_schema($pdo);
    }
    return $pdo;
}

/**
 * Adds the "who changed it" columns to an older database automatically (once per login session),
 * so the app keeps working even if migration_multi_coordinator.sql was not imported.
 */
function ensure_schema(PDO $pdo): void
{
    if (!empty($_SESSION['schema_v3'])) {
        return;
    }
    $needed = [
        ['expenses', 'updated_by', 'ALTER TABLE expenses ADD COLUMN updated_by INT UNSIGNED NULL AFTER approved_at'],
        ['expenses', 'updated_at', 'ALTER TABLE expenses ADD COLUMN updated_at DATETIME NULL AFTER updated_by'],
        ['users', 'is_treasurer', 'ALTER TABLE users ADD COLUMN is_treasurer TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active'],
        ['collections', 'updated_by', 'ALTER TABLE collections ADD COLUMN updated_by INT UNSIGNED NULL AFTER created_by'],
        ['announcements', 'updated_by', 'ALTER TABLE announcements ADD COLUMN updated_by INT UNSIGNED NULL AFTER created_by'],
    ];
    try {
        $chk = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
        foreach ($needed as [$table, $col, $sql]) {
            $chk->execute([$table, $col]);
            if (!(int) $chk->fetchColumn()) {
                $pdo->exec($sql);
            }
        }
        $pdo->exec('CREATE TABLE IF NOT EXISTS event_images (
            image_id      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            collection_id INT UNSIGNED NOT NULL,
            stored_name   VARCHAR(80) NOT NULL,
            original_name VARCHAR(200) NOT NULL,
            mime_type     VARCHAR(80) NOT NULL,
            file_size     INT UNSIGNED NOT NULL,
            uploaded_by   INT UNSIGNED NULL,
            uploaded_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_event_images_collection (collection_id),
            CONSTRAINT fk_event_images_collection FOREIGN KEY (collection_id) REFERENCES collections(collection_id) ON DELETE CASCADE,
            CONSTRAINT fk_event_images_user FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB');
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['schema_v3'] = 1;
        }
    } catch (Throwable $e) {
        error_log('BatchFund schema check failed: ' . $e->getMessage());
    }
}
