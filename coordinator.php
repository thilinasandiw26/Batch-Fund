<?php
require_once __DIR__ . '/auth.php';
$user = require_role('coordinator');
$page = $_GET['page'] ?? 'dashboard';

$nav = [
  ['dashboard', 'Dashboard', '?page=dashboard'],
  ['students', 'Students', '?page=students'],
  ['monthly', 'Monthly Collections', '?page=monthly'],
  ['events', 'Special Events', '?page=events'],
  ['payments', 'Payments', '?page=payments'],
  ['expenses', 'Expenses', '?page=expenses'],
  ['reports', 'Financial Reports', '?page=reports'],
  ['reminders', 'Email Reminders', '?page=reminders'],
  ['announcements', 'Announcements', '?page=announcements'],
  ['notifications', 'Notifications', '?page=notifications'],
  ['logs', 'Email & Audit Logs', '?page=logs'],
  ['account', 'My Account', '?page=account'],
];
$titles = [
  'dashboard' => 'Dashboard',
  'students' => 'Student Management',
  'student_view' => 'Student Details',
  'monthly' => 'Monthly Collections',
  'events' => 'Special-Event Collections',
  'collection_view' => 'Collection Details',
  'payments' => 'Payment Verification',
  'expenses' => 'Expense Management',
  'expense_edit' => 'Edit Expense',
  'reports' => 'Financial Reports',
  'reminders' => 'Email Reminders',
  'announcements' => 'Announcements',
  'notifications' => 'Notifications',
  'logs' => 'Email & Audit Logs',
  'account' => 'My Account'
];
if (!isset($titles[$page])) {
  $page = 'dashboard';
}
$navActive = in_array($page, ['student_view'], true) ? 'students' : ($page === 'collection_view' ? (($_GET['kind'] ?? '') === 'event' ? 'events' : 'monthly') : ($page === 'expense_edit' ? 'expenses' : $page));
$unread = (int) val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$user['user_id']]);

page_start($titles[$page], $nav, $navActive, $user, $unread);
('page_' . $page)($user);
page_end();

function here(): string
{
  return h($_SERVER['REQUEST_URI'] ? basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . '?' . ($_SERVER['QUERY_STRING'] ?? '') : 'coordinator.php');
}
function ret(): string
{
  return '<input type="hidden" name="return" value="' . here() . '">';
}
function post_form(string $action, string $label, array $fields = [], string $cls = 'btn btn-sm', string $confirm = ''): string
{
  $h = '<form method="post" action="actions.php" class="inline"' . ($confirm ? ' data-confirm="' . h($confirm) . '"' : '') . '>' . csrf_field() . ret() . '<input type="hidden" name="action" value="' . h($action) . '">';
  foreach ($fields as $k => $v)
    $h .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
  return $h . '<button class="' . h($cls) . '" type="submit">' . h($label) . '</button></form>';
}
function stat_card(string $label, string $value, string $cls = ''): string
{
  return '<div class="stat ' . $cls . '"><span>' . h($label) . '</span><strong>' . h($value) . '</strong></div>';
}
function progress(float $pct): string
{
  $pct = max(0, min(100, $pct));
  return '<div class="progress"><div style="width:' . round($pct) . '%"></div></div>';
}


function page_dashboard(array $u): void
{
  $t = fund_totals();
  $students = (int) val("SELECT COUNT(*) FROM students WHERE status='active'");
  $pending = (int) val("SELECT COUNT(*) FROM payments WHERE status IN ('receipt_submitted','under_verification')");
  $monthly = array_reverse(collection_performance('monthly', 6));
  $events = collection_performance('event', 6);
  $mLabels = array_column($monthly, 'title');
  ?>
  <div class="stats">
    <?= stat_card('Registered students', (string) $students) ?>
    <?= stat_card('Confirmed income', money($t['income']), 'green') ?>
    <?= stat_card('Approved expenses', money($t['expenses']), 'red') ?>
    <?= stat_card('Available balance', money($t['balance']), 'blue') ?>
    <?= stat_card('Outstanding contributions', money(total_outstanding()), 'amber') ?>
    <a class="stat amber link" href="?page=payments&status=pending"><span>Pending
        verifications</span><strong><?= $pending ?></strong></a>
  </div>
  <div class="grid2">
    <div class="card">
      <h2>Monthly collection performance</h2>
      <canvas class="chart" data-chart='<?= h(json_encode([
        'type' => 'bar',
        'labels' => $mLabels,
        'series' => [
          ['name' => 'Expected', 'data' => array_map(fn($c) => (float) $c['amount'] * (int) $c['students'], $monthly)],
          ['name' => 'Collected', 'data' => array_map(fn($c) => (float) $c['collected'], $monthly)]
        ]
      ])) ?>'></canvas>
    </div>
    <div class="card">
      <h2>Income vs expenses</h2>
      <canvas class="chart"
        data-chart='<?= h(json_encode(['type' => 'doughnut', 'labels' => ['Available balance', 'Approved expenses'], 'series' => [['name' => '', 'data' => [max(0, $t['balance']), $t['expenses']]]]])) ?>'></canvas>
    </div>
  </div>
  <div class="card">
    <h2>Special-event collection performance</h2>
    <?php if (!$events): ?>
      <p class="muted">No special events yet.</p><?php endif; ?>
    <?php foreach ($events as $c):
      $goal = $c['target_amount'] ?: $c['amount'] * $c['students'];
      $pct = $goal > 0 ? $c['collected'] / $goal * 100 : 0; ?>
      <div class="row-progress">
        <div><a href="?page=collection_view&id=<?= $c['collection_id'] ?>&kind=event"><?= h($c['title']) ?></a>
          <?= status_badge($c['status']) ?></div>
        <?= progress($pct) ?><small><?= money($c['collected']) ?> of <?= money($goal) ?> (<?= round($pct) ?>%) ·
          <?= $c['paid_count'] ?> paid</small>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="card">
    <h2>Recent financial transactions</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Description</th>
            <th class="num">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (recent_transactions() as $r): ?>
            <tr>
              <td><?= fmt_date($r['dt']) ?></td>
              <td><span class="badge <?= $r['type'] === 'Income' ? 'b-green' : 'b-red' ?>"><?= h($r['type']) ?></span></td>
              <td><?= h($r['label']) ?></td>
              <td class="num"><?= money($r['amount']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php }


function page_students(array $u): void
{
  $search = trim((string) ($_GET['q'] ?? ''));
  $district = trim((string) ($_GET['district'] ?? ''));
  $w = [];
  $p = [];
  if ($search !== '') {
    $w[] = '(u.full_name LIKE ? OR s.reg_no LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    array_push($p, $like, $like, $like);
  }
  if ($district !== '') {
    $w[] = 's.district = ?';
    $p[] = $district;
  }
  $list = rows('SELECT s.*, u.full_name, u.email, u.role FROM students s JOIN users u ON u.user_id = s.user_id' . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY s.reg_no', $p);
  $districts = rows('SELECT DISTINCT district FROM students ORDER BY district');
  $cnt = row("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active, COALESCE(SUM(status = 'inactive'), 0) AS inactive FROM students");
  $coordinators = rows("SELECT u.user_id, u.full_name, u.email, u.last_login_at, u.is_treasurer, s.student_id FROM users u LEFT JOIN students s ON s.user_id = u.user_id WHERE u.role = 'coordinator' AND u.is_active = 1 ORDER BY u.full_name");
  ?>
  <div class="stats">
    <?= stat_card('Total students', (string) (int) $cnt['total']) ?>
    <?= stat_card('Active students', (string) (int) $cnt['active'], 'green') ?>
    <?= stat_card('Inactive students', (string) (int) $cnt['inactive'], 'amber') ?>
    <?= stat_card('Coordinators', (string) count($coordinators), 'blue') ?>
  </div>
  <div class="toolbar">
    <form method="get" class="filters"><input type="hidden" name="page" value="students">
      <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search name, reg. no, email…">
      <select name="district">
        <option value="">All districts</option><?php foreach ($districts as $d): ?>
          <option <?= $district === $d['district'] ? 'selected' : '' ?>><?= h($d['district']) ?></option><?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Filter</button>
    </form>
    <button class="btn btn-primary" data-dialog="dlgAddStudent" type="button">+ Add student</button>
  </div>
  <div class="card">
    <h2>Coordinators (<?= count($coordinators) ?>)</h2>
    <p class="muted small">Coordinators can verify payments. Expenses made by a coordinator are approved by the <strong>treasurer</strong> only. To add a coordinator, open a student below and choose <strong>Make coordinator</strong>.</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Last login</th><th>Treasurer</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($coordinators as $c): ?>
            <tr>
              <td><?= h($c['full_name']) ?><?= (int) $c['user_id'] === (int) $u['user_id'] ? ' <small class="muted">(you)</small>' : '' ?></td>
              <td><?= h($c['email']) ?></td>
              <td><?= $c['last_login_at'] ? fmt_dt($c['last_login_at']) : 'Never' ?></td>
              <td><?php
                $curT = treasurer_id(); $me = (int) $u['user_id'];
                if ($c['is_treasurer']) {
                  echo '<span class="badge b-green">Treasurer</span> ';
                  if ($curT === $me) echo post_form('clear_treasurer', 'Step down', [], 'btn btn-sm', 'Step down as treasurer? Any coordinator will be able to approve expenses until a new treasurer is appointed.');
                } elseif ($curT === null || $curT === $me) {
                  echo post_form('set_treasurer', 'Make treasurer', ['user_id' => $c['user_id']], 'btn btn-sm', 'Make ' . $c['full_name'] . ' the treasurer? Only the treasurer can approve expenses made by other coordinators.');
                } else echo '<span class="muted">—</span>';
              ?></td>
              <td class="actions"><?php if ($c['student_id']): ?><a class="btn btn-sm" href="?page=student_view&id=<?= (int) $c['student_id'] ?>">Manage</a><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <p class="muted small">Showing <?= count($list) ?> of <?= (int) $cnt['total'] ?> students</p>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Index No</th>
            <th>Name</th>
            <th>Degree</th>
            <th>Email</th>
            <th>Contact</th>
            <th>District</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $s): ?>
            <tr>
              <td><?= h($s['reg_no']) ?></td>
              <td><?= h($s['full_name']) ?><?= $s['role'] === 'coordinator' ? ' <span class="badge b-blue">Coordinator</span>' : '' ?></td>
              <td><?= h($s['degree'] ?? '') ?></td>
              <td><?= h($s['email']) ?></td>
              <td><?= h($s['contact_no']) ?></td>
              <td><?= h($s['district']) ?></td>
              <td><?= status_badge($s['status']) ?></td>
              <td class="actions"><a class="btn btn-sm" href="?page=student_view&id=<?= $s['student_id'] ?>">View / Edit</a>
              </td>
            </tr>
          <?php endforeach;
          if (!$list): ?>
            <tr>
              <td colspan="8" class="muted center">No students found.</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <dialog id="dlgAddStudent">
    <form method="post" action="actions.php" class="form" data-validate>
      <h2>Add student</h2>
      <?= csrf_field() ?><?= ret() ?><input type="hidden" name="action" value="add_student">
      <label>Full name<input name="full_name" required minlength="3" maxlength="120"></label>
      <label>Index number<input name="reg_no" required pattern="[A-Za-z0-9]{7}" minlength="7" maxlength="7"></label>
      <label>Degree Name
        <select name="degree" required>
          <option value="IT">IT</option>
          <option value="ITM">ITM</option>
          <option value="AI">AI</option>
        </select>
      </label>
      <label>Email<input type="email" name="email" required maxlength="150"></label>
      <label>Contact number<input name="contact_no" required pattern="\+?[0-9][0-9\s\-]{6,18}"
          placeholder="0771234567"></label>
      <label>District
        <select name="district" required>
          <option value="Colombo">Colombo</option>
          <option value="Gampaha">Gampaha</option>
          <option value="Kalutara">Kalutara</option>
          <option value="Kandy">Kandy</option>
          <option value="Kurunegala">Kurunegala</option>
          <option value="Matara">Matara</option>
        </select>
      </label>
      <p class="muted small">Default password: Student@123</p>
      <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary"
          type="submit">Add student</button></div>
    </form>
  </dialog>
<?php }

function page_student_view(array $u): void
{
  $sid = (int) ($_GET['id'] ?? 0);
  $s = row('SELECT s.*, u.full_name, u.email, u.last_login_at, u.role FROM students s JOIN users u ON u.user_id = s.user_id WHERE s.student_id = ?', [$sid]);
  if (!$s) {
    echo '<div class="alert alert-error">Student not found.</div>';
    return;
  }
  $list = student_collections($sid);
  $paid = 0;
  $out = 0;
  foreach ($list as $c) {
    if ($c['pay_status'] === 'approved')
      $paid += $c['paid_amount'];
    else
      $out += $c['amount'];
  }
  $curDegree = (string) ($s['degree'] ?? '');
  $degrees = ['IT', 'ITM', 'AI'];
  if ($curDegree !== '' && !in_array($curDegree, $degrees, true))
    $degrees[] = $curDegree;
  $districts = ['Colombo', 'Gampaha', 'Kalutara', 'Kandy', 'Kurunegala', 'Matara'];
  if ($s['district'] !== '' && !in_array($s['district'], $districts, true))
    $districts[] = $s['district'];
  ?>
  <p><a href="?page=students">← Back to students</a></p>
  <div class="stats">
    <?= stat_card('Total paid (approved)', money($paid), 'green') ?>  <?= stat_card('Outstanding', money($out), 'amber') ?>  <?= stat_card('Last login', $s['last_login_at'] ? fmt_dt($s['last_login_at']) : 'Never') ?>
  </div>
  <div class="grid2">
    <div class="card">
      <h2>Edit details</h2>
      <form method="post" action="actions.php" class="form" data-validate><?= csrf_field() ?><?= ret() ?><input
          type="hidden" name="action" value="update_student"><input type="hidden" name="student_id" value="<?= $sid ?>">
        <label>Full name<input name="full_name" value="<?= h($s['full_name']) ?>" required minlength="3"></label>
        <label>Index number<input name="reg_no" value="<?= h($s['reg_no']) ?>" required pattern="[A-Za-z0-9]{7}" minlength="7" maxlength="7"></label>
        <label>Degree
          <select name="degree" required>
            <?php if ($curDegree === ''): ?><option value="" selected disabled>Select…</option><?php endif; ?>
            <?php foreach ($degrees as $d): ?>
              <option value="<?= h($d) ?>" <?= $curDegree === $d ? 'selected' : '' ?>><?= h($d) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Email<input type="email" name="email" value="<?= h($s['email']) ?>" required></label>
        <label>Contact number<input name="contact_no" value="<?= h($s['contact_no']) ?>" required></label>
        <label>District
          <select name="district" required>
            <?php foreach ($districts as $d): ?>
              <option value="<?= h($d) ?>" <?= $s['district'] === $d ? 'selected' : '' ?>><?= h($d) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button class="btn btn-primary" type="submit">Save changes</button>
      </form>
    </div>
    <div class="card">
      <h2>Account</h2>
      <p>Status: <?= status_badge($s['status']) ?> &nbsp; Role: <?= $s['role'] === 'coordinator' ? '<span class="badge b-blue">Coordinator</span>' : '<span class="badge b-grey">Student</span>' ?></p>
      <div class="btn-row">
        <?php if ($s['role'] === 'coordinator'): ?>
          <?php if ((int) $s['user_id'] !== (int) $u['user_id']): ?>
            <?= post_form('demote_coordinator', 'Remove coordinator access', ['student_id' => $sid], 'btn btn-danger', 'Remove coordinator access from ' . $s['full_name'] . '? They will become a regular student.') ?>
          <?php else: ?>
            <small class="muted">This is your own account. Another coordinator can change your role.</small>
          <?php endif; ?>
          <p class="muted small">Remove coordinator access first if you need to deactivate, reset the password of, or delete this account.</p>
        <?php else: ?>
          <?php if ($s['status'] === 'active'): ?>
            <?= post_form('promote_coordinator', 'Make coordinator', ['student_id' => $sid], 'btn btn-primary', 'Make ' . $s['full_name'] . ' a coordinator? They will be able to approve payments and expenses, manage students, and promote others.') ?>
          <?php endif; ?>
          <?= post_form('toggle_student', $s['status'] === 'active' ? 'Deactivate account' : 'Activate account', ['student_id' => $sid], 'btn', 'Change this account\'s status?') ?>
          <?= post_form('reset_password', 'Reset password', ['student_id' => $sid], 'btn', 'Generate a new temporary password for this student?') ?>
          <?= post_form('delete_student', 'Delete student', ['student_id' => $sid], 'btn btn-danger', 'Permanently delete this student? This only works if they have no payments.') ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="card">
    <h2>Payment records</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Collection</th>
            <th>Type</th>
            <th>Due</th>
            <th class="num">Amount</th>
            <th>Status</th>
            <th>Reference</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $c):
            $st = $c['pay_status'] ?? (($c['deadline'] < date('Y-m-d') && $c['status'] === 'open') ? 'overdue' : 'unpaid'); ?>
            <tr>
              <td><?= h($c['title']) ?></td>
              <td><?= kind_badge($c['kind']) ?></td>
              <td><?= fmt_date($c['deadline']) ?></td>
              <td class="num"><?= money($c['amount']) ?></td>
              <td><?= status_badge($st) ?></td>
              <td><?= h($c['reference_no'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php }


function page_monthly(array $u): void
{
  collections_page('monthly');
}
function page_events(array $u): void
{
  collections_page('event');
}
function collections_page(string $kind): void
{
  $list = collection_performance($kind, 500);
  $isEvent = $kind === 'event';
  $imgMap = event_image_ids(array_column($list, 'collection_id'));
  ?>
  <div class="toolbar"><span class="muted"><?= count($list) ?>   <?= $isEvent ? 'events' : 'monthly collections' ?></span>
    <button class="btn btn-primary" data-dialog="dlgCollection" type="button">+ New
      <?= $isEvent ? 'event' : 'monthly collection' ?></button>
  </div>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Title</th><?= $isEvent ? '<th>Event date</th>' : '<th>Month</th>' ?>
            <th>Deadline</th>
            <th class="num">Contribution</th>
            <th>Progress</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $c):
            $full = row('SELECT collection_month, event_date FROM collections WHERE collection_id = ?', [$c['collection_id']]);
            $goal = $c['target_amount'] ?: $c['amount'] * $c['students'];
            $pct = $goal > 0 ? $c['collected'] / $goal * 100 : 0; ?>
            <tr>
              <td><div class="title-cell">
                <?php $im = $imgMap[(int) $c['collection_id']] ?? []; if ($im): ?><img class="thumb" src="<?= h(event_image_url($im[0])) ?>" alt="" loading="lazy"><?php endif; ?>
                <div><?= kind_badge($kind) ?> <?= h($c['title']) ?><?= $im ? '<small class="by">' . count($im) . ' photo' . (count($im) === 1 ? '' : 's') . '</small>' : '' ?></div></div></td>
              <td>
                <?= $isEvent ? fmt_date($full['event_date']) : ($full['collection_month'] ? date('F Y', strtotime($full['collection_month'])) : '—') ?>
              </td>
              <td><?= fmt_date($c['deadline']) ?></td>
              <td class="num"><?= money($c['amount']) ?></td>
              <td style="min-width:150px"><?= progress($pct) ?><small><?= money($c['collected']) ?> ·
                  <?= $c['paid_count'] ?>/<?= $c['students'] ?> paid</small></td>
              <td><?= status_badge($c['status']) ?></td>
              <td class="actions"><a class="btn btn-sm"
                  href="?page=collection_view&id=<?= $c['collection_id'] ?>&kind=<?= $kind ?>">Manage</a></td>
            </tr>
          <?php endforeach;
          if (!$list): ?>
            <tr>
              <td colspan="7" class="muted center">Nothing here yet.</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <dialog id="dlgCollection">
    <form method="post" action="actions.php" class="form" data-validate enctype="multipart/form-data">
      <h2>New <?= $isEvent ? 'special event' : 'monthly collection' ?></h2><?= csrf_field() ?><?= ret() ?><input
        type="hidden" name="action" value="<?= $isEvent ? 'create_event' : 'create_collection' ?>">
      <?php collection_form_fields($kind, null); ?>
      <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary"
          type="submit">Create &amp; notify students</button></div>
    </form>
  </dialog>
<?php }
function collection_form_fields(string $kind, ?array $c): void
{ ?>
  <label>Title<input name="title" required minlength="3" maxlength="150" value="<?= h($c['title'] ?? '') ?>"></label>
  <label>Description<textarea name="description" rows="3"
      maxlength="1000"><?= h($c['description'] ?? '') ?></textarea></label>
  <label>Contribution amount (<?= CURRENCY ?>)<input type="number" name="amount" step="0.01" min="1" required
      value="<?= h($c['amount'] ?? '') ?>"></label>
  <?php if ($kind === 'monthly'): ?>
    <label>Collection month<input type="month" name="collection_month" required
        value="<?= h(isset($c['collection_month']) ? substr($c['collection_month'], 0, 7) : date('Y-m')) ?>"></label>
  <?php else: ?>
    <label>Target amount (<?= CURRENCY ?>, optional)<input type="number" name="target_amount" step="0.01" min="1"
        value="<?= h($c['target_amount'] ?? '') ?>"></label>
    <label>Event date (optional)<input type="date" name="event_date" value="<?= h($c['event_date'] ?? '') ?>"></label>
  <?php endif; ?>
  <label>Payment deadline<input type="date" name="deadline" required value="<?= h($c['deadline'] ?? '') ?>"></label>
  <?php /* every collection (monthly or event) needs a cover image */ ?>
    <?php $haveImg = $c ? (int) val('SELECT COUNT(*) FROM event_images WHERE collection_id = ?', [$c['collection_id']]) : 0; ?>
    <label>Cover image<?= $haveImg ? '' : ' <span class="text-red">*</span>' ?><input type="file" name="images[]" multiple <?= $haveImg ? '' : 'required' ?> accept=".jpg,.jpeg,.png,.webp" data-max="<?= MAX_UPLOAD_BYTES ?>" data-images
        data-limit="<?= EVENT_IMAGE_LIMIT ?>" data-size="<?= EVENT_IMAGE_SIZE ?>" data-raw-max="<?= EVENT_IMAGE_RAW_MAX ?>"><small class="muted">JPG, PNG or WEBP · originals up to <?= EVENT_IMAGE_RAW_MAX / 1048576 ?> MB · up to <?= EVENT_IMAGE_LIMIT ?> per collection. <?= $haveImg ? 'Add more or remove old ones below.' : 'At least one is required – the first image is the cover shown to students.' ?> You crop each one to a square and it is resized automatically.</small></label>
<?php }
function page_collection_view(array $u): void
{
  $cid = (int) ($_GET['id'] ?? 0);
  $c = row('SELECT * FROM collections WHERE collection_id = ?', [$cid]);
  if (!$c) {
    echo '<div class="alert alert-error">Collection not found.</div>';
    return;
  }
  $roster = collection_roster($cid);
  $today = date('Y-m-d');
  $paid = array_filter($roster, fn($r) => $r['pay_status'] === 'approved');
  $unpaid = array_filter($roster, fn($r) => $r['pay_status'] === null || $r['pay_status'] === 'rejected');
  $awaiting = array_filter($roster, fn($r) => in_array($r['pay_status'], ['receipt_submitted', 'under_verification'], true));
  $overdue = $c['deadline'] < $today ? $unpaid : [];
  $collected = array_sum(array_map(fn($r) => (float) $r['paid_amount'], $paid));
  $goal = $c['target_amount'] ?: $c['amount'] * count($roster);
  $kind = $c['kind'];
  $who = row('SELECT cr.full_name AS creator, up.full_name AS editor FROM collections c LEFT JOIN users cr ON cr.user_id = c.created_by LEFT JOIN users up ON up.user_id = c.updated_by WHERE c.collection_id = ?', [$cid]);
  ?>
  <p><a href="?page=<?= $kind === 'event' ? 'events' : 'monthly' ?>">← Back</a></p>
  <div class="stats">
    <?= stat_card('Confirmed collected', money($collected), 'green') ?>  <?= stat_card('Paid students', count($paid) . ' / ' . count($roster)) ?>
    <?= stat_card('Awaiting verification', (string) count($awaiting), 'amber') ?>  <?= stat_card('Unpaid', (string) count($unpaid), 'red') ?>  <?= stat_card('Overdue', (string) count($overdue), 'red') ?>
  </div>
  <div class="card">
    <div class="flex-between">
      <h2><?= kind_badge($kind) ?>   <?= h($c['title']) ?>   <?= status_badge($c['status']) ?></h2>
      <div class="btn-row">
        <?= $c['status'] === 'open' ? post_form('close_collection', 'Close collection', ['collection_id' => $cid], 'btn', 'Close this collection? Students will no longer be able to submit payments.') : post_form('reopen_collection', 'Reopen', ['collection_id' => $cid]) ?>
      </div>
    </div>
    <?= progress($goal > 0 ? $collected / $goal * 100 : 0) ?><small>Goal <?= money($goal) ?> · deadline
      <?= fmt_date($c['deadline']) ?></small>
    <?= credit([['Created by', $who['creator'] ?? null], ['Last changed by', $who['editor'] ?? null, $who['editor'] ? $c['updated_at'] : null]]) ?>
    <?php if (true): $imgs = rows('SELECT * FROM event_images WHERE collection_id = ? ORDER BY image_id', [$cid]); ?>
      <div class="gallery">
        <?php foreach ($imgs as $im): ?>
          <figure class="g-item"><a href="<?= h(event_image_url((int) $im['image_id'])) ?>" target="_blank" rel="noopener"><img src="<?= h(event_image_url((int) $im['image_id'])) ?>" alt="<?= h($im['original_name']) ?>" loading="lazy"></a>
            <?= post_form('delete_event_image', 'Remove', ['image_id' => $im['image_id']], 'btn btn-sm', 'Remove this image?') ?></figure>
        <?php endforeach; if (!$imgs): ?><p class="muted small">No images yet – add some in the Edit form below.</p><?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="grid2">
    <div class="card">
      <h2>Edit</h2>
      <form method="post" action="actions.php" class="form" data-validate enctype="multipart/form-data"><?= csrf_field() ?><?= ret() ?>
        <input type="hidden" name="action" value="update_collection"><input type="hidden" name="collection_id"
          value="<?= $cid ?>">
        <?php collection_form_fields($kind, $c); ?><button class="btn btn-primary" type="submit">Save changes</button>
      </form>
    </div>
    <div class="card">
      <h2>Send reminders</h2>
      <p class="muted">Each student receives their own personalised email.</p>
      <form method="post" action="actions.php" class="form"><?= csrf_field() ?><?= ret() ?><input type="hidden"
          name="action" value="send_bulk_reminders"><input type="hidden" name="collection_id" value="<?= $cid ?>">
        <label>Reminder type<select name="mode">
            <option value="unpaid">Unpaid students</option>
            <option value="overdue">Overdue students only</option>
            <option value="deadline">Deadline approaching (within 7 days)</option>
          </select></label>
        <button class="btn btn-primary" type="submit"
          data-confirm-btn="Send reminder emails to the matching students now?">Send bulk reminders</button>
      </form>
    </div>
  </div>
  <?php foreach ([['Unpaid students', $unpaid, true], ['Awaiting verification', $awaiting, false], ['Paid students', $paid, false]] as [$label, $set, $canRemind]): ?>
    <div class="card">
      <h2><?= $label ?> (<?= count($set) ?>)</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Reg. No</th>
              <th>Name</th>
              <th>District</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($set as $r):
              $st = $r['pay_status'] ?? ($c['deadline'] < $today ? 'overdue' : 'unpaid'); ?>
              <tr>
                <td><?= h($r['reg_no']) ?></td>
                <td><?= h($r['full_name']) ?></td>
                <td><?= h($r['district']) ?></td>
                <td><?= status_badge($st) ?></td>
                <td class="actions">
                  <?= $canRemind ? post_form('send_reminder', 'Email reminder', ['student_id' => $r['student_id'], 'collection_id' => $cid, 'mode' => $c['deadline'] < $today ? 'overdue' : 'unpaid']) : '' ?>
                </td>
              </tr>
            <?php endforeach;
            if (!$set): ?>
              <tr>
                <td colspan="5" class="muted center">None</td>
              </tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach;
}


function page_payments(array $u): void
{
  $status = $_GET['status'] ?? '';
  $cid = (int) ($_GET['collection_id'] ?? 0);
  $w = [];
  $p = [];
  if ($status === 'pending')
    $w[] = "p.status IN ('receipt_submitted','under_verification')";
  elseif (in_array($status, ['receipt_submitted', 'under_verification', 'approved', 'rejected'], true)) {
    $w[] = 'p.status = ?';
    $p[] = $status;
  }
  if ($cid) {
    $w[] = 'p.collection_id = ?';
    $p[] = $cid;
  }
  $list = rows("SELECT p.*, s.reg_no, s.user_id AS student_user, u.full_name, rv.full_name AS reviewer, c.title, c.kind FROM payments p LEFT JOIN users rv ON rv.user_id = p.reviewed_by JOIN students s ON s.student_id = p.student_id JOIN users u ON u.user_id = s.user_id
                  JOIN collections c ON c.collection_id = p.collection_id" . ($w ? ' WHERE ' . implode(' AND ', $w) : '') . ' ORDER BY (p.status IN (\'receipt_submitted\',\'under_verification\')) DESC, p.submitted_at DESC LIMIT 500', $p);
  $cols = rows('SELECT collection_id, title FROM collections ORDER BY deadline DESC');
  ?>
  <div class="toolbar">
    <form method="get" class="filters"><input type="hidden" name="page" value="payments">
      <select name="status">
        <option value="">All statuses</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Needs verification</option>
        <?php foreach (['receipt_submitted' => 'Receipt Submitted', 'under_verification' => 'Under Verification', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $k => $l): ?>
          <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
      </select>
      <select name="collection_id">
        <option value="">All collections</option><?php foreach ($cols as $c): ?>
          <option value="<?= $c['collection_id'] ?>" <?= $cid === (int) $c['collection_id'] ? 'selected' : '' ?>>
            <?= h($c['title']) ?></option><?php endforeach; ?>
      </select>
      <input type="search" placeholder="Search this table…" data-filter-table="payTable"><button class="btn"
        type="submit">Filter</button>
    </form>
  </div>
  <div class="card">
    <div class="table-wrap">
      <table id="payTable">
        <thead>
          <tr>
            <th>Submitted</th>
            <th>Student</th>
            <th>Collection</th>
            <th class="num">Amount</th>
            <th>Reference</th>
            <th>Attempt</th>
            <th>Status</th>
            <th>Receipts</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $r):
            $receipts = rows('SELECT receipt_id, attempt_no FROM payment_receipts WHERE payment_id = ? ORDER BY attempt_no', [$r['payment_id']]); ?>
            <tr>
              <td><?= fmt_dt($r['submitted_at']) ?></td>
              <td><?= h($r['full_name']) ?><br><small><?= h($r['reg_no']) ?></small></td>
              <td><?= kind_badge($r['kind']) ?>     <?= h($r['title']) ?></td>
              <td class="num"><?= money($r['amount']) ?></td>
              <td><?= h($r['reference_no']) ?></td>
              <td><?= (int) $r['attempt_no'] ?><?= $r['attempt_no'] > 1 ? ' <small>(resubmitted)</small>' : '' ?></td>
              <td>
                <?= status_badge($r['status']) ?>    <?= $r['rejection_reason'] ? '<br><small class="muted">' . h($r['rejection_reason']) . '</small>' : '' ?>
                <?= credit([[review_label($r['status']), $r['reviewer'], $r['status'] === 'under_verification' ? null : $r['reviewed_at']]]) ?>
              </td>
              <td><?php foreach ($receipts as $rc): ?><a
                    href="actions.php?action=download_receipt&id=<?= $rc['receipt_id'] ?>" target="_blank"
                    rel="noopener">#<?= $rc['attempt_no'] ?></a> <?php endforeach; ?></td>
              <td class="actions"><?php if ((int) $r['student_user'] === (int) $u['user_id'] && in_array($r['status'], ['receipt_submitted', 'under_verification'], true)): ?>
                  <small class="muted">Your own payment – another coordinator must verify it.</small>
                <?php elseif (in_array($r['status'], ['receipt_submitted', 'under_verification'], true)): ?>
                  <?= $r['status'] === 'receipt_submitted' ? post_form('start_review', 'Review', ['payment_id' => $r['payment_id']]) : '' ?>
                  <?= post_form('approve_payment', 'Approve', ['payment_id' => $r['payment_id']], 'btn btn-sm btn-primary', 'Approve this payment of ' . money($r['amount']) . ' from ' . $r['full_name'] . '?') ?>
                  <button class="btn btn-sm btn-danger" type="button"
                    data-reject="<?= $r['payment_id'] ?>">Reject</button><?php endif; ?>
              </td>
            </tr>
          <?php endforeach;
          if (!$list): ?>
            <tr>
              <td colspan="9" class="muted center">No payments found.</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <dialog id="dlgReject">
    <form method="post" action="actions.php" class="form" data-validate>
      <h2>Reject payment</h2><?= csrf_field() ?><?= ret() ?>
      <input type="hidden" name="action" value="reject_payment"><input type="hidden" name="payment_id"
        id="rejectPaymentId">
      <label>Reason (shown to the student)<textarea name="reason" required minlength="5" maxlength="250"
          rows="3"></textarea></label>
      <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-danger"
          type="submit">Reject payment</button></div>
    </form>
  </dialog>
<?php }


function expense_form_fields(?array $e, array $cols): void
{ ?>
  <label>Title<input name="title" required minlength="3" maxlength="150" value="<?= h($e['title'] ?? '') ?>"></label>
  <label>Description<textarea name="description" rows="2"
      maxlength="1000"><?= h($e['description'] ?? '') ?></textarea></label>
  <label>Category<select name="category" required><?php foreach (expense_categories() as $cat): ?>
        <option <?= ($e['category'] ?? '') === $cat ? 'selected' : '' ?>><?= h($cat) ?></option><?php endforeach; ?>
    </select></label>
  <label>Amount (<?= CURRENCY ?>)<input type="number" name="amount" step="0.01" min="0.01" required
      value="<?= h($e['amount'] ?? '') ?>"></label>
  <label>Date<input type="date" name="expense_date" required
      value="<?= h($e['expense_date'] ?? date('Y-m-d')) ?>"></label>
  <label>Linked collection / event (optional)<select name="collection_id">
      <option value="">— none —</option>
      <?php foreach ($cols as $c): ?>
        <option value="<?= $c['collection_id'] ?>" <?= (int) ($e['collection_id'] ?? 0) === (int) $c['collection_id'] ? 'selected' : '' ?>><?= h($c['title']) ?></option><?php endforeach; ?>
    </select></label>
  <label>Invoice / receipt (JPG, PNG, WEBP or PDF, max 3 MB)<input type="file" name="document"
      accept=".jpg,.jpeg,.png,.webp,.pdf" data-max="<?= MAX_UPLOAD_BYTES ?>"></label>
  <label class="check"><input type="checkbox" name="doc_public" value="1"> Let students view this document</label>
<?php }
function page_expenses(array $u): void
{
  $list = rows('SELECT e.*, (SELECT COUNT(*) FROM expense_documents d WHERE d.expense_id = e.expense_id) AS docs, c.title AS ctitle, cr.full_name AS creator, ap.full_name AS approver, up.full_name AS editor FROM expenses e LEFT JOIN collections c ON c.collection_id = e.collection_id LEFT JOIN users cr ON cr.user_id = e.created_by LEFT JOIN users ap ON ap.user_id = e.approved_by LEFT JOIN users up ON up.user_id = e.updated_by ORDER BY e.expense_date DESC, e.expense_id DESC');
  $cols = rows('SELECT collection_id, title FROM collections ORDER BY deadline DESC');
  $tName = treasurer_name();
  ?>
  <div class="alert alert-info"><?= $tName ? 'Treasurer: <strong>' . h($tName) . '</strong> – only the treasurer can approve expenses made by other coordinators.' : 'No treasurer appointed yet – any coordinator can approve expenses. Appoint one under Students → Coordinators.' ?></div>
  <div class="toolbar"><input type="search" placeholder="Search expenses…" data-filter-table="expTable"><button
      class="btn btn-primary" data-dialog="dlgExpense" type="button">+ Add expense</button></div>
  <div class="card">
    <div class="table-wrap">
      <table id="expTable">
        <thead>
          <tr>
            <th>Date</th>
            <th>Title</th>
            <th>Category</th>
            <th>Linked to</th>
            <th class="num">Amount</th>
            <th>Status</th>
            <th>Docs</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($list as $e):
            $docs = $e['docs'] ? rows('SELECT document_id, original_name FROM expense_documents WHERE expense_id = ?', [$e['expense_id']]) : []; ?>
            <tr>
              <td><?= fmt_date($e['expense_date']) ?></td>
              <td><?= h($e['title']) ?><?= credit([['Added by', $e['creator']], ['Edited by', $e['editor'], $e['updated_at']]]) ?></td>
              <td><?= h($e['category']) ?></td>
              <td><?= h($e['ctitle'] ?? '—') ?></td>
              <td class="num"><?= money($e['amount']) ?></td>
              <td><?= status_badge($e['status']) ?><?= $e['status'] === 'pending' ? '' : credit([[review_label($e['status']), $e['approver'], $e['approved_at']]]) ?></td>
              <td><?php foreach ($docs as $d): ?><a
                    href="actions.php?action=download_expense_doc&id=<?= $d['document_id'] ?>" target="_blank"
                    rel="noopener">📎</a> <?php endforeach; ?></td>
              <td class="actions"><a class="btn btn-sm" href="?page=expense_edit&id=<?= $e['expense_id'] ?>">Edit</a>
                <?php if (can_decide_expense($u, $e)): ?>
                  <?= $e['status'] !== 'approved' ? post_form('approve_expense', 'Approve', ['expense_id' => $e['expense_id']], 'btn btn-sm btn-primary', 'Approve this expense? It will reduce the available balance.') : '' ?>
                  <?= $e['status'] === 'pending' ? post_form('reject_expense', 'Reject', ['expense_id' => $e['expense_id']], 'btn btn-sm', 'Reject this expense?') : '' ?>
                  <?= $e['status'] === 'approved' ? post_form('reject_expense', 'Revoke', ['expense_id' => $e['expense_id']], 'btn btn-sm', 'Revoke approval? The amount will no longer count as an expense.') : '' ?>
                <?php elseif ($e['status'] === 'pending'): ?>
                  <small class="muted"><?= (int) $e['created_by'] === treasurer_id() ? 'Needs another coordinator' : 'Waiting for treasurer' ?></small>
                <?php endif; ?>
                <?= $e['status'] !== 'approved' ? post_form('delete_expense', 'Delete', ['expense_id' => $e['expense_id']], 'btn btn-sm btn-danger', 'Delete this expense permanently?') : '' ?>
              </td>
            </tr>
          <?php endforeach;
          if (!$list): ?>
            <tr>
              <td colspan="8" class="muted center">No expenses recorded.</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <dialog id="dlgExpense">
    <form method="post" action="actions.php" enctype="multipart/form-data" class="form" data-validate>
      <h2>Add expense</h2><?= csrf_field() ?><?= ret() ?><input type="hidden" name="action" value="add_expense">
      <?php expense_form_fields(null, $cols); ?>
      <p class="muted small">
        <?= EXPENSE_APPROVAL_REQUIRED ? 'New expenses stay pending until approved.' : 'Expenses are approved immediately.' ?>
      </p>
      <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary"
          type="submit">Save expense</button></div>
    </form>
  </dialog>
<?php }
function page_expense_edit(array $u): void
{
  $id = (int) ($_GET['id'] ?? 0);
  $e = row('SELECT e.*, cr.full_name AS creator, ap.full_name AS approver, up.full_name AS editor FROM expenses e LEFT JOIN users cr ON cr.user_id = e.created_by LEFT JOIN users ap ON ap.user_id = e.approved_by LEFT JOIN users up ON up.user_id = e.updated_by WHERE e.expense_id = ?', [$id]);
  if (!$e) {
    echo '<div class="alert alert-error">Expense not found.</div>';
    return;
  }
  $cols = rows('SELECT collection_id, title FROM collections ORDER BY deadline DESC');
  $docs = rows('SELECT * FROM expense_documents WHERE expense_id = ?', [$id]);
  ?>
  <p><a href="?page=expenses">← Back to expenses</a></p>
  <div class="grid2">
    <div class="card">
      <h2>Expense details <?= status_badge($e['status']) ?></h2>
      <?= credit([['Added by', $e['creator']], [review_label($e['status']), $e['status'] === 'pending' ? null : $e['approver'], $e['approved_at']], ['Last edited by', $e['editor'], $e['updated_at']]]) ?>
      <form method="post" action="actions.php" enctype="multipart/form-data" class="form" data-validate>
        <?= csrf_field() ?><input type="hidden" name="return" value="coordinator.php?page=expenses">
        <input type="hidden" name="action" value="update_expense"><input type="hidden" name="expense_id"
          value="<?= $id ?>"><?php expense_form_fields($e, $cols); ?>
        <?php if (EXPENSE_APPROVAL_REQUIRED && $e['status'] === 'approved'): ?>
          <p class="alert alert-warning">Saving changes sends this expense back for approval.</p><?php endif; ?>
        <button class="btn btn-primary" type="submit">Save changes</button>
      </form>
    </div>
    <div class="card">
      <h2>Supporting documents</h2>
      <?php foreach ($docs as $d): ?>
        <div class="flex-between"><a href="actions.php?action=download_expense_doc&id=<?= $d['document_id'] ?>"
            target="_blank" rel="noopener"><?= h($d['original_name']) ?></a>
          <span><?= $d['is_public'] ? '<span class="badge b-green">Visible to students</span>' : '<span class="badge b-grey">Private</span>' ?>
            <?= post_form('delete_expense_doc', 'Remove', ['document_id' => $d['document_id']], 'btn btn-sm btn-danger', 'Remove this document?') ?></span>
        </div><?php endforeach;
      if (!$docs)
        echo '<p class="muted">No documents uploaded.</p>'; ?>
    </div>
  </div>
<?php }


function page_reports(array $u): void
{
  $f = report_filters();
  $t = fund_totals($f);
  $cols = rows('SELECT collection_id, title FROM collections ORDER BY deadline DESC');
  $perf = collection_performance(null, 500, $f);
  $br = expense_breakdown($f);
  $ps = payment_status_summary($f);
  $qs = http_build_query(array_filter($f));
  ?>
  <form method="get" class="filters card no-print"><input type="hidden" name="page" value="reports">
    <label>From<input type="date" name="from" value="<?= h($f['from']) ?>"></label><label>To<input type="date" name="to"
        value="<?= h($f['to']) ?>"></label>
    <label>Collection / event<select name="collection_id">
        <option value="">All</option><?php foreach ($cols as $c): ?>
          <option value="<?= $c['collection_id'] ?>" <?= $f['collection_id'] === (int) $c['collection_id'] ? 'selected' : '' ?>>
            <?= h($c['title']) ?></option><?php endforeach; ?>
      </select></label>
    <button class="btn btn-primary" type="submit">Apply</button><a class="btn" href="?page=reports">Reset</a>
    <span class="spacer"></span><button class="btn" type="button" data-print>Print report</button>
    <a class="btn" href="actions.php?action=export_csv&type=payments&<?= h($qs) ?>">CSV: payments</a><a class="btn"
      href="actions.php?action=export_csv&type=expenses&<?= h($qs) ?>">CSV: expenses</a><a class="btn"
      href="actions.php?action=export_csv&type=collections&<?= h($qs) ?>">CSV: collections</a>
  </form>
  <div class="print-title"><?= APP_NAME ?> financial report – generated
    <?= date('d M Y H:i') ?>  <?= $f['from'] || $f['to'] ? ' · ' . fmt_date($f['from']) . ' to ' . fmt_date($f['to']) : '' ?>
  </div>
  <div class="stats">
    <?= stat_card('Total confirmed income', money($t['income']), 'green') ?>  <?= stat_card('Total approved expenses', money($t['expenses']), 'red') ?>  <?= stat_card('Available balance', money($t['balance']), 'blue') ?>
  </div>
  <p class="muted small">Available balance = approved payments − approved expenses. Unpaid, pending and rejected payments
    are not counted as income.</p>
  <div class="card">
    <h2>Collection &amp; event summaries</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Collection</th>
            <th>Type</th>
            <th>Deadline</th>
            <th class="num">Contribution</th>
            <th class="num">Paid</th>
            <th class="num">Pending</th>
            <th class="num">Confirmed</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($perf as $c): ?>
            <tr>
              <td><?= h($c['title']) ?></td>
              <td><?= kind_badge($c['kind']) ?></td>
              <td><?= fmt_date($c['deadline']) ?></td>
              <td class="num"><?= money($c['amount']) ?></td>
              <td class="num"><?= $c['paid_count'] ?>/<?= $c['students'] ?></td>
              <td class="num"><?= $c['pending_count'] ?></td>
              <td class="num"><?= money($c['collected']) ?></td>
            </tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="grid2">
    <div class="card">
      <h2>Expense breakdown</h2>
      <table>
        <thead>
          <tr>
            <th>Category</th>
            <th class="num">Items</th>
            <th class="num">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($br as $b): ?>
            <tr>
              <td><?= h($b['category']) ?></td>
              <td class="num"><?= $b['items'] ?></td>
              <td class="num"><?= money($b['total']) ?></td>
            </tr><?php endforeach;
          if (!$br): ?>
            <tr>
              <td colspan="3" class="muted">No approved expenses.</td>
            </tr><?php endif; ?>
        </tbody>
      </table>
      <?php if ($br): ?><canvas class="chart"
          data-chart='<?= h(json_encode(['type' => 'doughnut', 'labels' => array_column($br, 'category'), 'series' => [['name' => '', 'data' => array_map('floatval', array_column($br, 'total'))]]])) ?>'></canvas><?php endif; ?>
    </div>
    <div class="card">
      <h2>Payment status summary</h2>
      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th class="num">Payments</th>
            <th class="num">Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (['receipt_submitted', 'under_verification', 'approved', 'rejected'] as $s): ?>
            <tr>
              <td><?= status_badge($s) ?></td>
              <td class="num"><?= (int) ($ps[$s]['n'] ?? 0) ?></td>
              <td class="num"><?= money($ps[$s]['total'] ?? 0) ?></td>
            </tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php }


function page_reminders(array $u): void
{
  $cols = rows("SELECT collection_id, title, kind, deadline FROM collections WHERE status = 'open' ORDER BY deadline");
  $students = rows("SELECT s.student_id, s.reg_no, u.full_name FROM students s JOIN users u ON u.user_id = s.user_id WHERE s.status = 'active' ORDER BY s.reg_no");
  ?>
  <div class="grid2">
    <div class="card">
      <h2>Individual reminder</h2>
      <form method="post" action="actions.php" class="form" data-validate><?= csrf_field() ?><?= ret() ?><input
          type="hidden" name="action" value="send_reminder">
        <label>Student<select name="student_id" required>
            <option value="">Select…</option><?php foreach ($students as $s): ?>
              <option value="<?= $s['student_id'] ?>"><?= h($s['reg_no'] . ' – ' . $s['full_name']) ?></option>
            <?php endforeach; ?>
          </select></label>
        <label>Collection<select name="collection_id" required>
            <option value="">Select…</option><?php foreach ($cols as $c): ?>
              <option value="<?= $c['collection_id'] ?>"><?= h($c['title']) ?> (due <?= fmt_date($c['deadline']) ?>)
              </option><?php endforeach; ?>
          </select></label>
        <label>Type<select name="mode">
            <option value="unpaid">Payment reminder</option>
            <option value="overdue">Overdue notice</option>
            <option value="deadline">Deadline notification</option>
          </select></label>
        <button class="btn btn-primary" type="submit">Send email</button>
      </form>
    </div>
    <div class="card">
      <h2>Bulk reminders</h2>
      <form method="post" action="actions.php" class="form" data-validate><?= csrf_field() ?><?= ret() ?><input
          type="hidden" name="action" value="send_bulk_reminders">
        <label>Collection<select name="collection_id">
            <option value="">All open collections</option><?php foreach ($cols as $c): ?>
              <option value="<?= $c['collection_id'] ?>"><?= h($c['title']) ?></option><?php endforeach; ?>
          </select></label>
        <label>Send to<select name="mode">
            <option value="unpaid">All unpaid students</option>
            <option value="overdue">Overdue students only</option>
            <option value="deadline">Deadline approaching</option>
          </select></label>
        <label>"Approaching" means within (days)<input type="number" name="days" min="1" max="30" value="7"></label>
        <button class="btn btn-primary" type="submit"
          data-confirm-btn="Send reminder emails to all matching students now?">Send bulk reminders</button>
      </form>
    </div>
  </div>
<?php }


function page_announcements(array $u): void
{
  $list = rows('SELECT a.*, cr.full_name AS author, up.full_name AS editor FROM announcements a LEFT JOIN users cr ON cr.user_id = a.created_by LEFT JOIN users up ON up.user_id = a.updated_by ORDER BY a.publish_date DESC, a.announcement_id DESC'); ?>
  <div class="toolbar"><span></span><button class="btn btn-primary" data-dialog="dlgAnn" type="button">+ New
      announcement</button></div>
  <?php foreach ($list as $a): ?>
    <div class="card">
      <div class="flex-between">
        <h2><?= h($a['title']) ?>     <?= status_badge($a['status']) ?></h2><small><?= fmt_date($a['publish_date']) ?></small>
      </div>
      <p class="pre"><?= h($a['body']) ?></p>
      <?= credit([['Posted by', $a['author']], ['Last changed by', $a['editor'], $a['editor'] ? $a['updated_at'] : null]]) ?>
      <div class="btn-row"><button class="btn btn-sm" type="button"
          data-dialog="dlgEditAnn<?= $a['announcement_id'] ?>">Edit</button>
        <?= $a['status'] === 'draft' ? post_form('publish_announcement', 'Publish', ['announcement_id' => $a['announcement_id']], 'btn btn-sm btn-primary', 'Publish and notify all students?') : post_form('unpublish_announcement', 'Unpublish', ['announcement_id' => $a['announcement_id']]) ?>
        <?= post_form('delete_announcement', 'Delete', ['announcement_id' => $a['announcement_id']], 'btn btn-sm btn-danger', 'Delete this announcement?') ?>
      </div>
    </div>
    <dialog id="dlgEditAnn<?= $a['announcement_id'] ?>">
      <form method="post" action="actions.php" class="form" data-validate>
        <h2>Edit announcement</h2><?= csrf_field() ?><?= ret() ?>
        <input type="hidden" name="action" value="update_announcement"><input type="hidden" name="announcement_id"
          value="<?= $a['announcement_id'] ?>">
        <label>Title<input name="title" required minlength="3" value="<?= h($a['title']) ?>"></label><label>Text<textarea
            name="body" rows="6" required minlength="5"><?= h($a['body']) ?></textarea></label>
        <label>Announcement date<input type="date" name="publish_date" required
            value="<?= h($a['publish_date']) ?>"></label>
        <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn btn-primary"
            type="submit">Save</button></div>
      </form>
    </dialog>
  <?php endforeach;
  if (!$list)
    echo '<div class="card muted">No announcements yet.</div>'; ?>
  <dialog id="dlgAnn">
    <form method="post" action="actions.php" class="form" data-validate>
      <h2>New announcement</h2><?= csrf_field() ?><?= ret() ?><input type="hidden" name="action"
        value="create_announcement">
      <label>Title<input name="title" required minlength="3" maxlength="150"></label><label>Text<textarea name="body"
          rows="6" required minlength="5"></textarea></label>
      <label>Announcement date<input type="date" name="publish_date" required value="<?= date('Y-m-d') ?>"></label>
      <div class="dlg-actions"><button class="btn" type="button" data-close>Cancel</button><button class="btn"
          type="submit" name="publish" value="0">Save as draft</button><button class="btn btn-primary" type="submit"
          name="publish" value="1">Publish now</button></div>
    </form>
  </dialog>
<?php }


function page_notifications(array $u): void
{
  $list = rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, notification_id DESC LIMIT 100', [$u['user_id']]); ?>
  <div class="grid2">
    <div class="card">
      <h2>Send a notification to all students</h2>
      <form method="post" action="actions.php" class="form" data-validate><?= csrf_field() ?><?= ret() ?><input
          type="hidden" name="action" value="send_notification">
        <label>Message<textarea name="message" rows="3" required minlength="5" maxlength="255"></textarea></label><button
          class="btn btn-primary" type="submit">Send</button>
      </form>
    </div>
    <div class="card">
      <h2>Your notifications</h2>
      <div class="btn-row">
        <?= post_form('mark_notifications_read', 'Mark all read') ?>  <?= post_form('clear_notifications', 'Clear read', [], 'btn btn-sm', 'Delete all read notifications?') ?>
      </div>
      <ul class="notes"><?php foreach ($list as $n): ?>
          <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
            <?= h($n['message']) ?><small><?= fmt_dt($n['created_at']) ?></small></li>
        <?php endforeach;
      if (!$list)
        echo '<li class="muted">Nothing yet.</li>'; ?>
      </ul>
    </div>
  </div>
<?php }


function page_logs(array $u): void
{
  $emails = rows('SELECT l.*, usr.full_name FROM email_logs l LEFT JOIN students s ON s.student_id = l.student_id LEFT JOIN users usr ON usr.user_id = s.user_id ORDER BY l.sent_at DESC, l.log_id DESC LIMIT 200');
  $audit = rows('SELECT a.*, u.full_name FROM audit_logs a LEFT JOIN users u ON u.user_id = a.user_id ORDER BY a.created_at DESC, a.log_id DESC LIMIT 200'); ?>
  <div class="card">
    <h2>Email log</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>When</th>
            <th>Recipient</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Error</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($emails as $l): ?>
            <tr>
              <td><?= fmt_dt($l['sent_at']) ?></td>
              <td><?= h($l['full_name'] ?? '') ?><br><small><?= h($l['recipient_email']) ?></small></td>
              <td><?= h($l['subject']) ?></td>
              <td><?= status_badge($l['status']) ?></td>
              <td><small><?= h($l['error_message']) ?></small></td>
            </tr>
          <?php endforeach;
          if (!$emails)
            echo '<tr><td colspan="5" class="muted center">No emails sent yet.</td></tr>'; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <h2>Audit log (latest 200)</h2><input type="search" placeholder="Search audit log…" data-filter-table="auditTable">
    <div class="table-wrap">
      <table id="auditTable">
        <thead>
          <tr>
            <th>When</th>
            <th>User</th>
            <th>Action</th>
            <th>Entity</th>
            <th>Details</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($audit as $a): ?>
            <tr>
              <td><?= fmt_dt($a['created_at']) ?></td>
              <td><?= h($a['full_name'] ?? 'system') ?></td>
              <td><?= h($a['action']) ?></td>
              <td><?= h($a['entity'] . ($a['entity_id'] ? ' #' . $a['entity_id'] : '')) ?></td>
              <td><small><?= h($a['details']) ?></small></td>
              <td><small><?= h($a['ip_address']) ?></small></td>
            </tr><?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php }

function page_account(array $u): void
{
  account_page('coordinator.php?page=account');
}