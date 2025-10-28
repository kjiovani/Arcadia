<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';

require_user_login($_SERVER['REQUEST_URI']); // paksa login user
include __DIR__ . '/_header.php';

/* ===== ambil data walkthrough + judul game ===== */
$id = (int) ($_GET['id'] ?? 0);
$w = db_one(
  $mysqli,
  "SELECT w.*, g.title AS game_title
   FROM walkthroughs w
   JOIN games g ON g.id = w.game_id
   WHERE w.id=?",
  [$id],
  "i"
);

if (!$w) {
  echo '<div class="card">Walkthrough tidak ditemukan.</div>';
  include __DIR__ . '/_footer.php';
  exit;
}

/* ===== helper ambil YouTube ID dari URL ===== */
function yt_id_from_url(?string $u): string
{
  $u = trim((string) $u);
  if ($u === '')
    return '';
  if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $u, $m))
    return $m[1];
  return '';
}

/* ===== header walkthrough ===== */
?>
<style>
  .yt-wrap {
    position: relative;
    width: 100%;
    aspect-ratio: 16/9;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .08);
    background: #0f0f16;
    margin: .65rem 0 1rem;
  }

  .yt-wrap iframe {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    border: 0
  }

  .ch-img {
    margin: .65rem 0 1rem
  }

  .ch-img img {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, .08)
  }

  .chap {
    margin: .5rem 0
  }
</style>

<div class="card">
  <div class="small">
    <a href="game.php?id=<?= (int) $w['game_id'] ?>">← <?= e($w['game_title']) ?></a>
  </div>
  <h1><?= e($w['title']) ?> <span class="badge"><?= e($w['difficulty']) ?></span></h1>
  <?php
  // Cover walkthrough yang bisa diatur fokusnya (jika ada kolom/URL)
  $fx = (int) ($w['cover_focus_x'] ?? 50);
  $fy = (int) ($w['cover_focus_y'] ?? 50);
  if (!empty($w['cover_url'])):
    ?>
    <div style="margin:.8rem 0 1.1rem;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,.08)">
      <img class="cover-adjustable" data-table="walkthroughs" data-id="<?= (int) $w['id'] ?>"
        src="<?= e($w['cover_url']) ?>" alt="<?= e($w['title']) ?>"
        style="width:100%;height:350px;object-fit:cover;object-position:<?= $fx ?>% <?= $fy ?>%">
    </div>
  <?php endif; ?>
  <?php if (!empty($w['overview'])): ?>
    <p><?= nl2br(e($w['overview'])) ?></p>
  <?php endif; ?>
</div>

<?php
/* ===== ambil chapters + kolom video/gambar ===== */
$chapters = db_all(
  $mysqli,
  "SELECT id, title, content, order_number, youtube_url, image_url
   FROM chapters
   WHERE walk_id = ?
   ORDER BY order_number ASC, id ASC",
  [$w['id']],
  "i"
);
?>

<div class="card">
  <h2>Chapters</h2>
  <?php if (!$chapters): ?>
    <p class="small">Belum ada chapter.</p>
  <?php else: ?>
    <?php foreach ($chapters as $c): ?>
      <div class="card chap">
        <h3><?= (int) $c['order_number'] ?>. <?= e($c['title']) ?></h3>

        <?php $yt = yt_id_from_url($c['youtube_url'] ?? ''); ?>
        <?php if ($yt): ?>
          <div class="yt-wrap">
            <iframe src="https://www.youtube-nocookie.com/embed/<?= e($yt) ?>?rel=0&modestbranding=1" title="YouTube video"
              loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen></iframe>
          </div>
        <?php elseif (!empty($c['image_url'])): ?>
          <figure class="ch-img">
            <img src="<?= e($c['image_url']) ?>" alt="">
          </figure>
        <?php endif; ?>

        <p><?= nl2br(e($c['content'])) ?></p>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/_footer.php'; ?>