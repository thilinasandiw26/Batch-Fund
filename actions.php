<?php
/**
 * actions.php – all backend operations, selected by ?action= / POST action=.
 * Every action is authorised on the server (role + record ownership) and every POST is CSRF-checked.
 *
 * Owners:  M1 students/account | M2 collections | M3 payments/receipts | M4 expenses/export | M5 communication
 */
require_once __DIR__ . '/auth.php';

$user = current_user();
if (!$user) { flash('error', 'Please log in to continue.'); redirect('index.php'); }

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');

// action => allowed role ('any' = either), method
$routes = [
    'change_password' => ['any', 'POST'],
    // M1
    'add_student' => ['coordinator', 'POST'], 'update_student' => ['coordinator', 'POST'], 'toggle_student' => ['coordinator', 'POST'],
    'reset_password' => ['coordinator', 'POST'], 'delete_student' => ['coordinator', 'POST'],
    // M2
    'create_collection' => ['coordinator', 'POST'], 'create_event' => ['coordinator', 'POST'], 'update_collection' => ['coordinator', 'POST'],
    'close_collection' => ['coordinator', 'POST'], 'reopen_collection' => ['coordinator', 'POST'],
    // M3
    'submit_payment' => ['student', 'POST'], 'start_review' => ['coordinator', 'POST'], 'approve_payment' => ['coordinator', 'POST'],
    'reject_payment' => ['coordinator', 'POST'], 'download_receipt' => ['any', 'GET'],
    // M4
    'add_expense' => ['coordinator', 'POST'], 'update_expense' => ['coordinator', 'POST'], 'delete_expense' => ['coordinator', 'POST'],
    'approve_expense' => ['coordinator', 'POST'], 'reject_expense' => ['coordinator', 'POST'], 'delete_expense_doc' => ['coordinator', 'POST'],
    'download_expense_doc' => ['any', 'GET'], 'export_csv' => ['coordinator', 'GET'],
    // M5
    'send_reminder' => ['coordinator', 'POST'], 'send_bulk_reminders' => ['coordinator', 'POST'],
    'create_announcement' => ['coordinator', 'POST'], 'update_announcement' => ['coordinator', 'POST'], 'delete_announcement' => ['coordinator', 'POST'],
    'publish_announcement' => ['coordinator', 'POST'], 'unpublish_announcement' => ['coordinator', 'POST'],
    'send_notification' => ['coordinator', 'POST'], 'mark_notifications_read' => ['any', 'POST'], 'clear_notifications' => ['any', 'POST'],
];

if (!isset($routes[$action])) { http_response_code(400); exit('Unknown action.'); }
[$roleNeeded, $method] = $routes[$action];
if ($_SERVER['REQUEST_METHOD'] !== $method) { http_response_code(405); exit('Method not allowed.'); }
if ($roleNeeded !== 'any' && $user['role'] !== $roleNeeded) { http_response_code(403); exit('403 Forbidden.'); }

$defaultReturn = home_for($user['role']);
if ($method === 'POST' && !csrf_valid()) {
    flash('error', 'Security token invalid or expired. Please try again.');
    redirect(safe_return($defaultReturn));
}

try {
    ('act_' . $action)($user);
} catch (Throwable $e) {
    error_log('BatchFund action ' . $action . ' failed: ' . $e->getMessage());
    if (db()->inTransaction()) db()->rollBack();
    flash('error', 'Something went wrong while processing your request. Nothing was changed.');
}
redirect(safe_return($defaultReturn));

/* ---------- small helper: stop with an error message ---------- */
function fail(string $msg): void { flash('error', $msg); redirect(safe_return(home_for(current_user()['role']))); }
function check_errors(array $errors): void { if ($errors) fail(implode(' ', $errors)); }

/* =====================================================================
 * SHARED
 * ===================================================================== */
function act_change_password(array $u): void {
    $row = row('SELECT password FROM users WHERE user_id = ?', [$u['user_id']]);
    $new = (string)($_POST['new_password'] ?? '');
    if ((string)($_POST['current_password'] ?? '') !== $row['password']) fail('Current password is incorrect.');
    if ($new !== (string)($_POST['confirm_password'] ?? '')) fail('New passwords do not match.');
    if (!strong_password($new)) fail('New password must be at least 8 characters with letters and numbers.');
    q('UPDATE users SET password = ? WHERE user_id = ?', [$new, $u['user_id']]);
    audit('change_password', 'user', (int)$u['user_id']);
    flash('success', 'Password updated.');
}

/* =====================================================================
 * MEMBER 1 – STUDENTS
 * ===================================================================== */
function student_input(?int $studentId, ?int $userId): array {
    $d = ['name' => post_str('full_name', 120), 'email' => strtolower(post_str('email', 150)),
          'reg' => strtoupper(post_str('reg_no', 30)), 'contact' => post_str('contact_no', 20), 'district' => post_str('district', 60),'degree' => post_str('degree', 100)];
    $e = [];
    if (mb_strlen($d['name']) < 3) $e[] = 'Full name is required (min 3 characters).';
    if (!valid_email($d['email'])) $e[] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Z0-9]{7}$/', $d['reg'])) $e[] = 'Registration number must be 7 characters long and contain only letters and numbers.';
    if (!valid_phone($d['contact'])) $e[] = 'Enter a valid contact number.';
    if ($d['district'] === '') $e[] = 'District is required.';
    if ($d['degree'] === '') $e[] = 'Degree is required.';
    if (!$e) {
        if (val('SELECT COUNT(*) FROM students WHERE reg_no = ? AND student_id <> ?', [$d['reg'], $studentId ?? 0])) $e[] = 'That registration number already exists.';
        if (val('SELECT COUNT(*) FROM users WHERE email = ? AND user_id <> ?', [$d['email'], $userId ?? 0])) $e[] = 'That email address is already registered.';
    }
    check_errors($e);
    return $d;
}
function act_add_student(array $u): void {
    $d = student_input(null, null);
    $pw = 'Student@123';
    db()->beginTransaction();
    q("INSERT INTO users (role, email, password, full_name) VALUES ('student',?,?,?)", [$d['email'], $pw, $d['name']]);
    $uid = (int)db()->lastInsertId();
    q('INSERT INTO students (user_id, reg_no, contact_no, district, degree) VALUES (?,?,?,?,?)', [$uid, $d['reg'], $d['contact'], $d['district'], $d['degree']]);
    $sid = (int)db()->lastInsertId();
    audit('add_student', 'student', $sid, $d['reg'] . ' ' . $d['name']);
    db()->commit();
    notify($uid, 'info', 'Welcome to ' . APP_NAME . '! Check the Collections page for contributions.');
    flash('success', 'Student added.');
}
function act_update_student(array $u): void {
    $sid = post_int('student_id');
    $s = row('SELECT * FROM students WHERE student_id = ?', [$sid]);
    if (!$s) fail('Student not found.');
    $d = student_input($sid, (int)$s['user_id']);
    db()->beginTransaction();
    q('UPDATE users SET full_name = ?, email = ? WHERE user_id = ?', [$d['name'], $d['email'], $s['user_id']]);
    q('UPDATE students SET reg_no = ?, contact_no = ?, district = ?, degree = ? WHERE student_id = ?', [$d['reg'], $d['contact'], $d['district'], $d['degree'], $sid]);
    audit('update_student', 'student', $sid, $d['reg']);
    db()->commit();
    flash('success', 'Student details updated.');
}
function act_toggle_student(array $u): void {
    $sid = post_int('student_id');
    $s = row('SELECT * FROM students WHERE student_id = ?', [$sid]);
    if (!$s) fail('Student not found.');
    $new = $s['status'] === 'active' ? 'inactive' : 'active';
    db()->beginTransaction();
    q('UPDATE students SET status = ? WHERE student_id = ?', [$new, $sid]);
    q('UPDATE users SET is_active = ? WHERE user_id = ?', [$new === 'active' ? 1 : 0, $s['user_id']]);
    audit('toggle_student', 'student', $sid, $new);
    db()->commit();
    flash('success', 'Student account is now ' . $new . '.');
}
function act_reset_password(array $u): void {
    $sid = post_int('student_id');
    $s = row('SELECT user_id FROM students WHERE student_id = ?', [$sid]);
    if (!$s) fail('Student not found.');
    $pw = 'Student@123';
    q('UPDATE users SET password = ? WHERE user_id = ?', [$pw, $s['user_id']]);
    audit('reset_password', 'student', $sid);
    flash('success', "Password reset to default: $pw");
}
function act_delete_student(array $u): void {
    $sid = post_int('student_id');
    $s = row('SELECT user_id FROM students WHERE student_id = ?', [$sid]);
    if (!$s) fail('Student not found.');
    if (val('SELECT COUNT(*) FROM payments WHERE student_id = ?', [$sid])) fail('This student has payment records and cannot be deleted. Deactivate the account instead.');
    q('DELETE FROM users WHERE user_id = ?', [$s['user_id']]);   // cascades to students
    audit('delete_student', 'student', $sid);
    flash('success', 'Student deleted.');
    redirect('coordinator.php?page=students');
}

/* =====================================================================
 * MEMBER 2 – COLLECTIONS & EVENTS
 * ===================================================================== */
function collection_input(string $kind): array {
    $d = ['title' => post_str('title', 150), 'description' => post_text('description', 1000), 'amount' => post_amount('amount'),
          'deadline' => (string)($_POST['deadline'] ?? ''), 'month' => null, 'event_date' => null, 'target' => null];
    $e = [];
    if (mb_strlen($d['title']) < 3) $e[] = 'Title is required.';
    if ($d['amount'] <= 0 || $d['amount'] > 10000000) $e[] = 'Contribution amount must be greater than zero.';
    if (!valid_date($d['deadline'])) $e[] = 'A valid payment deadline is required.';
    if ($kind === 'monthly') {
        $m = (string)($_POST['collection_month'] ?? '');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $m)) $e[] = 'Select the collection month.'; else $d['month'] = $m . '-01';
    } else {
        $ed = (string)($_POST['event_date'] ?? '');
        if ($ed !== '') { if (valid_date($ed)) $d['event_date'] = $ed; else $e[] = 'Invalid event date.'; }
        $t = post_amount('target_amount');
        if ((string)($_POST['target_amount'] ?? '') !== '') { if ($t > 0) $d['target'] = $t; else $e[] = 'Target amount must be positive.'; }
    }
    check_errors($e);
    return $d;
}
function act_create_collection(array $u): void { create_any_collection($u, 'monthly'); }
function act_create_event(array $u): void { create_any_collection($u, 'event'); }
function create_any_collection(array $u, string $kind): void {
    $d = collection_input($kind);
    q('INSERT INTO collections (kind, title, description, amount, target_amount, collection_month, event_date, deadline, created_by) VALUES (?,?,?,?,?,?,?,?,?)',
      [$kind, $d['title'], $d['description'], $d['amount'], $d['target'], $d['month'], $d['event_date'], $d['deadline'], $u['user_id']]);
    $cid = (int)db()->lastInsertId();
    audit('create_collection', 'collection', $cid, "$kind: {$d['title']}");
    notify_all_students($kind === 'event' ? 'new_event' : 'new_collection',
        ($kind === 'event' ? 'New special-event collection: ' : 'New monthly collection: ') . $d['title'] . ' – ' . money($d['amount']) . ', due ' . fmt_date($d['deadline']) . '.');
    flash('success', ($kind === 'event' ? 'Event' : 'Collection') . ' created and students notified.');
}
function act_update_collection(array $u): void {
    $cid = post_int('collection_id');
    $c = row('SELECT * FROM collections WHERE collection_id = ?', [$cid]);
    if (!$c) fail('Collection not found.');
    $d = collection_input($c['kind']);
    if ((float)$d['amount'] !== (float)$c['amount'] && val('SELECT COUNT(*) FROM payments WHERE collection_id = ?', [$cid]))
        fail('The contribution amount cannot change after payments have been submitted.');
    q('UPDATE collections SET title=?, description=?, amount=?, target_amount=?, collection_month=?, event_date=?, deadline=? WHERE collection_id=?',
      [$d['title'], $d['description'], $d['amount'], $d['target'], $d['month'], $d['event_date'], $d['deadline'], $cid]);
    audit('update_collection', 'collection', $cid, $d['title']);
    flash('success', 'Collection updated.');
}
function act_close_collection(array $u): void { set_collection_status('closed'); }
function act_reopen_collection(array $u): void { set_collection_status('open'); }
function set_collection_status(string $status): void {
    $cid = post_int('collection_id');
    if (!val('SELECT COUNT(*) FROM collections WHERE collection_id = ?', [$cid])) fail('Collection not found.');
    q('UPDATE collections SET status = ? WHERE collection_id = ?', [$status, $cid]);
    audit($status === 'closed' ? 'close_collection' : 'reopen_collection', 'collection', $cid);
    flash('success', 'Collection ' . $status . '.');
}

/* =====================================================================
 * MEMBER 3 – PAYMENTS
 * ===================================================================== */
function act_submit_payment(array $u): void {
    $sid = (int)$u['student_id'];                      // always from the session, never from the form
    $cid = post_int('collection_id');
    $c = row('SELECT * FROM collections WHERE collection_id = ?', [$cid]);
    if (!$c) fail('Collection not found.');
    if ($c['status'] !== 'open') fail('This collection is closed.');
    $amount = post_amount('amount'); $ref = post_str('reference_no', 60);
    $e = [];
    if (abs($amount - (float)$c['amount']) > 0.001) $e[] = 'Payment amount must equal the contribution amount (' . money($c['amount']) . ').';
    if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\-_\/ ]{3,59}$/', $ref)) $e[] = 'Enter a valid transaction reference (4–60 letters/numbers).';
    check_errors($e);
    $existing = row('SELECT * FROM payments WHERE student_id = ? AND collection_id = ?', [$sid, $cid]);
    if ($existing && $existing['status'] !== 'rejected') fail('A payment for this collection has already been submitted.');
    if (val('SELECT COUNT(*) FROM payments WHERE reference_no = ? AND payment_id <> ?', [$ref, $existing['payment_id'] ?? 0])) fail('This transaction reference was already used.');

    [$ok, $file] = handle_upload($_FILES['receipt'] ?? []);
    if (!$ok) fail($file);
    try {
        db()->beginTransaction();
        if ($existing) {            // resubmission of a rejected payment
            $upd = q("UPDATE payments SET amount=?, reference_no=?, status='receipt_submitted', attempt_no=attempt_no+1, rejection_reason=NULL,
                      reviewed_by=NULL, reviewed_at=NULL, submitted_at=NOW() WHERE payment_id=? AND student_id=? AND status='rejected'",
                     [$amount, $ref, $existing['payment_id'], $sid]);
            if ($upd->rowCount() !== 1) throw new RuntimeException('Payment state changed.');
            $pid = (int)$existing['payment_id'];
            $attempt = (int)$existing['attempt_no'] + 1;
        } else {
            q("INSERT INTO payments (student_id, collection_id, amount, reference_no, status) VALUES (?,?,?,?, 'receipt_submitted')", [$sid, $cid, $amount, $ref]);
            $pid = (int)db()->lastInsertId(); $attempt = 1;
        }
        q('INSERT INTO payment_receipts (payment_id, attempt_no, stored_name, original_name, mime_type, file_size) VALUES (?,?,?,?,?,?)',
          [$pid, $attempt, $file['stored_name'], $file['original_name'], $file['mime_type'], $file['file_size']]);
        audit($existing ? 'resubmit_payment' : 'submit_payment', 'payment', $pid, $c['title'] . ' ref ' . $ref);
        db()->commit();
    } catch (Throwable $ex) {
        if (db()->inTransaction()) db()->rollBack();
        delete_stored_file($file['stored_name']);
        throw $ex;
    }
    notify_coordinators('payment_submitted', $u['full_name'] . ' submitted a payment for "' . $c['title'] . '".');
    flash('success', 'Payment submitted for verification.');
    redirect('student.php?page=history');
}
function load_payment_for_review(): array {
    $p = row('SELECT p.*, c.title, s.user_id AS student_user FROM payments p JOIN collections c ON c.collection_id = p.collection_id
              JOIN students s ON s.student_id = p.student_id WHERE p.payment_id = ?', [post_int('payment_id')]);
    if (!$p) fail('Payment not found.');
    return $p;
}
function act_start_review(array $u): void {
    $p = load_payment_for_review();
    $r = q("UPDATE payments SET status='under_verification' WHERE payment_id=? AND status='receipt_submitted'", [$p['payment_id']]);
    if ($r->rowCount() === 1) audit('start_review', 'payment', (int)$p['payment_id']);
    flash('success', 'Payment marked as under verification.');
}
function act_approve_payment(array $u): void {
    $p = load_payment_for_review();
    db()->beginTransaction();
    $locked = row('SELECT status FROM payments WHERE payment_id = ? FOR UPDATE', [$p['payment_id']]);   // prevents double approval
    if (!in_array($locked['status'], ['receipt_submitted', 'under_verification'], true)) { db()->rollBack(); fail('This payment has already been processed.'); }
    q("UPDATE payments SET status='approved', reviewed_by=?, reviewed_at=NOW(), rejection_reason=NULL WHERE payment_id=?", [$u['user_id'], $p['payment_id']]);
    audit('approve_payment', 'payment', (int)$p['payment_id'], money($p['amount']) . ' – ' . $p['title']);
    db()->commit();
    notify((int)$p['student_user'], 'payment_approved', 'Your payment for "' . $p['title'] . '" (' . money($p['amount']) . ') was approved.');
    flash('success', 'Payment approved.');
}
function act_reject_payment(array $u): void {
    $p = load_payment_for_review();
    $reason = post_str('reason', 250);
    if (mb_strlen($reason) < 5) fail('Please give a rejection reason (at least 5 characters).');
    db()->beginTransaction();
    $locked = row('SELECT status FROM payments WHERE payment_id = ? FOR UPDATE', [$p['payment_id']]);
    if (!in_array($locked['status'], ['receipt_submitted', 'under_verification'], true)) { db()->rollBack(); fail('This payment has already been processed.'); }
    q("UPDATE payments SET status='rejected', reviewed_by=?, reviewed_at=NOW(), rejection_reason=? WHERE payment_id=?", [$u['user_id'], $reason, $p['payment_id']]);
    audit('reject_payment', 'payment', (int)$p['payment_id'], $reason);
    db()->commit();
    notify((int)$p['student_user'], 'payment_rejected', 'Your payment for "' . $p['title'] . '" was rejected: ' . $reason);
    flash('success', 'Payment rejected and student notified.');
}
function act_download_receipt(array $u): void {
    $r = row('SELECT r.*, p.student_id FROM payment_receipts r JOIN payments p ON p.payment_id = r.payment_id WHERE r.receipt_id = ?', [(int)($_GET['id'] ?? 0)]);
    if (!$r) { http_response_code(404); exit('Not found.'); }
    if ($u['role'] === 'student' && (int)$r['student_id'] !== (int)$u['student_id']) { http_response_code(403); exit('403 Forbidden.'); }
    serve_file($r['stored_name'], $r['mime_type'], $r['original_name']);
}

/* =====================================================================
 * MEMBER 4 – EXPENSES & EXPORT
 * ===================================================================== */
function expense_input(): array {
    $cats = expense_categories();
    $d = ['title' => post_str('title', 150), 'description' => post_text('description', 1000), 'category' => post_str('category', 50),
          'amount' => post_amount('amount'), 'date' => (string)($_POST['expense_date'] ?? ''), 'cid' => post_int('collection_id') ?: null,
          'public' => isset($_POST['doc_public']) ? 1 : 0];
    $e = [];
    if (mb_strlen($d['title']) < 3) $e[] = 'Expense title is required.';
    if (!in_array($d['category'], $cats, true)) $e[] = 'Choose a category.';
    if ($d['amount'] <= 0 || $d['amount'] > 100000000) $e[] = 'Amount must be greater than zero.';
    if (!valid_date($d['date'])) $e[] = 'A valid date is required.';
    if ($d['cid'] && !val('SELECT COUNT(*) FROM collections WHERE collection_id = ?', [$d['cid']])) $e[] = 'Linked collection not found.';
    check_errors($e);
    return $d;
}
function expense_categories(): array { return ['Event', 'Food & Refreshments', 'Printing & Stationery', 'Transport', 'Gifts & Welfare', 'Equipment', 'Donations', 'Other']; }
function save_expense_document(int $expenseId, int $public): void {
    if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) return;
    [$ok, $f] = handle_upload($_FILES['document']);
    if (!$ok) fail($f);
    q('INSERT INTO expense_documents (expense_id, stored_name, original_name, mime_type, file_size, is_public) VALUES (?,?,?,?,?,?)',
      [$expenseId, $f['stored_name'], $f['original_name'], $f['mime_type'], $f['file_size'], $public]);
}
function act_add_expense(array $u): void {
    $d = expense_input();
    $status = EXPENSE_APPROVAL_REQUIRED ? 'pending' : 'approved';
    db()->beginTransaction();
    q('INSERT INTO expenses (title, description, category, amount, expense_date, collection_id, status, created_by, approved_by, approved_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
      [$d['title'], $d['description'], $d['category'], $d['amount'], $d['date'], $d['cid'], $status, $u['user_id'],
       $status === 'approved' ? $u['user_id'] : null, $status === 'approved' ? date('Y-m-d H:i:s') : null]);
    $id = (int)db()->lastInsertId();
    save_expense_document($id, $d['public']);
    audit('add_expense', 'expense', $id, $d['title'] . ' ' . money($d['amount']) . " [$status]");
    db()->commit();
    flash('success', 'Expense recorded (' . $status . ').');
}
function act_update_expense(array $u): void {
    $id = post_int('expense_id');
    $old = row('SELECT * FROM expenses WHERE expense_id = ?', [$id]);
    if (!$old) fail('Expense not found.');
    $d = expense_input();
    $status = $old['status'];
    if (EXPENSE_APPROVAL_REQUIRED && $status === 'approved') $status = 'pending';   // edits to approved expenses need re-approval
    db()->beginTransaction();
    q('UPDATE expenses SET title=?, description=?, category=?, amount=?, expense_date=?, collection_id=?, status=?, approved_by=NULL, approved_at=NULL WHERE expense_id=?',
      [$d['title'], $d['description'], $d['category'], $d['amount'], $d['date'], $d['cid'], $status, $id]);
    if ($status === 'approved') q('UPDATE expenses SET approved_by=?, approved_at=? WHERE expense_id=?', [$old['approved_by'], $old['approved_at'], $id]);
    save_expense_document($id, $d['public']);
    audit('update_expense', 'expense', $id, 'was ' . money($old['amount']) . ' now ' . money($d['amount']) . " [$status]");
    db()->commit();
    flash('success', 'Expense updated' . ($status === 'pending' && $old['status'] === 'approved' ? ' and sent back for approval.' : '.'));
    redirect('coordinator.php?page=expenses');
}
function act_delete_expense(array $u): void {
    $id = post_int('expense_id');
    $e = row('SELECT * FROM expenses WHERE expense_id = ?', [$id]);
    if (!$e) fail('Expense not found.');
    if ($e['status'] === 'approved') fail('Approved expenses are part of the financial record and cannot be deleted. Reject it first if it was entered in error.');
    $docs = rows('SELECT stored_name FROM expense_documents WHERE expense_id = ?', [$id]);
    q('DELETE FROM expenses WHERE expense_id = ?', [$id]);
    foreach ($docs as $d) delete_stored_file($d['stored_name']);
    audit('delete_expense', 'expense', $id, $e['title']);
    flash('success', 'Expense deleted.');
    redirect('coordinator.php?page=expenses');
}
function act_approve_expense(array $u): void { review_expense($u, 'approved'); }
function act_reject_expense(array $u): void { review_expense($u, 'rejected'); }
function review_expense(array $u, string $to): void {
    $id = post_int('expense_id');
    db()->beginTransaction();
    $e = row('SELECT * FROM expenses WHERE expense_id = ? FOR UPDATE', [$id]);
    if (!$e) { db()->rollBack(); fail('Expense not found.'); }
    if ($e['status'] === $to) { db()->rollBack(); fail('Expense is already ' . $to . '.'); }
    if ($to === 'approved' && $e['status'] !== 'pending' && $e['status'] !== 'rejected') { db()->rollBack(); fail('Expense cannot be approved.'); }
    q('UPDATE expenses SET status=?, approved_by=?, approved_at=NOW() WHERE expense_id=?', [$to, $u['user_id'], $id]);
    audit($to === 'approved' ? 'approve_expense' : 'reject_expense', 'expense', $id, $e['title'] . ' ' . money($e['amount']));
    db()->commit();
    flash('success', 'Expense ' . $to . '.');
}
function act_delete_expense_doc(array $u): void {
    $d = row('SELECT * FROM expense_documents WHERE document_id = ?', [post_int('document_id')]);
    if (!$d) fail('Document not found.');
    q('DELETE FROM expense_documents WHERE document_id = ?', [$d['document_id']]);
    delete_stored_file($d['stored_name']);
    audit('delete_expense_doc', 'expense', (int)$d['expense_id']);
    flash('success', 'Document removed.');
}
function act_download_expense_doc(array $u): void {
    $d = row("SELECT d.*, e.status FROM expense_documents d JOIN expenses e ON e.expense_id = d.expense_id WHERE d.document_id = ?", [(int)($_GET['id'] ?? 0)]);
    if (!$d) { http_response_code(404); exit('Not found.'); }
    if ($u['role'] === 'student' && !($d['is_public'] && $d['status'] === 'approved')) { http_response_code(403); exit('403 Forbidden.'); }
    serve_file($d['stored_name'], $d['mime_type'], $d['original_name']);
}
function csv_safe($v): string { $v = (string)$v; return ($v !== '' && strpos("=+-@\t\r", $v[0]) !== false) ? "'" . $v : $v; }
function act_export_csv(array $u): void {
    $f = report_filters(); $type = $_GET['type'] ?? 'payments';
    $w = []; $p = [];
    if ($type === 'expenses') {
        $head = ['Date', 'Title', 'Category', 'Amount', 'Status', 'Collection/Event'];
        if ($f['from']) { $w[] = 'e.expense_date >= ?'; $p[] = $f['from']; }
        if ($f['to']) { $w[] = 'e.expense_date <= ?'; $p[] = $f['to']; }
        if ($f['collection_id']) { $w[] = 'e.collection_id = ?'; $p[] = $f['collection_id']; }
        $data = rows('SELECT e.expense_date, e.title, e.category, e.amount, e.status, c.title AS ctitle FROM expenses e LEFT JOIN collections c ON c.collection_id = e.collection_id'
            . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY e.expense_date DESC', $p);
    } elseif ($type === 'collections') {
        $head = ['Collection', 'Type', 'Deadline', 'Contribution', 'Students', 'Paid', 'Pending', 'Collected', 'Status'];
        $data = array_map(fn($c) => [$c['title'], $c['kind'], $c['deadline'], $c['amount'], $c['students'], $c['paid_count'], $c['pending_count'], $c['collected'], $c['status']],
                          collection_performance(null, 1000, $f));
    } else {
        $type = 'payments';
        $head = ['Submitted', 'Reg No', 'Student', 'Collection', 'Amount', 'Reference', 'Status', 'Reviewed'];
        if ($f['from']) { $w[] = 'DATE(p.submitted_at) >= ?'; $p[] = $f['from']; }
        if ($f['to']) { $w[] = 'DATE(p.submitted_at) <= ?'; $p[] = $f['to']; }
        if ($f['collection_id']) { $w[] = 'p.collection_id = ?'; $p[] = $f['collection_id']; }
        $data = rows('SELECT p.submitted_at, s.reg_no, u.full_name, c.title, p.amount, p.reference_no, p.status, p.reviewed_at FROM payments p
                      JOIN students s ON s.student_id = p.student_id JOIN users u ON u.user_id = s.user_id JOIN collections c ON c.collection_id = p.collection_id'
            . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY p.submitted_at DESC', $p);
    }
    audit('export_csv', 'report', null, $type);
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="batchfund_' . $type . '_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");                                    // UTF-8 BOM so Excel opens it correctly
    fputcsv($out, $head);
    foreach ($data as $r) fputcsv($out, array_map('csv_safe', array_values($r)));
    fclose($out);
    exit;
}

/* =====================================================================
 * MEMBER 5 – E-MAIL, ANNOUNCEMENTS, NOTIFICATIONS
 * ===================================================================== */
function act_send_reminder(array $u): void {
    $s = row('SELECT s.student_id, s.user_id, u.full_name, u.email FROM students s JOIN users u ON u.user_id = s.user_id WHERE s.student_id = ? AND s.status = \'active\'', [post_int('student_id')]);
    $c = row('SELECT * FROM collections WHERE collection_id = ?', [post_int('collection_id')]);
    if (!$s || !$c) fail('Student or collection not found.');
    $mode = in_array($_POST['mode'] ?? '', ['unpaid', 'overdue', 'deadline'], true) ? $_POST['mode'] : 'unpaid';
    if (val("SELECT COUNT(*) FROM payments WHERE student_id=? AND collection_id=? AND status IN ('approved','receipt_submitted','under_verification')", [$s['student_id'], $c['collection_id']]))
        fail('This student has already paid or has a payment awaiting verification.');
    [$subject, $body] = reminder_message($s, $c, $mode);
    $ok = send_logged_mail($s, (int)$c['collection_id'], $subject, $body);
    notify((int)$s['user_id'], $mode === 'overdue' ? 'overdue' : 'deadline', 'Reminder: "' . $c['title'] . '" – ' . money($c['amount']) . ' due ' . fmt_date($c['deadline']) . '.');
    audit('send_reminder', 'collection', (int)$c['collection_id'], $s['email'] . ($ok ? ' sent' : ' failed'));
    flash($ok ? 'success' : 'error', $ok ? 'Reminder emailed to ' . $s['full_name'] . '.' : 'Email could not be sent – see the Email Log for details. (An in-app notification was still created.)');
}
function act_send_bulk_reminders(array $u): void {
    set_time_limit(180);
    $mode = in_array($_POST['mode'] ?? '', ['unpaid', 'overdue', 'deadline'], true) ? $_POST['mode'] : 'unpaid';
    $cidPost = post_int('collection_id');
    $cols = $cidPost ? rows("SELECT * FROM collections WHERE collection_id = ?", [$cidPost]) : rows("SELECT * FROM collections WHERE status = 'open'");
    if (!$cols) fail('No matching collections.');
    $days = max(1, min(30, post_int('days') ?: 7));
    $sent = 0; $failed = 0;
    foreach ($cols as $c) {
        if ($mode === 'overdue' && $c['deadline'] >= date('Y-m-d')) continue;
        if ($mode === 'deadline' && ($c['deadline'] < date('Y-m-d') || strtotime($c['deadline']) > strtotime("+$days days"))) continue;
        foreach (collection_roster((int)$c['collection_id']) as $s) {
            if ($s['pay_status'] !== null && $s['pay_status'] !== 'rejected') continue;   // paid / awaiting verification
            [$subject, $body] = reminder_message($s, $c, $mode);
            // one individual message per student: recipients never see each other's addresses
            if (send_logged_mail($s, (int)$c['collection_id'], $subject, $body)) $sent++; else $failed++;
            notify((int)$s['user_id'], $c['deadline'] < date('Y-m-d') ? 'overdue' : 'deadline', 'Reminder: "' . $c['title'] . '" – ' . money($c['amount']) . ' due ' . fmt_date($c['deadline']) . '.');
        }
    }
    audit('send_bulk_reminders', 'email', null, "mode=$mode sent=$sent failed=$failed");
    flash($failed ? 'warning' : 'success', "Bulk reminders finished: $sent sent, $failed failed." . ($sent + $failed === 0 ? ' No students matched.' : ''));
}
function announcement_input(): array {
    $d = ['title' => post_str('title', 150), 'body' => post_text('body', 5000), 'date' => (string)($_POST['publish_date'] ?? '')];
    $e = [];
    if (mb_strlen($d['title']) < 3) $e[] = 'Title is required.';
    if (mb_strlen($d['body']) < 5) $e[] = 'Announcement text is required.';
    if (!valid_date($d['date'])) $e[] = 'A valid announcement date is required.';
    check_errors($e);
    return $d;
}
function act_create_announcement(array $u): void {
    $d = announcement_input();
    $publish = ($_POST['publish'] ?? '') === '1';
    q('INSERT INTO announcements (title, body, status, publish_date, created_by) VALUES (?,?,?,?,?)', [$d['title'], $d['body'], $publish ? 'published' : 'draft', $d['date'], $u['user_id']]);
    $id = (int)db()->lastInsertId();
    audit('create_announcement', 'announcement', $id, $d['title']);
    if ($publish) notify_all_students('announcement', 'New announcement: ' . $d['title']);
    flash('success', $publish ? 'Announcement published.' : 'Draft saved.');
}
function act_update_announcement(array $u): void {
    $id = post_int('announcement_id'); $d = announcement_input();
    if (!val('SELECT COUNT(*) FROM announcements WHERE announcement_id = ?', [$id])) fail('Announcement not found.');
    q('UPDATE announcements SET title=?, body=?, publish_date=? WHERE announcement_id=?', [$d['title'], $d['body'], $d['date'], $id]);
    audit('update_announcement', 'announcement', $id);
    flash('success', 'Announcement updated.');
}
function act_delete_announcement(array $u): void {
    $id = post_int('announcement_id');
    q('DELETE FROM announcements WHERE announcement_id = ?', [$id]);
    audit('delete_announcement', 'announcement', $id);
    flash('success', 'Announcement deleted.');
}
function act_publish_announcement(array $u): void {
    $id = post_int('announcement_id');
    $a = row('SELECT * FROM announcements WHERE announcement_id = ?', [$id]);
    if (!$a) fail('Announcement not found.');
    if ($a['status'] !== 'published') {
        q("UPDATE announcements SET status='published' WHERE announcement_id=?", [$id]);
        notify_all_students('announcement', 'New announcement: ' . $a['title']);
        audit('publish_announcement', 'announcement', $id);
    }
    flash('success', 'Announcement published.');
}
function act_unpublish_announcement(array $u): void {
    $id = post_int('announcement_id');
    q("UPDATE announcements SET status='draft' WHERE announcement_id=?", [$id]);
    audit('unpublish_announcement', 'announcement', $id);
    flash('success', 'Announcement moved back to draft.');
}
function act_send_notification(array $u): void {
    $msg = post_str('message', 255);
    if (mb_strlen($msg) < 5) fail('Notification text is too short.');
    notify_all_students('info', $msg);
    audit('send_notification', 'notification', null, $msg);
    flash('success', 'Notification sent to all active students.');
}
function act_mark_notifications_read(array $u): void {
    q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['user_id']]);
    flash('success', 'All notifications marked as read.');
}
function act_clear_notifications(array $u): void {
    q('DELETE FROM notifications WHERE user_id = ? AND is_read = 1', [$u['user_id']]);
    flash('success', 'Read notifications cleared.');
}
