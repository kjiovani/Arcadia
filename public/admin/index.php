<?php
// public/admin/index.php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../../lib/db.php';


if (!function_exists('e')) {
  function e($s)
  {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  }
}

// ===== Ringkasan untuk KPI =====
$stats = [
  'games' => (int) (db_one($mysqli, "SELECT COUNT(*) c FROM games")['c'] ?? 0),
  'walks' => (int) (db_one($mysqli, "SELECT COUNT(*) c FROM walkthroughs")['c'] ?? 0),
  'chapters' => (int) (db_one($mysqli, "SELECT COUNT(*) c FROM chapters")['c'] ?? 0),
];

// ===== 5 data terakhir (tanpa kolom created_at) =====
$latest_games = db_all($mysqli, "SELECT id, title FROM games ORDER BY id DESC LIMIT 5");
$latest_walks = db_all($mysqli, "SELECT id, title FROM walkthroughs ORDER BY id DESC LIMIT 5");
?>
<style>
  /* ====== Aesthetic dashboard styles (khusus halaman ini) ====== */
  .dash-wrap {
    display: flex;
    flex-direction: column;
    gap: 18px
  }

  .page-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between
  }

  .page-head h1 {
    margin: 0;
    font-weight: 800;
    letter-spacing: .2px
  }

  .page-sub {
    opacity: .8;
    margin-top: 4px
  }

  /* KPI cards */
  .kpis {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px
  }

  @media (max-width:980px) {
    .kpis {
      grid-template-columns: 1fr
    }
  }

  .kpi {
    position: relative;
    border-radius: 18px;
    padding: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    border: 1px solid rgba(255, 255, 255, .08);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .25), inset 0 1px 0 rgba(255, 255, 255, .04);
    overflow: hidden;
  }

  .kpi:before {
    content: "";
    position: absolute;
    inset: -40% -20% auto auto;
    height: 160px;
    width: 160px;
    border-radius: 50%;
    background: radial-gradient(circle at 30% 30%, var(--primary), transparent 60%);
    opacity: .26;
    filter: blur(8px);
  }

  .kpi .label {
    opacity: .85;
    font-weight: 700
  }

  .kpi .value {
    font-size: 40px;
    line-height: 1.1;
    margin-top: 6px;
    font-weight: 900;
    letter-spacing: .5px
  }

  .kpi .badge {
    margin-top: 10px;
    display: inline-block;
    padding: 8px 14px;
    border-radius: 12px;
    font-weight: 700;
    background: rgba(167, 139, 250, .22);
    border: 1px solid rgba(167, 139, 250, .45);
    text-decoration: none;
    color: inherit
  }

  /* Panels */
  .panels {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px
  }

  @media (max-width:980px) {
    .panels {
      grid-template-columns: 1fr
    }
  }

  .panel {
    border-radius: 16px;
    padding: 16px;
    background: var(--panel);
    border: 1px solid var(--border);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .22), inset 0 1px 0 rgba(255, 255, 255, .04)
  }

  .panel h3 {
    margin: 0 0 10px;
    font-weight: 800
  }

  .list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin: 0;
    padding: 0;
    list-style: none
  }

  .item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(255, 255, 255, .02)
  }

  .item:hover {
    border-color: var(--primary)
  }

  .item .title {
    font-weight: 700
  }

  .item .meta {
    margin-left: auto;
    opacity: .75;
    font-size: .9rem
  }

  .actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap
  }

  .actions .btn {
    padding: 10px 14px;
    border-radius: 12px;
    border: 1px solid var(--border);
    text-decoration: none;
    color: inherit
  }

  .actions .btn:hover {
    border-color: var(--primary);
    box-shadow: 0 8px 22px var(--ring)
  }
</style>

<div class="admin-main">
  <div class="dash-wrap">

    <div class="page-head">
      <div>
        <h1>Dashboard</h1>
        <div class="page-sub">Ringkasan data dan pintasan cepat pengelolaan.</div>
      </div>
    </div>

    <!-- KPI cards -->
    <section class="kpis">
      <div class="kpi">
        <div class="label">Games</div>
        <div class="value"><?= number_format($stats['games']) ?></div>
        <a class="badge" href="games.php">Kelola</a>
      </div>
      <div class="kpi">
        <div class="label">Walkthroughs</div>
        <div class="value"><?= number_format($stats['walks']) ?></div>
        <a class="badge" href="walkthroughs.php">Kelola</a>
      </div>
      <div class="kpi">
        <div class="label">Chapters</div>
        <div class="value"><?= number_format($stats['chapters']) ?></div>
        <a class="badge" href="chapters.php">Kelola</a>
      </div>
    </section>

    <!-- Quick actions + latest -->
    <section class="panels">
      <div class="panel">
        <h3>Quick Actions</h3>
        <div class="actions">
          <a class="btn" href="games.php">Kelola Games</a>
          <a class="btn" href="walkthroughs.php">Kelola Walkthroughs</a>
          <a class="btn" href="chapters.php">Kelola Chapters</a>
          <a class="btn" href="tags.php">Kelola Tags</a>
          <a class="btn" href="mediafiles.php">Kelola Media</a>
        </div>
      </div>

      <div class="panel">
        <h3>Terbaru</h3>
        <ul class="list">
          <?php if (!empty($latest_games)): ?>
            <li class="item">
              <div class="title">Games terbaru</div>
              <div class="meta"><?= e($latest_games[0]['title'] ?? '-') ?></div>
            </li>
          <?php endif; ?>
          <?php if (!empty($latest_walks)): ?>
            <li class="item">
              <div class="title">Walkthrough terbaru</div>
              <div class="meta"><?= e($latest_walks[0]['title'] ?? '-') ?></div>
            </li>
          <?php endif; ?>
          <li class="item">
            <div class="title">Total konten</div>
            <div class="meta"><?= number_format($stats['games'] + $stats['walks'] + $stats['chapters']) ?> item</div>
          </li>
        </ul>
      </div>
    </section>

    <!-- Daftar 5 terakhir -->
    <section class="panels">
      <div class="panel">
        <h3>5 Games Terakhir</h3>
        <ul class="list">
          <?php foreach ($latest_games as $g): ?>
            <li class="item">
              <div class="title"><?= e($g['title']) ?></div>
              <div class="meta">#<?= (int) $g['id'] ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="panel">
        <h3>5 Walkthroughs Terakhir</h3>
        <ul class="list">
          <?php foreach ($latest_walks as $w): ?>
            <li class="item">
              <div class="title"><?= e($w['title']) ?></div>
              <div class="meta">#<?= (int) $w['id'] ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </section>

  </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>