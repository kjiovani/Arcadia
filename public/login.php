<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/csrf.php';

$next = $_GET['next'] ?? '/arcadia/public/admin/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $email = trim($_POST['email'] ?? '');
  $pass = (string) ($_POST['password'] ?? '');
  if ($email === '') {
    flash('error', 'Email wajib diisi');
  } else {
    $u = db_one($mysqli, "SELECT * FROM users WHERE email=?", [$email]);
    if ($u && password_verify($pass, $u['password_hash'])) {
      $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
      redirect($next);
    } else {
      flash('error', 'Email atau password salah.');
    }
  }
}

include __DIR__ . '/_header.php';
echo '<div class="card"><h1>Login Admin</h1>';
if ($m = flash('error'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '<form method="post" class="grid">';
csrf_field();
echo '<label>Email<input class="input" type="email" name="email" required></label>';
echo '<label>Password<input class="input" type="password" name="password" required></label>';
echo '<button class="btn">Masuk</button>';
echo '</form>';
echo '<p class="small">Belum ada admin? <a href="setup.php">Buat admin default</a>.</p>';
echo '</div>';
include __DIR__ . '/_footer.php';
?>