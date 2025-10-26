<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validation.php';
require_once __DIR__ . '/../lib/auth_user.php'; // ✅ hanya guard user (BUKAN auth.php)
include __DIR__ . '/_header.php';

/* ==== HANDLE KOMENTAR (POST) + CRUD ==== */
if (!isset($_SESSION))
  session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  try {
    csrf_verify();

    // Honeypot anti-bot
    if (!empty($_POST['website']))
      throw new Exception('Spam terdeteksi.');

    if ($act === 'comment_create') {
      $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
      $content = required(str_trim($_POST['content'] ?? ''), 'Komentar');
      if (mb_strlen($name) > 80)
        throw new Exception('Nama terlalu panjang.');
      if (mb_strlen($content) > 2000)
        throw new Exception('Komentar terlalu panjang.');

      $ip = $_SERVER['REMOTE_ADDR'] ?? null;
      db_exec($mysqli, "INSERT INTO comments(name,content,ip) VALUES(?,?,?)", [$name, $content, $ip], 'sss');

      // tandai komentar milik session ini
      $newId = mysqli_insert_id($mysqli);
      $_SESSION['my_comments'] = $_SESSION['my_comments'] ?? [];
      $_SESSION['my_comments'][$newId] = true;

      flash('ok', 'Komentarmu terkirim! 🙌');
      redirect('index.php#comments');
    }

    if ($act === 'comment_update') {
      $id = positive_int($_POST['id'] ?? 0, 'ID');
      $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
      $content = required(str_trim($_POST['content'] ?? ''), 'Komentar');
      if (empty($_SESSION['my_comments'][$id]))
        throw new Exception('Tidak diizinkan mengubah komentar ini.');
      if (mb_strlen($name) > 80)
        throw new Exception('Nama terlalu panjang.');
      if (mb_strlen($content) > 2000)
        throw new Exception('Komentar terlalu panjang.');

      db_exec($mysqli, "UPDATE comments SET name=?, content=? WHERE id=?", [$name, $content, $id], 'ssi');
      flash('ok', 'Komentar diperbarui.');
      redirect('index.php#comments');
    }

    if ($act === 'comment_delete') {
      $id = positive_int($_POST['id'] ?? 0, 'ID');
      if (empty($_SESSION['my_comments'][$id]))
        throw new Exception('Tidak diizinkan menghapus komentar ini.');
      db_exec($mysqli, "DELETE FROM comments WHERE id=?", [$id], 'i');
      unset($_SESSION['my_comments'][$id]);
      flash('ok', 'Komentar dihapus.');
      redirect('index.php#comments');
    }

  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('index.php#comments');
  }
}

/* bila sedang edit, prefill form */
$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId && !empty($_SESSION['my_comments'][$editId])) {
  $editRow = db_one($mysqli, "SELECT id,name,content FROM comments WHERE id=?", [$editId], 'i');
}

/* ==== DATA BERANDA ==== */
$activeGenre = trim($_GET['genre'] ?? '');
$params = [];
$types = '';
$sqlGames = "SELECT id,title,genre,platform,image_url,LEFT(description,140) AS excerpt FROM games";
if ($activeGenre !== '') {
  $sqlGames .= " WHERE genre=?";
  $params[] = $activeGenre;
  $types .= 's';
}
$sqlGames .= " ORDER BY title ASC";
$games = db_all($mysqli, $sqlGames, $params, $types);

$featured = db_all($mysqli, "
  SELECT w.id, w.title, w.difficulty, LEFT(w.overview,150) AS excerpt,
         g.title AS game, g.id AS game_id, g.image_url AS game_image
  FROM walkthroughs w
  JOIN games g ON g.id = w.game_id
  ORDER BY w.id DESC
  LIMIT 2
");

/* Ambil komentar terbaru (10) */
$comments = db_all($mysqli, "SELECT id,name,content,created_at FROM comments ORDER BY id DESC LIMIT 10");
?>

<!-- (1) Arcadia / Hero -->
<div class="hero">
  <div class="hero-title">Arcadia</div>
  <div class="hero-sub">Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.</div>
  <form method="get" action="search.php" class="hero-search">
    <input class="input" type="text" name="q" placeholder="Cari judul game atau walkthrough…">
    <button class="btn btn-search" aria-label="Cari"><span>Cari Panduan</span></button>
  </form>
</div>

<!-- (2) Daftar Game — satu kotak berisi 4 item (tanpa Genre) -->
<section id="games" class="section card">
  <h1>Daftar Game</h1>
  <p class="small">Pilih game untuk melihat walkthrough.</p>

  <?php
  $gamesLimited = array_slice($games, 0, 4);
  $needFill = max(0, 4 - count($gamesLimited));
  ?>
  <div class="games-grid">
    <?php foreach ($gamesLimited as $g): ?>
      <div class="game-item v2">
        <?php if (!empty($g['image_url'])): ?>
          <img class="game-thumb big" src="<?= e($g['image_url']) ?>" alt="">
        <?php else: ?>
          <div class="placeholder-thumb big" data-initial="<?= e(mb_strtoupper(mb_substr($g['title'], 0, 1))) ?>"></div>
        <?php endif; ?>

        <div class="game-meta">
          <div class="title-row">
            <h3><?= e($g['title']) ?></h3>
          </div>
          <div class="meta-row small"><?= e($g['platform']) ?></div>
          <p class="desc clamp-2"><?= e($g['excerpt']) ?>…</p>
        </div>

        <?php
        // ✅ pakai URL absolut + cek login user
        $detailUrl = '/arcadia/public/game.php?id=' . $g['id'];
        if (!is_user_logged_in()) {
          $detailUrl = '/arcadia/public/auth/login.php?next=' . urlencode($detailUrl);
        }
        ?>
        <div class="game-actions">
          <a class="btn ghost" href="<?= e($detailUrl) ?>">Lihat Detail</a>
        </div>

      </div>
    <?php endforeach; ?>

    <?php for ($i = 0; $i < $needFill; $i++): ?>
      <div class="game-item v2 placeholder">
        <div class="placeholder-thumb big" data-initial="+"></div>
        <div class="game-meta">
          <div class="title-row">
            <h3>Tambahkan Game</h3>
          </div>
          <p class="small">Belum ada data di slot ini. Isi dari panel Admin.</p>
        </div>
        <div class="game-actions">
          <a class="btn" href="/arcadia/public/admin/games.php">+ Tambah Game</a>
        </div>
      </div>
    <?php endfor; ?>
  </div>

  <div class="more-wrap">
  <a class="btn btn-pill btn-more-logout" href="/arcadia/public/games.php">
    <span>Lainnya</span>
  </a>
</div>



</section>

<!-- (3) Panduan Unggulan — Carousel -->
<section class="section card">
  <h2 style="margin-bottom:.25rem">Panduan Unggulan</h2>
  <p class="small" style="margin:.15rem 0 1rem">Pilihan terbaru/terbaik dari Arcadia.</p>

  <?php
  $feat4 = array_slice($featured ?? [], 0, 6);
  function diff_cls($d)
  {
    $d = strtolower(trim($d));
    return $d === 'easy' ? 'easy' : ($d === 'hard' ? 'hard' : 'medium');
  }
  ?>

  <?php if (!$feat4): ?>
    <p class="small">Belum ada data.</p>
  <?php else: ?>
    <div id="feat4" class="feat4-wrap">
      <div class="feat4-viewport">
        <div class="feat4-track">
          <?php foreach ($feat4 as $f):
            $initial = mb_strtoupper(mb_substr($f['game'], 0, 1)); ?>
            <article class="feat4-card">
              <div class="feat4-thumb">
                <?php if (!empty($f['game_image'])): ?>
                  <img src="<?= e($f['game_image']) ?>" alt="">
                <?php else: ?>
                  <div class="feat4-fallback"><?= e($initial) ?></div>
                <?php endif; ?>
              </div>

              <div class="feat4-body">
                <div class="feat4-titleRow">
                  <h3><?= e($f['title']) ?></h3>
                  <span class="badge diff <?= diff_cls($f['difficulty']) ?>"><?= e($f['difficulty']) ?></span>
                </div>
                <div class="feat4-meta">
                  🎮 <a href="game.php?id=<?= $f['game_id'] ?>" class="small" style="color:inherit;text-decoration:none">
                    <?= e($f['game']) ?>
                  </a>
                </div>
                <p class="feat4-desc clamp-2">
                  <?= e(mb_strimwidth($f['excerpt'] ?? '', 0, 160, '…', 'UTF-8')) ?>
                </p>
                <?php
                // ✅ pakai URL absolut + cek login user
                $openUrl = '/arcadia/public/walkthrough.php?id=' . $f['id'];
                if (!is_user_logged_in()) {
                  $openUrl = '/arcadia/public/auth/login.php?next=' . urlencode($openUrl);
                }
                ?>
                <div class="feat4-actions">
                  <a class="btn" href="<?= e($openUrl) ?>">Buka Panduan</a>
                </div>

              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- (5) Tentang — versi v2 -->
<section id="about" class="section card about--loose">
  <header class="about-head">
    <span class="pretitle">Tentang</span>
    <h2>Arcadia — Guide hub rapi, cepat, dan enak dibaca.</h2>
    <p class="muted">
      Struktur konten <strong>Game → Walkthrough → Chapter</strong> bikin navigasi jelas, nyaman di mata, dan mudah
      dicari.
    </p>

    <div class="about-cta">
      <a class="btn btn-grad" href="search.php"><span>Jelajahi Panduan</span></a>
      <a class="btn btn-ghost" href="/arcadia/public/admin" target="_blank" rel="noopener"><span>Panel Admin</span></a>
    </div>
  </header>

  <div class="about-body">
    <div class="about-col">
      <ul class="feature-grid">
        <li>
          <div class="fi">N</div>
          <div>
            <h3>Navigasi terstruktur</h3>
            <p>Hierarki konsisten dari game sampai chapter, anti nyasar.</p>
          </div>
        </li>
        <li>
          <div class="fi">C</div>
          <div>
            <h3>Chapter ringkas</h3>
            <p>Langkah fokus, minim spoiler, mudah di-scan.</p>
          </div>
        </li>
        <li>
          <div class="fi">S</div>
          <div>
            <h3>Pencarian cepat</h3>
            <p>Cari boss, shrine, atau tips dalam hitungan detik.</p>
          </div>
        </li>
        <li>
          <div class="fi">P</div>
          <div>
            <h3>Keamanan bawaan</h3>
            <p>Prepared statements + CSRF token pada form.</p>
          </div>
        </li>
      </ul>

      <div class="how">
        <div class="how-step"><span>1</span> Pilih game</div>
        <div class="how-step"><span>2</span> Buka walkthrough</div>
        <div class="how-step"><span>3</span> Ikuti chapter</div>
      </div>
    </div>

    <div class="about-col">
      <div class="stats">
        <div class="stat">
          <div class="n"><?= count($games) ?></div>
          <div class="l">Game</div>
        </div>
        <div class="stat">
          <div class="n"><?= (int) db_one($mysqli, "SELECT COUNT(*) c FROM walkthroughs")['c'] ?></div>
          <div class="l">Walkthrough</div>
        </div>
        <div class="stat">
          <div class="n"><?= (int) db_one($mysqli, "SELECT COUNT(*) c FROM comments")['c'] ?></div>
          <div class="l">Komentar</div>
        </div>
      </div>

      <div class="stack">
        <div class="stack-title">Teknologi</div>
        <div class="stack-chips">
          <span class="chip">PHP</span><span class="chip">HTML</span><span class="chip">CSS</span><span
            class="chip">JavaScript</span>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/_footer.php'; ?>

<script>
  // Ripple spotlight mengikuti kursor (halus)
  document.addEventListener('pointermove', e => {
    document.querySelectorAll('.btn-more-logout').forEach(b => {
      const r = b.getBoundingClientRect();
      b.style.setProperty('--x', (e.clientX - r.left) + 'px');
      b.style.setProperty('--y', (e.clientY - r.top) + 'px');
    });
  }, {passive:true});
</script>
