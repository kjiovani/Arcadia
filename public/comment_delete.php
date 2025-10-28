<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/auth.php';
require_user_login('/arcadia/public/');
require_once __DIR__ . '/../lib/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location:/arcadia/public/');
  exit;
}
csrf_verify();

$me = current_user();
$uid = (int) ($me['id'] ?? 0);
$isAdmin = function_exists('is_admin') && is_admin();

$id = (int) ($_POST['id'] ?? 0);
$game_id = (int) ($_POST['game_id'] ?? 0);

if (!$id || !$game_id) {
  flash('error', 'Data tidak lengkap.');
  header("Location: game.php?id=$game_id#komentar");
  exit;
}

$row = db_one($mysqli, "SELECT user_id FROM comments WHERE id=? AND game_id=?", [$id, $game_id], "ii");
if (!$row) {
  flash('error', 'Komentar tidak ditemukan.');
  header("Location: game.php?id=$game_id#komentar");
  exit;
}

if (!$isAdmin && (int) $row['user_id'] !== $uid) {
  flash('error', 'Tidak diizinkan menghapus komentar ini.');
  header("Location: game.php?id=$game_id#komentar");
  exit;
}

// Soft delete
db_exec($mysqli, "UPDATE comments SET status='HIDDEN', updated_at=NOW() WHERE id=?", [$id], "i");

flash('ok', 'Komentar dihapus.');
header("Location: /arcadia/public/game.php?id=$game_id#komentar");
exit;
