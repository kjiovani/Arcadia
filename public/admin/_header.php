<?php
// /arcadia/public/admin/_header.php

$ROOT = dirname(__DIR__, 2); // dari /public/admin → /arcadia

require_once $ROOT . '/config.php';
require_once $ROOT . '/lib/helpers.php';
require_once $ROOT . '/lib/auth.php';
require_once $ROOT . '/lib/db.php';

require_admin();


if (session_status() === PHP_SESSION_NONE)
  session_start();

/* ==== Helper aktif menu (pakai REQUEST_URI agar query string aman) ==== */
$path = (string) ($_SERVER['REQUEST_URI'] ?? '');
function active(string $needle, string $path): string
{
  return (strpos($path, $needle) !== false) ? 'is-active' : '';
}

/* ==== Ambil user dari session, lalu REFRESH dari DB agar avatar terbaru tampil ==== */
$me = $_SESSION['user'] ?? [];
$uid = (int) ($me['id'] ?? 0);

if ($uid > 0) {
  $row = db_one($mysqli, "SELECT name, role, avatar_url FROM users WHERE id=?", [$uid], 'i');
  if ($row) {
    foreach (['name', 'role', 'avatar_url'] as $k) {
      if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
        $_SESSION['user'][$k] = $row[$k];
      }
    }
    $me = $_SESSION['user'];
  }
}

$userName = e($me['name'] ?? 'Admin');
$userRole = strtolower((string) ($me['role'] ?? 'admin'));
$userAvatar = trim((string) ($me['avatar_url'] ?? ''));

// info owner
$meFull = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
$isOwner = $meFull && strtoupper($meFull['role'] ?? '') === 'OWNER';

/* ==== App settings (logo & brand colors) ==== */
function app_setting($k, $def = '')
{
  global $mysqli;
  // kalau tabel belum ada, aman fallback
  $has = $mysqli->query("SHOW TABLES LIKE 'app_settings'");
  if (!$has || $has->num_rows === 0)
    return $def;
  $row = db_one($mysqli, "SELECT value FROM app_settings WHERE `key`=?", [$k], 's');
  return $row['value'] ?? $def;
}

$APP_LOGO = app_setting('site_logo_url', '');     // URL logo (opsional)
$P1 = app_setting('brand_color_p1', '#c9b3ff');   // warna gradient 1
$P2 = app_setting('brand_color_p2', '#9a78ff');   // warna gradient 2
$P3 = app_setting('brand_color_p3', '#7a5cff');   // warna gradient 3
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Owner</title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
  <style>
    /* brand colors (bisa diubah dari Owner UI Editor) */
    :root {
      --arc-p1:
        <?= e($P1) ?>
      ;
      --arc-p2:
        <?= e($P2) ?>
      ;
      --arc-p3:
        <?= e($P3) ?>
      ;
    }

    :root {
      --bg: #0e0e15;
      --panel: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
      --border: rgba(255, 255, 255, .08);
      --muted: rgba(255, 255, 255, .72);
      --primary: #a78bfa;
      --ring: rgba(167, 139, 250, .35);
    }

    html,
    body {
      height: 100%
    }

    body {
      background: #0b0b11;
      color: #eee
    }

    .admin-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      min-height: 100dvh
    }

    @media (max-width:980px) {
      .admin-layout {
        grid-template-columns: 1fr
      }
    }

    .sidenav {
      position: sticky;
      top: 0;
      height: 100dvh;
      border-right: 1px solid var(--border);
      background: radial-gradient(120% 120% at 10% -10%, rgba(167, 139, 250, .08), transparent 60%), var(--bg);
      padding: 18px 14px;
      display: flex;
      flex-direction: column;
      gap: 14px
    }

    @media (max-width:980px) {
      .sidenav {
        position: relative;
        height: auto;
        border-right: none;
        border-bottom: 1px solid var(--border)
      }
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 10px;
      font-weight: 800;
      letter-spacing: .2px;
      padding: 10px 12px;
      border-radius: 12px;
      background: var(--panel);
      border: 1px solid var(--border)
    }

    .brand-badge {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      background: radial-gradient(120% 120% at 30% 20%, rgba(167, 139, 250, .45), rgba(217, 70, 239, .18));
      border: 1px solid rgba(255, 255, 255, .16);
      font-weight: 900
    }

    .nav-section {
      margin-top: 4px
    }

    .nav-title {
      font-size: .85rem;
      opacity: .8;
      margin: 8px 8px
    }

    .nav-list {
      display: flex;
      flex-direction: column;
      gap: 6px;
      list-style: none;
      padding: 0;
      margin: 0
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 12px;
      color: inherit;
      text-decoration: none;
      border: 1px solid transparent;
      transition: .18s ease
    }

    .nav-link:hover {
      background: var(--panel);
      border-color: var(--border)
    }

    .nav-link.is-active {
      background: var(--primary);
      color: #0f0f16;
      border-color: var(--primary);
      box-shadow: 0 8px 22px var(--ring);
      font-weight: 700
    }

    .nav-foot {
      margin-top: auto;
      padding-top: 8px;
      border-top: 1px dashed var(--border);
      display: flex;
      flex-direction: column;
      gap: 8px;
      color: var(--muted)
    }

    .user-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid var(--border);
      background: var(--panel);
      text-decoration: none;
      color: inherit
    }

    .user-chip:hover {
      border-color: var(--primary)
    }

    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      overflow: hidden;
      display: grid;
      place-items: center;
      font-weight: 800;
      background: rgba(255, 255, 255, .08);
      border: 1px solid var(--border)
    }

    .avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block
    }

    .role {
      opacity: .8;
      margin-left: auto;
      font-size: .85rem
    }

    .logout {
      color: inherit;
      text-decoration: none;
      font-weight: 600;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid var(--border)
    }

    .logout:hover {
      background: rgba(239, 68, 68, .12);
      border-color: rgba(239, 68, 68, .45);
      color: #fff
    }

    /* Topbar mobile */
    .topbar {
      display: none;
      position: sticky;
      top: 0;
      z-index: 20;
      background: var(--bg);
      border-bottom: 1px solid var(--border);
      padding: 10px 14px;
      align-items: center;
      justify-content: space-between;
    }

    .menu-btn {
      display: none;
      border: 1px solid var(--border);
      background: var(--panel);
      padding: .45rem .7rem;
      border-radius: 10px;
      color: inherit;
      cursor: pointer
    }

    @media (max-width:980px) {
      .topbar {
        display: flex
      }

      .menu-btn {
        display: inline-block
      }
    }

    .admin-main {
      padding: 0 22px 22px
    }

    .page-title {
      margin: 0 0 12px;
      font-weight: 800
    }

    .container> :first-child {
      margin-top: 0
    }

    .admin-main {
      padding: 22px
    }

    .container {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 16px
    }

    .sidenav[data-collapsed="true"] {
      display: none
    }

    @media (max-width:980px) {
      .sidenav[data-collapsed="true"] {
        display: none
      }

      .sidenav[data-collapsed="false"] {
        display: flex
      }
    }

    /* ================= Uploader Dropzone (global admin) ================= */
    .dropzone {
      border: 2px dashed rgba(255, 255, 255, .22);
      border-radius: 14px;
      padding: 14px;
      cursor: pointer;
      background: rgba(255, 255, 255, .02);
      transition: .15s ease;
    }

    .dropzone:hover {
      background: rgba(255, 255, 255, .04);
    }

    .dropzone.is-dragover {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--ring) inset;
      background: rgba(167, 139, 250, .06);
    }

    .dropzone .hint {
      opacity: .75;
      font-size: .9rem;
    }
  </style>

  <!-- =============== Uploader Script (aktif untuk semua [data-dropzone]) =============== -->
  <script defer>
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('[data-dropzone]').forEach(function (zone) {
        var fileInput = zone.querySelector('input[type="file"]');
        var preview = zone.querySelector('[data-preview]');
        var label = zone.querySelector('[data-label]');

        function setPreview(file) {
          if (!file) return;
          // hanya preview gambar
          if (!file.type || file.type.indexOf('image/') !== 0) return;
          var reader = new FileReader();
          reader.onload = function (e) {
            if (preview) { preview.src = e.target.result; preview.style.display = 'block'; }
            if (label) { label.textContent = file.name; }
          };
          reader.readAsDataURL(file);
        }

        // Klik area -> buka file dialog (kecuali klik langsung input)
        zone.addEventListener('click', function (e) {
          if (e.target.tagName !== 'INPUT' && fileInput) fileInput.click();
        });

        // Drag n drop
        zone.addEventListener('dragover', function (e) { e.preventDefault(); zone.classList.add('is-dragover'); });
        zone.addEventListener('dragleave', function () { zone.classList.remove('is-dragover'); });
        zone.addEventListener('drop', function (e) {
          e.preventDefault(); zone.classList.remove('is-dragover');
          if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0] && fileInput) {
            fileInput.files = e.dataTransfer.files;
            setPreview(fileInput.files[0]);
          }
        });

        if (fileInput) {
          fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files[0]) setPreview(fileInput.files[0]);
          });
        }
      });
    });
  </script>
</head>

<body>

  <div class="admin-layout">
    <aside class="sidenav" id="sidenav" data-collapsed="false">
      <div class="brand">
        <?php if ($APP_LOGO): ?>
          <img src="<?= e($APP_LOGO) ?>" alt="Logo" style="height:28px;width:auto;display:block">
          <div>Owner Arcadia</div>
        <?php else: ?>
          <div class="brand-badge">⟡</div>
          <div>Owner Arcadia</div>
        <?php endif; ?>
      </div>

      <div class="nav-section">
        <div class="nav-title">Navigasi</div>
        <ul class="nav-list">
          <li><a
              class="nav-link <?= active('/admin/profile.php', $path) ?> <?= active('/admin/admin-info.php', $path) ?>"
              href="/arcadia/public/admin/profile.php">Profil</a></li>
          <li><a class="nav-link <?= active('/admin/index.php', $path) ?>" href="/arcadia/public/admin/">Dashboard</a>
          </li>
          <li><a class="nav-link <?= active('/admin/games.php', $path) ?>"
              href="/arcadia/public/admin/games.php">Games</a></li>
          <li><a class="nav-link <?= active('/admin/walkthroughs.php', $path) ?>"
              href="/arcadia/public/admin/walkthroughs.php">Walkthroughs</a></li>
          <li><a class="nav-link <?= active('/admin/chapters.php', $path) ?>"
              href="/arcadia/public/admin/chapters.php">Chapters</a></li>
          <li><a class="nav-link <?= active('/admin/tags.php', $path) ?>" href="/arcadia/public/admin/tags.php">Tags</a>
          </li>
          <?php if ($isOwner): ?>
            <li><a class="nav-link <?= active('/admin/users.php', $path) ?>"
                href="/arcadia/public/admin/users.php">Akun</a></li>
          <?php endif; ?>
          <?php if ($isOwner): ?>
            <li><a class="nav-link <?= active('/admin/appearance.php', $path) ?>"
                href="/arcadia/public/admin/appearance.php">Tampilan</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="nav-foot">
        <a class="user-chip" href="/arcadia/public/admin/profile.php" title="Kelola profil">
          <div class="avatar">
            <?php if ($userAvatar !== ''): ?>
              <img src="<?= e($userAvatar) ?>" alt="Avatar">
            <?php else: ?>
              <?= e(mb_strtoupper(mb_substr($userName, 0, 1))) ?>
            <?php endif; ?>
          </div>
          <div><?= $userName ?></div>
          <span class="role"><?= e($userRole) ?></span>
        </a>
        <a class="logout" href="/arcadia/public/logout.php">Logout</a>
      </div>
    </aside>

    <div class="admin-main">
      <div class="topbar">
        <strong>Arcadia Admin</strong>
        <button class="menu-btn" id="btnMenu" aria-controls="sidenav" aria-expanded="true">Menu</button>
      </div>

      <div class="container">
        <!-- konten halaman dimulai di sini -->
        <script>
          /* Tambahan: toggle sidebar untuk layar kecil */
          (function () {
            var btn = document.getElementById('btnMenu');
            var nav = document.getElementById('sidenav');
            if (!btn || !nav) return;
            btn.addEventListener('click', function () {
              var collapsed = nav.getAttribute('data-collapsed') === 'true';
              nav.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
              btn.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
            });
          })();
        </script>
        <?php
        // Muat Owner UI Editor (hanya untuk OWNER)
        if ($isOwner) {
          echo '<script src="/arcadia/public/assets/owner-ui.js" defer></script>';
        }
        ?>