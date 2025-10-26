<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
include __DIR__ . '/_header.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/auth.php';
require_user_login($_SERVER['REQUEST_URI']); // paksa login user

$id = (int) ($_GET['id'] ?? 0);
$game = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], "i");
if (!$game) {
  echo '<div class="card">Game tidak ditemukan.</div>';
  include __DIR__ . '/_footer.php';
  exit;
}

echo '<div class="card">';
echo '<h1>' . e($game['title']) . '</h1>';
echo '<div class="small">' . e($game['genre']) . ' • ' . e($game['platform']) . ' • Rilis ' . e($game['release_year']) . '</div>';
if (!empty($game['image_url']))
  echo '<img src="' . e($game['image_url']) . '" alt="" style="width:100%;max-height:300px;object-fit:cover;border-radius:12px;margin:.75rem 0" />';
echo '<p>' . nl2br(e($game['description'])) . '</p>';
echo '</div>';

$walks = db_all($mysqli, "SELECT id,title,overview,difficulty FROM walkthroughs WHERE game_id=? ORDER BY id DESC", [$id], "i");
echo '<div class="card"><h2>Walkthrough</h2>';
if (!$walks) {
  echo '<p class="small">Belum ada walkthrough.</p>';
}
foreach ($walks as $w) {
  echo '<div class="card" style="margin:.5rem 0">';
  echo '<h3>' . e($w['title']) . ' <span class="badge">' . e($w['difficulty']) . '</span></h3>';
  echo '<p>' . e(mb_strimwidth($w['overview'] ?? '', 0, 160, '…', 'UTF-8')) . '</p>';
  echo '<a class="btn" href="walkthrough.php?id=' . $w['id'] . '">Buka</a>';
  echo '</div>';
}
echo '</div>';

include __DIR__ . '/_footer.php';
?>