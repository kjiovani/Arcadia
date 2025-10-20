
<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../lib/db.php';
require_once __DIR__.'/../lib/helpers.php';
include __DIR__.'/_header.php';

/* Data untuk halaman */
$games = db_all($mysqli,"SELECT id,title,genre,platform,image_url,LEFT(description,140) AS excerpt FROM games ORDER BY title ASC");
$featured = db_all($mysqli,"
  SELECT w.id, w.title, w.difficulty, LEFT(w.overview,150) excerpt, g.title AS game, g.id AS game_id
  FROM walkthroughs w JOIN games g ON g.id=w.game_id
  ORDER BY w.id DESC LIMIT 4
");
?>

<!-- (2) Arcadia / Hero -->
<div class="hero">
  <div class="hero-title">Arcadia</div>
  <div class="hero-sub">Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.</div>
  <form method="get" action="search.php" class="hero-search">
    <input class="input" type="text" name="q" placeholder="Cari judul game atau walkthrough…">
    <button class="btn btn-search" aria-label="Cari"><span>Cari Panduan</span></button>
  </form>
</div>

<!-- (3) Panduan Unggulan -->
<section class="section card">
  <h2 style="margin-bottom:.25rem">Panduan Unggulan</h2>
  <p class="small" style="margin:.15rem 0 1rem">Pilihan terbaru/terbaik dari Arcadia.</p>
  <div class="grid cols-2">
    <?php if(!$featured): ?>
      <p class="small">Belum ada data.</p>
    <?php else: foreach($featured as $f): ?>
      <div class="card feature-card">
        <div class="feature-head">
          <span class="badge"><?= e($f['difficulty']) ?></span>
          <a class="small" href="game.php?id=<?= $f['game_id'] ?>">🎮 <?= e($f['game']) ?></a>
        </div>
        <h3 style="margin:.3rem 0 .35rem"><?= e($f['title']) ?></h3>
        <p><?= e(mb_strimwidth($f['excerpt'] ?? '',0,160,'…','UTF-8')) ?></p>
        <div><a class="btn" href="walkthrough.php?id=<?= $f['id'] ?>">Buka Panduan</a></div>
      </div>
    <?php endforeach; endif; ?>
  </div>
</section>

<!-- (4) Daftar Game — satu kotak berisi 4 item -->
<section id="games" class="section card">
  <h1>Daftar Game</h1>
  <p class="small">Pilih game untuk melihat walkthrough.</p>

  <?php
  // Selalu tampilkan 4 item; jika kurang, isi placeholder
  $gamesLimited = array_slice($games, 0, 4);
  $needFill = max(0, 4 - count($gamesLimited));
  ?>
  <div class="games-grid">
    <?php foreach($gamesLimited as $g): ?>
      <div class="game-item">
        <?php if(!empty($g['image_url'])): ?>
          <img class="game-thumb" src="<?= e($g['image_url']) ?>" alt="">
        <?php endif; ?>
        <div class="game-meta">
          <h3><?= e($g['title']) ?></h3>
          <div class="small"><?= e($g['genre']) ?> • <?= e($g['platform']) ?></div>
          <p><?= e($g['excerpt']) ?>…</p>
        </div>
        <div class="game-actions">
          <a class="btn" href="game.php?id=<?= $g['id'] ?>">Lihat Detail</a>
        </div>
      </div>
    <?php endforeach; ?>

    <?php for($i=0; $i<$needFill; $i++): ?>
      <div class="game-item placeholder">
        <div class="badge">Kosong</div>
        <div class="game-meta">
          <h3>Tambahkan Game</h3>
          <p class="small">Belum ada data di slot ini. Isi dari panel Admin.</p>
        </div>
        <div class="game-actions">
          <a class="btn" href="/arcadia/public/admin/games.php">+ Tambah Game</a>
        </div>
      </div>
    <?php endfor; ?>
  </div>
</section>

<!-- (5) Genre Games -->
<section id="genres" class="section card">
  <h2 style="margin-bottom:.25rem">Genre Games</h2>
  <p class="small" style="margin:.15rem 0 1rem">Pilih genre untuk melihat panduan terkait.</p>

  <div class="genre-grid">
    <?php
      // genre => [label, deskripsi]
      $genres = [
        'Action'        => ['Action',        'Pertarungan cepat, refleks & kombo.'],
        'Adventure'     => ['Adventure',     'Eksplorasi dunia, cerita & pemecahan masalah.'],
        'RPG'           => ['RPG',           'Leveling, build karakter, quest & loot.'],
        'Action RPG'    => ['Action RPG',    'Campuran aksi real-time & progres RPG.'],
        'Shooter'       => ['Shooter',       'FPS/TPS: aiming, recoil, & strategi tim.'],
        'Platformer'    => ['Platformer',    'Lompatan presisi, rintangan & timing.'],
        'Puzzle'        => ['Puzzle',        'Teka-teki logika & pola mekanik.'],
        'Strategy'      => ['Strategy',      'RTS/Turn-Based, taktik & resource.'],
        'Simulation'    => ['Simulation',    'Bangun/kelola kota, kehidupan, bisnis.'],
        'Sports'        => ['Sports',        'Olahraga kompetitif: bola, balap, dll.'],
        'Fighting'      => ['Fighting',      '1v1, frame data, chain & anti-air.'],
        'Horror'        => ['Horror',        'Survival & atmosfer menegangkan.'],
        'Open World'    => ['Open World',    'Eksplorasi bebas & misi non-linear.'],
        'Soulslike'     => ['Soulslike',     'Bos sulit & pola serangan ketat.'],
      ];
      foreach ($genres as $key => [$label, $desc]) {
        $q = urlencode($key);
        echo '<a class="genre-card" href="search.php?q='.$q.'">'.
               '<div class="genre-title">'.$label.'</div>'.
               '<div class="genre-desc small">'.$desc.'</div>'.
             '</a>';
      }
    ?>
  </div>
</section>

<!-- (6) Tentang -->
<section id="about" class="section card">
  <h2>Tentang Arcadia</h2>
  <p class="small">Arcadia adalah website panduan game yang fokus pada struktur konten yang rapi: game → walkthrough → chapter. Semua data disunting via panel Admin dengan keamanan dasar (prepared statements & CSRF).</p>
</section>

<?php include __DIR__.'/_footer.php'; // (6) Footer ?>
