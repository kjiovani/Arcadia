</div>

<?php
require_once __DIR__.'/../lib/db.php'; // pastikan sudah ada $mysqli

// === Sorotan angka ===
$gCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM games")['c'] ?? 0);
$wCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM walkthroughs")['c'] ?? 0);
$cCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM comments")['c'] ?? 0);

// === Tag populer (opsional; fallback kosong jika tabel relasi tidak ada) ===
$popularTags = [];
try {
  $popularTags = db_all(
    $mysqli,
    "SELECT t.name, COUNT(*) as c
     FROM tags t
     JOIN walktag wt ON wt.tag_id = t.id
     GROUP BY t.id, t.name
     ORDER BY c DESC, t.name ASC
     LIMIT 6"
  );
} catch (Throwable $e) {
  $popularTags = [];
}
?>
<footer class="site-footer enhanced">
  <div class="container fgrid">

    <!-- Brand & tagline -->
    <section class="f-col brand center">
  <div class="brand-stack">
    <div class="brand-line">
      <span class="brand-mark">⌘</span>
      <h3 class="brand-name">Arcadia</h3>
    </div>

    <p class="tagline">
      Hub panduan game yang ringkas, konsisten, dan nyaman dibaca—tanpa kebanjiran spoiler.
    </p>

    <div class="chips compact">
      <span class="chip"><strong><?= number_format($gCount) ?></strong> Game</span>
      <span class="chip"><strong><?= number_format($wCount) ?></strong> Walkthrough</span>
      <span class="chip"><strong><?= number_format($cCount) ?></strong> Komentar</span>
    </div>
  </div>
</section>


      <!-- Sorotan angka -->
      <div class="chips">
        <span class="chip"><strong><?= number_format($gCount) ?></strong> Game</span>
        <span class="chip"><strong><?= number_format($wCount) ?></strong> Walkthrough</span>
        <span class="chip"><strong><?= number_format($cCount) ?></strong> Komentar</span>
      </div>

      <?php if ($popularTags): ?>
      <div class="tags-wrap">
        <div class="tags-title">Tag populer</div>
        <div class="tags">
          <?php foreach ($popularTags as $t): ?>
            <a class="t" href="/arcadia/public/search.php?q=<?= e($t['name']) ?>">#<?= e($t['name']) ?></a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </section>

    <!-- Navigasi -->
    <nav class="f-col nav" aria-label="Navigasi footer">
      <h4>Navigasi</h4>
      <a href="#top">Beranda</a>
      <a href="#comments">Komentar</a>
      <a href="#games">Game</a>
      <a href="search.php">Panduan</a>
      <a href="/arcadia/public/admin">Admin</a>
    </nav>

    <!-- Info singkat -->
    <section class="f-col info">
      <h4>Tentang proyek</h4>
      <ul class="bullets">
        <li>Struktur konten rapi: <em>game → walkthrough → chapter</em>.</li>
        <li>Aman: prepared statements + CSRF token.</li>
        <li>Teknologi: PHP, MySQL/MariaDB, Vanilla CSS.</li>
      </ul>
    </section>

    <!-- Bar legal -->
    <div class="f-bar">
      <div class="left">© Arcadia • Made for learning — <?= date('Y') ?></div>
      <div class="right">
        <a href="#about">Tentang</a><span>·</span>
        <a href="#">Kebijakan</a><span>·</span>
        <a href="#">Lisensi</a>
      </div>
    </div>
  </div>

  <!-- Tombol back to top -->
  <button class="to-top" aria-label="Kembali ke atas" title="Ke atas">
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 5.5 3.5 14l1.4 1.4L12 8.3l7.1 7.1 1.4-1.4Z"/></svg>
  </button>

  <script>
    (function(){
      const btn = document.querySelector('.site-footer.enhanced .to-top');
      const onScroll = () => btn.classList.toggle('show', window.scrollY > 600);
      window.addEventListener('scroll', onScroll, {passive:true});
      onScroll();
      btn.addEventListener('click', () => window.scrollTo({top:0, behavior:'smooth'}));
    })();
  </script>
</footer>
</body>
</html>
