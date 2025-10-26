<?php
// /arcadia/public/comment_add.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php'; // session start
require_once __DIR__ . '/../lib/auth.php';
require_user_login('/arcadia/public/');

require_once __DIR__ . '/../lib/csrf.php';      // setelah session aktif

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /arcadia/public/index.php');
  exit;
}

// CSRF: memeriksa $_POST['_csrf'] vs $_SESSION['_csrf']
csrf_verify();

$user    = current_user();
$game_id = (int)($_POST['game_id'] ?? 0);
$body    = trim($_POST['body'] ?? '');

if ($game_id <= 0 || $body === '') {
  flash('error', 'Komentar tidak boleh kosong.');
  header('Location: /arcadia/public/game.php?id=' . $game_id . '#komentar');
  exit;
}
if (mb_strlen($body) > 1000) {
  flash('error', 'Komentar terlalu panjang (maks 1000 karakter).');
  header('Location: /arcadia/public/game.php?id=' . $game_id . '#komentar');
  exit;
}

// Pastikan game ada
$game = db_one($mysqli, "SELECT id FROM games WHERE id=?", [$game_id], "i");
if (!$game) {
  flash('error', 'Game tidak ditemukan.');
  header('Location: /arcadia/public/index.php');
  exit;
}

// Simpan komentar (ganti nama tabel ke `game_comments` bila kamu pakai itu)
db_exec(
  $mysqli,
  "INSERT INTO comments (game_id, user_id, body, status) VALUES (?,?,?, 'PUBLISHED')",
  [$game_id, (int)$user['id'], $body],
  "iis"
);

flash('ok', 'Komentar terkirim!');
header('Location: /arcadia/public/game.php?id=' . $game_id . '#komentar');
exit;
