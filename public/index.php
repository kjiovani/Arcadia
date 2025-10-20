<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';
include __DIR__.'/_header.php';

$games = db_all($mysqli,"SELECT id,title,genre,platform,image_url,LEFT(description,140) AS excerpt FROM games ORDER BY title ASC");
?>
<div class="hero">
  <div class="hero-title">Arcadia — Panduan Game yang Rapi & Terstruktur</div>
  <div class="hero-sub">Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.</div>
  <form method="get" action="search.php">
    <input class="input" type="text" name="q" placeholder="Cari judul game atau walkthrough…">
    <button class="btn">Cari Panduan</button>
  </form>
  <div class="small" style="margin-top:.75rem">Contoh: <span class="badge">Elden Ring</span> <span class="badge">Zelda</span> <span class="badge">Boss</span></div>
</div>
<div class="section card"><h1>Daftar Game</h1><p class="small">Pilih game untuk melihat walkthrough.</p></div>
<div class="grid cols-2">
<?php foreach($games as $g): ?>
  <div class="card game-card">
    <?php if(!empty($g['image_url'])): ?>
      <img src="<?= e($g['image_url']) ?>" alt="">
    <?php endif; ?>
    <h3><?= e($g['title']) ?></h3>
    <div class="small"><?= e($g['genre']) ?> • <?= e($g['platform']) ?></div>
    <p><?= e($g['excerpt']) ?>…</p>
    <a class="btn" href="game.php?id=<?= $g['id'] ?>">Lihat Detail</a>
  </div>
<?php endforeach; ?>
</div>
<?php include __DIR__.'/_footer.php'; ?>
