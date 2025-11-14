<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
include __DIR__ . '/_header.php';

$q = trim($_GET['q'] ?? '');
$activeGenre = trim($_GET['genre'] ?? '');

/* ------- Kategori game (genre) dari DB ------- */
$rawGenres = db_all(
  $mysqli,
  "SELECT DISTINCT genre FROM games
   WHERE genre IS NOT NULL AND TRIM(genre) <> ''
   ORDER BY genre ASC"
);

$genreNames = [];
foreach ($rawGenres as $g) {
  $genre = trim((string) ($g['genre'] ?? ''));
  if ($genre !== '' && !in_array($genre, $genreNames, true)) {
    $genreNames[] = $genre;
  }
}

/* fallback kalau belum ada data genre di DB */
if (!$genreNames) {
  $genreNames = ['Action', 'Adventure', 'RPG', 'Strategy', 'Puzzle', 'Horror'];
}

/* ------- Hot keywords (14 hari) ------- */
/* Masih dihitung, tapi tidak lagi ditampilkan di UI */
$hot = db_all(
  $mysqli,
  "SELECT keyword, COUNT(*) n
     FROM searchlogs
    WHERE searched_at >= NOW() - INTERVAL 14 DAY
      AND TRIM(keyword) <> ''
    GROUP BY keyword
    ORDER BY n DESC
    LIMIT 10"
);

/* ------- Datalist suggestions ------- */
$suggGames = db_all($mysqli, "SELECT title FROM games ORDER BY id DESC LIMIT 25");
$suggWalks = db_all($mysqli, "SELECT title FROM walkthroughs ORDER BY id DESC LIMIT 25");
$pool = [];
foreach (array_merge($suggWalks, $suggGames) as $r) {
  $t = trim((string) ($r['title'] ?? ''));
  if ($t !== '' && !isset($pool[mb_strtolower($t)]))
    $pool[mb_strtolower($t)] = $t;
}
$suggestions = array_slice(array_values($pool), 0, 40);

/* ------- Query hasil ------- */
$rows = [];
$hasFilter = ($q !== '' || $activeGenre !== '');

if ($hasFilter) {
  $like = $q !== '' ? '%' . $q . '%' : '%';

  // log pencarian hanya kalau ada kata kunci
  if ($q !== '') {
    db_exec($mysqli, "INSERT INTO searchlogs(keyword, searched_at) VALUES(?, NOW())", [$q]);
  }

  $sql = "
    SELECT 'game' AS type, g.id, g.title, g.platform AS extra, g.image_url AS img, NULL AS difficulty, LEFT(g.description,140) AS excerpt
      FROM games g
     WHERE g.title LIKE ?
  ";
  $params = [$like];

  if ($activeGenre !== '') {
    $sql .= " AND g.genre = ?";
    $params[] = $activeGenre;
  }

  $sql .= "
    UNION ALL
    SELECT 'walk' AS type, w.id, w.title, g.title AS extra, g.image_url AS img, w.difficulty, LEFT(w.overview,140) AS excerpt
      FROM walkthroughs w
      JOIN games g ON g.id = w.game_id
     WHERE w.title LIKE ?
  ";
  $params[] = $like;

  if ($activeGenre !== '') {
    $sql .= " AND g.genre = ?";
    $params[] = $activeGenre;
  }

  $sql .= "
    ORDER BY type ASC
    LIMIT 60
  ";

  $rows = db_all($mysqli, $sql, $params);
}

/* ------- Helper highlight (ringan) ------- */
function hl($text, $needle)
{
  if ($needle === '')
    return e($text);
  $re = '/' . preg_quote($needle, '/') . '/iu';
  return preg_replace_callback($re, fn($m) => '<mark class="hl">' . $m[0] . '</mark>', e($text));
}

/* ------- Kelas badge difficulty ------- */
function diff_cls($d)
{
  $d = strtolower(trim((string) $d));
  return $d === 'easy' ? 'easy' : ($d === 'hard' ? 'hard' : 'medium');
}
?>
<style>
  /* ====== Aesthetic for Search page ====== */
  .search-hero {
    margin: 10px auto 18px;
    padding: 22px 20px;
    border-radius: 18px;
    background: radial-gradient(120% 120% at 20% 0%, rgba(167, 139, 250, .15), transparent 60%),
      linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    border: 1px solid rgba(255, 255, 255, .08);
  }

  .search-heading {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 10px;
  }

  .search-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: grid;
    place-items: center;
    background: radial-gradient(120% 120% at 30% 20%, rgba(167, 139, 250, .45), rgba(217, 70, 239, .18));
    border: 1px solid rgba(255, 255, 255, .16);
    font-weight: 800
  }

  .search-heading h1 {
    margin: 0;
  }

  .search-sub {
    margin: .25rem 0 1rem;
    opacity: .9
  }

  .search-row {
    display: grid;
    grid-template-columns: 1fr 180px;
    gap: 10px;
  }

  @media (max-width:720px) {
    .search-row {
      grid-template-columns: 1fr
    }
  }

  .hotchips {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 14px
  }

  .chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: .42rem .7rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .06);
    border: 1px solid rgba(255, 255, 255, .12);
    text-decoration: none;
    color: inherit;
    font-size: .95rem;
    transition: background .15s ease, border-color .15s ease, transform .12s ease;
  }

  .chip:hover {
    background: rgba(255, 255, 255, .10);
    border-color: rgba(255, 255, 255, .18);
    transform: translateY(-1px)
  }

  .chip .tag {
    opacity: .8;
    font-size: .85rem
  }

  /* === Bar kategori game === */
  .genre-row {
    margin-top: 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
  }

  .genre-row .hotchips {
    margin-top: 0;
  }

  .genre-label {
    font-size: .9rem;
    opacity: .85;
  }

  .chip.genre-active {
    background: var(--primary);
    border-color: var(--primary);
    color: #0f0f16;
  }

  /* Hasil */
  .results {
    margin-top: 16px
  }

  .res-card {
    display: grid;
    grid-template-columns: 84px 1fr auto;
    gap: 12px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, .06);
    border-radius: 14px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
  }

  @media (max-width:680px) {
    .res-card {
      grid-template-columns: 68px 1fr
    }

    .res-go {
      grid-column: 1 / -1;
      justify-self: flex-end
    }
  }

  .res-thumb {
    width: 84px;
    height: 84px;
    border-radius: 10px;
    overflow: hidden;
    background: rgba(255, 255, 255, .04);
    display: grid;
    place-items: center;
    border: 1px solid rgba(255, 255, 255, .06);
  }

  .res-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover
  }

  .res-fallback {
    font-weight: 800;
    font-size: 1.2rem;
    opacity: .9
  }

  .res-title {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 4px
  }

  .res-title a {
    color: inherit;
    text-decoration: none;
    font-weight: 800
  }

  .res-title a:hover {
    text-decoration: underline
  }

  .res-meta {
    opacity: .9
  }

  .res-excerpt {
    margin-top: 6px;
    opacity: .95;
    line-height: 1.6
  }

  .res-go .btn.ghost {
    background: transparent;
    border: 1px solid rgba(167, 139, 250, .35);
    color: #e9e9ff;
    border-radius: 12px;
    padding: .55rem 1rem;
    transition: background .18s ease, border-color .18s ease, box-shadow .18s ease, transform .14s ease;
  }

  .res-go .btn.ghost:hover,
  .res-go .btn.ghost:focus-visible {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #0f0f16;
    box-shadow: 0 8px 22px var(--ring);
    transform: translateY(-1px);
  }

  /* highlight kecil */
  .hl {
    background: linear-gradient(0deg, rgba(167, 139, 250, .45), rgba(167, 139, 250, .45));
    color: #0f0f16;
    padding: 0 .15rem;
    border-radius: .25rem
  }
</style>

<div class="search-hero card">
  <div class="search-heading">
    <div class="search-icon">⌕</div>
    <h1>Cari</h1>
  </div>
  <p class="search-sub">Temukan game, walkthrough, atau chapter dengan cepat.</p>

  <form method="get" class="search-row" action="search.php">
    <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Judul game atau walkthrough…"
      list="q_suggest" autofocus>
    <button class="btn">Cari</button>
  </form>

  <!-- BAR KATEGORI GAME -->
  <div class="genre-row">
    <span class="genre-label small">Kategori game:</span>
    <div class="hotchips">
      <?php
      // link "Semua"
      $baseParams = [];
      if ($q !== '') {
        $baseParams['q'] = $q;
      }
      $hrefAll = '/arcadia/public/search.php';
      if ($baseParams) {
        $hrefAll .= '?' . http_build_query($baseParams);
      }
      ?>
      <a class="chip<?= $activeGenre === '' ? ' genre-active' : '' ?>" href="<?= e($hrefAll) ?>">Semua</a>

      <?php foreach ($genreNames as $genre):
        $params = ['genre' => $genre];
        if ($q !== '') {
          $params['q'] = $q;
        }
        $href = '/arcadia/public/search.php?' . http_build_query($params);
        ?>
        <a class="chip<?= $activeGenre === $genre ? ' genre-active' : '' ?>" href="<?= e($href) ?>">
          <?= e($genre) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Bar kata kunci populer DIHILANGKAN dari UI -->
</div>

<datalist id="q_suggest">
  <?php foreach ($suggestions as $s): ?>
    <option value="<?= e($s) ?>"></option>
  <?php endforeach; ?>
</datalist>

<div class="results">
  <?php if ($hasFilter): ?>
    <div class="card" style="padding:12px 14px;border-radius:12px;margin-bottom:10px;opacity:.9">
      Menampilkan hasil
      <?php if ($q !== ''): ?>
        untuk <strong>"<?= e($q) ?>"</strong>
      <?php endif; ?>
      <?php if ($activeGenre !== ''): ?>
        pada kategori <strong><?= e($activeGenre) ?></strong>
      <?php endif; ?>
      (<?= count($rows) ?>)
    </div>

    <?php if (!$rows): ?>
      <div class="card" style="padding:18px;border-radius:14px">
        Tidak ada hasil. Coba istilah lain atau telusuri <a href="/arcadia/public/games.php"
          style="color:inherit;text-decoration:underline">daftar game</a>.
      </div>
    <?php else: ?>
      <div class="grid" style="display:grid;gap:12px">
        <?php foreach ($rows as $r):
          $title = $r['title'];
          $extra = $r['extra'];
          $img = $r['img'];
          $diff = $r['difficulty'];
          $excerpt = $r['excerpt'] ?? '';
          $isGame = $r['type'] === 'game';
          $url = $isGame ? ('/arcadia/public/game.php?id=' . (int) $r['id']) : ('/arcadia/public/walkthrough.php?id=' . (int) $r['id']);
          $initial = mb_strtoupper(mb_substr($isGame ? $title : $extra, 0, 1));
          ?>
          <article class="res-card">
            <div class="res-thumb">
              <?php if (!empty($img)): ?>
                <img src="<?= e($img) ?>" alt="">
              <?php else: ?>
                <div class="res-fallback"><?= e($initial) ?></div>
              <?php endif; ?>
            </div>

            <div class="res-body" style="min-width:0">
              <div class="res-title">
                <a href="<?= e($url) ?>"><?= hl($title, $q) ?></a>
                <?php if (!$isGame): ?>
                  <span class="badge diff <?= diff_cls($diff) ?>"><?= e($diff) ?></span>
                <?php endif; ?>
              </div>
              <div class="res-meta small">
                <?= $isGame ? '🎮 Game · ' . e($extra) : '🎮 ' . e($extra) ?>
              </div>
              <?php if (trim($excerpt) !== ''): ?>
                <div class="res-excerpt small"><?= hl($excerpt, $q) ?>…</div>
              <?php endif; ?>
            </div>

            <div class="res-go">
              <a class="btn ghost" href="<?= e($url) ?>"><?= $isGame ? 'Lihat Detail' : 'Buka' ?></a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <!-- Empty state awal -->
    <div class="card" style="padding:18px;border-radius:14px;opacity:.92">
      Ketik judul pada kolom di atas, atau pilih kategori.
    </div>
  <?php endif; ?>
</div>
