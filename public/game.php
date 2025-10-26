<?php
// /arcadia/public/game.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/auth.php';

require_user_login($_SERVER['REQUEST_URI']);
require_once __DIR__ . '/../lib/csrf.php';

// Ambil game
$id   = (int) ($_GET['id'] ?? 0);
$game = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], "i");

// Output
include __DIR__ . '/_header.php';
echo '<link rel="stylesheet" href="/arcadia/public/assets/comments.css">';

/* ===== CSS khusus komentar (pakai FLEX agar sejajar) ===== */
echo '<style>
.cmt-list{display:grid;gap:22px}

/* Kartu komentar */
.cmt{
  position:relative;
  border-radius:18px;
  background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
  border:1px solid rgba(255,255,255,.06);
  box-shadow:0 8px 24px rgba(0,0,0,.22);
  padding:16px 64px 16px 16px; /* ruang kanan utk kebab */
}

/* BARIS: avatar kiri + stack kanan */
.cmt-row{
  display:flex;
  align-items:flex-start;
  gap:12px;
  min-width:0;
}
.cmt-av{
  width:42px;height:42px;border-radius:999px;
  display:grid;place-items:center;font-weight:700;
  background:rgba(255,255,255,.06);
  flex:0 0 42px;
}

/* STACK kanan: Nama -> Isi -> Waktu, semuanya sejajar */
.cmt-stack{
  display:flex;
  flex-direction:column;
  gap:8px;
  min-width:0;
}
.cmt-title{
  font-size:1.06rem;font-weight:800;letter-spacing:.2px;line-height:1.25;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
}
.cmt-body{
  white-space:normal;
  word-break:break-word;
  line-height:1.75;
}
.cmt-time{
  font-size:.95rem;opacity:.8;white-space:nowrap;
}

/* Tombol ⋮ pojok kanan atas */
.cmt-act{ position:absolute; top:12px; right:14px; z-index:5; }
.cmt-kebab{
  background:transparent;border:1px solid rgba(255,255,255,.14);
  padding:6px 8px;border-radius:10px;cursor:pointer;line-height:1;
}
.cmt-menu{
  position:absolute; right:0; top:36px; min-width:168px; z-index:40;
  padding:6px;border-radius:12px;border:1px solid rgba(255,255,255,.08);
  background:rgba(20,20,28,.96);backdrop-filter:blur(8px);
  box-shadow:0 12px 28px rgba(0,0,0,.45);
}
.menu-item{display:block;width:100%;text-align:left;padding:10px 12px;border:0;background:transparent;border-radius:10px;cursor:pointer}
.menu-item:hover{background:rgba(255,255,255,.07)}
.menu-item.danger{color:#ef4444}
.menu-form{margin:0}

.cmt-edit{margin-top:10px}
.cmt-edit textarea{width:100%;border-radius:10px}
.cmt-edit-actions{display:flex;gap:8px;margin-top:8px}
</style>';

if (!$game) {
  echo '<div class="card">Game tidak ditemukan.</div>';
  include __DIR__ . '/_footer.php';
  exit;
}

/* ===== DETAIL GAME ===== */
echo '<div class="card">';
echo '<h1>' . e($game['title']) . '</h1>';
echo '<div class="small">' . e($game['genre']) . ' • ' . e($game['platform']) . ' • Rilis ' . e($game['release_year']) . '</div>';
if (!empty($game['image_url'])) {
  echo '<img src="' . e($game['image_url']) . '" alt="" style="width:100%;max-height:300px;object-fit:cover;border-radius:12px;margin:.75rem 0" />';
}
echo '<p>' . nl2br(e($game['description'])) . '</p>';
echo '</div>';

/* ===== WALKTHROUGH ===== */
$walks = db_all(
  $mysqli,
  "SELECT id,title,overview,difficulty FROM walkthroughs WHERE game_id=? ORDER BY id DESC",
  [$id], "i"
);
echo '<div class="card"><h2>Walkthrough</h2>';
if (!$walks) {
  echo '<p class="small">Belum ada walkthrough.</p>';
}
foreach ($walks as $w) {
  echo '<div class="card" style="margin:.5rem 0">';
  echo '<h3>' . e($w['title']) . ' <span class="badge">' . e($w['difficulty']) . '</span></h3>';
  echo '<p>' . e(mb_strimwidth($w['overview'] ?? "", 0, 160, "…", "UTF-8")) . '</p>';
  echo '<a class="btn" href="walkthrough.php?id='.(int)$w['id'].'">Buka</a>';
  echo '</div>';
}
echo '</div>';

/* ===== KOMENTAR ===== */
echo '<div class="card" id="komentar">';
echo '<h2>Komentar</h2>';

$me  = function_exists('current_user') ? current_user() : null;
$uid = (int)($me['id'] ?? 0);

/* Form komentar */
if (is_user_logged_in()) {
  echo '<div class="card" style="margin-top:.25rem">';
  echo '<h3>Tulis Komentar</h3>';
  echo '<form method="POST" action="comment_add.php">';
  echo '<input type="hidden" name="game_id" value="'.(int)$id.'">';
  csrf_field();
  echo '<textarea name="body" rows="4" maxlength="1000" placeholder="Tulis komentar kamu..." required class="input" style="width:100%;margin:.5rem 0"></textarea>';
  echo '<button class="btn">Kirim</button>';
  echo '</form>';
  echo '</div>';
} else {
  echo '<p class="small">Silakan <a href="/arcadia/public/auth/login.php?next=' . urlencode(current_path()) . '">login</a> untuk berkomentar.</p>';
}

/* List komentar */
$comments = db_all(
  $mysqli,
  "SELECT c.id, c.user_id, c.body, c.status, c.created_at, u.name AS user_name
     FROM comments c JOIN users u ON u.id = c.user_id
    WHERE c.game_id = ? AND c.status = 'PUBLISHED'
    ORDER BY c.created_at DESC
    LIMIT 100",
  [$id], "i"
);

if (!$comments) {
  echo '<p class="small" style="margin-top:.75rem">Belum ada komentar.</p>';
} else {
  echo '<div class="cmt-list" style="margin-top:.75rem">';
  foreach ($comments as $c) {
    $cid       = (int)$c['id'];
    $owner     = (int)$c['user_id'] === $uid;
    $canManage = $owner || (function_exists('is_admin') && is_admin());
    $nama      = trim($c['user_name'] ?: 'Pengguna');
    $initial   = mb_strtoupper(mb_substr($nama, 0, 1));
    $tglRaw    = date('d M Y • H:i', strtotime($c['created_at']));
    $tglHtml   = str_replace(' ', '&nbsp;', $tglRaw);

    // === NORMALISASI TEKS ===
    // Jika mayoritas baris sangat pendek (≤2 huruf), gabungkan supaya tidak turun per-huruf.
    $raw   = str_replace(["\r\n", "\r"], "\n", (string)$c['body']);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), fn($s)=>$s!==''));
    if ($lines) {
      $shortCnt = 0;
      foreach ($lines as $ln) { if (mb_strlen($ln) <= 2) $shortCnt++; }
      if ($shortCnt === count($lines)) {
        // Semua baris pendek -> satukan
        $bodyHtml = e(implode(' ', $lines));
      } else {
        $bodyHtml = nl2br(e($raw));
      }
    } else {
      $bodyHtml = '';
    }

    echo '<article class="cmt">';

      // kebab
      echo '<div class="cmt-act">';
      if ($canManage) {
        $menuId  = 'menu-'.$cid;
        $editId  = 'edit-'.$cid;
        $viewId  = 'view-'.$cid;
        echo '<button class="cmt-kebab" type="button" aria-haspopup="true" aria-controls="'.$menuId.'" aria-label="Menu komentar">⋮</button>';
        echo '<div class="cmt-menu" id="'.$menuId.'" hidden>';
          echo '<button class="menu-item" type="button" data-edit-toggle="'.$editId.'" data-view="'.$viewId.'">Edit</button>';
          echo '<form method="POST" action="comment_delete.php" class="menu-form">';
            echo '<input type="hidden" name="id" value="'.$cid.'">';
            echo '<input type="hidden" name="game_id" value="'.$id.'">';
            csrf_field();
            echo '<button class="menu-item danger" type="submit" onclick="return confirm(\'Hapus komentar ini?\')">Hapus</button>';
          echo '</form>';
        echo '</div>';
      }
      echo '</div>';

      // BARIS komentar: avatar + stack (nama, isi, waktu)
      echo '<div class="cmt-row">';
        echo '<div class="cmt-av" aria-hidden="true">'. e($initial) .'</div>';
        echo '<div class="cmt-stack">';
          echo '<div class="cmt-title" title="'. e($nama) .'">'. e($nama) .'</div>';
          echo '<div class="cmt-body" id="view-'.$cid.'">'. $bodyHtml .'</div>';
          echo '<div class="cmt-time">'.$tglHtml.'</div>';
        echo '</div>';
      echo '</div>';

      // Form edit inline
      if ($canManage) {
        echo '<form method="POST" action="comment_update.php" class="cmt-edit" id="edit-'.$cid.'" hidden>';
          echo '<input type="hidden" name="id" value="'.$cid.'">';
          echo '<input type="hidden" name="game_id" value="'.$id.'">';
          csrf_field();
          echo '<textarea name="body" rows="3" maxlength="1000" class="input" required>'. e($c['body']) .'</textarea>';
          echo '<div class="cmt-edit-actions">';
            echo '<button class="btn" type="submit">Simpan</button>';
            echo '<button class="btn ghost" type="button" data-edit-cancel="edit-'.$cid.'" data-view="view-'.$cid.'">Batal</button>';
          echo '</div>';
        echo '</form>';
      }

    echo '</article>';
  }
  echo '</div>';
}

echo '</div>'; // #komentar

/* ===== JS kebab & edit inline ===== */
echo '<script>
document.addEventListener("click", (e) => {
  const btn  = e.target.closest(".cmt-kebab");
  const menu = e.target.closest(".cmt-menu");
  if (btn){
    const m = btn.parentElement.querySelector(".cmt-menu");
    const open = !m.hasAttribute("hidden");
    document.querySelectorAll(".cmt-menu").forEach(mm => mm.setAttribute("hidden",""));
    if (!open) m.removeAttribute("hidden");
  } else if (!menu){
    document.querySelectorAll(".cmt-menu").forEach(mm => mm.setAttribute("hidden",""));
  }
  const editBtn = e.target.closest("[data-edit-toggle]");
  if (editBtn){
    const to = document.getElementById(editBtn.dataset.editToggle);
    const view = document.getElementById(editBtn.dataset.view);
    if (to && view){ to.removeAttribute("hidden"); view.setAttribute("hidden",""); }
    document.querySelectorAll(".cmt-menu").forEach(mm => mm.setAttribute("hidden",""));
  }
  const cancelBtn = e.target.closest("[data-edit-cancel]");
  if (cancelBtn){
    const to = document.getElementById(cancelBtn.dataset.editCancel);
    const view = document.getElementById(cancelBtn.dataset.view);
    if (to && view){ to.setAttribute("hidden",""); view.removeAttribute("hidden"); }
  }
}, {passive:true});
</script>';

include __DIR__ . '/_footer.php';
