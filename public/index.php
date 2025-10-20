<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/csrf.php';
require_once __DIR__ . '/../lib/validation.php';
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
$activeGenre = trim($_GET['genre'] ?? ''); // (boleh tetap, kalau suatu saat dipakai)
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
  SELECT w.id, w.title, w.difficulty, LEFT(w.overview,150) excerpt,
         g.title AS game, g.id AS game_id, g.image_url AS game_image
  FROM walkthroughs w JOIN games g ON g.id=w.game_id
  ORDER BY w.id DESC LIMIT 4
");

/* Ambil komentar terbaru (10) */
$comments = db_all($mysqli, "SELECT id,name,content,created_at FROM comments ORDER BY id DESC LIMIT 10");

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

<!-- (4) Daftar Game — satu kotak berisi 4 item (tanpa Genre) -->
<section id="games" class="section card">
  <h1>Daftar Game</h1>
  <p class="small">Pilih game untuk melihat walkthrough.</p>

  <?php
  // tetap pakai 4 item (isi placeholder kalau kurang)
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

        <div class="game-actions">
          <a class="btn ghost" href="game.php?id=<?= $g['id'] ?>">Lihat Detail</a>
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
</section>

<!-- (3) Panduan Unggulan — Carousel -->
<section class="section card">
  <h2 style="margin-bottom:.25rem">Panduan Unggulan</h2>
  <p class="small" style="margin:.15rem 0 1rem">Pilihan terbaru/terbaik dari Arcadia.</p>

  <?php
  // ambil sampai 6 item supaya bisa di-slide
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
      <button class="feat4-btn prev" type="button" aria-label="Sebelumnya" aria-disabled="false">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
        </svg>
      </button>

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
                <div class="feat4-actions">
                  <a class="btn" href="walkthrough.php?id=<?= $f['id'] ?>">Buka Panduan</a>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </div>

      <button class="feat4-btn next" type="button" aria-label="Berikutnya" aria-disabled="false">
        <svg viewBox="0 0 24 24" aria-hidden="true">
          <path d="M8.59 16.59 10 18l6-6-6-6-1.41 1.41L13.17 12z" />
        </svg>
      </button>
    </div>
  <?php endif; ?>
</section>

<!-- (5) Komentar -->
<section id="comments" class="section">
  <div class="cmt-card">
    <div class="cmt-head">
      <h2 style="margin:0">Komentar</h2>
      <div class="cmt-sub small">Bagikan saran, koreksi, atau request panduan.</div>
    </div>

    <!-- Form komentar -->
    <form class="cmt-form" method="post" action="index.php#comments">
      <?php csrf_field(); ?>
      <input type="hidden" name="action" value="<?= $editRow ? 'comment_update' : 'comment_create' ?>">
      <?php if ($editRow): ?>
        <input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>">
      <?php endif; ?>

      <input type="text" name="website" style="display:none" tabindex="-1" autocomplete="off"> <!-- honeypot -->

      <div class="fld <?= $editRow ? 'filled' : '' ?>">
        <input class="input" id="cmt-name" name="name" placeholder="Namamu"
          value="<?= $editRow ? e($editRow['name']) : '' ?>">
        <label for="cmt-name">Nama</label>
      </div>

      <div class="fld <?= $editRow ? 'filled' : '' ?>">
        <textarea class="input" id="cmt-content" name="content" rows="4"
          placeholder="Tulis komentar..."><?= $editRow ? e($editRow['content']) : '' ?></textarea>
        <label for="cmt-content">Komentar</label>
      </div>

      <div class="cmt-actions">
        <?php if ($editRow): ?>
          <a class="btn ghost" href="index.php#comments">Batal</a>
        <?php endif; ?>
        <span class="cmt-count" id="cmt-count">0/1000</span>
        <button class="btn-send" type="submit" aria-label="<?= $editRow ? 'Simpan Perubahan' : 'Kirim komentar' ?>">
          <svg viewBox="0 0 24 24">
            <path d="M2 21l21-9L2 3v7l15 2-15 2v7z" />
          </svg>
          <?= $editRow ? 'Simpan' : 'Kirim' ?>
        </button>
      </div>
    </form>

    <?php if ($m = flash('ok'))
      echo '<div class="alert" style="margin-top:.8rem">' . e($m) . '</div>'; ?>
    <?php if ($m = flash('err'))
      echo '<div class="alert" style="margin-top:.8rem">' . e($m) . '</div>'; ?>

    <!-- Daftar komentar -->
<div class="cmt-list">
  <?php if (!$comments): ?>
    <div class="cmt-empty small">Belum ada komentar.</div>
  <?php else: foreach($comments as $c):
    $mine = !empty($_SESSION['my_comments'][$c['id'] ?? 0]);
  ?>
    <article class="cmt" id="cmt-<?= (int)$c['id'] ?>">
      <div class="avatar" data-initial="<?= e(mb_strtoupper(mb_substr($c['name'],0,1))) ?>"></div>
      <div class="cmt-bubble">
        <div class="cmt-headline" style="justify-content:space-between;">
          <div>
            <strong><?= e($c['name']) ?></strong>
            <span class="time"><?= e(date('d M Y, H:i', strtotime($c['created_at']))) ?></span>
          </div>
          <?php if ($mine): ?>
            <div class="cmt-actions-row" style="display:flex; gap:.4rem;">
              <a class="btn tiny ghost" href="index.php?edit=<?= (int)$c['id'] ?>#comments">Edit</a>
              <form method="post" action="index.php#comments" onsubmit="return confirm('Hapus komentar ini?')">
                <?php csrf_field(); ?>
                <input type="hidden" name="action" value="comment_delete">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button class="btn tiny danger" type="submit">Hapus</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
        <p><?= nl2br(e($c['content'])) ?></p>
      </div>
    </article>
  <?php endforeach; endif; ?>
</div>

  </div>
</section>

<!-- (6) Tentang — versi v2 -->
<section id="about" class="section card about-v2">
  <header class="about-head">
    <span class="pretitle">Tentang</span>
    <h2>Arcadia — Guide hub rapi, cepat, dan enak dibaca.</h2>
    <p class="muted">
      Struktur konten <strong>Game → Walkthrough → Chapter</strong> bikin navigasi jelas, nyaman di mata, dan mudah dicari.
    </p>

    <div class="about-cta">
      <a class="btn btn-grad" href="search.php">
        <span>Jelajahi Panduan</span>
      </a>
      <a class="btn btn-ghost" href="/arcadia/public/admin" target="_blank" rel="noopener">
        <span>Panel Admin</span>
      </a>
    </div>
  </header>

  <div class="about-body">
    <!-- KOLom kiri: fitur -->
    <div class="about-col">
      <ul class="feature-grid">
        <li>
          <div class="fi">🧭</div>
          <div>
            <h3>Navigasi terstruktur</h3>
            <p>Hierarki konsisten dari game sampai chapter, anti nyasar.</p>
          </div>
        </li>
        <li>
          <div class="fi">🧩</div>
          <div>
            <h3>Chapter ringkas</h3>
            <p>Langkah fokus, minim spoiler, mudah di-scan.</p>
          </div>
        </li>
        <li>
          <div class="fi">🔎</div>
          <div>
            <h3>Pencarian cepat</h3>
            <p>Cari boss, shrine, atau tips dalam hitungan detik.</p>
          </div>
        </li>
        <li>
          <div class="fi">🛡️</div>
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

    <!-- Kolom kanan: statistik & stack -->
    <div class="about-col">
      <div class="stats">
        <div class="stat">
          <div class="n"><?= count($games) ?></div>
          <div class="l">Game</div>
        </div>
        <div class="stat">
          <div class="n"><?= (int)db_one($mysqli,"SELECT COUNT(*) c FROM walkthroughs")['c'] ?></div>
          <div class="l">Walkthrough</div>
        </div>
        <div class="stat">
          <div class="n"><?= (int)db_one($mysqli,"SELECT COUNT(*) c FROM comments")['c'] ?></div>
          <div class="l">Komentar</div>
        </div>
      </div>

      <div class="stack">
        <div class="stack-title">Teknologi</div>
        <div class="stack-chips">
          <span class="chip">PHP</span>
          <span class="chip">MySQL/MariaDB</span>
          <span class="chip">CSRF Guard</span>
          <span class="chip">Vanilla CSS</span>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/_footer.php'; // (6) Footer ?>

<script>
  (function () {
    const wrap = document.getElementById('feat4');
    if (!wrap) return;
    const vp = wrap.querySelector('.feat4-viewport');
    const track = wrap.querySelector('.feat4-track');
    const prev = wrap.querySelector('.feat4-btn.prev');
    const next = wrap.querySelector('.feat4-btn.next');

    // Hitung langkah: lebar kartu + gap (agar nge-snap rapi)
    function stepSize() {
      const card = track.querySelector('.feat4-card');
      if (!card) return vp.clientWidth;
      const cs = getComputedStyle(track);
      const gap = parseFloat(cs.columnGap || cs.gap || 0) || 0;
      return card.getBoundingClientRect().width + gap;
    }

    // Gerak
    const go = (dir = 1) => vp.scrollBy({ left: dir * stepSize(), behavior: 'smooth' });

    // Dinamis enable/disable
    const update = () => {
      const tol = 2; // toleransi pixel
      const atStart = vp.scrollLeft <= tol;
      const atEnd = (vp.scrollLeft + vp.clientWidth) >= (vp.scrollWidth - tol);
      prev.setAttribute('aria-disabled', atStart ? 'true' : 'false');
      next.setAttribute('aria-disabled', atEnd ? 'true' : 'false');
    };

    prev.addEventListener('click', () => { if (prev.getAttribute('aria-disabled') === 'false') go(-1); });
    next.addEventListener('click', () => { if (next.getAttribute('aria-disabled') === 'false') go(1); });

    // Press & hold (auto-repeat)
    function holdRepeat(btn, dir) {
      let t;
      const start = (e) => {
        e.preventDefault(); if (btn.getAttribute('aria-disabled') === 'true') return;
        go(dir); t = setInterval(() => go(dir), 380);
      };
      const end = () => clearInterval(t);
      btn.addEventListener('mousedown', start);
      btn.addEventListener('touchstart', start, { passive: false });
      ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(ev => btn.addEventListener(ev, end));
    }
    holdRepeat(prev, -1); holdRepeat(next, 1);

    // Keyboard support saat fokus di dalam section
    wrap.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
    });

    // Perbarui status tombol saat scroll/resize/konten berubah
    vp.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    new ResizeObserver(update).observe(track);
    update();
  })();
</script>

<script>
  (function () {
    const name = document.getElementById('cmt-name');
    const body = document.getElementById('cmt-content');
    const cnt = document.getElementById('cmt-count');
    const limit = 1000;

    function setFilled(el) {
      el.closest('.fld').classList.toggle('filled', !!el.value.trim());
    }
    ['input', 'change'].forEach(ev => {
      name && name.addEventListener(ev, () => setFilled(name));
      body && body.addEventListener(ev, () => { setFilled(body); updateCount(); autoSize(); });
    });

    function updateCount() {
      if (!cnt || !body) return;
      const n = body.value.length;
      cnt.textContent = `${n}/${limit}`;
      cnt.style.color = n > limit ? '#fca5a5' : '';
    }
    function autoSize() {
      if (!body) return;
      body.style.height = 'auto';
      body.style.height = Math.min(body.scrollHeight, 360) + 'px';
    }
    // init on load
    setFilled(name || { value: '' });
    setFilled(body || { value: '' });
    updateCount(); autoSize();
  })();
</script>

<script>
(function(){
  const ta = document.getElementById('cmt-content');
  const name = document.getElementById('cmt-name');
  const cnt = document.getElementById('cmt-count');
  const max = 1000;

  function updateCount(){
    if(!ta || !cnt) return;
    cnt.textContent = (ta.value || '').length + '/' + max;
  }
  function autosize(el){
    if(!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 400) + 'px';
  }

  if(ta){
    ['input','change'].forEach(ev => ta.addEventListener(ev, ()=>{updateCount(); autosize(ta);} ));
    setTimeout(()=>{updateCount(); autosize(ta);}, 0);
  }
  if(name) {
    name.addEventListener('input', ()=> name.parentElement.classList.toggle('filled', !!name.value.trim()));
    name.parentElement.classList.toggle('filled', !!name.value.trim());
  }
})();
</script>
