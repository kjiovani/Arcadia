<?php
// /arcadia/public/index.php (tanpa komentar beranda)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php'; // hanya guard user (bukan auth admin)
include __DIR__ . '/_header.php';

/* ==== DATA BERANDA ==== */
$activeGenre = trim($_GET['genre'] ?? '');
$params = [];
$types  = '';

$sqlGames = "SELECT id,title,genre,platform,image_url,LEFT(description,140) AS excerpt FROM games";
if ($activeGenre !== '') {
  $sqlGames .= " WHERE genre=?";
  $params[] = $activeGenre;
  $types   .= 's';
}
$sqlGames .= " ORDER BY title ASC";
$games = db_all($mysqli, $sqlGames, $params, $types);

/* Panduan unggulan */
$featured = db_all($mysqli, "
  SELECT w.id, w.title, w.difficulty, LEFT(w.overview,150) AS excerpt,
         g.title AS game, g.id AS game_id, g.image_url AS game_image
  FROM walkthroughs w
  JOIN games g ON g.id = w.game_id
  ORDER BY w.id DESC
  LIMIT 2
");

/* ====== TAMBAHAN: data pendukung ====== */
/* Suggestions untuk datalist (gabungan judul terbaru) */
$suggGames = db_all($mysqli, "SELECT title FROM games ORDER BY id DESC LIMIT 20");
$suggWalks = db_all($mysqli, "SELECT title FROM walkthroughs ORDER BY id DESC LIMIT 20");
$_tmpS = [];
foreach (array_merge($suggWalks, $suggGames) as $r) {
  $t = trim((string)($r['title'] ?? ''));
  if ($t !== '' && !isset($_tmpS[mb_strtolower($t)])) {
    $_tmpS[mb_strtolower($t)] = $t;
  }
}
$suggestions = array_slice(array_values($_tmpS), 0, 30);

/* Baru Diupdate: fallback cerdas sesuai kolom yang tersedia (updated_at / created_at / id) */
$hasUpdated = db_one(
  $mysqli,
  "SELECT COUNT(*) AS n
     FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'walkthroughs'
      AND COLUMN_NAME = 'updated_at'"
);
$hasCreated = db_one(
  $mysqli,
  "SELECT COUNT(*) AS n
     FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'walkthroughs'
      AND COLUMN_NAME = 'created_at'"
);

$useUpdated = (int)($hasUpdated['n'] ?? 0) > 0;
$useCreated = (int)($hasCreated['n'] ?? 0) > 0;

if ($useUpdated) {
  $recentWalks = db_all(
    $mysqli,
    "SELECT w.id, w.title, w.difficulty,
            COALESCE(w.updated_at, w.created_at) AS updated_at,
            LEFT(COALESCE(w.overview,''),140) AS preview,
            g.id AS game_id, g.title AS game_title, g.image_url AS game_image
       FROM walkthroughs w
       JOIN games g ON g.id = w.game_id
      ORDER BY COALESCE(w.updated_at, w.created_at) DESC
      LIMIT 6"
  );
} elseif ($useCreated) {
  $recentWalks = db_all(
    $mysqli,
    "SELECT w.id, w.title, w.difficulty,
            w.created_at AS updated_at,
            LEFT(COALESCE(w.overview,''),140) AS preview,
            g.id AS game_id, g.title AS game_title, g.image_url AS game_image
       FROM walkthroughs w
       JOIN games g ON g.id = w.game_id
      ORDER BY w.created_at DESC
      LIMIT 6"
  );
} else {
  // Tidak ada kolom waktu -> pakai id DESC dan alias-kan sebagai 'updated_at' untuk ditampilkan
  $recentWalks = db_all(
    $mysqli,
    "SELECT w.id, w.title, w.difficulty,
            w.id AS updated_at,
            LEFT(COALESCE(w.overview,''),140) AS preview,
            g.id AS game_id, g.title AS game_title, g.image_url AS game_image
       FROM walkthroughs w
       JOIN games g ON g.id = w.game_id
      ORDER BY w.id DESC
      LIMIT 6"
  );
}
?>

<!-- ====== TAMBAHAN: gaya kecil untuk blok 'Baru Diupdate' ====== -->
<style>
  .grid-upd{display:grid;gap:14px;grid-template-columns:repeat(2,minmax(0,1fr))}
  @media (max-width:920px){.grid-upd{grid-template-columns:1fr}}
  .upd-item{
    display:grid;grid-template-columns:120px 1fr;gap:12px;padding:12px;border-radius:14px;
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.06);
  }
  .upd-thumb{width:120px;height:84px;border-radius:10px;overflow:hidden;background:rgba(255,255,255,.04);display:grid;place-items:center}
  .upd-thumb img{width:100%;height:100%;object-fit:cover}
  .upd-fallback{font-weight:800;font-size:1.4rem;opacity:.9}
  .upd-title{display:flex;align-items:center;gap:8px;margin-bottom:2px}
  .upd-title a{color:inherit;text-decoration:none;font-weight:700}
  .upd-title a:hover{text-decoration:underline}
  .link-lite{color:inherit;text-decoration:none}
  .link-lite:hover{text-decoration:underline}
  /* biar preview nyaman & maksimal 2 baris */
  .clamp-2{
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    line-height:1.6;
  }
</style>

<!-- (1) Arcadia / Hero -->
<div class="hero">
  <div class="hero-title">Arcadia</div>
  <div class="hero-sub">Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.</div>
  <form method="get" action="search.php" class="hero-search">
    <input class="input" type="text" name="q" placeholder="Cari judul game atau walkthrough…">
    <button class="btn btn-search" aria-label="Cari"><span>Cari Panduan</span></button>
  </form>

  <!-- Datalist suggestions untuk input search (tanpa mengubah baris asli input) -->
  <datalist id="q_suggest">
    <?php foreach ($suggestions as $s): ?>
      <option value="<?= e($s) ?>"></option>
    <?php endforeach; ?>
  </datalist>
  <script>
    (function(){
      const f=document.querySelector('.hero-search input[name="q"]');
      if(f && !f.getAttribute('list')) f.setAttribute('list','q_suggest');
    })();
  </script>
</div>

<!-- (2) Daftar Game — satu kotak berisi 4 item -->
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

<!-- (3) Panduan Unggulan -->
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
                  🎮 <a href="game.php?id=<?= (int) $f['game_id'] ?>" class="small"
                    style="color:inherit;text-decoration:none">
                    <?= e($f['game']) ?>
                  </a>
                </div>
                <p class="feat4-desc clamp-2">
                  <?= e(mb_strimwidth($f['excerpt'] ?? '', 0, 160, '…', 'UTF-8')) ?>
                </p>
                <?php
                $openUrl = '/arcadia/public/walkthrough.php?id=' . (int) $f['id'];
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

<!-- ====== TAMBAHAN: Baru Diupdate ✨ (RAPI) ====== -->
<style>
  .upd-grid{
    display:grid; gap:16px;
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
  @media (max-width:920px){ .upd-grid{ grid-template-columns:1fr } }

  .upd-card{
    display:flex; flex-direction:column; gap:10px;
    padding:14px; border-radius:16px;
    background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.06);
    min-height:168px;
  }

  /* Header: avatar + judul + badge */
  .upd-head{ display:flex; align-items:center; gap:12px; }
  .upd-ava{
    width:56px; height:56px; border-radius:12px;
    background:rgba(255,255,255,.05);
    display:grid; place-items:center; font-weight:800; font-size:1.1rem;
    border:1px solid rgba(255,255,255,.08);
    flex:0 0 56px;
  }
  .upd-titleWrap{ display:flex; align-items:center; gap:10px; flex-wrap:wrap }
  .upd-title{
    margin:0; font-weight:800; font-size:1.05rem;
  }
  .upd-title a{ color:inherit; text-decoration:none }
  .upd-title a:hover{ text-decoration:underline }

  /* Meta + preview */
  .upd-meta{ margin-top:2px }
  .upd-preview{
    margin-top:6px; opacity:.95;
    display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; line-height:1.6;
  }

  /* Footer */
  .upd-foot{
    margin-top:auto; display:flex; align-items:center; justify-content:space-between; gap:10px;
  }
  .upd-date{ opacity:.85; font-size:.95rem }

  /* Tombol "Buka" — default outline, hover ungu seperti "Lihat Detail" */
  .btn.ghost.btn-open{
    background: transparent;
    border: 1px solid rgba(167,139,250,.35);
    color: #e9e9ff;
    border-radius: 12px;
    padding: .55rem 1rem;
    transition: background .18s ease, border-color .18s ease, box-shadow .18s ease, transform .14s ease;
  }

  /* Hover & fokus: jadikan ungu seperti tombol "Lihat Detail" */
  .btn.ghost.btn-open:hover,
  .btn.ghost.btn-open:focus-visible{
    background: var(--primary) !important;           /* paksa ungu */
    border-color: var(--primary) !important;
    color: #0f0f16;                                   /* teks gelap biar kontras */
    box-shadow: 0 8px 22px var(--ring);               /* glow ungu sama */
    transform: translateY(-1px);
  }

  /* State aktif supaya terasa klik */
  .btn.ghost.btn-open:active{
    transform: translateY(0);
    box-shadow: 0 4px 12px var(--ring);
  }
</style>

<section class="section card">
  <h2 style="margin-bottom:.25rem">Baru Diupdate ✨</h2>
  <p class="small" style="margin:.15rem 0 1rem">Walkthrough yang baru dibuat/diubah.</p>

  <?php if (!$recentWalks): ?>
    <p class="small">Belum ada pembaruan.</p>
  <?php else: ?>
    <div class="upd-grid">
      <?php foreach ($recentWalks as $rw):
        $u = date('d M Y • H:i', strtotime($rw['updated_at']));
        $badge = diff_cls($rw['difficulty']);
        $openUrl = '/arcadia/public/walkthrough.php?id=' . (int)$rw['id'];
        if (!is_user_logged_in()) {
          $openUrl = '/arcadia/public/auth/login.php?next=' . urlencode($openUrl);
        }
        $initial = mb_strtoupper(mb_substr($rw['game_title'], 0, 1));
      ?>
        <article class="upd-card">
          <!-- Head -->
          <div class="upd-head">
            <div class="upd-ava" aria-hidden="true"><?= e($initial) ?></div>
            <div style="min-width:0">
              <div class="upd-titleWrap">
                <h3 class="upd-title"><a href="<?= e($openUrl) ?>"><?= e($rw['title']) ?></a></h3>
                <span class="badge diff <?= e($badge) ?>"><?= e($rw['difficulty']) ?></span>
              </div>
              <div class="small upd-meta">🎮
                <a class="link-lite" href="/arcadia/public/game.php?id=<?= (int)$rw['game_id'] ?>">
                  <?= e($rw['game_title']) ?>
                </a>
              </div>
            </div>
          </div>

          <!-- Preview -->
          <?php if (trim((string)($rw['preview'] ?? '')) !== ''): ?>
            <div class="upd-preview small"><?= e($rw['preview']) ?>…</div>
          <?php endif; ?>

          <!-- Footer -->
          <div class="upd-foot">
            <div class="upd-date small">Diupdate <?= e($u) ?></div>
            <!-- tombol disamakan dengan "Lihat Detail" -->
            <a class="btn ghost btn-open" href="<?= e($openUrl) ?>">Buka</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
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
  }, { passive: true });
</script>
