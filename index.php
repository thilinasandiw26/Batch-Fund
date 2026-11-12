<?php
/** index.php – login page / entry point (Member 1) */
require_once __DIR__ . '/auth.php';
$u = current_user();
if ($u)
  redirect(home_for($u['role']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login – <?= APP_NAME ?></title>
  <link rel="stylesheet" href="style.css">
</head>

<body class="login-body">
  <div class="login-card">
    <div class="brand center"><span class="logo">₿F</span>
      <div><strong><?= APP_NAME ?></strong><small><?= h(BATCH_NAME) ?></small></div>
    </div>
    <?php foreach ($_SESSION['flash'] ?? [] as $f): ?>
      <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['msg']) ?></div>
    <?php endforeach;
    unset($_SESSION['flash']); ?>
    <form method="post" action="auth.php" class="form" data-validate>
      <?= csrf_field() ?><input type="hidden" name="action" value="login">
      <label>Email address<input type="email" name="email" required autofocus autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-primary btn-block" type="submit">Log in</button>
    </form>
    <p class="muted small center">Forgot your password? Ask the batch coordinator to reset it.</p>
  </div>
</body>

</html>