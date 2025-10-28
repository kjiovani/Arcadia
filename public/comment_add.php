<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/auth.php';
require_user_login('/arcadia/public/');
require_once __DIR__ . '/../lib/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: /arcadia/public/index.php');
  exit;
}

csrf_verify();

$user = current_user();
$game_id = (int) ($_POST['game_id'] ?? 0);
$body = (string) ($_POST['body'] ?? '');

// ========== NORMALISASI ==========
$body = str_replace(["\r\n", "\r"], "\n", $body);          // samakan newline
$body = preg_replace('/[ \t]+/', ' ', $body);             // kompres spasi
$body = preg_replace('/\n{3,}/', "\n\n", $body);          // batasi blank line

$lines = array_values(array_filter(array_map('trim', explode("\n", $body)), fn($s) => $s !== ''));
if ($lines) {
  $lineCount = count($lines);
  $shortCnt = 0;
  foreach ($lines as $ln)
    if (mb_strlen($ln) <= 2)
      $shortCnt++;

  // Tangga: minimal 3 baris dan >=80% baris sangat pendek
  if ($lineCount >= 3 && ($shortCnt / $lineCount) >= 0.8) {
    $body = preg_replace('/\s+/', ' ', implode(' ', $lines));   // "H J S"
  } else {
    $body = implode("\n", $lines);                               // paragraf normal
  }
}

// Fallback: jika tidak ada newline/spasi & hanya huruf (3–12) → sisipkan spasi antar huruf
if (strpos($body, "\n") === false && !preg_match('/\s/', $body) && preg_match('/^[A-Za-z]{3,12}$/u', $body)) {
  $body = implode(' ', preg_split('//u', $body, -1, PREG_SPLIT_NO_EMPTY));
}

$body = trim($body);

// ========== VALIDASI ==========
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

$game = db_one($mysqli, "SELECT id FROM games WHERE id=?", [$game_id], "i");
if (!$game) {
  flash('error', 'Game tidak ditemukan.');
  header('Location: /arcadia/public/index.php');
  exit;
}

// ========== SIMPAN ==========
db_exec(
  $mysqli,
  "INSERT INTO comments (game_id, user_id, body, status) VALUES (?,?,?, 'PUBLISHED')",
  [$game_id, (int) $user['id'], $body],
  "iis"
);

flash('ok', 'Komentar terkirim!');
header('Location: /arcadia/public/game.php?id=' . $game_id . '#komentar');
exit;
