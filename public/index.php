<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
// settings.php opsional; kita pakai helper lokal di bawah
include __DIR__ . '/_header.php';

if (!function_exists('e')) {
  function e($s)
  {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  }
}

/* ==== helper setting (read) ==== */
function arc_table_has(mysqli $db, string $table): bool
{
  $t = $db->real_escape_string($table);
  $r = $db->query("SHOW TABLES LIKE '{$t}'");
  return $r && $r->num_rows > 0;
}
function arc_table_has_col(mysqli $db, string $table, string $col): bool
{
  $t = $db->real_escape_string($table);
  $c = $db->real_escape_string($col);
  $q = "SELECT COUNT(*) c FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$t}' AND COLUMN_NAME='{$c}'";
  $r = $db->query($q);
  $row = $r ? $r->fetch_assoc() : null;
  return (int) ($row['c'] ?? 0) > 0;
}
function arc_setting_get(mysqli $db, string $key, string $default = ''): string
{
  $row = db_one($db, "SELECT `value` FROM app_settings WHERE `key`=?", [$key], 's');
  if ($row && $row['value'] !== null && $row['value'] !== '')
    return (string) $row['value'];

  if (arc_table_has($db, 'settings')) {
    if (arc_table_has_col($db, 'settings', 'key') && arc_table_has_col($db, 'settings', 'value')) {
      $row = db_one($db, "SELECT `value` FROM settings WHERE `key`=?", [$key], 's');
      if ($row && $row['value'] !== '')
        return (string) $row['value'];
    } elseif (arc_table_has_col($db, 'settings', 'name') && arc_table_has_col($db, 'settings', 'value')) {
      $row = db_one($db, "SELECT `value` FROM settings WHERE `name`=?", [$key], 's');
      if ($row && $row['value'] !== '')
        return (string) $row['value'];
    }
  }
  return $default;
}

/* ==== normalisasi URL ikon + cache buster ==== */
function arc_norm_icon(string $u): string
{
  $u = trim($u);
  if ($u === '')
    return '';
  if (preg_match('~^https?://~i', $u))
    return $u;
  $u = preg_replace('~//+~', '/', $u);

  // jika simpanan berupa 'uploads/...'
  if (preg_match('~^/?uploads/~', $u)) {
    $base = dirname($_SERVER['SCRIPT_NAME'] ?? '/arcadia/public'); // => /arcadia/public
    $u = rtrim($base, '/') . '/' . ltrim($u, '/');
  }
  $u = preg_replace('~^/arcadia/public/(?:public/)+~', '/arcadia/public/', $u);

  // map web->fs utk versi
  $fs = null;
  if (strpos($u, '/arcadia/public/') === 0) {
    $fs = __DIR__ . '/' . ltrim(substr($u, strlen('/arcadia/public/')), '/');
  } elseif (strpos($u, '/arcadia/') === 0) {
    $fs = dirname(__DIR__) . '/' . ltrim(substr($u, strlen('/arcadia/')), '/');
  }
  if ($fs && is_file($fs))
    $u .= '?v=' . filemtime($fs);
  return $u;
}

/* =========================
   Data utama
========================= */
$activeGenre = trim($_GET['genre'] ?? '');
$params = [];
$types = '';

$sqlGames = "SELECT id,title,genre,platform,image_url,
             IFNULL(cover_focus_x,50) AS cover_focus_x,
             IFNULL(cover_focus_y,50) AS cover_focus_y,
             LEFT(description,140) AS excerpt
             FROM games";
if ($activeGenre !== '') {
  $sqlGames .= " WHERE genre=?";
  $params[] = $activeGenre;
  $types .= 's';
}
$sqlGames .= " ORDER BY title ASC";
$games = db_all($mysqli, $sqlGames, $params, $types);

$featured = db_all($mysqli, "
  SELECT w.id, w.title, w.difficulty, LEFT(w.overview,150) AS excerpt,
         g.title AS game, g.id AS game_id, g.image_url AS game_image,
         IFNULL(g.cover_focus_x,50) AS g_fx, IFNULL(g.cover_focus_y,50) AS g_fy
  FROM walkthroughs w
  JOIN games g ON g.id = w.game_id
  ORDER BY w.id DESC
  LIMIT 2
");

$suggGames = db_all($mysqli, "SELECT title FROM games ORDER BY id DESC LIMIT 20");
$suggWalks = db_all($mysqli, "SELECT title FROM walkthroughs ORDER BY id DESC LIMIT 20");
$_tmpS = [];
foreach (array_merge($suggWalks, $suggGames) as $r) {
  $t = trim((string) ($r['title'] ?? ''));
  if ($t !== '' && !isset($_tmpS[mb_strtolower($t)]))
    $_tmpS[mb_strtolower($t)] = $t;
}
$suggestions = array_slice(array_values($_tmpS), 0, 30);

$hasUpdated = db_one($mysqli, "SELECT COUNT(*) n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='walkthroughs' AND COLUMN_NAME='updated_at'");
$hasCreated = db_one($mysqli, "SELECT COUNT(*) n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='walkthroughs' AND COLUMN_NAME='created_at'");
$useUpdated = (int) ($hasUpdated['n'] ?? 0) > 0;
$useCreated = (int) ($hasCreated['n'] ?? 0) > 0;

if ($useUpdated) {
  $recentWalks = db_all($mysqli, "SELECT w.id, w.title, w.difficulty,
    COALESCE(w.updated_at,w.created_at) AS updated_at,
    LEFT(COALESCE(w.overview,''),140) AS preview,
    g.id AS game_id, g.title AS game_title, g.image_url AS game_image
    FROM walkthroughs w JOIN games g ON g.id=w.game_id
    ORDER BY COALESCE(w.updated_at,w.created_at) DESC LIMIT 6");
} elseif ($useCreated) {
  $recentWalks = db_all($mysqli, "SELECT w.id, w.title, w.difficulty,
    w.created_at AS updated_at,
    LEFT(COALESCE(w.overview,''),140) AS preview,
    g.id AS game_id, g.title AS game_title, g.image_url AS game_image
    FROM walkthroughs w JOIN games g ON g.id=w.game_id
    ORDER BY w.created_at DESC LIMIT 6");
} else {
  $recentWalks = db_all($mysqli, "SELECT w.id, w.title, w.difficulty,
    w.id AS updated_at, LEFT(COALESCE(w.overview,''),140) AS preview,
    g.id AS game_id, g.title AS game_title, g.image_url AS game_image
    FROM walkthroughs w JOIN games g ON g.id=w.game_id
    ORDER BY w.id DESC LIMIT 6");
}

function diff_cls($d)
{
  $d = strtolower(trim($d));
  return $d === 'easy' ? 'easy' : ($d === 'hard' ? 'hard' : 'medium');
}

/* =========================
   Settings hero & ikon section
========================= */
$hero_title = arc_setting_get($mysqli, 'hero_title', 'Arcadia');
$hero_sub = arc_setting_get($mysqli, 'hero_subtitle', 'Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.');

$logo_games = arc_norm_icon(arc_setting_get($mysqli, 'logo_section_games', ''));
$logo_feat = arc_norm_icon(arc_setting_get($mysqli, 'logo_section_featured', ''));
$logo_recent = arc_norm_icon(arc_setting_get($mysqli, 'logo_section_recent', ''));
?>
<style>
  .grid-upd {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr))
  }

  @media (max-width:920px) {
    .grid-upd {
      grid-template-columns: 1fr
    }
  }

  .upd-item {
    display: grid;
    grid-template-columns: 120px 1fr;
    gap: 12px;
    padding: 12px;
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    border: 1px solid rgba(255, 255, 255, .06)
  }

  .upd-thumb {
    width: 120px;
    height: 84px;
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255, 255, 255, .04);
    display: grid;
    place-items: center
  }

  .upd-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover
  }

  .upd-fallback {
    font-weight: 800;
    font-size: 1.4rem;
    opacity: .9
  }

  .upd-title {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px
  }

  .upd-title a {
    color: inherit;
    text-decoration: none;
    font-weight: 700
  }

  .upd-title a:hover {
    text-decoration: underline
  }

  .link-lite {
    color: inherit;
    text-decoration: none
  }

  .link-lite:hover {
    text-decoration: underline
  }

  .clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.6
  }

  .btn.ghost.btn-open {
    background: transparent;
    border: 1px solid rgba(167, 139, 250, .35);
    color: #e9e9ff;
    border-radius: 12px;
    padding: .55rem 1rem;
    transition: background .18s, border-color .18s, box-shadow .18s, transform .14s
  }

  .btn.ghost.btn-open:hover,
  .btn.ghost.btn-open:focus-visible {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #0f0f16;
    box-shadow: 0 8px 22px var(--ring);
    transform: translateY(-1px)
  }

  .btn.ghost.btn-open:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px var(--ring)
  }

  .cover-frame {
    position: relative;
    border-radius: 12px;
    overflow: hidden
  }



  .title-logo {
    height: 22px;
    width: auto;
    vertical-align: -4px;
    margin-right: 8px;
    border-radius: 6px;
    border: 1px solid rgba(255, 255, 255, .08)
  }

  /* Perbesar ikon judul section & hilangkan kotaknya */
  .title-logo {
    height: 1.35em;
    /* sedikit lebih tinggi dari teks judul */
    width: auto;
    vertical-align: -0.2em;
    /* sejajarkan dengan teks */
    margin-right: .5rem;
    border: 0 !important;
    /* hilangkan “kotak” */
    border-radius: 0 !important;
    /* pastikan tidak membulat */
    background: transparent !important;
    box-shadow: none !important;
    outline: none !important;
  }

  /* Biar teks & ikon rapi satu garis */
  .section h1,
  .section h2 {
    display: flex;
    align-items: center;
    gap: .5rem;
  }

  /* ===== Kotak cover PERSEGI khusus section Daftar Game ===== */
  #games{ --game-thumb: 102px; }   /* samakan dengan ukuran kotak + di bawah */


  /* samakan dengan ukuran kotak “+” kamu */

  #games .game-thumbbox {
    width: var(--game-thumb);
    height: var(--game-thumb);
    /* persegi 1:1 */
    padding: 0;
    /* tanpa jarak: gambar nempel kotak */
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, .12);
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    overflow: hidden;
    /* potong gambar sesuai radius */
    display: grid;
    place-items: center;
  }

  #games .game-thumbbox .cover-frame {
    width: 100%;
    height: 100%;
    border-radius: inherit;
    overflow: hidden;
    background: none;
  }

  #games .game-thumbbox img,
  #games .game-thumbbox .placeholder-thumb.big {
    width: 100%;
    height: 100%;
    object-fit: cover;
    /* pas isi kotak */
    border-radius: 0;
  }

  /* opsional: kecilkan di layar sempit */
  @media (max-width: 520px) {
    #games {
      --game-thumb: 96px;
    }
  }
</style>

<!-- (1) Hero -->
<div class="hero">
  <div class="hero-title"><?= e($hero_title) ?></div>
  <div class="hero-sub"><?= e($hero_sub) ?></div>
  <form method="get" action="search.php" class="hero-search">
    <input class="input" type="text" name="q" placeholder="Cari judul game atau walkthrough…">
    <button class="btn btn-search" aria-label="Cari"><span>Cari Panduan</span></button>
  </form>
  <datalist id="q_suggest">
    <?php foreach ($suggestions as $s): ?>
      <option value="<?= e($s) ?>"></option><?php endforeach; ?>
  </datalist>
  <script>(() => { const f = document.querySelector('.hero-search input[name="q"]'); if (f && !f.getAttribute('list')) f.setAttribute('list', 'q_suggest') })();</script>
</div>

<!-- (2) Daftar Game -->
<section id="games" class="section card">
  <h1><?php if ($logo_games): ?><img class="title-logo" src="<?= e($logo_games) ?>" alt="" loading="lazy"
        decoding="async" onerror="this.remove()"><?php endif; ?>Daftar Game</h1>
  <p class="small">Pilih game untuk melihat walkthrough.</p>

  <?php $gamesLimited = array_slice($games, 0, 4);
  $needFill = max(0, 4 - count($gamesLimited)); ?>
  <div class="games-grid">
    <?php foreach ($gamesLimited as $g): ?>
      <div class="game-item v2">
        <div class="game-thumbbox">
  <div class="cover-frame">
    <?php if (!empty($g['image_url'])): ?>
      <?php $fx=(int)($g['cover_focus_x'] ?? 50); $fy=(int)($g['cover_focus_y'] ?? 50); ?>
      <img src="<?= e($g['image_url']) ?>" alt="<?= e($g['title']) ?>"
           style="object-fit:cover;object-position:<?= $fx ?>% <?= $fy ?>%;">
    <?php else: ?>
      <div class="placeholder-thumb big" data-initial="<?= e(mb_strtoupper(mb_substr($g['title'],0,1))) ?>"></div>
    <?php endif; ?>
  </div>
</div>



        <div class="game-meta">
          <div class="title-row">
            <h3><?= e($g['title']) ?></h3>
          </div>
          <div class="meta-row small"><?= e($g['platform']) ?></div>
          <p class="desc clamp-2"><?= e($g['excerpt']) ?>…</p>
        </div>

        <?php $detailUrl = '/arcadia/public/game.php?id=' . $g['id'];
        if (!is_user_logged_in()) {
          $detailUrl = '/arcadia/public/auth/login.php?next=' . urlencode($detailUrl);
        } ?>
        <div class="game-actions"><a class="btn ghost" href="<?= e($detailUrl) ?>">Lihat Detail</a></div>
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
        <div class="game-actions"><a class="btn" href="/arcadia/public/admin/games.php">+ Tambah Game</a></div>
      </div>
    <?php endfor; ?>
  </div>

  <div class="more-wrap"><a class="btn btn-pill btn-more-logout"
      href="/arcadia/public/games.php"><span>Lainnya</span></a></div>
</section>

<!-- (3) Panduan Unggulan -->
<section class="section card">
  <h2 style="margin-bottom:.25rem"><?php if ($logo_feat): ?><img class="title-logo" src="<?= e($logo_feat) ?>" alt=""
        loading="lazy" decoding="async" onerror="this.remove()"><?php endif; ?>Panduan Unggulan</h2>
  <p class="small" style="margin:.15rem 0 1rem">Pilihan terbaru/terbaik dari Arcadia.</p>

  <?php $feat4 = array_slice($featured ?? [], 0, 6); ?>
  <?php if (!$feat4): ?>
    <p class="small">Belum ada data.</p>
  <?php else: ?>
    <div id="feat4" class="feat4-wrap">
      <div class="feat4-viewport">
        <div class="feat4-track">
          <?php foreach ($feat4 as $f):
            $initial = mb_strtoupper(mb_substr($f['game'], 0, 1)); ?>
            <article class="feat4-card">
              <div class="feat4-thumb cover-frame">
                <?php if (!empty($f['game_image'])): ?>
                  <?php $fx = (int) ($f['g_fx'] ?? 50);
                  $fy = (int) ($f['g_fy'] ?? 50); ?>
                  <img class="cover-adjustable" data-table="games" data-id="<?= (int) $f['game_id'] ?>"
                    src="<?= e($f['game_image']) ?>" alt="<?= e($f['game']) ?>"
                    style="width:100%;height:100%;object-fit:cover;object-position:<?= $fx ?>% <?= $fy ?>%">
                <?php else: ?>
                  <div class="feat4-fallback"><?= e($initial) ?></div><?php endif; ?>
              </div>

              <div class="feat4-body">
                <div class="feat4-titleRow">
                  <h3><?= e($f['title']) ?></h3>
                  <span class="badge diff <?= diff_cls($f['difficulty']) ?>"><?= e($f['difficulty']) ?></span>
                </div>
                <div class="feat4-meta">🎮 <a href="game.php?id=<?= (int) $f['game_id'] ?>" class="small"
                    style="color:inherit;text-decoration:none"><?= e($f['game']) ?></a></div>
                <p class="feat4-desc clamp-2"><?= e(mb_strimwidth($f['excerpt'] ?? '', 0, 160, '…', 'UTF-8')) ?></p>
                <?php $openUrl = '/arcadia/public/walkthrough.php?id=' . (int) $f['id'];
                if (!is_user_logged_in()) {
                  $openUrl = '/arcadia/public/auth/login.php?next=' . urlencode($openUrl);
                } ?>
                <div class="feat4-actions"><a class="btn" href="<?= e($openUrl) ?>">Buka Panduan</a></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- (4) Baru Diupdate ✨ -->
<section class="section card">
  <h2 style="margin-bottom:.25rem"><?php if ($logo_recent): ?><img class="title-logo" src="<?= e($logo_recent) ?>"
        alt="" loading="lazy" decoding="async" onerror="this.remove()"><?php endif; ?>Baru Diupdate ✨</h2>
  <p class="small" style="margin:.15rem 0 1rem">Walkthrough yang baru dibuat/diubah.</p>

  <?php if (!$recentWalks): ?>
    <p class="small">Belum ada pembaruan.</p>
  <?php else: ?>
    <div class="grid-upd">
      <?php foreach ($recentWalks as $rw):
        $u = date('d M Y • H:i', strtotime($rw['updated_at']));
        $badge = diff_cls($rw['difficulty']);
        $openUrl = '/arcadia/public/walkthrough.php?id=' . (int) $rw['id'];
        if (!is_user_logged_in()) {
          $openUrl = '/arcadia/public/auth/login.php?next=' . urlencode($openUrl);
        }
        $initial = mb_strtoupper(mb_substr($rw['game_title'], 0, 1));
        ?>
        <article class="upd-item">
          <div class="upd-thumb cover-frame">
            <?php if (!empty($rw['game_image'])): ?>
              <img class="cover-adjustable" data-table="games" data-id="<?= (int) $rw['game_id'] ?>"
                src="<?= e($rw['game_image']) ?>" alt="<?= e($rw['game_title']) ?>"
                style="width:100%;height:100%;object-fit:cover;object-position:50% 50%">
            <?php else: ?>
              <div class="upd-fallback"><?= e($initial) ?></div><?php endif; ?>
          </div>
          <div>
            <div class="upd-title"><a href="<?= e($openUrl) ?>"><?= e($rw['title']) ?></a>
              <span class="badge diff <?= e($badge) ?>" style="margin-left:8px"><?= e($rw['difficulty']) ?></span>
            </div>
            <?php if (trim((string) ($rw['preview'] ?? '')) !== ''): ?>
              <div class="clamp-2 small"><?= e($rw['preview']) ?>…</div>
            <?php endif; ?>
            <div class="small"
              style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:10px">
              <span>Diupdate <?= e($u) ?></span>
              <a class="btn ghost btn-open" href="<?= e($openUrl) ?>">Buka</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_footer.php'; ?>

<script>
  // Ripple spotlight untuk tombol "Lainnya"
  document.addEventListener('pointermove', e => {
    document.querySelectorAll('.btn-more-logout').forEach(b => {
      const r = b.getBoundingClientRect();
      b.style.setProperty('--x', (e.clientX - r.left) + 'px');
      b.style.setProperty('--y', (e.clientY - r.top) + 'px');
    });
  }, { passive: true });
</script>