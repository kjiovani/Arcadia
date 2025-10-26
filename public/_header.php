<?php require_once __DIR__ . '/../config.php'; ?>
<?php require_once __DIR__ . '/../lib/helpers.php'; ?>
<?php require_once __DIR__ . '/../lib/auth_user.php'; ?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Arcadia</title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
</head>

<body>
  <header class="nav v2">
    <div class="nav-inner">
      <a class="brand" href="/arcadia/public/">
        <span class="brand-mark">⌘</span> Arcadia
      </a>


      <nav class="nav-menu">
        <a class="nav-link <?= strpos($path, '/public/index.php') !== false ? 'active' : '' ?>"
          href="/arcadia/public/index.php">Beranda</a>
        <a class="nav-link <?= strpos($path, '/public/search.php') !== false ? 'active' : '' ?>"
          href="/arcadia/public/search.php">Cari</a>
        <a class="nav-link <?= (strpos($path, '/public/games.php') !== false || strpos($path, '/public/walkthrough.php') !== false) ? 'active' : '' ?>"
          href="/arcadia/public/games.php">Game</a>
        <a class="nav-link" href="/arcadia/public/index.php#about">Tentang</a>
        <a class="fx-underline" href="/arcadia/public/admin-info.php">Admin</a>
        <span class="nav-sep"></span>
        <div class="nav-actions">
          <?php if (is_user_logged_in()): ?>
            <!-- SEMENTARA: tidak tampilkan tombol Login Admin -->
            <!-- Jika ingin, kamu bisa tampilkan menu profil/logout: -->
            <a class="nav-cta outline" href="/arcadia/public/auth/logout.php">Logout</a>
          <?php else: ?>
            <!-- HANYA tampilkan Login & Daftar untuk user -->
            <a class="nav-cta" href="/arcadia/public/auth/login.php">Login</a>
            <a class="nav-cta outline" href="/arcadia/public/auth/register.php">Daftar</a>
          <?php endif; ?>
        </div>
      </nav>


      <button class="nav-toggle" aria-label="Toggle Menu"
        onclick="document.querySelector('.nav-stack').classList.toggle('open')">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>
  <div class="container">

    <script>
      // Fallback untuk beberapa browser lama
      document.addEventListener('click', function (e) {
        const a = e.target.closest('a[href^="#"]');
        if (!a) return;
        const id = a.getAttribute('href').slice(1);
        const el = document.getElementById(id);
        if (el) {
          e.preventDefault();
          el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    </script>