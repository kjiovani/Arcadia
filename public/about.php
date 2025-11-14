<?php
// /arcadia/public/about.php (tanpa tombol admin)
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

include __DIR__ . '/_header.php';

// Statistik dinamis
$gamesCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM games")['c'] ?? 0);
$walksCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM walkthroughs")['c'] ?? 0);
$chaptersCount = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM chapters")['c'] ?? 0);
?>
<style>
  .about-hero h1 {
    margin: .5rem 0 1rem
  }

  .about-kpi {
    display: grid;
    grid-template-columns: repeat(3, minmax(160px, 1fr));
    gap: 12px;
    margin: .75rem 0 1rem
  }

  .about-kpi .card {
    display: grid;
    place-items: center;
    padding: 18px
  }

  .about-kpi .num {
    font-size: 2rem;
    font-weight: 800
  }

  .about-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 14px
  }

  .about-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
    margin-top: .75rem
  }

  .step {
    display: flex;
    align-items: flex-start;
    gap: 10px
  }

  .step .badge {
    flex: 0 0 auto
  }

  .faq {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px
  }

  .faq .q {
    font-weight: 700;
    margin-bottom: .35rem
  }

  .stack {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-top: 10px
  }
</style>

<div class="card about-hero" style="padding:28px">
  <span class="badge">Tentang</span>
  <h1>Arcadia — Panduan game rapi dan cepat.</h1>
  <p>
    Arcadia menyusun konten <strong>Game → Walkthrough → Chapter</strong> agar kamu mudah mengikuti langkah permainan.
  </p>

  <div style="display:flex;gap:12px;margin:16px 0 6px">
    <a class="btn" href="/arcadia/public/index.php">Jelajahi Panduan</a>
  </div>

  <div class="about-kpi">
    <div class="card">
      <div class="num"><?= e($gamesCount) ?></div>
      <div>Game</div>
    </div>
    <div class="card">
      <div class="num"><?= e($walksCount) ?></div>
      <div>Walkthrough</div>
    </div>
    <div class="card">
      <div class="num"><?= e($chaptersCount) ?></div>
      <div>Chapter</div>
    </div>
  </div>

  
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Kenapa Arcadia?</h2>
  <div class="about-grid">
    <div class="card">
      <h3>Gampang Cari Jalan</h3>
      <p>Dari daftar game sampai chapter terakhir, alurnya jelas dan mudah diikuti.</p>
    </div>
    <div class="card">
      <h3>Penjelasan Singkat & Jelas</h3>
      <p>Langsung ke langkah penting tanpa paragraf panjang dan ribet.</p>
    </div>
    <div class="card">
      <h3>Bantu Saat Lagi Stuck</h3>
      <p>Cari boss, misi, atau lokasi—dapat solusi dalam beberapa detik.</p>
    </div>
    <div class="card">
      <h3>Nyaman Dipakai Lama</h3>
      <p>Tampilan gelap, rapi, dan enak dibaca sambil main game.</p>
    </div>
    <div class="card">
      <h3>Tetap Ringan</h3>
      <p>Halaman dibuat sederhana supaya tetap cepat meski koneksi biasa saja.</p>
    </div>
    <div class="card">
      <h3>Komentar Lebih Terjaga</h3>
      <p>Admin memantau komentar supaya diskusi tetap rapi dan sopan.</p>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Cara menggunakan</h2>
  <div class="about-steps">
    <div class="step">
      <span class="badge">1</span>
      <div><strong>Pilih Game</strong><br>Pilih dari daftar game yang tersedia.</div>
    </div>
    <div class="step">
      <span class="badge">2</span>
      <div><strong>Buka Walkthrough</strong><br>Lihat ringkasan dan tingkat kesulitan.</div>
    </div>
    <div class="step">
      <span class="badge">3</span>
      <div><strong>Ikuti Chapter</strong><br>Ikuti langkahnya dan baca komentar untuk tips.</div>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Tanya jawab singkat</h2>
  <div class="faq">
    <div class="card">
      <div class="q">Siapa saja yang bisa pakai Arcadia?</div>
      <div>Siapa pun yang main game dan butuh panduan: pemain baru, yang lagi stuck, sampai yang cuma mau cek ulang langkah.</div>
    </div>
    <div class="card">
      <div class="q">Kapan sebaiknya saya buka Arcadia?</div>
      <div>Saat kamu bingung di satu misi, lupa langkah, atau mau lihat gambaran dulu sebelum mulai game baru.</div>
    </div>
    <div class="card">
      <div class="q">Apakah saya boleh berbagi tips sendiri?</div>
      <div>Boleh. Kamu bisa menuliskan pengalaman atau trik di kolom komentar, selama tetap sopan dan membantu pemain lain.</div>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Kontribusi & Kontak</h2>
  <p>Kalau menemukan bug atau punya ide fitur, tinggalkan komentar atau hubungi admin.</p>
  <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
    <a class="btn" href="/arcadia/public/index.php">Mulai jelajahi</a>
  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>
