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
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
    $email = required(str_trim($_POST['email'] ?? ''), 'Email');
    $pass = required(str_trim($_POST['password'] ?? ''), 'Password');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new Exception('Format email tidak valid.');
    if (mb_strlen($pass) < 6)
      throw new Exception('Password minimal 6 karakter.');

    $exists = db_one($mysqli, "SELECT id FROM users WHERE email=?", [$email], 's');
    if ($exists)
      throw new Exception('Email sudah terdaftar.');

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    db_exec($mysqli, "INSERT INTO users(name,email,password_hash) VALUES(?,?,?)", [$name, $email, $hash], 'sss');

    // Auto-login
    $uid = (int) mysqli_insert_id($mysqli);
    $_SESSION['user'] = ['id' => $uid, 'name' => $name, 'email' => $email];

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
  <h1>Daftar Akun</h1>
  <?php if (!empty($err)): ?>
    <div class="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="grid">
    <?php csrf_field(); ?>
    <input type="hidden" name="next" value="<?= e($next) ?>">
    <label>Nama<input class="input" name="name" required></label>
    <label>Email<input class="input" type="email" name="email" required></label>
    <label>Password<input class="input" type="password" name="password" required></label>
    <button class="btn">Daftar</button>
    <div class="small">Sudah punya akun? <a
        href="/arcadia/public/auth/login.php<?= $next ? ('?next=' . urlencode($next)) : '' ?>">Masuk</a></div>
  </form>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>