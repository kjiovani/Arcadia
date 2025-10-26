<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth_user.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify();
    $email = required(str_trim($_POST['email'] ?? ''), 'Email');
    $pass = required(str_trim($_POST['password'] ?? ''), 'Password');
    $row = db_one($mysqli, "SELECT * FROM users WHERE email=?", [$email], 's');
    if (!$row || !password_verify($pass, $row['password_hash'])) {
      throw new Exception('Email atau password tidak cocok.');
    }

    db_exec($mysqli, "UPDATE users SET last_login_at=NOW() WHERE id=?", [$row['id']], 'i');
    $_SESSION['user'] = ['id' => $row['id'], 'name' => $row['name'], 'email' => $row['email']];

    $next = $_POST['next'] ?? '';
    header('Location: ' . ($next ?: '/arcadia/public/index.php'));
    exit;
  } catch (Exception $e) {
    $err = $e->getMessage();
  }
}

$next = $_GET['next'] ?? '';
include __DIR__ . '/../_header.php';
?>
<div class="container card" style="max-width:560px">
  <h1>Masuk</h1>
  <?php if (!empty($err)): ?>
    <div class="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="grid">
    <?php csrf_field(); ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Email<input class="input" type="email" name="email" required></label>
    <label>Password<input class="input" type="password" name="password" required></label>
    <button class="btn">Masuk</button>
    <div class="small">Belum punya akun? <a
        href="/arcadia/public/auth/register.php<?= $next ? ('?next=' . urlencode($next)) : '' ?>">Daftar</a></div>
  </form>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>