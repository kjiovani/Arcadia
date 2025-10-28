<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/settings.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$path = (string)($_SERVER['REQUEST_URI'] ?? '');
$brand_name = setting_get($mysqli,'brand_name','Arcadia');
$site_logo  = setting_get($mysqli,'site_logo_url','');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($brand_name) ?></title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
</head>
<body>
  <header class="nav v2">
    <div class="nav-inner">
      <a class="brand" href="/arcadia/public/">
        <?php if ($site_logo): ?>
          <img src="<?= e($site_logo) ?>" alt="<?= e($brand_name) ?>"
               style="height:22px;width:auto;vertical-align:middle;margin-right:8px;border-radius:6px;border:1px solid rgba(255,255,255,.1)">
          <span><?= e($brand_name) ?></span>
        <?php else: ?>
          <span class="brand-mark">⌘</span> <?= e($brand_name) ?>
        <?php endif; ?>
      </a>

      <nav class="nav-menu">
        <a class="nav-link <?= (strpos($path,'/public/index.php')!==false || $path==='/arcadia/public/' || $path==='/arcadia/public')?'active':'' ?>"
           href="/arcadia/public/index.php">Beranda</a>
        <a class="nav-link <?= (strpos($path,'/public/games.php')!==false || strpos($path,'/public/walkthrough.php')!==false || strpos($path,'/public/game.php')!==false)?'active':'' ?>"
           href="/arcadia/public/games.php">Game</a>
        <a class="nav-link <?= strpos($path,'/public/search.php')!==false?'active':'' ?>"
           href="/arcadia/public/search.php">Cari</a>
        <a class="nav-link <?= strpos($path,'/public/about.php')!==false?'active':'' ?>"
           href="/arcadia/public/about.php">Tentang</a>
        <a class="fx-underline" href="/arcadia/public/admin-info.php">Jadi Admin?</a>

        <span class="nav-sep"></span>
        <div class="nav-actions">
          <?php if (is_user_logged_in()): ?>
            <a class="nav-cta outline" href="/arcadia/public/auth/logout.php">Logout</a>
          <?php else: ?>
            <a class="nav-cta" href="/arcadia/public/auth/login.php">Login</a>
            <a class="nav-cta outline" href="/arcadia/public/auth/register.php">Daftar</a>
          <?php endif; ?>
        </div>
      </nav>

      <button class="nav-toggle" aria-label="Toggle Menu"
        onclick="document.querySelector('.nav-stack')?.classList.toggle('open')">
        <span></span><span></span><span></span>
      </button>
    </div>
  </header>

  <div class="container">

  <script>
    // smooth anchor fallback
    document.addEventListener('click', function (e) {
      const a = e.target.closest('a[href^="#"]');
      if (!a) return;
      const id = a.getAttribute('href').slice(1);
      const el = document.getElementById(id);
      if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  </script>
