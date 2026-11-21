<?php

require_once __DIR__ . '/auth.php';
$user = require_role('student');
$sid = (int)$user['student_id'];

// Old links (student.php?page=…) still work: they open the matching pop-up (or just the dashboard).
if (isset($_GET['page'])) {
    $dlg = ['notifications' => 'dlgNotifications', 'announcements' => 'dlgAnnouncements', 'finances' => 'dlgFinances', 'history' => 'dlgHistory', 'account' => 'dlgAccount'];
    $p = (string)$_GET['page'];
    $cid = (int)($_GET['collection_id'] ?? 0);
    redirect('student.php' . ($cid ? '?collection_id=' . $cid : '') . (isset($dlg[$p]) ? '#' . $dlg[$p] : ($p === 'pay' || $p === 'collections' ? '#pay' : '')));
}

generate_deadline_notifications($user);
$unread = (int)val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['user_id']]);

// Minimal header: bell + account menu only. Secondary pages open as pop-ups so the page itself stays short.
$nav = [
    ['notifications', 'Notifications', '#dlgNotifications', 'dlgNotifications'],
    ['history', 'Payment History', '#dlgHistory', 'dlgHistory'],
    ['finances', 'Financial Statement', '#dlgFinances', 'dlgFinances'],
    ['announcements', 'Announcements', '#dlgAnnouncements', 'dlgAnnouncements'],
    ['account', 'My Account', '#dlgAccount', 'dlgAccount'],
];
$firstName = explode(' ', trim((string)$user['full_name']))[0];

page_start('Hello, ' . $firstName, $nav, '', $user, $unread);
echo '<div data-single-page>';
home_view($user);
echo '</div>';
sdialog('dlgHistory', 'Payment history', fn() => spage_history($user), true);
sdialog('dlgFinances', 'Financial statement', fn() => spage_finances($user), true);
sdialog('dlgAnnouncements', 'Announcements', fn() => spage_announcements($user));
sdialog('dlgNotifications', 'Notifications', fn() => spage_notifications($user));
sdialog('dlgAccount', 'My account', fn() => spage_account($user));
page_end();

function sdialog(string $id, string $title, callable $body, bool $wide = false): void
{
    echo '<dialog id="' . $id . '" class="' . ($wide ? 'dlg-wide' : '') . '"><div class="dlg-head"><h2>' . h($title) . '</h2>'
       . '<button class="btn btn-sm" type="button" data-close aria-label="Close">✕</button></div><div class="dlg-body">';
    $body();
    echo '</div></dialog>';
}

function sret(string $url): string { return '<input type="hidden" name="return" value="' . h($url) . '">'; }
function pay_state(array $c): string {
    if ($c['pay_status']) return $c['pay_status'];
    return ($c['status'] === 'open' && $c['deadline'] < date('Y-m-d')) ? 'overdue' : 'unpaid';
}
function can_pay(array $c): bool { return $c['status'] === 'open' && ($c['pay_status'] === null || $c['pay_status'] === 'rejected'); }


function home_view(array $u): void {
    $sid = (int)$u['student_id'];
    $all = student_collections($sid);
    $paid = 0; $out = 0; $await = 0; $awaitAmt = 0; $upcoming = [];
    foreach ($all as $c) {
        if ($c['pay_status'] === 'approved') { $paid += $c['paid_amount']; continue; }
        $out += $c['amount'];
        if (in_array($c['pay_status'], ['receipt_submitted', 'under_verification'], true)) { $await++; $awaitAmt += $c['amount']; }
        elseif ($c['status'] === 'open') $upcoming[] = $c;
    }
    usort($upcoming, fn($a, $b) => strcmp($a['deadline'], $b['deadline']));
    $evImgs = event_image_ids(array_map(fn($c) => (int)$c['collection_id'], array_slice($upcoming, 0, 4)));
    $payable = array_values(array_filter($all, 'can_pay'));
    $selected = (int)($_GET['collection_id'] ?? 0);
    if (!$selected && count($payable) === 1) $selected = (int)$payable[0]['collection_id'];
    $recent = rows('SELECT p.*, c.title, c.status AS cstatus, rv.full_name AS reviewer,
                           (SELECT receipt_id FROM payment_receipts r WHERE r.payment_id = p.payment_id ORDER BY attempt_no DESC LIMIT 1) AS receipt_id
                    FROM payments p JOIN collections c ON c.collection_id = p.collection_id LEFT JOIN users rv ON rv.user_id = p.reviewed_by
                    WHERE p.student_id = ? ORDER BY p.submitted_at DESC LIMIT 5', [$sid]);
    $totalPay = (int)val('SELECT COUNT(*) FROM payments WHERE student_id = ?', [$sid]);
    $today = strtotime(date('Y-m-d'));
    ?>
    <div class="stats home-stats">
      <div class="stat primary"><span>Outstanding balance</span><strong><?= money($out) ?></strong>
        <small><?= $payable ? count($payable) . ' to pay' : 'Nothing left to pay' ?><?= $awaitAmt > 0 ? ' · incl. ' . money($awaitAmt) . ' awaiting verification' : '' ?></small></div>
      <div class="stat green"><span>Paid so far</span><strong><?= money($paid) ?></strong></div>
      <div class="stat blue"><span>Awaiting check</span><strong><?= $await ?></strong></div>
    </div>

    <div class="home-grid">
      <section class="upcoming">
        <?php
        $shown = array_slice($upcoming, 0, 4);
        $dueText = function (array $c) use ($today): array {
            $d = (int)floor((strtotime($c['deadline']) - $today) / 86400);
            return [$d, $d < 0 ? 'Overdue by ' . abs($d) . ' day' . (abs($d) === 1 ? '' : 's') : ($d === 0 ? 'Due today' : 'Due in ' . $d . ' day' . ($d === 1 ? '' : 's'))];
        }; ?>
        <div class="deadline-grid">
        <?php foreach ($shown as $i => $c):
            [$days, $when] = $dueText($c);
            $ev = $evImgs[(int)$c['collection_id']] ?? []; ?>
          <article class="card dl-card">
            <?php if ($i < 2): ?><h2 class="dl-head<?= $i ? ' is-ghost' : '' ?>"<?= $i ? ' aria-hidden="true"' : '' ?>>Upcoming deadlines</h2><?php endif; ?>
            <?php if ($ev): ?>
              <button type="button" class="dl-cover" data-dialog="dlgEv<?= (int)$c['collection_id'] ?>" aria-label="View photos of <?= h($c['title']) ?>">
                <img src="<?= h(event_image_url($ev[0])) ?>" alt="" loading="lazy">
                <?php if (count($ev) > 1): ?><span class="dl-more">+<?= count($ev) - 1 ?></span><?php endif; ?>
              </button>
            <?php else: ?>
              <div class="dl-cover dl-cover--none" aria-hidden="true">📅</div>
            <?php endif; ?>
            <p class="dl-line"><strong><?= h($c['title']) ?></strong>
              <span class="<?= $days < 0 ? 'text-red' : 'muted' ?>">· <?= money($c['amount']) ?> · <?= fmt_date($c['deadline']) ?> · <?= $when ?></span></p>
            <?= $c['pay_status'] === 'rejected' && $c['rejection_reason'] ? '<p class="dl-reject">Rejected: ' . h($c['rejection_reason']) . '</p>' : '' ?>
            <a class="btn btn-sm btn-primary dl-pay" href="#pay" data-pay-for="<?= (int)$c['collection_id'] ?>"><?= $c['pay_status'] === 'rejected' ? 'Resubmit' : 'Pay' ?></a>
          </article>
        <?php endforeach; ?>
        </div>
        <?php foreach ($shown as $c): $ev = $evImgs[(int)$c['collection_id']] ?? []; if (!$ev) continue; ?>
          <dialog id="dlgEv<?= (int)$c['collection_id'] ?>" class="dlg-wide"><div class="dlg-head"><h2><?= h($c['title']) ?></h2><button class="btn btn-sm" type="button" data-close aria-label="Close">✕</button></div>
            <div class="dlg-body"><p class="muted"><?= money($c['amount']) ?> · pay by <?= fmt_date($c['deadline']) ?><?= $c['event_date'] ? ' · event on ' . fmt_date($c['event_date']) : '' ?></p>
              <?= $c['description'] ? '<p class="pre">' . h($c['description']) . '</p>' : '' ?>
              <div class="ev-photos"><?php foreach ($ev as $iid): ?><img src="<?= h(event_image_url($iid)) ?>" alt="" loading="lazy"><?php endforeach; ?></div>
              <a class="btn btn-primary btn-block" href="#pay" data-pay-for="<?= (int)$c['collection_id'] ?>" data-close>Pay</a></div></dialog>
        <?php endforeach;
        if (!$upcoming) echo '<p class="muted">You are all caught up. 🎉</p>';
        if (count($upcoming) > 4) echo '<p class="muted small">+ ' . (count($upcoming) - 4) . ' more due later</p>'; ?>
      </section>

      <div class="card" id="pay"><h2>Submit a payment slip</h2>
      <?php if (!$payable): ?><p class="muted">No payments are due right now.</p><?php else: ?>
        <form method="post" action="actions.php" enctype="multipart/form-data" class="form" data-validate data-pay-form>
          <?= csrf_field() ?><input type="hidden" name="action" value="submit_payment"><?= sret('student.php#recent') ?>
          <label>Collection<select name="collection_id" required data-pay-select><option value="">Select…</option>
            <?php foreach ($payable as $c): ?><option value="<?= $c['collection_id'] ?>" data-amount="<?= h($c['amount']) ?>" <?= $selected === (int)$c['collection_id'] ? 'selected' : '' ?>>
              <?= h($c['title']) ?> – <?= money($c['amount']) ?><?= $c['pay_status'] === 'rejected' ? ' (resubmit)' : '' ?></option><?php endforeach; ?></select></label>
          <label>Amount (<?= CURRENCY ?>)<input type="number" name="amount" step="0.01" min="1" required readonly data-pay-amount placeholder="Filled in automatically"></label>
          <label>Transaction reference<input name="reference_no" required minlength="4" maxlength="60" pattern="[A-Za-z0-9][A-Za-z0-9\-_\/ ]{3,59}" placeholder="Bank slip / transaction number"></label>
          <label>Payment slip (JPG, PNG, WEBP or PDF, max <?= MAX_UPLOAD_BYTES / 1048576 ?> MB)<input type="file" name="receipt" required accept=".jpg,.jpeg,.png,.webp,.pdf" data-max="<?= MAX_UPLOAD_BYTES ?>"></label>
          <button class="btn btn-primary btn-block" type="submit">Submit for verification</button>
        </form>
        <details class="how"><summary>How to pay</summary><p class="pre"><?= h(PAYMENT_INSTRUCTIONS) ?></p></details>
      <?php endif; ?></div>
    </div>

    <div class="card" id="recent"><div class="flex-between"><h2>Recent payments</h2>
      <?php if ($totalPay > 0): ?><button class="btn btn-sm" type="button" data-dialog="dlgHistory">View all<?= $totalPay > 5 ? ' (' . $totalPay . ')' : '' ?></button><?php endif; ?></div>
      <?php foreach ($recent as $p): ?>
        <div class="row-item">
          <div><strong><?= h($p['title']) ?></strong><small class="muted"><?= money($p['amount']) ?> · <?= fmt_dt($p['submitted_at']) ?></small>
            <?= $p['status'] === 'rejected' && $p['rejection_reason'] ? '<small class="text-red">' . h($p['rejection_reason']) . '</small>' : '' ?>
            <?= credit([[review_label($p['status']), $p['reviewer'], $p['status'] === 'under_verification' ? null : $p['reviewed_at']]]) ?></div>
          <div class="row-side"><?= status_badge($p['status']) ?>
            <?php if ($p['receipt_id']): ?><a href="actions.php?action=download_receipt&id=<?= (int)$p['receipt_id'] ?>" target="_blank" rel="noopener">Slip</a><?php endif; ?>
            <?php if ($p['status'] === 'rejected' && $p['cstatus'] === 'open'): ?><a class="btn btn-sm btn-primary" href="#pay" data-pay-for="<?= (int)$p['collection_id'] ?>">Resubmit</a><?php endif; ?></div>
        </div>
      <?php endforeach; if (!$recent) echo '<p class="muted">No payments yet.</p>'; ?>
    </div>
<?php }

function spage_history(array $u): void {
    $list = rows('SELECT p.*, c.title, c.kind, c.status AS cstatus, rv.full_name AS reviewer, (SELECT receipt_id FROM payment_receipts r WHERE r.payment_id = p.payment_id ORDER BY attempt_no DESC LIMIT 1) AS receipt_id
                  FROM payments p JOIN collections c ON c.collection_id = p.collection_id LEFT JOIN users rv ON rv.user_id = p.reviewed_by WHERE p.student_id = ? ORDER BY p.submitted_at DESC', [$u['student_id']]); ?>
    <div class="card"><div class="table-wrap"><table><thead><tr><th>Collection</th><th class="num">Amount</th><th>Reference</th><th>Submitted</th><th>Status</th><th>Receipt</th><th></th></tr></thead><tbody>
    <?php foreach ($list as $p): ?>
      <tr><td><?= kind_badge($p['kind']) ?> <?= h($p['title']) ?></td><td class="num"><?= money($p['amount']) ?></td><td><?= h($p['reference_no']) ?></td><td><?= fmt_dt($p['submitted_at']) ?></td>
        <td><?= status_badge($p['status']) ?><?= $p['status'] === 'rejected' ? '<br><small class="text-red">' . h($p['rejection_reason']) . '</small>' : '' ?><?= credit([[review_label($p['status']), $p['reviewer'], $p['status'] === 'under_verification' ? null : $p['reviewed_at']]]) ?><?= $p['attempt_no'] > 1 ? '<br><small>Attempt ' . (int)$p['attempt_no'] . '</small>' : '' ?></td>
        <td><?php if ($p['receipt_id']): ?><a href="actions.php?action=download_receipt&id=<?= (int)$p['receipt_id'] ?>" target="_blank" rel="noopener">View</a><?php endif; ?></td>
        <td class="actions"><?php if ($p['status'] === 'rejected' && $p['cstatus'] === 'open'): ?><a class="btn btn-sm btn-primary" href="?collection_id=<?= $p['collection_id'] ?>#pay">Resubmit</a><?php endif; ?></td></tr>
    <?php endforeach; if (!$list): ?><tr><td colspan="7" class="muted center">You have not submitted any payments.</td></tr><?php endif; ?></tbody></table></div></div>
<?php }

function spage_finances(array $u): void {
    $t = fund_totals();
    $exp = rows("SELECT e.*, (SELECT GROUP_CONCAT(d.document_id) FROM expense_documents d WHERE d.expense_id = e.expense_id AND d.is_public = 1) AS public_docs, ap.full_name AS approver
                 FROM expenses e LEFT JOIN users ap ON ap.user_id = e.approved_by WHERE e.status = 'approved' ORDER BY e.expense_date DESC, e.expense_id DESC"); ?>
    <div class="stats"><div class="stat green"><span>Total confirmed income</span><strong><?= money($t['income']) ?></strong></div>
      <div class="stat red"><span>Total approved expenses</span><strong><?= money($t['expenses']) ?></strong></div>
      <div class="stat blue"><span>Available balance</span><strong><?= money($t['balance']) ?></strong></div></div>
    <div class="card"><h2>Expenses</h2><input type="search" placeholder="Search expenses…" data-filter-table="finTable"><div class="table-wrap"><table id="finTable"><thead><tr><th>Date</th><th>Expense</th><th>Category</th><th class="num">Amount</th><th>Approved by</th><th>Documents</th></tr></thead><tbody>
      <?php foreach ($exp as $e): ?><tr><td><?= fmt_date($e['expense_date']) ?></td><td><strong><?= h($e['title']) ?></strong><br><small class="muted"><?= h($e['description']) ?></small></td><td><?= h($e['category']) ?></td><td class="num"><?= money($e['amount']) ?></td>
        <td><?= h($e['approver'] ?? '—') ?><?= $e['approved_at'] ? '<small class="by">' . h(fmt_dt($e['approved_at'])) . '</small>' : '' ?></td>
        <td><?php foreach (array_filter(explode(',', (string)$e['public_docs'])) as $id): ?><a href="actions.php?action=download_expense_doc&id=<?= (int)$id ?>" target="_blank" rel="noopener">📎</a> <?php endforeach; ?></td></tr>
      <?php endforeach; if (!$exp) echo '<tr><td colspan="6" class="muted center">No approved expenses yet.</td></tr>'; ?></tbody></table></div></div>
<?php }

function spage_announcements(array $u): void {
    $ann = rows("SELECT a.*, cr.full_name AS author, up.full_name AS editor FROM announcements a LEFT JOIN users cr ON cr.user_id = a.created_by LEFT JOIN users up ON up.user_id = a.updated_by WHERE a.status='published' AND a.publish_date <= CURDATE() ORDER BY a.publish_date DESC, a.announcement_id DESC");
    foreach ($ann as $a): ?><div class="card"><div class="flex-between"><h2><?= h($a['title']) ?></h2><small><?= fmt_date($a['publish_date']) ?></small></div><p class="pre"><?= h($a['body']) ?></p><?= credit([['Posted by', $a['author']], ['Edited by', $a['editor']]]) ?></div>
    <?php endforeach; if (!$ann) echo '<div class="card muted">No announcements.</div>';
}

function spage_notifications(array $u): void {
    $list = rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT 100', [$u['user_id']]); ?>
    <div class="card"><div class="btn-row">
      <form method="post" action="actions.php" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="mark_notifications_read"><?= sret('student.php') ?><button class="btn btn-sm">Mark all as read</button></form>
      <form method="post" action="actions.php" class="inline" data-confirm="Delete all read notifications?"><?= csrf_field() ?><input type="hidden" name="action" value="clear_notifications"><?= sret('student.php') ?><button class="btn btn-sm">Clear read</button></form></div>
      <ul class="notes"><?php foreach ($list as $n): ?><li class="<?= $n['is_read'] ? '' : 'unread' ?>"><?= h($n['message']) ?><small><?= fmt_dt($n['created_at']) ?></small></li><?php endforeach; if (!$list) echo '<li class="muted">No notifications.</li>'; ?></ul></div>
<?php }

function spage_account(array $u): void { account_page('student.php'); }
