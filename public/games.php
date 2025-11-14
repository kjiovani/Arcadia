<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/validation.php';
require_once __DIR__ . '/../lib/csrf.php';       // <<< penting untuk csrf_verify()
require_once __DIR__ . '/../lib/auth_user.php'; // <<< penting untuk is_user_logged_in()

$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

// Verifikasi CSRF pada semua POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

/* === Query filter & pencarian === */
$q        = trim($_GET['q'] ?? '');
$platform = trim($_GET['platform'] ?? '');
$genre    = trim($_GET['genre'] ?? '');

$params = [];
$types  = '';
$where  = [];

if ($q !== '') {
  $where[]  = "(title LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%'))";
  $params[] = $q;
  $params[] = $q;
  $types   .= 'ss';
}
if ($platform !== '') {
  $where[]  = "platform = ?";
  $params[] = $platform;
  $types   .= 's';
}
if ($genre !== '') {
  $where[]  = "genre = ?";
  $params[] = $genre;
  $types   .= 's';
}

/* ====== Pagination (6 game / halaman) ====== */
$perPage = 6;

// Hitung total game (sesuai filter)
$countSql = "SELECT COUNT(*) AS total FROM games";
if ($where) {
  $countSql .= " WHERE " . implode(" AND ", $where);
}
$row        = db_one($mysqli, $countSql, $params, $types);
$totalGames = (int)($row['total'] ?? 0);

$totalPages = max(1, (int)ceil($totalGames / $perPage));
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

/* Query list game (LIMIT + OFFSET) */
$listParams   = $params;
$listTypes    = $types . 'ii';
$listParams[] = $offset;
$listParams[] = $perPage;

$sql = "SELECT id,title,platform,genre,release_year,image_url,
               IFNULL(cover_focus_x,50) AS cover_focus_x,
               IFNULL(cover_focus_y,50) AS cover_focus_y,
               LEFT(description,150) AS excerpt
        FROM games";

if ($where) {
  $sql .= " WHERE " . implode(" AND ", $where);
}
$sql   .= " ORDER BY title ASC LIMIT ?, ?";
$games = db_all($mysqli, $sql, $listParams, $listTypes);

/* opsi: ambil daftar distinct untuk filter */
$platforms = db_all($mysqli, "SELECT DISTINCT platform FROM games WHERE platform<>'' ORDER BY platform");
$genres    = db_all($mysqli, "SELECT DISTINCT genre    FROM games WHERE genre<>''    ORDER BY genre");

include __DIR__ . '/_header.php';
?>
<style>
  /* ====== LAYOUT UMUM ====== */
  .page-hero {
    text-align: center;
    padding: 1.4rem 1rem .4rem;
  }

  .page-hero h1 {
    margin: 0;
    font-size: 2.1rem;
    letter-spacing: .4px;
  }

  .page-hero .sub {
    color: #bdb7d9;
    margin-top: .35rem;
  }

  .filters {
    display: grid;
    gap: .8rem;
    grid-template-columns: 1fr;
    margin: 1.2rem auto 1.4rem;
    max-width: 1040px;
  }

  @media (min-width:760px) {
    .filters {
      grid-template-columns: 1.4fr 1fr 1fr .8fr;
    }
  }

  .filters .input,
  .filters select,
  .filters .btn {
    width: 100%;
  }

  .grid {
    display: grid;
    gap: 1.2rem;
    grid-template-columns: repeat(1, 1fr);
    padding-bottom: .6rem;
  }

  @media (min-width:620px) {
    .grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }

  @media (min-width:1040px) {
    .grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  /* ====== KARTU GAME ====== */
  .card-game {
    position: relative;
    overflow: hidden;
    border-radius: 18px;
    background: rgba(255, 255, 255, .03);
    border: 1px solid rgba(255, 255, 255, .06);
    box-shadow: 0 1px 0 rgba(0, 0, 0, .25);
    transition:
      transform .28s cubic-bezier(.22, .61, .36, 1),
      box-shadow .28s cubic-bezier(.22, .61, .36, 1),
      border-color .28s cubic-bezier(.22, .61, .36, 1);
    animation: fadeUp .5s cubic-bezier(.22, .61, .36, 1) both;
  }

  .grid .card-game:nth-child(1) { animation-delay: .02s }
  .grid .card-game:nth-child(2) { animation-delay: .06s }
  .grid .card-game:nth-child(3) { animation-delay: .1s }
  .grid .card-game:nth-child(4) { animation-delay: .14s }
  .grid .card-game:nth-child(5) { animation-delay: .18s }
  .grid .card-game:nth-child(6) { animation-delay: .22s }

  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .card-game:hover {
    transform: translateY(-2px);
    box-shadow: 0 18px 48px rgba(139, 92, 246, .18);
    border-color: rgba(139, 92, 246, .26);
  }

  .thumb-wrap {
    position: relative;
    overflow: hidden;
  }

  .thumb {
    aspect-ratio: 16/9;
    width: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1.001);
    transition: transform .38s cubic-bezier(.22, .61, .36, 1);
  }

  .thumb-fallback {
    aspect-ratio: 16/9;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(180deg, #2b2146, #1a1825);
    font-size: 3.1rem;
    font-weight: 800;
    color: #cabffd;
  }

  .card-game:hover .thumb {
    transform: scale(1.045);
  }

  .thumb-vignette {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 55%, rgba(10, 8, 20, .65));
    pointer-events: none;
  }

  .card-body {
    padding: 1rem 1rem 1.05rem;
  }

  .meta {
    font-size: .9rem;
    color: #bdb7d9;
    margin: .15rem 0 .5rem
  }

  .desc {
    color: #d9d6ea;
    min-height: 2.9em
  }

  .actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: .7rem
  }

  .chip {
    font-size: .74rem;
    padding: .24rem .6rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .12)
  }

  .chip-soft {
    background: linear-gradient(180deg, rgba(255, 255, 255, .08), rgba(255, 255, 255, .03));
  }

  .card-game a.cover-link {
    position: absolute;
    inset: 0;
  }

  /* ====== ANIMASI TOMBOL ====== */
  .btn-fx {
    position: relative;
    overflow: hidden;
    will-change: transform, box-shadow, background-color, border-color;
    transition:
      transform .34s cubic-bezier(.16, .84, .44, 1),
      box-shadow .34s cubic-bezier(.16, .84, .44, 1),
      background-color .34s cubic-bezier(.16, .84, .44, 1),
      border-color .34s cubic-bezier(.16, .84, .44, 1),
      color .34s cubic-bezier(.16, .84, .44, 1);
    transform: translateZ(0);
  }

  .btn-fx:hover {
    transform: translateY(-1px) scale(1.012);
    box-shadow: 0 10px 26px rgba(139, 92, 246, .22);
  }

  .btn-fx:active {
    transition-duration: .18s;
    transform: translateY(0) scale(.992);
    box-shadow: 0 6px 16px rgba(139, 92, 246, .16);
  }

  .btn-fx::after {
    content: "";
    position: absolute;
    inset: -50%;
    border-radius: inherit;
    pointer-events: none;
    background:
      radial-gradient(160px 160px at var(--x, 50%) var(--y, 50%),
        rgba(255, 255, 255, .14), transparent 60%);
    opacity: 0;
    transition: opacity .40s cubic-bezier(.16, .84, .44, 1);
  }

  .btn-fx:hover::after {
    opacity: .55;
  }

  .btn-fx:focus-visible {
    outline: 2px solid rgba(180, 160, 255, .55);
    outline-offset: 2px;
    border-radius: 12px;
  }

  @media (prefers-reduced-motion: reduce) {
    .btn-fx {
      transition: none !important
    }

    .btn-fx:hover,
    .btn-fx:active {
      transform: none !important;
      box-shadow: none !important;
    }

    .btn-fx::after {
      display: none !important;
    }
  }

  :root {
    --violet-500: #8B5CF6;
    --violet-400: #A78BFA;
    --violet-300: #C4B5FD;
    --ink-dark: #0e0f1a;
  }

  /* Tombol TERAPKAN */
  .btn-apply {
    background: linear-gradient(180deg, var(--violet-400) 0%, var(--violet-500) 100%);
    border: 1px solid rgba(255, 255, 255, .08);
    color: var(--ink-dark);
    font-weight: 800;
    border-radius: 14px;
    transition:
      transform .34s cubic-bezier(.16, .84, .44, 1),
      box-shadow .34s cubic-bezier(.16, .84, .44, 1),
      background-color .34s cubic-bezier(.16, .84, .44, 1),
      background-position .5s cubic-bezier(.22, .61, .36, 1);
  }

  .btn-apply:hover {
    background: linear-gradient(180deg, var(--violet-300) 0%, var(--violet-400) 100%);
    box-shadow: 0 14px 32px rgba(139, 92, 246, .28);
    transform: translateY(-1px) scale(1.012);
  }

  .btn-apply:active {
    transform: translateY(0) scale(.985);
    box-shadow: 0 8px 18px rgba(139, 92, 246, .20);
  }

  /* Tombol LIHAT DETAIL */
  .btn-detail {
    border: 1px solid rgba(214, 197, 255, .22);
    background: rgba(255, 255, 255, .02);
    color: #e9e6ff;
    transition:
      transform .28s cubic-bezier(.16, .84, .44, 1),
      box-shadow .28s cubic-bezier(.16, .84, .44, 1),
      background-color .28s cubic-bezier(.16, .84, .44, 1),
      border-color .28s cubic-bezier(.16, .84, .44, 1),
      color .28s cubic-bezier(.16, .84, .44, 1);
  }

  .btn.btn-detail:hover {
    background: var(--violet-400);
    border-color: var(--violet-400);
    color: var(--ink-dark);
    box-shadow: 0 12px 30px rgba(139, 92, 246, .32);
    transform: translateY(-1px) scale(1.012);
  }

  .btn-detail .btn-ic {
    display: inline-block;
    transform: translateX(-4px);
    opacity: 0;
    transition: transform .28s cubic-bezier(.16, .84, .44, 1), opacity .28s cubic-bezier(.16, .84, .44, 1);
  }

  .btn-detail:hover .btn-ic {
    transform: translateX(2px);
    opacity: 1;
  }

  .btn-detail:active {
    transform: translateY(0) scale(.985);
    box-shadow: 0 6px 16px rgba(139, 92, 246, .22);
  }

  .card-game .card-body,
  .card-game .actions,
  .card-game .actions .btn,
  .card-game .btn-detail,
  .card-game .chip {
    position: relative;
    z-index: 2;
  }

  /* Select dark theme */
  select,
  .input[type="select"],
  select.input {
    background-color: #1a1825;
    color: #ece9ff;
    border: 1px solid rgba(214, 197, 255, .28);
    color-scheme: dark;
  }

  select option {
    background-color: #1a1825;
    color: #ece9ff;
  }

  select option:checked,
  select option:hover {
    background-color: #8b5cf6 !important;
    color: #0e0f1a !important;
  }

  select option[disabled],
  select option:disabled {
    color: #8c84ab;
  }

  select:focus {
    outline: 2px solid rgba(180, 160, 255, .6);
    outline-offset: 2px;
    border-color: rgba(180, 160, 255, .6);
  }

  select,
  select.input {
    border-radius: 12px;
    padding: .6rem .9rem;
  }

  /* Ikon panah untuk select filter */
  .select-wrap {
    position: relative;
  }

  .select-wrap::after {
    content: "▾";
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: .9rem;
    opacity: .8;
  }

  .select-wrap > select.input {
    padding-right: 2.2rem;
  }

  /* ===== Pagination ===== */
  .pager {
    margin: 24px auto 8px;
    display: flex;
    gap: 8px;
    justify-content: center;
    align-items: center;
    flex-wrap: wrap;
  }

  .pager-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 38px;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.9rem;
    text-decoration: none;
    color: #f9f5ff;
    background: radial-gradient(circle at 0 0, rgba(167,139,250,.35), rgba(59,130,246,.18));
    border: 1px solid rgba(255,255,255,.16);
    box-shadow: 0 8px 20px rgba(15,23,42,.45);
    transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
  }

  .pager-link:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
    box-shadow: 0 10px 24px rgba(129,140,248,.55);
  }

  .pager-active {
    background: linear-gradient(135deg, #a855f7, #6366f1);
    box-shadow: 0 10px 26px rgba(124,58,237,.6);
    cursor: default;
  }

  .pager-disabled {
    opacity: .45;
    cursor: default;
    box-shadow: none;
  }

  .pager-ellipsis {
    color: rgba(226,232,240,.75);
    padding: 0 4px;
  }

  .pager-prev,
  .pager-next {
    padding-inline: 14px;
  }

  /* === Thumbnail Walkthrough (dipakai di game.php, style di sini aman) === */
  .wt-thumb-wrap {
    border-radius: 18px 18px 0 0;
    overflow: hidden;
    background: #111827;
    border-bottom: 1px solid rgba(255, 255, 255, .06);
  }

  .wt-thumb {
    width: 100%;
    height: 190px;
    object-fit: cover;
    display: block;
  }

  .wt-thumb-fallback {
    width: 100%;
    height: 190px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.6rem;
    font-weight: 800;
    color: #c4b5fd;
    background: linear-gradient(180deg, #1f2937, #020617);
  }
</style>

<div class="container card" style="max-width:1040px">
  <div class="page-hero">
    <h1>Koleksi Panduan Game</h1>
    <div class="sub">Jelajahi walkthrough, tips, dan chapter lengkap untuk menamatkan game favoritmu.</div>
  </div>

  <!-- Filter -->
  <form class="filters" method="get" action="games.php">
    <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau deskripsi game…">

    <div class="select-wrap">
      <select class="input" name="platform">
        <option value="">Semua Platform</option>
        <?php foreach ($platforms as $p): ?>
          <option value="<?= e($p['platform']) ?>" <?= $platform === $p['platform'] ? 'selected' : '' ?>>
            <?= e($p['platform']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="select-wrap">
      <select class="input" name="genre">
        <option value="">Semua Genre</option>
        <?php foreach ($genres as $g): ?>
          <option value="<?= e($g['genre']) ?>" <?= $genre === $g['genre'] ? 'selected' : '' ?>>
            <?= e($g['genre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="btn btn-apply">Terapkan</button>
  </form>

  <?php if (!$games): ?>
    <div class="card" style="text-align:center">Tidak ada game yang cocok dengan filter.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($games as $gm):
        $detailUrl = '/arcadia/public/game.php?id=' . $gm['id'];
        if (!is_user_logged_in()) {
          $detailUrl = '/arcadia/public/auth/login.php?next=' . urlencode($detailUrl);
        }
        ?>
        <article class="card-game">
          <?php if (!empty($gm['image_url'])): ?>
            <?php
              $fx = (int)($gm['cover_focus_x'] ?? 50);
              $fy = (int)($gm['cover_focus_y'] ?? 50);
            ?>
            <img class="thumb cover-adjustable"
                 data-table="games"
                 data-id="<?= (int)$gm['id'] ?>"
                 src="<?= e($gm['image_url']) ?>"
                 alt="<?= e($gm['title']) ?>"
                 style="object-position:<?= $fx ?>% <?= $fy ?>%">
          <?php else: ?>
            <div class="thumb-fallback"><?= e(mb_strtoupper(mb_substr($gm['title'], 0, 1))) ?></div>
          <?php endif; ?>

          <div class="card-body">
            <h3 style="margin:.1rem 0 .15rem"><?= e($gm['title']) ?></h3>
            <div class="meta">
              <?= e($gm['platform']) ?> ·
              <?= e($gm['genre'] ?: 'Uncategorized') ?>
              <?= $gm['release_year'] ? ' · Rilis ' . e($gm['release_year']) : '' ?>
            </div>
            <div class="desc"><?= e($gm['excerpt']) ?>…</div>
            <div class="actions">
              <span class="chip chip-soft">Guide tersedia</span>
              <a class="btn btn-detail ghost" href="<?= e($detailUrl) ?>">Lihat Detail</a>
            </div>
          </div>

          <a class="cover-link" href="<?= e($detailUrl) ?>" aria-label="Buka detail <?= e($gm['title']) ?>"></a>
        </article>
      <?php endforeach; ?>
    </div>

    <?php
      // Build query string dasar untuk pagination (bawa q / platform / genre)
      $paramsPage = [];
      if ($q !== '')        $paramsPage['q']        = $q;
      if ($platform !== '') $paramsPage['platform'] = $platform;
      if ($genre !== '')    $paramsPage['genre']    = $genre;
      $qsBase = http_build_query($paramsPage);
    ?>

    <?php if ($totalPages > 1): ?>
      <div class="pager">
        <!-- Prev -->
        <?php if ($page > 1): ?>
          <a class="pager-link pager-prev"
             href="games.php?<?= $qsBase ? $qsBase . '&' : '' ?>page=<?= $page - 1 ?>">&laquo; Sebelumnya</a>
        <?php else: ?>
          <span class="pager-link pager-disabled">&laquo; Sebelumnya</span>
        <?php endif; ?>

        <!-- Nomor halaman (sekitar halaman aktif) -->
        <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);

          if ($start > 1) {
            echo '<a class="pager-link" href="games.php?' .
                 ($qsBase ? $qsBase . '&' : '') . 'page=1">1</a>';
            if ($start > 2) echo '<span class="pager-ellipsis">...</span>';
          }

          for ($i = $start; $i <= $end; $i++):
        ?>
          <?php if ($i == $page): ?>
            <span class="pager-link pager-active"><?= $i ?></span>
          <?php else: ?>
            <a class="pager-link"
               href="games.php?<?= $qsBase ? $qsBase . '&' : '' ?>page=<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?>
            <span class="pager-ellipsis">...</span>
          <?php endif; ?>
          <a class="pager-link"
             href="games.php?<?= $qsBase ? $qsBase . '&' : '' ?>page=<?= $totalPages ?>"><?= $totalPages ?></a>
        <?php endif; ?>

        <!-- Next -->
        <?php if ($page < $totalPages): ?>
          <a class="pager-link pager-next"
             href="games.php?<?= $qsBase ? $qsBase . '&' : '' ?>page=<?= $page + 1 ?>">Berikutnya &raquo;</a>
        <?php else: ?>
          <span class="pager-link pager-disabled">Berikutnya &raquo;</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<script>
  document.addEventListener('pointermove', (e) => {
    document.querySelectorAll('.btn-fx').forEach(btn => {
      const r = btn.getBoundingClientRect();
      btn.style.setProperty('--x', (e.clientX - r.left) + 'px');
      btn.style.setProperty('--y', (e.clientY - r.top) + 'px');
    });
  }, { passive: true });

  document.addEventListener('pointermove', e => {
    document.querySelectorAll('.btn-apply').forEach(b => {
      const r = b.getBoundingClientRect();
      b.style.setProperty('--x', (e.clientX - r.left) + 'px');
      b.style.setProperty('--y', (e.clientY - r.top) + 'px');
    });
  }, { passive: true });
</script>
