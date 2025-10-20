<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
include __DIR__ . '/_header.php';

$q = trim($_GET['q'] ?? '');
echo '<div class="card"><h1>Cari</h1>
<form method="get" class="grid">
  <input class="input" type="text" name="q" placeholder="Judul game atau walkthrough..." value="' . e($q) . '"/>
  <button class="btn">Cari</button>
</form></div>';

$rows = [];
if ($q !== '') {
  $like = '%' . $q . '%';
  // log pencarian (sederhana)
  db_exec($mysqli, "INSERT INTO searchlogs(keyword, searched_at) VALUES(?, NOW())", [$q]);
  $rows = db_all(
    $mysqli, "
    SELECT 'game' AS type, id, title, NULL AS extra FROM games WHERE title LIKE ? 
    UNION ALL
    SELECT 'walk' AS type, id, title, difficulty AS extra FROM walkthroughs WHERE title LIKE ?
    LIMIT 50",
    [$like, $like]
  );
}
if ($q !== '') {
  echo '<div class="card"><h2>Hasil</h2>';
  if (!$rows) {
    echo '<p class="small">Tidak ditemukan.</p>';
  }
  foreach ($rows as $r) {
    if ($r['type'] === 'game') {
      echo '<div><a href="game.php?id=' . $r['id'] . '">🎮 ' . e($r['title']) . '</a></div>';
    } else {
      echo '<div><a href="walkthrough.php?id=' . $r['id'] . '">🧭 ' . e($r['title']) . '</a> <span class="badge">' . e($r['extra']) . '</span></div>';
    }
  }
  echo '</div>';
}


?>