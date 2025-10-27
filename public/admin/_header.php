<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_admin();

$path = $_SERVER['SCRIPT_NAME'] ?? '';
function active($needle, $path) {
  return (strpos($path, $needle) !== false) ? 'is-active' : '';
}
$userName = e($_SESSION['user']['name'] ?? 'Admin');
$userRole = strtolower((string)($_SESSION['user']['role'] ?? 'admin'));

/* === Tambahan: dukung avatar dari session bila tersedia === */
$userAvatar = trim((string)($_SESSION['user']['avatar_url'] ?? '')); // kosong = fallback inisial
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Owner</title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
  <style>
    :root{ --bg:#0e0e15; --panel:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
           --border:rgba(255,255,255,.08); --muted:rgba(255,255,255,.72);
           --primary:#a78bfa; --ring:rgba(167,139,250,.35); }
    html,body{height:100%} body{background:#0b0b11;color:#eee}
    .admin-layout{display:grid;grid-template-columns:260px 1fr;min-height:100dvh}
    @media (max-width:980px){.admin-layout{grid-template-columns:1fr}}
    .sidenav{position:sticky;top:0;height:100dvh;border-right:1px solid var(--border);
             background:radial-gradient(120% 120% at 10% -10%, rgba(167,139,250,.08), transparent 60%), var(--bg);
             padding:18px 14px;display:flex;flex-direction:column;gap:14px}
    @media (max-width:980px){.sidenav{position:relative;height:auto;border-right:none;border-bottom:1px solid var(--border)}}
    .brand{display:flex;align-items:center;gap:10px;font-weight:800;letter-spacing:.2px;padding:10px 12px;border-radius:12px;background:var(--panel);border:1px solid var(--border)}
    .brand-badge{width:28px;height:28px;border-radius:8px;display:grid;place-items:center;background:radial-gradient(120% 120% at 30% 20%, rgba(167,139,250,.45), rgba(217,70,239,.18));border:1px solid rgba(255,255,255,.16);font-weight:900}
    .nav-section{margin-top:4px}.nav-title{font-size:.85rem;opacity:.8;margin:8px 8px}
    .nav-list{display:flex;flex-direction:column;gap:6px;list-style:none;padding:0;margin:0}
    .nav-link{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;color:inherit;text-decoration:none;border:1px solid transparent;transition:.18s ease}
    .nav-link:hover{background:var(--panel);border-color:var(--border)}
    .nav-link.is-active{background:var(--primary);color:#0f0f16;border-color:var(--primary);box-shadow:0 8px 22px var(--ring);font-weight:700}
    .nav-foot{margin-top:auto;padding-top:8px;border-top:1px dashed var(--border);display:flex;flex-direction:column;gap:8px;color:var(--muted)}
    .user-chip{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;border:1px solid var(--border);background:var(--panel);text-decoration:none;color:inherit}
    .user-chip:hover{border-color:var(--primary)}
    /* === Tambahan: avatar support (img / inisial) === */
    .avatar{width:34px;height:34px;border-radius:10px;overflow:hidden;display:grid;place-items:center;font-weight:800;background:rgba(255,255,255,.08);border:1px solid var(--border)}
    .avatar img{width:100%;height:100%;object-fit:cover;display:block}
    .role{opacity:.8;margin-left:auto;font-size:.85rem}
    .logout{color:inherit;text-decoration:none;font-weight:600;padding:10px 12px;border-radius:12px;border:1px solid var(--border)}
    .logout:hover{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.45);color:#fff}
    /* GANTI bagian ini */

/* semula: .topbar { display:none; ... display:flex; } -> bikin selalu muncul */
.topbar{
  display:none;                 /* desktop: sembunyikan total */
  position:sticky; top:0; z-index:20;
  background:var(--bg);
  border-bottom:1px solid var(--border);
  padding:10px 14px;
  align-items:center; justify-content:space-between;
}
.menu-btn{display:none; border:1px solid var(--border); background:var(--panel); padding:.45rem .7rem; border-radius:10px; color:inherit; cursor:pointer}

/* Tampilkan topbar hanya di layar kecil */
@media (max-width:980px){
  .topbar{display:flex}
  .menu-btn{display:inline-block}
}

/* Kurangi padding atas area konten supaya nempel ke atas */
.admin-main{padding:0 22px 22px}

/* Hapus margin atas elemen pertama di dalam .container / judul halaman */
.page-title{margin:0 0 12px; font-weight:800}
.container > :first-child{margin-top:0}

    @media (max-width:980px){.topbar .menu-btn{display:inline-block}}
    .admin-main{padding:22px}.container{background:var(--panel);border:1px solid var(--border);border-radius:16px;padding:16px}
    .sidenav[data-collapsed="true"]{display:none}
    @media (max-width:980px){.sidenav[data-collapsed="true"]{display:none}.sidenav[data-collapsed="false"]{display:flex}}
  </style>
</head>
<body>

<div class="admin-layout">
  <aside class="sidenav" id="sidenav" data-collapsed="false">
    <div class="brand">
      <div class="brand-badge">⟡</div>
      <div>Owner Arcadia</div>
    </div>

    <div class="nav-section">
      <div class="nav-title">Navigasi</div>
      <ul class="nav-list">
        <li><a class="nav-link <?= active('/admin/profile.php', $path) ?> <?= active('/admin/admin-info.php', $path) ?>" href="/arcadia/public/admin/profile.php">Profil</a></li>
        <li><a class="nav-link <?= active('/admin/index.php', $path) ?> <?= active('/admin/', $path) ?>" href="/arcadia/public/admin/">Dashboard</a></li>
        <li><a class="nav-link <?= active('/admin/games.php', $path) ?>" href="/arcadia/public/admin/games.php">Games</a></li>
        <li><a class="nav-link <?= active('/admin/walkthroughs.php', $path) ?>" href="/arcadia/public/admin/walkthroughs.php">Walkthroughs</a></li>
        <li><a class="nav-link <?= active('/admin/chapters.php', $path) ?>" href="/arcadia/public/admin/chapters.php">Chapters</a></li>
        <li><a class="nav-link <?= active('/admin/tags.php', $path) ?>" href="/arcadia/public/admin/tags.php">Tags</a></li>
        <li><a class="nav-link <?= active('/admin/mediafiles.php', $path) ?>" href="/arcadia/public/admin/mediafiles.php">Media</a></li>
      </ul>
    </div>

    <div class="nav-foot">
      <!-- Chip user menuju profil -->
      <a class="user-chip" href="/arcadia/public/admin/profile.php" title="Kelola profil">
        <div class="avatar">
          <?php if ($userAvatar !== ''): ?>
            <img src="<?= e($userAvatar) ?>" alt="Avatar">
          <?php else: ?>
            <?= e(mb_strtoupper(mb_substr($userName,0,1))) ?>
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
(function(){
  var btn = document.getElementById('btnMenu');
  var nav = document.getElementById('sidenav');
  if(!btn || !nav) return;
  btn.addEventListener('click', function(){
    var collapsed = nav.getAttribute('data-collapsed') === 'true';
    nav.setAttribute('data-collapsed', collapsed ? 'false' : 'true');
    btn.setAttribute('aria-expanded', collapsed ? 'true' : 'false');
  });
})();
</script>
