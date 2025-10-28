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
  <h1>Arcadia — Pusat panduan game yang rapi, cepat, dan enak dibaca.</h1>
  <p>
    Arcadia menyusun konten <strong>Game → Walkthrough → Chapter</strong> dengan navigasi yang konsisten,
    fokus pada langkah inti, dan aman untuk diubah-ubah oleh kontributor.
  </p>

  <div style="display:flex;gap:12px;margin:16px 0 6px">
    <a class="btn" href="/arcadia/public/index.php">Jelajahi Panduan</a>
    <!-- Tombol admin dihilangkan -->
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

  <div class="stack">
    <span class="badge" style="background:rgba(255,255,255,.08)">PHP</span>
    <span class="badge" style="background:rgba(255,255,255,.08)">MySQL</span>
    <span class="badge" style="background:rgba(255,255,255,.08)">HTML</span>
    <span class="badge" style="background:rgba(255,255,255,.08)">CSS</span>
    <span class="badge" style="background:rgba(255,255,255,.08)">JavaScript</span>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Kenapa Arcadia?</h2>
  <div class="about-grid">
    <div class="card">
      <h3>Navigasi terstruktur</h3>
      <p>Hierarki jelas dari game hingga chapter. Anti nyasar dan mudah kembali ke konteks.</p>
    </div>
    <div class="card">
      <h3>Konten ringkas</h3>
      <p>Langkah-langkah fokus, minim spoiler. Teks dibatasi lebar baca agar nyaman di mata.</p>
    </div>
    <div class="card">
      <h3>Pencarian cepat</h3>
      <p>Cari boss, lokasi, atau tips dalam hitungan detik dari seluruh walkthrough.</p>
    </div>
    <div class="card">
      <h3>Keamanan bawaan</h3>
      <p>Prepared statements, token CSRF, validasi input, dan sanitasi output dengan <code>e()</code>.</p>
    </div>
    <div class="card">
      <h3>Kinerja ringan</h3>
      <p>Tidak bergantung framework berat. Halaman cepat dimuat bahkan di jaringan pas-pasan.</p>
    </div>
    <div class="card">
      <h3>Peran & Moderasi</h3>
      <p>Admin mengelola konten, komentar dengan status, serta aksi edit/hapus yang aman.</p>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Cara menggunakan</h2>
  <div class="about-steps">
    <div class="step">
      <span class="badge">1</span>
      <div><strong>Pilih Game</strong><br>Mulai dari daftar game untuk melihat ringkasan & gambar.</div>
    </div>
    <div class="step">
      <span class="badge">2</span>
      <div><strong>Buka Walkthrough</strong><br>Baca ikhtisar, tingkat kesulitan, lalu lanjut ke chapter.</div>
    </div>
    <div class="step">
      <span class="badge">3</span>
      <div><strong>Ikuti Chapter</strong><br>Ikuti langkah per langkah. Pakai komentar untuk berbagi tips.</div>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Tanya jawab singkat</h2>
  <div class="faq">
    <div class="card">
      <div class="q">Apakah saya perlu login?</div>
      <div>Melihat konten bebas. Login diperlukan untuk menulis komentar atau mengelola konten.</div>
    </div>
    <div class="card">
      <div class="q">Bagaimana menjaga komentar tetap rapi?</div>
      <div>Sistem otomatis merapikan baris tunggal/berulang (anti “turun tangga”) dan mencegah spam.</div>
    </div>
    <div class="card">
      <div class="q">Bisa dipakai di server sekolah/lokal?</div>
      <div>Bisa. Arcadia ringan, hanya butuh PHP + MySQL. Cukup impor database dan atur <code>config.php</code>.</div>
    </div>
  </div>
</div>

<div class="card" style="padding:24px">
  <h2 style="margin-bottom:.75rem">Kontribusi & Kontak</h2>
  <p>Kamu menemukan bug atau ingin usul fitur? Silakan tinggalkan masukan via komentar atau hubungi admin.</p>
  <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap">
    <a class="btn" href="/arcadia/public/index.php">Mulai jelajahi</a>
    <!-- Tombol "Masuk ke panel" dihilangkan -->
  </div>
</div>

<?php include __DIR__ . '/_footer.php'; ?>