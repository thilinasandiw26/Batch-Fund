<?php
/**
 * functions.php – shared helpers.
 * Sections are tagged with the owning member (see README).
 */
require_once __DIR__ . '/config.php';

/* =====================================================================
 * GENERAL HELPERS (Member 1)
 * ===================================================================== */
function h($v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function money($v): string
{
    return CURRENCY . ' ' . number_format((float) $v, 2);
}
function fmt_date($d): string
{
    return $d ? date('d M Y', strtotime($d)) : '—';
}
function fmt_dt($d): string
{
    return $d ? date('d M Y, H:i', strtotime($d)) : '—';
}
function redirect(string $url)
{
    header('Location: ' . $url);
    exit;
}
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

/* ---- database shortcuts ---- */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}
function rows(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}
function row(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r ?: null;
}
function val(string $sql, array $params = [])
{
    return q($sql, $params)->fetchColumn();
}

/* ---- input cleaning / validation ---- */
function post_str(string $k, int $max = 255): string
{
    return mb_substr(trim(preg_replace('/\s+/u', ' ', (string) ($_POST[$k] ?? ''))), 0, $max);
}
function post_text(string $k, int $max = 3000): string
{
    return mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
}
function post_int(string $k): int
{
    return (int) ($_POST[$k] ?? 0);
}
function post_amount(string $k): float
{
    $v = str_replace(',', '', (string) ($_POST[$k] ?? ''));
    return is_numeric($v) ? round((float) $v, 2) : -1;
}
function valid_date(?string $s): bool
{
    if (!$s)
        return false;
    $d = DateTime::createFromFormat('Y-m-d', $s);
    return $d && $d->format('Y-m-d') === $s;
}
function valid_email(string $s): bool
{
    return (bool) filter_var($s, FILTER_VALIDATE_EMAIL) && strlen($s) <= 150;
}
function valid_phone(string $s): bool
{
    return (bool) preg_match('/^\+?[0-9][0-9\s\-]{6,18}$/', $s);
}
function strong_password(string $p): bool
{
    return strlen($p) >= 8 && preg_match('/[A-Za-z]/', $p) && preg_match('/[0-9]/', $p);
}
function generate_password(): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $p = '';
    for ($i = 0; $i < 10; $i++) {
        $p .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $p . random_int(10, 99);
}

/* ---- CSRF protection ---- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}
function csrf_valid(): bool
{
    $t = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    return is_string($t) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/** Only allow redirects back to our own two dashboards. */
function safe_return(string $default): string
{
    $r = (string) ($_POST['return'] ?? $_GET['return'] ?? '');
    return preg_match('#^(coordinator|student)\.php(\?[A-Za-z0-9_=&%\-\.]*)?$#', $r) ? $r : $default;
}

/* =====================================================================
 * AUDIT LOG & NOTIFICATIONS (Members 4 & 5)
 * ===================================================================== */
function audit(string $action, string $entity, ?int $entityId, string $details = ''): void
{
    $uid = $_SESSION['user_id'] ?? null;
    q(
        'INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)',
        [$uid, $action, $entity, $entityId, mb_substr($details, 0, 500), $_SERVER['REMOTE_ADDR'] ?? '']
    );
}
function notify(int $userId, string $type, string $message, ?string $refKey = null): void
{
    q(
        'INSERT IGNORE INTO notifications (user_id, type, message, ref_key) VALUES (?,?,?,?)',
        [$userId, $type, mb_substr($message, 0, 255), $refKey]
    );
}
function notify_all_students(string $type, string $message): void
{
    q(
        "INSERT INTO notifications (user_id, type, message) SELECT s.user_id, ?, ? FROM students s WHERE s.status='active'",
        [$type, mb_substr($message, 0, 255)]
    );
}
function notify_coordinators(string $type, string $message): void
{
    q(
        "INSERT INTO notifications (user_id, type, message) SELECT user_id, ?, ? FROM users WHERE role='coordinator' AND is_active=1",
        [$type, mb_substr($message, 0, 255)]
    );
}
/** Creates "deadline soon" and "overdue" notices for a student (idempotent thanks to ref_key). */
function generate_deadline_notifications(array $student): void
{
    $list = student_collections((int) $student['student_id']);
    $today = date('Y-m-d');
    foreach ($list as $c) {
        if ($c['status'] !== 'open' || $c['pay_status'] === 'approved' || in_array($c['pay_status'], ['receipt_submitted', 'under_verification'], true))
            continue;
        $days = (strtotime($c['deadline']) - strtotime($today)) / 86400;
        if ($c['deadline'] < $today) {
            notify((int) $student['user_id'], 'overdue', "Overdue: \"{$c['title']}\" was due on " . fmt_date($c['deadline']) . '.', 'overdue-' . $c['collection_id']);
        } elseif ($days <= 3) {
            notify((int) $student['user_id'], 'deadline', "Deadline soon: \"{$c['title']}\" is due on " . fmt_date($c['deadline']) . '.', 'due-' . $c['collection_id']);
        }
    }
}

/* =====================================================================
 * STATUS BADGES (all)
 * ===================================================================== */
function status_badge(?string $s): string
{
    $map = [
        null => ['Unpaid', 'b-grey'],
        'unpaid' => ['Unpaid', 'b-grey'],
        'receipt_submitted' => ['Receipt Submitted', 'b-blue'],
        'under_verification' => ['Under Verification', 'b-amber'],
        'approved' => ['Approved', 'b-green'],
        'rejected' => ['Rejected', 'b-red'],
        'overdue' => ['Overdue', 'b-red'],
        'open' => ['Open', 'b-green'],
        'closed' => ['Closed', 'b-grey'],
        'pending' => ['Pending', 'b-amber'],
        'draft' => ['Draft', 'b-grey'],
        'published' => ['Published', 'b-green'],
        'active' => ['Active', 'b-green'],
        'inactive' => ['Inactive', 'b-grey'],
        'sent' => ['Sent', 'b-green'],
        'failed' => ['Failed', 'b-red'],
    ];
    [$label, $cls] = $map[$s] ?? [ucfirst((string) $s), 'b-grey'];
    return '<span class="badge ' . $cls . '">' . h($label) . '</span>';
}
function kind_badge(string $kind): string
{
    return $kind === 'event' ? '<span class="badge b-purple">Special Event</span>' : '<span class="badge b-teal">Monthly</span>';
}

/* =====================================================================
 * COLLECTIONS & PAYMENT QUERIES (Members 2 & 3)
 * ===================================================================== */
/** Collections for one student with that student's payment row (if any). */
function student_collections(int $studentId, ?string $kind = null): array
{
    $sql = "SELECT c.*, p.payment_id, p.status AS pay_status, p.amount AS paid_amount, p.reference_no,
                   p.rejection_reason, p.submitted_at
            FROM collections c
            LEFT JOIN payments p ON p.collection_id = c.collection_id AND p.student_id = ?";
    $params = [$studentId];
    if ($kind) {
        $sql .= ' WHERE c.kind = ?';
        $params[] = $kind;
    }
    return rows($sql . ' ORDER BY c.deadline DESC, c.collection_id DESC', $params);
}
/** Every active student with their payment state for a collection. */
function collection_roster(int $collectionId): array
{
    return rows("SELECT s.student_id, s.user_id, s.reg_no, s.district, u.full_name, u.email,
                        p.payment_id, p.status AS pay_status, p.amount AS paid_amount
                 FROM students s JOIN users u ON u.user_id = s.user_id
                 LEFT JOIN payments p ON p.student_id = s.student_id AND p.collection_id = ?
                 WHERE s.status = 'active' ORDER BY s.reg_no", [$collectionId]);
}
/** Per-collection performance used by dashboard charts and reports. */
function collection_performance(?string $kind = null, int $limit = 6, array $f = []): array
{
    $where = [];
    $params = [];
    if ($kind) {
        $where[] = 'c.kind = ?';
        $params[] = $kind;
    }
    if (!empty($f['collection_id'])) {
        $where[] = 'c.collection_id = ?';
        $params[] = (int) $f['collection_id'];
    }
    $sql = "SELECT c.collection_id, c.kind, c.title, c.amount, c.target_amount, c.deadline, c.status,
                   (SELECT COUNT(*) FROM students WHERE status='active') AS students,
                   COALESCE(SUM(CASE WHEN p.status='approved' THEN p.amount END),0) AS collected,
                   COALESCE(SUM(p.status='approved'),0) AS paid_count,
                   COALESCE(SUM(p.status IN ('receipt_submitted','under_verification')),0) AS pending_count,
                   COALESCE(SUM(p.status='rejected'),0) AS rejected_count
            FROM collections c LEFT JOIN payments p ON p.collection_id = c.collection_id"
        . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        . ' GROUP BY c.collection_id ORDER BY c.deadline DESC, c.collection_id DESC LIMIT ' . (int) $limit;
    return rows($sql, $params);
}

/* =====================================================================
 * FINANCIAL CALCULATIONS (Member 4)
 * Available Balance = Total Approved Payments − Total Approved Expenses
 * ===================================================================== */
function fund_totals(array $f = []): array
{
    $pw = ["p.status = 'approved'"];
    $pp = [];
    $ew = ["e.status = 'approved'"];
    $ep = [];
    if (!empty($f['from'])) {
        $pw[] = 'DATE(p.reviewed_at) >= ?';
        $pp[] = $f['from'];
        $ew[] = 'e.expense_date >= ?';
        $ep[] = $f['from'];
    }
    if (!empty($f['to'])) {
        $pw[] = 'DATE(p.reviewed_at) <= ?';
        $pp[] = $f['to'];
        $ew[] = 'e.expense_date <= ?';
        $ep[] = $f['to'];
    }
    if (!empty($f['collection_id'])) {
        $pw[] = 'p.collection_id = ?';
        $pp[] = (int) $f['collection_id'];
        $ew[] = 'e.collection_id = ?';
        $ep[] = (int) $f['collection_id'];
    }
    $income = (float) val('SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE ' . implode(' AND ', $pw), $pp);
    $expenses = (float) val('SELECT COALESCE(SUM(e.amount),0) FROM expenses e WHERE ' . implode(' AND ', $ew), $ep);
    return ['income' => $income, 'expenses' => $expenses, 'balance' => $income - $expenses];
}
/** Money still expected from active students (approved payments are the only thing that reduces it). */
function total_outstanding(): float
{
    return (float) val("SELECT COALESCE(SUM(c.amount - COALESCE(p.amount,0)),0)
        FROM collections c CROSS JOIN students s
        LEFT JOIN payments p ON p.collection_id = c.collection_id AND p.student_id = s.student_id AND p.status = 'approved'
        WHERE s.status = 'active'");
}
function recent_transactions(int $limit = 8): array
{
    return rows("(SELECT p.reviewed_at AS dt, 'Income' AS type, CONCAT(u.full_name, ' – ', c.title) AS label, p.amount
                  FROM payments p JOIN students s ON s.student_id = p.student_id JOIN users u ON u.user_id = s.user_id
                  JOIN collections c ON c.collection_id = p.collection_id WHERE p.status = 'approved')
                 UNION ALL
                 (SELECT CONCAT(e.expense_date, ' 00:00:00'), 'Expense', e.title, e.amount FROM expenses e WHERE e.status = 'approved')
                 ORDER BY dt DESC LIMIT " . (int) $limit);
}
function report_filters(): array
{
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    return [
        'from' => valid_date($from) ? $from : '',
        'to' => valid_date($to) ? $to : '',
        'collection_id' => (int) ($_GET['collection_id'] ?? 0),
    ];
}
function expense_breakdown(array $f = []): array
{
    $w = ["e.status = 'approved'"];
    $p = [];
    if (!empty($f['from'])) {
        $w[] = 'e.expense_date >= ?';
        $p[] = $f['from'];
    }
    if (!empty($f['to'])) {
        $w[] = 'e.expense_date <= ?';
        $p[] = $f['to'];
    }
    if (!empty($f['collection_id'])) {
        $w[] = 'e.collection_id = ?';
        $p[] = (int) $f['collection_id'];
    }
    return rows('SELECT e.category, COUNT(*) AS items, SUM(e.amount) AS total FROM expenses e WHERE ' . implode(' AND ', $w) . ' GROUP BY e.category ORDER BY total DESC', $p);
}
function payment_status_summary(array $f = []): array
{
    $w = [];
    $p = [];
    if (!empty($f['from'])) {
        $w[] = 'DATE(p.submitted_at) >= ?';
        $p[] = $f['from'];
    }
    if (!empty($f['to'])) {
        $w[] = 'DATE(p.submitted_at) <= ?';
        $p[] = $f['to'];
    }
    if (!empty($f['collection_id'])) {
        $w[] = 'p.collection_id = ?';
        $p[] = (int) $f['collection_id'];
    }
    $r = rows('SELECT p.status, COUNT(*) AS n, SUM(p.amount) AS total FROM payments p' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' GROUP BY p.status', $p);
    $out = [];
    foreach ($r as $x) {
        $out[$x['status']] = $x;
    }
    return $out;
}

/* =====================================================================
 * FILE UPLOADS (Member 3) – extension + MIME + size + image check, random names
 * ===================================================================== */
function handle_upload(array $file): array
{
    $allowed = ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp'], 'pdf' => ['application/pdf']];
    if (!isset($file['error']) || is_array($file['error']))
        return [false, 'Invalid upload.'];
    if ($file['error'] === UPLOAD_ERR_NO_FILE)
        return [false, 'Please choose a file.'];
    if ($file['error'] !== UPLOAD_ERR_OK)
        return [false, 'Upload failed (error code ' . (int) $file['error'] . ').'];
    if ($file['size'] <= 0 || $file['size'] > MAX_UPLOAD_BYTES)
        return [false, 'File must be smaller than ' . (MAX_UPLOAD_BYTES / 1048576) . ' MB.'];
    if (!is_uploaded_file($file['tmp_name']))
        return [false, 'Invalid upload source.'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext]))
        return [false, 'Only JPG, PNG, WEBP or PDF files are allowed.'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!in_array($mime, $allowed[$ext], true))
        return [false, 'File content does not match its extension.'];
    if ($mime !== 'application/pdf' && @getimagesize($file['tmp_name']) === false)
        return [false, 'The image file is corrupted.'];
    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }
    $stored = bin2hex(random_bytes(16)) . '.' . $ext;     // unique, non-guessable, never executable
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored))
        return [false, 'Could not save the file.'];
    @chmod(UPLOAD_DIR . $stored, 0644);
    $orig = preg_replace('/[^A-Za-z0-9._\- ]/', '_', basename($file['name']));
    return [true, ['stored_name' => $stored, 'original_name' => mb_substr($orig, 0, 200), 'mime_type' => $mime, 'file_size' => (int) $file['size']]];
}
function delete_stored_file(string $stored): void
{
    $path = UPLOAD_DIR . basename($stored);
    if (is_file($path)) {
        @unlink($path);
    }
}
/** Streams a stored file inline. Authorisation must be checked by the caller. */
function serve_file(string $stored, string $mime, string $name): void
{
    $path = UPLOAD_DIR . basename($stored);
    if (!is_file($path)) {
        http_response_code(404);
        exit('File not found.');
    }
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    header('Content-Disposition: inline; filename="' . str_replace('"', '', $name) . '"');
    header('X-Content-Type-Options: nosniff');
    header("Content-Security-Policy: default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'");
    readfile($path);
    exit;
}

/* =====================================================================
 * E-MAIL (Member 5) – small SMTP client with logging
 * ===================================================================== */
function send_mail(string $to, string $subject, string $body): array
{
    $to = str_replace(["\r", "\n"], '', $to);
    if (!valid_email($to))
        return [false, 'Invalid recipient address.'];
    $subjectEnc = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $fromName = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';

    if (SMTP_HOST === '') {   // fallback to mail()
        $ok = @mail($to, $subjectEnc, $body, "From: $fromName <" . MAIL_FROM . ">\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8");
        return [$ok, $ok ? '' : 'mail() failed – configure SMTP_* in config.php'];
    }
    $remote = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
    $fp = @stream_socket_client($remote, $errno, $errstr, 10);
    if (!$fp)
        return [false, "Cannot connect to SMTP server: $errstr"];
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $r = '';
        while (($l = fgets($fp, 515)) !== false) {
            $r .= $l;
            if (substr($l, 3, 1) === ' ')
                break; }return $r; };
    $cmd = function (?string $c, string $expect) use ($fp, $read) {
        if ($c !== null)
            fwrite($fp, $c . "\r\n");
        $r = $read();
        return [substr($r, 0, 3) === $expect, trim($r)];
    };
    $fail = function (string $msg) use ($fp) {
        @fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return [false, $msg]; };
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';

    [$ok, $r] = $cmd(null, '220');
    if (!$ok)
        return $fail("Greeting: $r");
    [$ok, $r] = $cmd("EHLO $host", '250');
    if (!$ok)
        return $fail("EHLO: $r");
    if (SMTP_SECURE === 'tls') {
        [$ok, $r] = $cmd('STARTTLS', '220');
        if (!$ok)
            return $fail("STARTTLS: $r");
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
            return $fail('TLS negotiation failed.');
        [$ok, $r] = $cmd("EHLO $host", '250');
        if (!$ok)
            return $fail("EHLO: $r");
    }
    if (SMTP_USER !== '') {
        [$ok, $r] = $cmd('AUTH LOGIN', '334');
        if (!$ok)
            return $fail("AUTH: $r");
        [$ok, $r] = $cmd(base64_encode(SMTP_USER), '334');
        if (!$ok)
            return $fail("AUTH user: $r");
        [$ok, $r] = $cmd(base64_encode(SMTP_PASS), '235');
        if (!$ok)
            return $fail('SMTP login rejected – check username/password.');
    }
    [$ok, $r] = $cmd('MAIL FROM:<' . MAIL_FROM . '>', '250');
    if (!$ok)
        return $fail("MAIL FROM: $r");
    [$ok, $r] = $cmd("RCPT TO:<$to>", '250');
    if (!$ok)
        return $fail("RCPT TO: $r");
    [$ok, $r] = $cmd('DATA', '354');
    if (!$ok)
        return $fail("DATA: $r");
    $headers = "Date: " . date('r') . "\r\nFrom: $fromName <" . MAIL_FROM . ">\r\nTo: <$to>\r\nSubject: $subjectEnc\r\n" .
        "Message-ID: <" . bin2hex(random_bytes(8)) . "@batchfund.local>\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
    $msg = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r"], "\n", $body));
    $msg = str_replace("\n", "\r\n", $msg);
    [$ok, $r] = $cmd($headers . "\r\n\r\n" . $msg . "\r\n.", '250');
    if (!$ok)
        return $fail("Message rejected: $r");
    $cmd('QUIT', '221');
    fclose($fp);
    return [true, ''];
}
/** Sends one e-mail and records the attempt in email_logs. */
function send_logged_mail(array $student, ?int $collectionId, string $subject, string $body): bool
{
    [$ok, $err] = send_mail($student['email'], $subject, $body);
    q(
        'INSERT INTO email_logs (student_id, recipient_email, subject, collection_id, status, error_message, sent_by) VALUES (?,?,?,?,?,?,?)',
        [$student['student_id'], $student['email'], mb_substr($subject, 0, 200), $collectionId, $ok ? 'sent' : 'failed', mb_substr($err, 0, 250), $_SESSION['user_id'] ?? null]
    );
    return $ok;
}
/** Personalised reminder text: name, collection, outstanding amount, deadline, instructions. */
function reminder_message(array $student, array $c, string $mode): array
{
    $overdue = $c['deadline'] < date('Y-m-d');
    $lead = $mode === 'overdue' || $overdue ? 'Your contribution is now OVERDUE.' : ($mode === 'deadline' ? 'The payment deadline is approaching.' : 'This is a friendly reminder about a pending contribution.');
    $subject = ($overdue ? 'Overdue: ' : 'Reminder: ') . $c['title'] . ' – ' . APP_NAME;
    $body = "Hi {$student['full_name']},\n\n$lead\n\n" .
        "Collection: {$c['title']}\nOutstanding amount: " . money($c['amount']) . "\nPayment deadline: " . fmt_date($c['deadline']) .
        "\n\nPayment instructions:\n" . PAYMENT_INSTRUCTIONS .
        "\n\nAfter paying, log in to BatchFund, submit the transaction reference and upload your receipt.\n\nThank you,\n" . MAIL_FROM_NAME;
    return [$subject, $body];
}

/* =====================================================================
 * PAGE LAYOUT (shared by coordinator.php and student.php) – Member 1
 * ===================================================================== */
function page_start(string $title, array $nav, string $active, array $user, int $unread = 0): void
{
    $nameParts = preg_split('/\s+/', trim((string) $user['full_name'])) ?: [''];
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1) . (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
    // less-used pages move into the top-right account dropdown
    $moved = ['reminders', 'announcements', 'logs', 'account'];
    // Notifications is reached through the bell icon, so it is not listed in the bar or the dropdown
    $mainNav = array_filter($nav, fn($i) => !in_array($i[0], $moved, true) && $i[0] !== 'notifications');
    $menuNav = array_filter($nav, fn($i) => in_array($i[0], $moved, true));
    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> – <?= APP_NAME ?></title>
        <link rel="stylesheet" href="style.css">
        <link rel="stylesheet" href="dashboard.css">
    </head>

    <body>
        <div class="layout">
            <aside class="sidebar" id="sidebar">
                <div class="brand"><span class="logo">₿F</span>
                    <div><strong><?= APP_NAME ?></strong><small><?= $user['role'] === 'coordinator' ? 'Coordinator' : 'Student' ?>
                            panel</small></div>
                </div>
                <nav>
                    <?php foreach ($mainNav as $item):
                        [$key, $label, $href] = $item; ?>
                        <a href="<?= h($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><?= h($label) ?>
                            <?php if ($key === 'notifications' && $unread > 0): ?><span
                                    class="count"><?= $unread ?></span><?php endif; ?></a>
                    <?php endforeach; ?>
                </nav>
                <div class="nav-right">
                    <a class="icon-btn bell" href="?page=notifications" title="Notifications" aria-label="Notifications">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 1 1 12 0c0 7 3 8 3 8H3s3-1 3-8"/><path d="M10.3 20a2 2 0 0 0 3.4 0"/></svg>
                        <?php if ($unread > 0): ?><span class="bell-badge"><?= $unread ?></span><?php endif; ?>
                    </a>
                    <span class="nav-divider"></span>
                    <details class="user-menu">
                        <summary aria-label="Account menu">
                            <span class="avatar"><?= h($initials) ?></span>
                            <svg class="chev" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                        </summary>
                        <div class="user-dropdown">
                            <div class="user-dropdown-name"><strong><?= h($user['full_name']) ?></strong><small><?= $user['role'] === 'coordinator' ? 'Coordinator' : 'Student' ?></small></div>
                            <?php foreach ($menuNav as $item):
                                [$key, $label, $href] = $item; ?>
                                <a href="<?= h($href) ?>" class="<?= $active === $key ? 'active' : '' ?>"><span><?= h($label) ?></span>
                                    <?php if ($key === 'notifications' && $unread > 0): ?><span class="count"><?= $unread ?></span><?php endif; ?></a>
                            <?php endforeach; ?>
                            <form method="post" action="auth.php" class="logout"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="btn btn-ghost" type="submit">Log out</button></form>
                        </div>
                    </details>
                </div>
            </aside>
            <div class="main">
                <header class="topbar">
                    <button class="menu-btn" id="menuBtn" type="button" aria-label="Toggle menu">☰</button>
                    <h1><?= h($title) ?></h1>
                </header>
                <main class="content">
                    <?php foreach ($_SESSION['flash'] ?? [] as $f): ?>
                        <div class="alert alert-<?= h($f['type']) ?>" role="alert"><?= h($f['msg']) ?></div>
                    <?php endforeach;
                    unset($_SESSION['flash']); ?>
                <?php }

function page_end(): void
{ ?>
                </main>
            </div>
        </div>
        <script src="script.js"></script>
    </body>

    </html>
<?php }

/** Account page shared by both roles (change own password). */
function account_page(string $returnUrl): void
{ ?>
    <div class="card narrow">
        <h2>Change password</h2>
        <form method="post" action="actions.php" class="form" data-validate>
            <?= csrf_field() ?><input type="hidden" name="action" value="change_password"><input type="hidden" name="return"
                value="<?= h($returnUrl) ?>">
            <label>Current password<input type="password" name="current_password" required
                    autocomplete="current-password"></label>
            <label>New password (min 8 chars, letters + numbers)<input type="password" name="new_password" required
                    minlength="8" autocomplete="new-password"></label>
            <label>Confirm new password<input type="password" name="confirm_password" required minlength="8"
                    autocomplete="new-password"></label>
            <button class="btn btn-primary" type="submit">Update password</button>
        </form>
    </div>
<?php }