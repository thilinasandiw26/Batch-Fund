<?php
/**
 * student.php – every student page, selected with ?page=
 * dashboard | collections | pay | history | finances | notifications | announcements | account
 * All data is limited to the logged-in student (student_id comes from the session only).
 */
require_once __DIR__ . '/auth.php';
$user = require_role('student');
$page = $_GET['page'] ?? 'dashboard';
$sid = (int)$user['student_id'];

generate_deadline_notifications($user);
$unread = (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['user_id']]);

$nav = [['dashboard', 'Dashboard', '?page=dashboard'], ['collections', 'Collections', '?page=collections'], ['pay', 'Submit Payment', '?page=pay'],
        ['history', 'Payment History', '?page=history'], ['finances', 'Financial Statement', '?page=finances'], ['announcements', 'Announcements', '?page=announcements'],
        ['notifications', 'Notifications', '?page=notifications'], ['account', 'My Account', '?page=account']];
$titles = ['dashboard' => 'My Dashboard', 'collections' => 'Collections', 'pay' => 'Submit Payment', 'history' => 'Payment History', 'finances' => 'Financial Transparency',
           'announcements' => 'Announcements', 'notifications' => 'Notifications', 'account' => 'My Account'];
if (!isset($titles[$page])) $page = 'dashboard';

page_start($titles[$page], $nav, $page, $user, $unread);
('spage_' . $page)($user);
page_end();

function sret(string $url): string { return '<input type="hidden" name="return" value="' . h($url) . '">'; }
function pay_state(array $c): string {
    if ($c['pay_status']) return $c['pay_status'];
    return ($c['status'] === 'open' && $c['deadline'] < date('Y-m-d')) ? 'overdue' : 'unpaid';
}
function can_pay(array $c): bool { return $c['status'] === 'open' && ($c['pay_status'] === null || $c['pay_status'] === 'rejected'); }

function spage_dashboard(array $u): void {
    $sid = (int)$u['student_id'];
    $all = student_collections($sid);
    $paid = 0; $out = 0; $await = 0; $upcoming = [];
    foreach ($all as $c) {
        if ($c['pay_status'] === 'approved') { $paid += $c['paid_amount']; continue; }
        $out += $c['amount'];
        if (in_array($c['pay_status'], ['receipt_submitted', 'under_verification'], true)) $await++;
        elseif ($c['status'] === 'open') $upcoming[] = $c;
    }
    usort($upcoming, fn($a, $b) => strcmp($a['deadline'], $b['deadline']));
    $recent = rows('SELECT p.*, c.title FROM payments p JOIN collections c ON c.collection_id = p.collection_id WHERE p.student_id = ? ORDER BY p.submitted_at DESC LIMIT 5', [$sid]);
    $ann = rows("SELECT * FROM announcements WHERE status='published' AND publish_date <= CURDATE() ORDER BY publish_date DESC, announcement_id DESC LIMIT 3");
    ?>
    <div class="stats">
      <div class="stat green"><span>Total contributions paid</span><strong><?= money($paid) ?></strong></div>
      <div class="stat amber"><span>Outstanding contributions</span><strong><?= money($out) ?></strong></div>
      <div class="stat blue"><span>Awaiting verification</span><strong><?= $await ?></strong></div>
    </div>
    <div class="grid2">
      <div class="card"><h2>Upcoming deadlines</h2>
        <?php foreach (array_slice($upcoming, 0, 5) as $c): ?>
          <div class="flex-between line"><div><?= kind_badge($c['kind']) ?> <?= h($c['title']) ?><br><small><?= money($c['amount']) ?> · due <?= fmt_date($c['deadline']) ?></small></div>
          <div><?= status_badge(pay_state($c)) ?> <a class="btn btn-sm btn-primary" href="?page=pay&collection_id=<?= $c['collection_id'] ?>">Pay</a></div></div>
        <?php endforeach; if (!$upcoming) echo '<p class="muted">You are all caught up. 🎉</p>'; ?></div>
      <div class="card"><h2>Recent payment activity</h2>
        <?php foreach ($recent as $p): ?><div class="flex-between line"><div><?= h($p['title']) ?><br><small><?= fmt_dt($p['submitted_at']) ?> · <?= money($p['amount']) ?></small></div><?= status_badge($p['status']) ?></div>
        <?php endforeach; if (!$recent) echo '<p class="muted">No payments yet.</p>'; ?></div>
    </div>
    <div class="card"><h2>Latest announcements</h2>
      <?php foreach ($ann as $a): ?><div class="line"><strong><?= h($a['title']) ?></strong> <small class="muted"><?= fmt_date($a['publish_date']) ?></small><p class="pre"><?= h(mb_strimwidth($a['body'], 0, 220, '…')) ?></p></div>
      <?php endforeach; if (!$ann) echo '<p class="muted">No announcements.</p>'; ?></div>
<?php }

function spage_collections(array $u): void {
    $sid = (int)$u['student_id'];
    foreach (['monthly' => 'Monthly collections', 'event' => 'Special-event collections'] as $kind => $label): $list = student_collections($sid, $kind); ?>
    <div class="card"><h2><?= $label ?></h2><div class="table-wrap"><table><thead><tr><th>Collection</th><th class="num">Contribution</th><th>Deadline</th><th>Status</th><th class="num">Outstanding</th><th></th></tr></thead><tbody>
      <?php foreach ($list as $c): ?>
        <tr><td><strong><?= h($c['title']) ?></strong><br><small class="muted"><?= h($c['description']) ?></small>
            <?= $c['rejection_reason'] && $c['pay_status'] === 'rejected' ? '<br><small class="text-red">Rejected: ' . h($c['rejection_reason']) . '</small>' : '' ?></td>
          <td class="num"><?= money($c['amount']) ?></td><td><?= fmt_date($c['deadline']) ?><?= $c['status'] === 'closed' ? '<br><small>Closed</small>' : '' ?></td>
          <td><?= status_badge(pay_state($c)) ?></td><td class="num"><?= money($c['pay_status'] === 'approved' ? 0 : $c['amount']) ?></td>
          <td class="actions"><?php if (can_pay($c)): ?><a class="btn btn-sm btn-primary" href="?page=pay&collection_id=<?= $c['collection_id'] ?>"><?= $c['pay_status'] === 'rejected' ? 'Resubmit' : 'Submit payment' ?></a><?php endif; ?></td></tr>
      <?php endforeach; if (!$list): ?><tr><td colspan="6" class="muted center">Nothing here yet.</td></tr><?php endif; ?></tbody></table></div></div>
    <?php endforeach;
}

function spage_pay(array $u): void {
    $sid = (int)$u['student_id'];
    $payable = array_values(array_filter(student_collections($sid), 'can_pay'));
    $selected = (int)($_GET['collection_id'] ?? 0);
    ?>
    <div class="grid2">
      <div class="card"><h2>Submit payment details</h2>
      <?php if (!$payable): ?><p class="muted">You have no collections awaiting payment.</p><?php else: ?>
        <form method="post" action="actions.php" enctype="multipart/form-data" class="form" data-validate data-pay-form>
          <?= csrf_field() ?><input type="hidden" name="action" value="submit_payment"><?= sret('student.php?page=pay') ?>
          <label>Collection<select name="collection_id" required data-pay-select><option value="">Select a collection…</option>
            <?php foreach ($payable as $c): ?><option value="<?= $c['collection_id'] ?>" data-amount="<?= h($c['amount']) ?>" <?= $selected === (int)$c['collection_id'] ? 'selected' : '' ?>>
              <?= h($c['title']) ?> – <?= money($c['amount']) ?><?= $c['pay_status'] === 'rejected' ? ' (resubmit)' : '' ?></option><?php endforeach; ?></select></label>
          <label>Amount paid (<?= CURRENCY ?>)<input type="number" name="amount" step="0.01" min="1" required data-pay-amount></label>
          <label>Transaction reference<input name="reference_no" required minlength="4" maxlength="60" pattern="[A-Za-z0-9][A-Za-z0-9\-_\/ ]{3,59}" placeholder="Bank slip / transaction number"></label>
          <label>Receipt (JPG, PNG, WEBP or PDF, max <?= MAX_UPLOAD_BYTES / 1048576 ?> MB)<input type="file" name="receipt" required accept=".jpg,.jpeg,.png,.webp,.pdf" data-max="<?= MAX_UPLOAD_BYTES ?>"></label>
          <button class="btn btn-primary" type="submit">Submit for verification</button></form>
      <?php endif; ?></div>
      <div class="card"><h2>How to pay</h2><p class="pre"><?= h(PAYMENT_INSTRUCTIONS) ?></p></div>
    </div>
<?php }

function spage_history(array $u): void {
    $list = rows('SELECT p.*, c.title, c.kind, c.status AS cstatus, (SELECT receipt_id FROM payment_receipts r WHERE r.payment_id = p.payment_id ORDER BY attempt_no DESC LIMIT 1) AS receipt_id
                  FROM payments p JOIN collections c ON c.collection_id = p.collection_id WHERE p.student_id = ? ORDER BY p.submitted_at DESC', [$u['student_id']]); ?>
    <div class="card"><div class="table-wrap"><table><thead><tr><th>Collection</th><th class="num">Amount</th><th>Reference</th><th>Submitted</th><th>Status</th><th>Receipt</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $p): ?>
      <tr><td><?= kind_badge($p['kind']) ?> <?= h($p['title']) ?></td><td class="num"><?= money($p['amount']) ?></td><td><?= h($p['reference_no']) ?></td><td><?= fmt_dt($p['submitted_at']) ?></td>
        <td><?= status_badge($p['status']) ?><?= $p['status'] === 'rejected' ? '<br><small class="text-red">' . h($p['rejection_reason']) . '</small>' : '' ?><?= $p['attempt_no'] > 1 ? '<br><small>Attempt ' . (int)$p['attempt_no'] . '</small>' : '' ?></td>
        <td><?php if ($p['receipt_id']): ?><a href="actions.php?action=download_receipt&id=<?= (int)$p['receipt_id'] ?>" target="_blank" rel="noopener">View</a><?php endif; ?></td>
        <td class="actions"><?php if ($p['status'] === 'rejected' && $p['cstatus'] === 'open'): ?><a class="btn btn-sm btn-primary" href="?page=pay&collection_id=<?= $p['collection_id'] ?>">Resubmit</a><?php endif; ?></td></tr>
    <?php endforeach; if (!$list): ?><tr><td colspan="7" class="muted center">You have not submitted any payments.</td></tr><?php endif; ?></tbody></table></div></div>
<?php }

function spage_finances(array $u): void {
    $t = fund_totals();
    $exp = rows("SELECT e.*, (SELECT GROUP_CONCAT(d.document_id) FROM expense_documents d WHERE d.expense_id = e.expense_id AND d.is_public = 1) AS public_docs
                 FROM expenses e WHERE e.status = 'approved' ORDER BY e.expense_date DESC, e.expense_id DESC"); ?>
    <div class="stats"><div class="stat green"><span>Total confirmed income</span><strong><?= money($t['income']) ?></strong></div>
      <div class="stat red"><span>Total approved expenses</span><strong><?= money($t['expenses']) ?></strong></div>
      <div class="stat blue"><span>Available balance</span><strong><?= money($t['balance']) ?></strong></div></div>
    <div class="card"><h2>Expenses</h2><input type="search" placeholder="Search expenses…" data-filter-table="finTable"><div class="table-wrap"><table id="finTable"><thead><tr><th>Date</th><th>Expense</th><th>Category</th><th class="num">Amount</th><th>Documents</th></tr></thead><tbody>
      <?php foreach ($exp as $e): ?><tr><td><?= fmt_date($e['expense_date']) ?></td><td><strong><?= h($e['title']) ?></strong><br><small class="muted"><?= h($e['description']) ?></small></td><td><?= h($e['category']) ?></td><td class="num"><?= money($e['amount']) ?></td>
        <td><?php foreach (array_filter(explode(',', (string)$e['public_docs'])) as $id): ?><a href="actions.php?action=download_expense_doc&id=<?= (int)$id ?>" target="_blank" rel="noopener">📎</a> <?php endforeach; ?></td></tr>
      <?php endforeach; if (!$exp) echo '<tr><td colspan="5" class="muted center">No approved expenses yet.</td></tr>'; ?></tbody></table></div></div>
<?php }

function spage_announcements(array $u): void {
    $ann = rows("SELECT * FROM announcements WHERE status='published' AND publish_date <= CURDATE() ORDER BY publish_date DESC, announcement_id DESC");
    foreach ($ann as $a): ?><div class="card"><div class="flex-between"><h2><?= h($a['title']) ?></h2><small><?= fmt_date($a['publish_date']) ?></small></div><p class="pre"><?= h($a['body']) ?></p></div>
    <?php endforeach; if (!$ann) echo '<div class="card muted">No announcements.</div>';
}

function spage_notifications(array $u): void {
    $list = rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT 100', [$u['user_id']]); ?>
    <div class="card"><div class="btn-row">
      <form method="post" action="actions.php" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_notifications_read"><?= sret('student.php?page=notifications') ?><button class="btn btn-sm">Mark all as read</button></form>
      <form method="post" action="actions.php" class="inline" data-confirm="Delete all read notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear_notifications"><?= sret('student.php?page=notifications') ?><button class="btn btn-sm">Clear read</button></form></div>
      <ul class="notes"><?php foreach ($list as $n): ?><li class="<?= $n['is_read'] ? '' : 'unread' ?>"><?= h($n['message']) ?><small><?= fmt_dt($n['created_at']) ?></small></li><?php endforeach; if (!$list) echo '<li class="muted">No notifications.</li>'; ?></ul></div>
<?php }

function spage_account(array $u): void { account_page('student.php?page=account'); }
