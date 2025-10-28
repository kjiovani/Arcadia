<?php
// /arcadia/public/game.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth_user.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/csrf.php';

require_user_login($_SERVER['REQUEST_URI']);

// Ambil game (pakai * supaya aman jika ada kolom baru)
$id   = (int)($_GET['id'] ?? 0);
$game = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], "i");

// Output
include __DIR__ . '/_header.php';
echo '<link rel="stylesheet" href="/arcadia/public/assets/comments.css">';

/* ===== CSS: kartu walkthrough + komentar ===== */
echo
'<style>
/* ===== Walkthrough grid ===== */
.w-list{display:grid;gap:14px}
@media(min-width:760px){.w-list{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1100px){.w-list{grid-template-columns:repeat(3,1fr)}}
.w-item{
  display:flex;flex-direction:column;gap:10px;
  border-radius:14px;padding:12px;
  background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));
  border:1px solid rgba(255,255,255,.08);
}
.w-thumb{height:140px;border-radius:10px;overflow:hidden;background:rgba(255,255,255,.04);
  display:grid;place-items:center;border:1px solid rgba(255,255,255,.06)}
.w-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.w-fallback{font-weight:900;font-size:1.6rem;opacity:.9}
.w-title{display:flex;align-items:center;gap:8px;margin:0}
.w-meta{opacity:.85}

/* ===== Komentar ===== */
.c-title{margin-bottom:.25rem}
.c-form{margin:.5rem 0 1rem 0; padding:1rem; border:1px solid rgba(255,255,255,.07); border-radius:14px; background:rgba(255,255,255,.02)}
.c-label{display:block; font-weight:700; margin-bottom:.35rem}
.c-input{width:100%; padding:.85rem 1rem; border-radius:12px; border:1px solid rgba(255,255,255,.1); background:rgba(15,15,20,.55); color:#e8e7ff; outline:none;}
.c-input:focus{border-color:rgba(167,139,250,.55)}
.c-actions{margin-top:.6rem}
.c-list{display:grid; gap:18px; margin-top:.5rem}
.c-item{position:relative; padding:14px 16px; border-radius:16px; background:linear-gradient(180deg, rgba(255,255,255,.025), rgba(255,255,255,.015)); border:1px solid rgba(255,255,255,.07); box-shadow:0 8px 26px rgba(0,0,0,.28);}
.c-head{display:flex; align-items:center; gap:12px}
.c-ava{width:40px; height:40px; border-radius:50%; display:grid; place-items:center; font-weight:800; color:#e6e1ff; border:1px solid rgba(255,255,255,.12); background:radial-gradient(100% 140% at 30% 15%, rgba(167,139,250,.35), rgba(217,70,239,.18));}
.c-meta{display:flex; flex-direction:column; min-width:0}
.c-name{font-weight:800; letter-spacing:.2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis}
.c-time{font-size:.92rem; opacity:.75}
.c-more{margin-left:auto; position:relative}
.c-kebab{width:32px;height:32px;display:grid;place-items:center;border-radius:999px;background:#2a2a2f;border:1px solid rgba(255,255,255,.16);padding:0;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s}
.c-kebab::before{content:"";width:4px;height:4px;border-radius:50%;background:#fff;box-shadow:-8px 0 0 0 #fff, 8px 0 0 0 #fff}
.c-kebab:hover{background:#32323a;border-color:rgba(255,255,255,.24);box-shadow:0 0 0 4px rgba(255,255,255,.06) inset}
.c-kebab:active{background:#3a3a44}
.c-kebab:focus-visible{outline:2px solid rgba(167,139,250,.6);outline-offset:2px}

.c-menu{position:absolute; right:0; top:40px; min-width:184px; z-index:40; padding:8px; border-radius:14px; background:#191a21; border:1px solid rgba(255,255,255,.18); box-shadow:0 14px 34px rgba(0,0,0,.55), 0 0 0 1px rgba(255,255,255,.04) inset; color:#e8e7ff;}
.c-mi{display:flex; align-items:center; gap:10px; width:100%; padding:10px 12px; border:0; background:transparent; cursor:pointer; border-radius:10px; color:#e8e7ff; font-weight:600; letter-spacing:.1px; position:relative}
.c-mi + .c-mi, .c-mi + .c-del {margin-top:4px;}
.c-mi::after{content:""; position:absolute; left:12px; right:12px; height:1px; bottom:-3px; background:linear-gradient(90deg, transparent, rgba(255,255,255,.08), transparent); pointer-events:none;}
.c-mi:hover, .c-mi:focus-visible{background:rgba(255,255,255,.10); outline:none;}
.c-mi.danger{color:#ff6b6b; font-weight:700;}
.c-mi.danger:hover, .c-mi.danger:focus-visible{background:rgba(255, 107, 107, .14); color:#ff8080;}
.c-del{margin:0}
</style>';

if (!$game) {
  echo '<div class="card">Game tidak ditemukan.</div>';
  include __DIR__ . '/_footer.php';
  exit;
}

/* ===== DETAIL GAME ===== */
echo '<div class="card">';
echo '<h1>' . e($game['title']) . '</h1>';
echo '<div class="small">' . e($game['genre']) . ' • ' . e($game['platform']) . ($game['release_year'] ? ' • Rilis ' . e($game['release_year']) : '') . '</div>';

/* Cover Game adjustable (pakai image_url) */
if (!empty($game['image_url'])) {
  $gfx = (int)($game['cover_focus_x'] ?? 50);
  $gfy = (int)($game['cover_focus_y'] ?? 50);
  echo '<div style="margin:.75rem 0 1rem;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.08)">';
  echo '<img class="cover-adjustable" data-table="games" data-id="' . (int)$game['id'] . '" src="' . e($game['image_url']) . '" alt="' . e($game['title']) . '" style="width:100%;max-height:300px;aspect-ratio:16/9;object-fit:cover;object-position:' . $gfx . '% ' . $gfy . '%">';
  echo '</div>';
}

echo '<p>' . nl2br(e($game['description'])) . '</p>';
echo '</div>';

/* ===== WALKTHROUGH: ambil cover & focus ===== */
/* ===== WALKTHROUGH ===== */
/* Deteksi kolom opsional agar query tidak error di skema lama */
function has_col(mysqli $mysqli, string $table, string $col): bool {
  $sql = "SELECT COUNT(*) n
            FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME   = ?
             AND COLUMN_NAME  = ?";
  $row = db_one($mysqli, $sql, [$table, $col], "ss");
  return (int)($row['n'] ?? 0) > 0;
}

$w_has_cover = has_col($mysqli, 'walkthroughs', 'cover_url');
$w_has_fx    = has_col($mysqli, 'walkthroughs', 'cover_focus_x');
$w_has_fy    = has_col($mysqli, 'walkthroughs', 'cover_focus_y');

$fields = [
  "w.id", "w.title", "w.overview", "w.difficulty"
];
$fields[] = $w_has_cover ? "w.cover_url"         : "NULL AS cover_url";
$fields[] = $w_has_fx    ? "w.cover_focus_x"     : "NULL AS cover_focus_x";
$fields[] = $w_has_fy    ? "w.cover_focus_y"     : "NULL AS cover_focus_y";

$sqlWalks = "SELECT " . implode(", ", $fields) . "
               FROM walkthroughs w
              WHERE w.game_id = ?
              ORDER BY w.id DESC";

$walks = db_all($mysqli, $sqlWalks, [$id], "i");

echo '<div class="card"><h2>Walkthrough</h2>';
if (!$walks) {
  echo '<p class="small">Belum ada walkthrough.</p>';
} else {
  echo '<div class="w-list">';
  foreach ($walks as $w) {
    $fx = (int)($w['cover_focus_x'] ?? 50);
    $fy = (int)($w['cover_focus_y'] ?? 50);
    echo '<article class="w-item">';

      // thumb
      echo '<div class="w-thumb">';
      if (!empty($w['cover_url'])) {
        echo '<img class="cover-adjustable"
                   data-table="walkthroughs"
                   data-id="'.(int)$w['id'].'"
                   src="'.e($w['cover_url']).'"
                   alt="'.e($w['title']).'"
                   style="object-position:'.$fx.'% '.$fy.'%">';
      } else {
        $initial = mb_strtoupper(mb_substr($w['title'] ?: "?", 0, 1));
        echo '<div class="w-fallback" aria-hidden="true">'.e($initial).'</div>';
      }
      echo '</div>';

      // title + badge
      echo '<h3 class="w-title">'.e($w['title']).' <span class="badge">'.e($w['difficulty']).'</span></h3>';

      // excerpt
      $prev = e(mb_strimwidth($w['overview'] ?? "", 0, 160, "…", "UTF-8"));
      if (trim($prev) !== '') echo '<div class="w-meta">'.$prev.'</div>';

      // action
      echo '<div style="margin-top:auto;display:flex;justify-content:flex-end">';
        echo '<a class="btn" href="walkthrough.php?id='.(int)$w['id'].'">Buka</a>';
      echo '</div>';

    echo '</article>';
  }
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
  echo '<input type="hidden" name="game_id" value="' . (int)$id . '">';
  csrf_field();
  echo '<label class="c-label" for="c-body">Tulis Komentar</label>';
  echo '<textarea id="c-body" name="body" rows="4" maxlength="1000" placeholder="Bagikan tips kamu…" required class="c-input"></textarea>';
  echo '<div class="c-actions"><button class="btn c-send">Kirim</button></div>';
  echo '</form>';
} else {
  echo '<p class="small c-login">Silakan <a href="/arcadia/public/auth/login.php?next=' . urlencode(current_path()) . '">login</a> untuk berkomentar.</p>';
}

/* Ambil komentar */
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
    $initial   = e(mb_strtoupper(mb_substr($nama, 0, 1)));
    $isOwner   = ((int)$c['user_id'] === $uid);
    $canManage = $isOwner || (function_exists('is_admin') && is_admin());
    $waktu     = e(date('d M Y • H:i', strtotime($c['created_at'])));

    // Normalisasi isi (tahan “tangga” & “HJS”)
    $raw   = str_replace(["\r\n","\r"], "\n", (string)$c['body']);
    $lines = array_values(array_filter(array_map('trim', explode("\n", $raw)), fn($s)=>$s!==''));
    if ($lines) {
      $lineCount = count($lines);
      $shortCnt  = 0;
      foreach ($lines as $ln) if (mb_strlen($ln) <= 2) $shortCnt++;
      if ($lineCount >= 3 && ($shortCnt / $lineCount) >= 0.8) {
        $bodyText = preg_replace("/\s+/", " ", implode(" ", $lines));
      } else {
        $bodyText = implode("\n", $lines);
      }
    } else {
      $bodyText = "";
    }
    if (strpos($bodyText, "\n") === false && !preg_match("/\s/", $bodyText) && preg_match("/^[A-Za-z]{3,24}$/u", $bodyText)) {
      $bodyText = implode(" ", preg_split("//u", $bodyText, -1, PREG_SPLIT_NO_EMPTY));
    }
    $bodyEsc = nl2br(e($bodyText));
    $namaEsc = e($nama);

    $menuId = "c-menu-$cid";
    $editId = "c-edit-$cid";
    $viewId = "c-view-$cid";

    // ==== RENDER ITEM (HEREDOC agar aman kutip) ====
    echo <<<HTML
<article class="c-item" data-cid="{$cid}">
  <header class="c-head">
    <div class="c-ava" aria-hidden="true">{$initial}</div>
    <div class="c-meta">
      <div class="c-name" title="{$namaEsc}">{$namaEsc}</div>
      <time class="c-time">{$waktu}</time>
    </div>
HTML;

    if ($canManage) {
      echo <<<HTML
    <div class="c-more">
      <button class="c-kebab" type="button" aria-controls="{$menuId}" aria-haspopup="true" aria-label="Menu komentar"></button>
      <div class="c-menu" id="{$menuId}" hidden>
        <button class="c-mi" type="button" data-edit-toggle="{$editId}" data-view="{$viewId}">Edit</button>
        <form method="POST" action="comment_delete.php" class="c-del">
          <input type="hidden" name="id" value="{$cid}">
          <input type="hidden" name="game_id" value="{$id}">
HTML;
      csrf_field();
      echo <<<HTML
          <button class="c-mi danger" onclick="return confirm('Hapus komentar ini?')" type="submit">Hapus</button>
        </form>
      </div>
    </div>
HTML;
    }

    echo <<<HTML
  </header>

  <div class="c-body" id="{$viewId}">{$bodyEsc}</div>
HTML;

    if ($canManage) {
      $bodyRawEsc = e($c['body']);
      echo <<<HTML
  <form class="c-edit" id="{$editId}" method="POST" action="comment_update.php" hidden>
    <input type="hidden" name="id" value="{$cid}">
    <input type="hidden" name="game_id" value="{$id}">
HTML;
      csrf_field();
      echo <<<HTML
    <textarea name="body" rows="3" maxlength="1000" class="c-input" required>{$bodyRawEsc}</textarea>
    <div class="c-edit-actions">
      <button class="btn">Simpan</button>
      <button class="btn ghost" type="button" data-edit-cancel="{$editId}" data-view="{$viewId}">Batal</button>
    </div>
  </form>
HTML;
    }

    echo "</article>";
  }
  echo '</div>'; // .c-list
}
echo '</div>'; // #komentar

/* ===== JS kebab & edit inline ===== */
echo <<<JS
<script>
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
</script>
JS;

include __DIR__ . '/_footer.php';
