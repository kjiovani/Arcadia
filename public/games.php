<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php'; // buat cek login saat klik detail
include __DIR__ . '/_header.php';

/* === Query filter & pencarian === */
$q = trim($_GET['q'] ?? '');
$platform = trim($_GET['platform'] ?? '');
$genre = trim($_GET['genre'] ?? '');

$params = [];
$types = '';
$where = [];

if ($q !== '') {
  $where[] = "(title LIKE CONCAT('%',?,'%') OR description LIKE CONCAT('%',?,'%'))";
  $params[] = $q;
  $params[] = $q;
  $types .= 'ss';
}
if ($platform !== '') {
  $where[] = "platform = ?";
  $params[] = $platform;
  $types .= 's';
}
if ($genre !== '') {
  $where[] = "genre = ?";
  $params[] = $genre;
  $types .= 's';
}

$sql = "SELECT id,title,platform,genre,release_year,image_url,LEFT(description,150) excerpt
        FROM games";
if ($where)
  $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY title ASC";
$games = db_all($mysqli, $sql, $params, $types);

/* opsi: ambil daftar distinct untuk filter */
$platforms = db_all($mysqli, "SELECT DISTINCT platform FROM games WHERE platform<>'' ORDER BY platform");
$genres = db_all($mysqli, "SELECT DISTINCT genre    FROM games WHERE genre<>''    ORDER BY genre");
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

  .grid .card-game:nth-child(1) {
    animation-delay: .02s
  }

  .grid .card-game:nth-child(2) {
    animation-delay: .06s
  }

  .grid .card-game:nth-child(3) {
    animation-delay: .1s
  }

  .grid .card-game:nth-child(4) {
    animation-delay: .14s
  }

  .grid .card-game:nth-child(5) {
    animation-delay: .18s
  }

  .grid .card-game:nth-child(6) {
    animation-delay: .22s
  }

  @keyframes fadeUp {
    from {
      opacity: 0;
      transform: translateY(8px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
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

  /* zoom halus saat hover */
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

  /* ====== ANIMASI TOMBOL (TERAPKAN + LIHAT DETAIL) ====== */
  .btn-fx {
    position: relative;
    overflow: hidden;
    transition:
      transform .26s cubic-bezier(.22, .61, .36, 1),
      box-shadow .26s cubic-bezier(.22, .61, .36, 1),
      background-color .26s cubic-bezier(.22, .61, .36, 1),
      border-color .26s cubic-bezier(.22, .61, .36, 1);
    will-change: transform;
  }

  .btn-fx:hover {
    transform: translateY(-1px) scale(1.02);
    box-shadow: 0 12px 30px rgba(139, 92, 246, .28);
  }

  .btn-fx:active {
    transform: translateY(0) scale(.985);
  }

  /* ripple halus yang mengikuti kursor */
  .btn-fx::after {
    content: "";
    position: absolute;
    inset: -40%;
    border-radius: inherit;
    background: radial-gradient(140px 140px at var(--x, 50%) var(--y, 50%), rgba(255, 255, 255, .16), transparent 60%);
    opacity: 0;
    transition: opacity .36s cubic-bezier(.22, .61, .36, 1);
    pointer-events: none;
  }

  .btn-fx:hover::after {
    opacity: .6;
  }

  /* aksesibilitas */
  .btn-fx:focus-visible {
    outline: 2px solid rgba(180, 160, 255, .65);
    outline-offset: 2px;
    border-radius: 12px;
  }

  @media (prefers-reduced-motion: reduce) {

    .card-game,
    .btn-fx,
    .thumb {
      transition: none !important;
      animation: none !important;
    }
  }
</style>


<div class="container card" style="max-width:1040px">
  <div class="page-hero">
    <h1>Kumpulan Panduan Game</h1>
    <div class="sub">Jelajahi koleksi guide—klik untuk lihat detail & walkthrough.</div>
  </div>

  <!-- Filter -->
  <form class="filters" method="get" action="games.php">
    <input class="input" type="text" name="q" value="<?= e($q) ?>" placeholder="Cari judul atau deskripsi game…">
    <select class="input" name="platform">
      <option value="">Semua Platform</option>
      <?php foreach ($platforms as $p): ?>
        <option value="<?= e($p['platform']) ?>" <?= $platform === $p['platform'] ? 'selected' : '' ?>><?= e($p['platform']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select class="input" name="genre">
      <option value="">Semua Genre</option>
      <?php foreach ($genres as $g): ?>
        <option value="<?= e($g['genre']) ?>" <?= $genre === $g['genre'] ? 'selected' : '' ?>><?= e($g['genre']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-fx">Terapkan</button>

  </form>

  <?php if (!$games): ?>
    <div class="card" style="text-align:center">Tidak ada game yang cocok dengan filter.</div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($games as $gm):
        $detailUrl = '/arcadia/public/game.php?id=' . $gm['id'];
        if (!is_user_logged_in()) { // redirect login jika belum login
          $detailUrl = '/arcadia/public/auth/login.php?next=' . urlencode($detailUrl);
        }
        ?>
        <article class="card-game">
          <?php if (!empty($gm['image_url'])): ?>
            <img class="thumb" src="<?= e($gm['image_url']) ?>" alt="">
          <?php else: ?>
            <div class="thumb-fallback"><?= e(mb_strtoupper(mb_substr($gm['title'], 0, 1))) ?></div>
          <?php endif; ?>
          <div class="card-body">
            <h3 style="margin:.1rem 0 .15rem"><?= e($gm['title']) ?></h3>
            <div class="meta"><?= e($gm['platform']) ?> ·
              <?= e($gm['genre'] ?: 'Uncategorized') ?>    <?= $gm['release_year'] ? ' · Rilis ' . e($gm['release_year']) : '' ?>
            </div>
            <div class="desc"><?= e($gm['excerpt']) ?>…</div>
            <div class="actions">
              <span class="chip chip-soft">Guide tersedia</span>
              <a class="btn ghost btn-fx" href="<?= e($detailUrl) ?>">Lihat Detail</a>

            </div>

          </div>
          <!-- cover-link agar seluruh kartu bisa diklik ke detail -->
          <a class="cover-link" href="<?= e($detailUrl) ?>" aria-label="Buka detail <?= e($gm['title']) ?>"></a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

<script>
  document.addEventListener('pointermove', (e) => {
    document.querySelectorAll('.btn-fx').forEach(btn => {
      const r = btn.getBoundingClientRect();
      btn.style.setProperty('--x', (e.clientX - r.left) + 'px');
      btn.style.setProperty('--y', (e.clientY - r.top) + 'px');
    });
  }, { passive: true });
</script>