<?php
// /arcadia/public/_header.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/settings.php';

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

$path        = (string)($_SERVER['REQUEST_URI'] ?? '');
$brand_name  = setting_get($mysqli,'brand_name','Arcadia');
$site_logo   = (string) setting_get($mysqli,'site_logo_url','');

/* ===== Normalisasi & verifikasi jalur logo (lokal/URL) ===== */
function map_web_to_fs(string $web): ?string {
  if (strpos($web,'/arcadia/public/')===0) return __DIR__ . '/' . ltrim(substr($web,strlen('/arcadia/public/')),'/');
  if (strpos($web,'/arcadia/')===0)        return dirname(__DIR__) . '/' . ltrim(substr($web,strlen('/arcadia/')),'/');
  return null;
}
function normalize_logo_url(string $u): string {
  $u = trim($u);
  if ($u==='') return '';
  if (preg_match('~^https?://~i',$u)) return $u; // URL absolut
  $u = preg_replace('~//+~','/',$u);
  if (preg_match('~^/?uploads/~',$u)) $u = '/arcadia/public/'.ltrim($u,'/');
  $u = preg_replace('~^/arcadia/public/(?:public/)+~','/arcadia/public/',$u);
  return $u;
}
$logo_url = normalize_logo_url($site_logo);

// Tambahkan cache-buster jika file lokal ada
if ($logo_url && !preg_match('~^https?://~i',$logo_url)) {
  $fs = map_web_to_fs($logo_url);
  if ($fs && is_file($fs)) $logo_url .= '?v='.filemtime($fs);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= e($brand_name) ?></title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
  <style>
    /* ===== Brand sizing: logo lebih besar dari teks ===== */
    :root{ --brandLogoH: clamp(34px, 2.2vw + 22px, 50px); } /* atur max jika ingin lebih besar */

    .nav.v2 .brand{
      display:flex; align-items:center; gap:12px; font-weight:800; letter-spacing:.2px;
    }
    .nav.v2 .brand img.brand-logo{
      height:var(--brandLogoH); width:auto; display:block;
      border-radius:10px; border:1px solid rgba(255,255,255,.10);
      box-shadow:0 6px 18px rgba(0,0,0,.28);
    }
    .nav.v2 .brand .brand-name{
      font-size:clamp(18px, 1vw + 11px, 22px);  /* lebih kecil dari logo */
      line-height:1; white-space:nowrap;
    }
    .brand-mark{
      display:inline-grid; place-items:center;
      width:var(--brandLogoH); height:var(--brandLogoH); border-radius:12px;
      background:radial-gradient(120% 120% at 30% 20%, rgba(167,139,250,.45), rgba(217,70,239,.18));
      border:1px solid rgba(255,255,255,.16); font-weight:900;
    }

   
  :root{ --brandLogoH: clamp(34px, 2.2vw + 22px, 50px); }

  .nav.v2 .brand{
    display:flex; align-items:center; gap:12px; font-weight:800; letter-spacing:.2px;
  }

  /* ——— HILANGKAN KOTAK DI SEKITAR LOGO ——— */
  .nav.v2 .brand img.brand-logo{
    height:var(--brandLogoH);
    width:auto;
    display:block;
    border:none;            /* no border */
    outline:none;
    box-shadow:none;        /* no shadow edge */
    border-radius:0;        /* no rounded container */
    background:transparent; /* ensure transparent */
  }

  .nav.v2 .brand .brand-name{
    font-size:clamp(18px, 1vw + 11px, 22px);
    line-height:1; white-space:nowrap;
  }

  /* Fallback mark (muncul kalau gambar gagal), tanpa kotak juga */
  .brand-mark{
    display:inline-grid; place-items:center;
    width:var(--brandLogoH); height:var(--brandLogoH);
    border:none; border-radius:0; background:transparent;
    font-weight:900;
  }


  </style>
</head>
<body>
  <header class="nav v2">
    <div class="nav-inner">
      <a class="brand" href="/arcadia/public/">
        <?php if ($logo_url): ?>
          <img class="brand-logo" src="<?= e($logo_url) ?>" alt="<?= e($brand_name) ?>" decoding="async"
               onerror="this.remove();document.getElementById('brandMark').style.display='grid'">
          <span id="brandMark" class="brand-mark" style="display:none">⌘</span>
          <span class="brand-name"><?= e($brand_name) ?></span>
        <?php else: ?>
          <span id="brandMark" class="brand-mark">⌘</span>
          <span class="brand-name"><?= e($brand_name) ?></span>
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
