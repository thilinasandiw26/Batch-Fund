<?php
require_once __DIR__ . '/functions.php';
start_secure_session();

function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    $cache = null;
    if (empty($_SESSION['user_id'])) return null;
    if (time() - ($_SESSION['last_seen'] ?? 0) > SESSION_IDLE_SECONDS) { $_SESSION = []; return null; }
    $_SESSION['last_seen'] = time();
    $u = row('SELECT user_id, role, email, full_name, is_active FROM users WHERE user_id = ?', [$_SESSION['user_id']]);
    if (!$u || !$u['is_active']) { $_SESSION = []; return null; }
    if ($u['role'] === 'student') {
        $s = row('SELECT student_id, reg_no, contact_no, district, status FROM students WHERE user_id = ?', [$u['user_id']]);
        if (!$s || $s['status'] !== 'active') { $_SESSION = []; return null; }
        $u = array_merge($u, $s);
    }
    return $cache = $u;
}

function require_role(string $role): array
{
    $u = current_user();
    if (!$u) { flash('error', 'Please log in to continue.'); redirect('index.php'); }
    if ($u['role'] !== $role) { http_response_code(403); exit('403 Forbidden – you do not have access to this page.'); }
    return $u;
}

function home_for(string $role): string { return $role === 'coordinator' ? 'coordinator.php' : 'student.php'; }

function attempt_login(string $email, string $password): array
{
    if (($_SESSION['lock_until'] ?? 0) > time()) {
        return [false, 'Too many failed attempts. Try again in a few minutes.'];
    }
    $u = row('SELECT * FROM users WHERE email = ?', [strtolower($email)]);
    
    $ok = $u && ($password === $u['password']) && $u['is_active'];
    
    if ($ok && $u['role'] === 'student') {
        $ok = (bool)val("SELECT COUNT(*) FROM students WHERE user_id = ? AND status = 'active'", [$u['user_id']]);
    }
    if (!$ok) {
        $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
        if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
        usleep(400000);
        return [false, 'Invalid email or password.'];
    }
    session_regenerate_id(true);
    $_SESSION = ['user_id' => (int)$u['user_id'], 'last_seen' => time(), 'csrf' => bin2hex(random_bytes(32))];
    
    q('UPDATE users SET last_login_at = NOW() WHERE user_id = ?', [$u['user_id']]);
    audit('login', 'user', (int)$u['user_id'], 'Successful login');
    return [true, home_for($u['role'])];
}

function logout_user(): void
{
    if (!empty($_SESSION['user_id'])) audit('logout', 'user', (int)$_SESSION['user_id']);
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'auth.php') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
    if (!csrf_valid()) { flash('error', 'Security token expired. Please try again.'); redirect('index.php'); }
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        [$ok, $msg] = attempt_login($email, (string)($_POST['password'] ?? ''));
        if ($ok) redirect($msg);
        flash('error', $msg);
        redirect('index.php');
    }
    if ($action === 'logout') {
        logout_user();
        start_secure_session();
        flash('success', 'You have been logged out.');
        redirect('index.php');
    }
    redirect('index.php');
}