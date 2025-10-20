<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';
include __DIR__.'/_header.php';

$id = (int)($_GET['id'] ?? 0);
$w = db_one($mysqli,"SELECT w.*, g.title AS game_title FROM walkthroughs w JOIN games g ON g.id=w.game_id WHERE w.id=?",[ $id ],"i");
if(!$w){ echo '<div class="card">Walkthrough tidak ditemukan.</div>'; include __DIR__.'/_footer.php'; exit; }

echo '<div class="card">';
echo '<div class="small"><a href="game.php?id='.$w['game_id'].'">← '.e($w['game_title']).'</a></div>';
echo '<h1>'.e($w['title']).' <span class="badge">'.e($w['difficulty']).'</span></h1>';
if(!empty($w['overview'])) echo '<p>'.nl2br(e($w['overview'])).'</p>';
echo '</div>';

$chapters = db_all($mysqli,"SELECT * FROM chapters WHERE walk_id=? ORDER BY order_number ASC",[ $id ],"i");
echo '<div class="card"><h2>Chapters</h2>';
if(!$chapters){ echo '<p class="small">Belum ada chapter.</p>'; }
foreach($chapters as $c){
  echo '<div class="card" style="margin:.5rem 0">';
  echo '<h3>'.e($c['order_number']).'. '.e($c['title']).'</h3>';
  echo '<p>'.nl2br(e($c['content'])).'</p>';
  echo '</div>';
}
echo '</div>';

include __DIR__.'/_footer.php';
?>
