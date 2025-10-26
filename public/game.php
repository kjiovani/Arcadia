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
$id   = (int)($_GET['id'] ?? 0);
$game = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], "i");

// Output
include __DIR__ . '/_header.php';
echo '<link rel="stylesheet" href="/arcadia/public/assets/comments.css">';

/* ===== CSS komentar ===== */
echo '<style>
/* ====== Komentar – Revamp ====== */
.c-title{margin-bottom:.25rem}

.c-form{margin:.5rem 0 1rem 0; padding:1rem; border:1px solid rgba(255,255,255,.07); border-radius:14px; background:rgba(255,255,255,.02)}
.c-label{display:block; font-weight:700; margin-bottom:.35rem}
.c-input{
  width:100%; padding:.85rem 1rem; border-radius:12px; border:1px solid rgba(255,255,255,.1);
  background:rgba(15,15,20,.55); color:#e8e7ff; outline:none;
}
.c-input:focus{border-color:rgba(167,139,250,.55)}
.c-actions{margin-top:.6rem}

.c-list{display:grid; gap:18px; margin-top:.5rem}
.c-item{
  position:relative; padding:14px 16px; border-radius:16px;
  background:linear-gradient(180deg, rgba(255,255,255,.025), rgba(255,255,255,.015));
  border:1px solid rgba(255,255,255,.07); box-shadow:0 8px 26px rgba(0,0,0,.28);
}
.c-head{display:flex; align-items:center; gap:12px}
.c-ava{
  width:40px; height:40px; border-radius:50%;
  display:grid; place-items:center; font-weight:800;
  color:#e6e1ff; border:1px solid rgba(255,255,255,.12);
  background:radial-gradient(100% 140% at 30% 15%, rgba(167,139,250,.35), rgba(217,70,239,.18));
}
.c-meta{display:flex; flex-direction:column; min-width:0}
.c-name{font-weight:800; letter-spacing:.2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
.c-time{font-size:.92rem; opacity:.75}
.c-more{margin-left:auto; position:relative}
.c-kebab{background:transparent; border:1px solid rgba(255,255,255,.15); border-radius:10px; padding:6px 8px; cursor:pointer}
.c-menu{
  position:absolute; right:0; top:38px; min-width:170px; padding:6px;
  border-radius:12px; border:1px solid rgba(255,255,255,.1);
  background:rgba(20,20,28,.96); backdrop-filter:blur(8px);
  box-shadow:0 12px 28px rgba(0,0,0,.45); z-index:30;
}
.c-mi{display:block; width:100%; padding:10px 12px; text-align:left; border:0; background:transparent; border-radius:10px; cursor:pointer}
.c-mi:hover{background:rgba(255,255,255,.08)}
.c-mi.danger{color:#ef4444}
.c-del{margin:0}

.c-body{margin:.6rem 0 0 52px; white-space:pre-wrap; word-break:break-word; overflow-wrap:anywhere; line-height:1.75}
.c-edit{margin:.6rem 0 0 52px}
.c-edit-actions{display:flex; gap:8px; margin-top:.5rem}

.c-empty{opacity:.8}
.c-login a{font-weight:700}

/* Tombol kebab: bulat, tiga titik putih */
.c-more{ margin-left:auto; position:relative; }

.c-kebab{
  width:32px; height:32px;
  display:grid; place-items:center;
  border-radius:999px;
  background:#2a2a2f;                 /* lingkaran gelap */
  border:1px solid rgba(255,255,255,.16);
  padding:0; cursor:pointer;
  transition:background .15s ease, border-color .15s ease, box-shadow .15s ease;
}

/* Tiga titik putih di tengah */
.c-kebab::before{
  content:"";
  width:4px; height:4px;              /* diameter titik */
  border-radius:50%;
  background:#fff;                     /* titik tengah */
  box-shadow:
    -8px 0 0 0 #fff,                   /* titik kiri */
     8px 0 0 0 #fff;                   /* titik kanan */
}

/* Hover/focus states */
.c-kebab:hover{
  background:#32323a;
  border-color:rgba(255,255,255,.24);
  box-shadow:0 0 0 4px rgba(255,255,255,.06) inset;
}
.c-kebab:active{
  background:#3a3a44;
}
.c-kebab:focus-visible{
  outline:2px solid rgba(167,139,250,.6);
  outline-offset:2px;
}

/* Menu popover tetap seperti sebelumnya */
.c-menu{
  position:absolute; right:0; top:40px; min-width:170px; z-index:30;
  padding:6px; border-radius:12px;
  border:1px solid rgba(255,255,255,.1);
  background:rgba(20,20,28,.96); backdrop-filter:blur(8px);
  box-shadow:0 12px 28px rgba(0,0,0,.45);
}

/* ==== Popover Menu - High Contrast ==== */
.c-menu{
  position:absolute; right:0; top:40px; min-width:184px; z-index:40;
  padding:8px;
  border-radius:14px;
  /* Naikkan kontras bg */
  background: #191a21;                      /* lebih terang dari sebelumnya */
  border:1px solid rgba(255,255,255,.18);
  box-shadow:
    0 14px 34px rgba(0,0,0,.55),
    0 0 0 1px rgba(255,255,255,.04) inset;
  color:#e8e7ff;                             /* teks default cerah */
}

/* Item di dalam menu */
.c-mi{
  display:flex; align-items:center; gap:10px;
  width:100%;
  padding:10px 12px;
  border:0; background:transparent; cursor:pointer;
  border-radius:10px;
  color:#e8e7ff;                             /* pastikan tidak mewarisi abu-abu */
  font-weight:600;                           /* sedikit lebih tebal */
  letter-spacing:.1px;
}

/* Garis pemisah halus antar item */
.c-mi + .c-mi,
.c-mi + .c-del {                             /* form delete yg setelah tombol */
  margin-top:4px;
}
.c-mi::after{
  content:"";
  position:absolute; left:12px; right:12px;
  height:1px; bottom:-3px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent);
  pointer-events:none;
}

/* Hover/focus: bg naik, teks tetap terang */
.c-mi:hover,
.c-mi:focus-visible{
  background: rgba(255,255,255,.10);
  outline:none;
}

/* Item berbahaya (Hapus) – lebih tebal & merah kontras */
.c-mi.danger{
  color:#ff6b6b;
  font-weight:700;
}
.c-mi.danger:hover,
.c-mi.danger:focus-visible{
  background: rgba(255, 107, 107, .14);
  color:#ff8080;
}

/* Perbaiki form delete agar nol margin */
.c-del{ margin:0; }

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

/* ===== KOMENTAR (revamp) ===== */
echo '<div class="card" id="komentar">';
echo '<h2 class="c-title">Komentar</h2>';

$me  = function_exists('current_user') ? current_user() : null;
$uid = (int)($me['id'] ?? 0);

/* Form komentar */
if (is_user_logged_in()) {
  echo '<form class="c-form" method="POST" action="comment_add.php">';
    echo '<input type="hidden" name="game_id" value="'.(int)$id.'">';
    csrf_field();
    echo '<label class="c-label" for="c-body">Tulis Komentar</label>';
    echo '<textarea id="c-body" name="body" rows="4" maxlength="1000" placeholder="Bagikan tips kamu…" required class="c-input"></textarea>';
    echo '<div class="c-actions"><button class="btn c-send">Kirim</button></div>';
  echo '</form>';
} else {
  echo '<p class="small c-login">Silakan <a href="/arcadia/public/auth/login.php?next=' . urlencode(current_path()) . '">login</a> untuk berkomentar.</p>';
}

/* Ambil komentar – nama selalu ada */
$comments = db_all(
  $mysqli,
  "SELECT c.id, c.user_id, c.body, c.status, c.created_at,
          COALESCE(NULLIF(TRIM(u.name),''), u.email) AS user_name
     FROM comments c 
     JOIN users u ON u.id = c.user_id
    WHERE c.game_id = ? AND c.status = 'PUBLISHED'
    ORDER BY c.created_at DESC
    LIMIT 100",
  [$id], "i"
);

if (!$comments) {
  echo '<p class="small c-empty">Belum ada komentar. Jadilah yang pertama!</p>';
} else {
  echo '<div class="c-list">';
  foreach ($comments as $c) {
    $cid       = (int)$c['id'];
    $nama      = trim($c['user_name'] ?: 'Pengguna');
    $initial   = mb_strtoupper(mb_substr($nama, 0, 1));
    $isOwner   = ((int)$c['user_id'] === $uid);
    $canManage = $isOwner || (function_exists('is_admin') && is_admin());
    $waktu     = date('d M Y • H:i', strtotime($c['created_at']));

    /* ===== Normalisasi isi (fix “tangga” & “HJS”) ===== */
    $raw   = str_replace(["\r\n","\r"], "\n", (string)$c['body']);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), fn($s)=>$s!==''));
    if ($lines) {
      $lineCount = count($lines);
      $shortCnt  = 0;
      foreach ($lines as $ln) if (mb_strlen($ln) <= 2) $shortCnt++;
      if ($lineCount >= 3 && ($shortCnt / $lineCount) >= 0.8) {
        $bodyText = preg_replace('/\s+/', ' ', implode(' ', $lines));   // "H J S"
      } else {
        $bodyText = implode("\n", $lines);                               // paragraf normal
      }
    } else {
      $bodyText = '';
    }
    if (strpos($bodyText, "\n") === false && !preg_match('/\s/', $bodyText) && preg_match('/^[A-Za-z]{3,24}$/u', $bodyText)) {
      $bodyText = implode(' ', preg_split('//u', $bodyText, -1, PREG_SPLIT_NO_EMPTY)); // "H J S"
    }

    echo '<article class="c-item" data-cid="'.$cid.'">';

      // header
      echo '<header class="c-head">';
        echo '<div class="c-ava" aria-hidden="true">'.e($initial).'</div>';
        echo '<div class="c-meta">';
          echo '<div class="c-name" title="'.e($nama).'">'.e($nama).'</div>';
          echo '<time class="c-time">'.e($waktu).'</time>';
        echo '</div>';

        if ($canManage) {
          $menuId  = 'c-menu-'.$cid;
          $editId  = 'c-edit-'.$cid;
          $viewId  = 'c-view-'.$cid;
          echo '<div class="c-more">';
            echo '<button class="c-kebab" type="button" aria-controls="<?=$menuId?>" aria-haspopup="true" aria-label="Menu komentar"></button>';
            echo '<div class="c-menu" id="'.$menuId.'" hidden>';
              echo '<button class="c-mi" type="button" data-edit-toggle="'.$editId.'" data-view="'.$viewId.'">Edit</button>';
              echo '<form method="POST" action="comment_delete.php" class="c-del">';
                echo '<input type="hidden" name="id" value="'.$cid.'">';
                echo '<input type="hidden" name="game_id" value="'.(int)$id.'">';
                csrf_field();
                echo '<button class="c-mi danger" onclick="return confirm(\'Hapus komentar ini?\')" type="submit">Hapus</button>';
              echo '</form>';
            echo '</div>';
          echo '</div>';
        }
      echo '</header>';

      // isi tampil
      echo '<div class="c-body" id="c-view-'.$cid.'">'. nl2br(e($bodyText)) .'</div>';

      // form edit inline
      if ($canManage) {
        echo '<form class="c-edit" id="c-edit-'.$cid.'" method="POST" action="comment_update.php" hidden>';
          echo '<input type="hidden" name="id" value="'.$cid.'">';
          echo '<input type="hidden" name="game_id" value="'.(int)$id.'">';
          csrf_field();
          echo '<textarea name="body" rows="3" maxlength="1000" class="c-input" required>'.e($c['body']).'</textarea>';
          echo '<div class="c-edit-actions">';
            echo '<button class="btn">Simpan</button>';
            echo '<button class="btn ghost" type="button" data-edit-cancel="c-edit-'.$cid.'" data-view="c-view-'.$cid.'">Batal</button>';
          echo '</div>';
        echo '</form>';
      }

    echo '</article>';
  }
  echo '</div>'; // .c-list
}
echo '</div>'; // #komentar

/* ===== JS kebab & edit inline ===== */
echo '<script>
document.addEventListener("click", (e) => {
  const kebab = e.target.closest(".c-kebab");
  const menu  = e.target.closest(".c-menu");
  if (kebab){
    const wrap = kebab.parentElement;
    const m = wrap.querySelector(".c-menu");
    const open = !m.hasAttribute("hidden");
    document.querySelectorAll(".c-menu").forEach(mm => mm.setAttribute("hidden",""));
    if (!open) m.removeAttribute("hidden");
  } else if (!menu){
    document.querySelectorAll(".c-menu").forEach(mm => mm.setAttribute("hidden",""));
  }

  const editBtn = e.target.closest("[data-edit-toggle]");
  if (editBtn){
    const edit = document.getElementById(editBtn.dataset.editToggle);
    const view = document.getElementById(editBtn.dataset.view);
    if (edit && view){ edit.removeAttribute("hidden"); view.setAttribute("hidden",""); }
    document.querySelectorAll(".c-menu").forEach(mm => mm.setAttribute("hidden",""));
  }

  const cancelBtn = e.target.closest("[data-edit-cancel]");
  if (cancelBtn){
    const edit = document.getElementById(cancelBtn.dataset.editCancel);
    const view = document.getElementById(cancelBtn.dataset.view);
    if (edit && view){ edit.setAttribute("hidden",""); view.removeAttribute("hidden"); }
  }
}, {passive:true});
</script>';


include __DIR__ . '/_footer.php';
