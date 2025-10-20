<?php
require_once __DIR__.'/_header.php';
require_once __DIR__.'/../../lib/db.php';

$stats = [
  'games' => db_one($mysqli,"SELECT COUNT(*) c FROM games")['c'] ?? 0,
  'walks' => db_one($mysqli,"SELECT COUNT(*) c FROM walkthroughs")['c'] ?? 0,
  'chapters' => db_one($mysqli,"SELECT COUNT(*) c FROM chapters")['c'] ?? 0,
];

echo '<div class="card"><h1>Dashboard</h1><p class="small">Ringkasan data.</p></div>';
echo '<div class="grid cols-2">';
echo '<div class="card"><h2>Games</h2><div style="font-size:2rem;font-weight:700">'.$stats['games'].'</div><a class="btn" href="games.php">Kelola</a></div>';
echo '<div class="card"><h2>Walkthroughs</h2><div style="font-size:2rem;font-weight:700">'.$stats['walks'].'</div><a class="btn" href="walkthroughs.php">Kelola</a></div>';
echo '<div class="card"><h2>Chapters</h2><div style="font-size:2rem;font-weight:700">'.$stats['chapters'].'</div><a class="btn" href="chapters.php">Kelola</a></div>';
echo '</div>';

require_once __DIR__.'/_footer.php';
?>
