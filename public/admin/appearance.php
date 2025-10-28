<?php
// /arcadia/public/admin/appearance.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/settings.php';

require_admin();

// ==== CSRF helper ====
$__csrf_path = __DIR__ . '/../../lib/csrf.php';
if (file_exists($__csrf_path)) require_once $__csrf_path;
if (!function_exists('csrf_field')) {
  function csrf_field(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    echo '<input type="hidden" name="csrf_token" value="'.htmlspecialchars($_SESSION['csrf_token'],ENT_QUOTES).'">';
  }
}
if (!function_exists('csrf_verify')) {
  function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $ok = isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
          hash_equals($_SESSION['csrf_token'], (string)$_POST['csrf_token']);
    if (!$ok) { http_response_code(419); die('CSRF token mismatch'); }
  }
}

if (!function_exists('e')) { function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

// ==== util cek kolom ====
function col_exists(mysqli $db, string $table, string $col): bool {
  $r = db_one($db, "SELECT COUNT(*) n FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?",
              [$table,$col],'ss');
  return (int)($r['n']??0) > 0;
}

// flags kolom opsional
$w_has_cover = col_exists($mysqli,'walkthroughs','cover_url');
$w_has_fx    = col_exists($mysqli,'walkthroughs','cover_focus_x');
$w_has_fy    = col_exists($mysqli,'walkthroughs','cover_focus_y');
$g_has_fx    = col_exists($mysqli,'games','cover_focus_x');
$g_has_fy    = col_exists($mysqli,'games','cover_focus_y');

// data pratinjau
$sql = "SELECT
          w.id, w.title,
          ".($w_has_cover?"w.cover_url":"NULL AS cover_url").",
          ".($w_has_fx?"IFNULL(w.cover_focus_x,50)":"50")." AS w_fx,
          ".($w_has_fy?"IFNULL(w.cover_focus_y,50)":"50")." AS w_fy,
          g.id AS game_id, g.title AS game_title, g.image_url AS game_image,
          ".($g_has_fx?"IFNULL(g.cover_focus_x,50)":"50")." AS g_fx,
          ".($g_has_fy?"IFNULL(g.cover_focus_y,50)":"50")." AS g_fy
        FROM walkthroughs w
        JOIN games g ON g.id = w.game_id
        ORDER BY w.id DESC LIMIT 12";
$items = db_all($mysqli, $sql);

// settings dasar
$brand_name = setting_get($mysqli,'brand_name','Arcadia');
$site_logo  = setting_get($mysqli,'site_logo_url','');
$hero_title = setting_get($mysqli,'hero_title','Arcadia');
$hero_sub   = setting_get($mysqli,'hero_subtitle','Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.');

// settings logo section
$logo_games  = setting_get($mysqli,'logo_section_games','');
$logo_feat   = setting_get($mysqli,'logo_section_featured','');
$logo_recent = setting_get($mysqli,'logo_section_recent','');

// simpan settings
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['__form__']??'')==='settings') {
  csrf_verify();
  setting_set($mysqli,'brand_name',          trim($_POST['brand_name'] ?? 'Arcadia'));
  setting_set($mysqli,'site_logo_url',       trim($_POST['site_logo_url'] ?? ''));
  setting_set($mysqli,'hero_title',          trim($_POST['hero_title'] ?? 'Arcadia'));
  setting_set($mysqli,'hero_subtitle',       trim($_POST['hero_subtitle'] ?? ''));
  setting_set($mysqli,'logo_section_games',  trim($_POST['logo_section_games'] ?? ''));
  setting_set($mysqli,'logo_section_featured',trim($_POST['logo_section_featured'] ?? ''));
  setting_set($mysqli,'logo_section_recent', trim($_POST['logo_section_recent'] ?? ''));
  header('Location: appearance.php?saved=1'); exit;
}

include __DIR__ . '/_header.php';
?>
<style>
  .app-wrap{display:grid;gap:18px}
  .grid{display:grid;gap:14px;grid-template-columns:1fr 1fr}
  @media(max-width:980px){.grid{grid-template-columns:1fr}}
  .panel{border-radius:16px;padding:16px;background:var(--panel);border:1px solid var(--border)}
  .list{display:grid;gap:12px}
  .item{display:grid;grid-template-columns:160px 1fr;gap:12px;border:1px solid var(--border);border-radius:14px;padding:12px;background:rgba(255,255,255,.02)}
  .thumb{width:160px;aspect-ratio:16/9;border-radius:10px;overflow:hidden;background:rgba(255,255,255,.04);display:grid;place-items:center}
  .thumb img{width:100%;height:100%;object-fit:cover;display:block}
  .title{font-weight:800;margin:0 0 .25rem}
  .small.mono{font-family:ui-monospace,Menlo,Consolas,monospace}
  .input{width:100%}
  .cover-adjustable{cursor:grab}
  .editing{outline:2px dashed rgba(167,139,250,.55);outline-offset:3px}

  /* dropzone */
  .dz{display:flex;gap:10px;align-items:center}
  .dz .preview{width:44px;height:44px;border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.12);background:#151521;display:grid;place-items:center}
  .dz .preview img{width:100%;height:100%;object-fit:cover}
  .dz .dropbox{flex:1;min-height:54px;border:1px dashed rgba(255,255,255,.25);border-radius:12px;padding:.65rem .8rem;display:flex;align-items:center;gap:10px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015))}
  .dz .dropbox.drag{outline:2px solid rgba(167,139,250,.6);outline-offset:4px}
</style>

<div class="admin-main">
  <div class="app-wrap">

    <div class="panel">
      <h2 style="margin-top:0">Tampilan Situs</h2>
      <?php if (isset($_GET['saved'])): ?>
        <div class="small" style="margin:.25rem 0 .75rem;color:#b7ffcc">✔ Disimpan</div>
      <?php endif; ?>

      <form method="POST" action="appearance.php" class="grid">
        <?php csrf_field(); ?>
        <input type="hidden" name="__form__" value="settings">

        <!-- Brand Name -->
        <div class="panel">
          <label class="small">Nama Brand</label>
          <input class="input" type="text" name="brand_name" value="<?= e($brand_name) ?>" placeholder="Arcadia">
          <div class="small mono" style="opacity:.8;margin-top:.25rem">setting: brand_name</div>
        </div>

        <!-- Brand Logo (Drag & Drop) -->
        <div class="panel">
          <label class="small">Logo Brand (Navbar)</label>
          <div class="dz">
            <div class="preview" id="pv_brand"><?= $site_logo ? '<img src="'.e($site_logo).'">' : '⟡' ?></div>
            <div class="dropbox" data-target="#site_logo_url">
              <input class="input" id="site_logo_url" name="site_logo_url" value="<?= e($site_logo) ?>" placeholder="https://.../logo.png">
            </div>
          </div>
          <div class="small mono" style="opacity:.8;margin-top:.25rem">setting: site_logo_url • drag & drop atau klik area putus-putus</div>
        </div>

        <!-- Hero -->
        <div class="panel">
          <label class="small">Hero Title</label>
          <input class="input" type="text" name="hero_title" value="<?= e($hero_title) ?>">
        </div>
        <div class="panel">
          <label class="small">Hero Subtitle</label>
          <input class="input" type="text" name="hero_subtitle" value="<?= e($hero_sub) ?>">
        </div>

        <!-- Section Logos -->
        <div class="panel">
          <label class="small">Logo “Daftar Game”</label>
          <div class="dz">
            <div class="preview" id="pv_games"><?= $logo_games ? '<img src="'.e($logo_games).'">' : '🎮' ?></div>
            <div class="dropbox" data-target="#logo_section_games">
              <input class="input" id="logo_section_games" name="logo_section_games" value="<?= e($logo_games) ?>" placeholder="https://...png">
            </div>
          </div>
        </div>

        <div class="panel">
          <label class="small">Logo “Panduan Unggulan”</label>
          <div class="dz">
            <div class="preview" id="pv_feat"><?= $logo_feat ? '<img src="'.e($logo_feat).'">' : '⭐' ?></div>
            <div class="dropbox" data-target="#logo_section_featured">
              <input class="input" id="logo_section_featured" name="logo_section_featured" value="<?= e($logo_feat) ?>" placeholder="https://...png">
            </div>
          </div>
        </div>

        <div class="panel">
          <label class="small">Logo “Baru Diupdate”</label>
          <div class="dz">
            <div class="preview" id="pv_recent"><?= $logo_recent ? '<img src="'.e($logo_recent).'">' : '✨' ?></div>
            <div class="dropbox" data-target="#logo_section_recent">
              <input class="input" id="logo_section_recent" name="logo_section_recent" value="<?= e($logo_recent) ?>" placeholder="https://...png">
            </div>
          </div>
        </div>

        <div style="grid-column:1/-1"><button class="btn">Simpan</button></div>
      </form>
    </div>

    <div class="panel">
      <h3 style="margin-top:0">Pratinjau Cover Walkthrough (drag untuk fokus, double-click untuk simpan)</h3>
      <div class="list">
        <?php foreach ($items as $it):
          $img = $it['cover_url'] ?: $it['game_image'];
          $fx  = (int)($it['cover_url'] ? $it['w_fx'] : $it['g_fx']);
          $fy  = (int)($it['cover_url'] ? $it['w_fy'] : $it['g_fy']);
        ?>
          <div class="item">
            <div class="thumb editing">
              <?php if ($img): ?>
                <img class="cover-adjustable"
                     data-table="<?= $it['cover_url'] ? 'walkthroughs' : 'games' ?>"
                     data-id="<?= $it['cover_url'] ? (int)$it['id'] : (int)$it['game_id'] ?>"
                     src="<?= e($img) ?>"
                     alt="<?= e($it['title']) ?>"
                     style="object-position:<?= $fx ?>% <?= $fy ?>%">
              <?php else: ?><div class="small" style="opacity:.7">No image</div><?php endif; ?>
            </div>
            <div>
              <div class="title"><?= e($it['title']) ?></div>
              <div class="small mono">WT#<?= (int)$it['id'] ?> • Game: <?= e($it['game_title']) ?></div>
              <div class="small" style="opacity:.8;margin-top:.35rem">Simpan = <b>double-click</b> pada gambar.</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<script>
(function(){
  // set preview dari url input
  function setPrev(sel, url){ const pv=document.querySelector(sel); if(pv) pv.innerHTML = url ? '<img src="'+url+'">' : '—'; }
  [['#site_logo_url','#pv_brand'],['#logo_section_games','#pv_games'],['#logo_section_featured','#pv_feat'],['#logo_section_recent','#pv_recent']]
  .forEach(([inp,pv])=>{
    const el=document.querySelector(inp); if(el) el.addEventListener('input',()=>setPrev(pv, el.value.trim()));
  });

  // drag & drop uploader
  document.querySelectorAll('.dropbox').forEach(box=>{
    const target = document.querySelector(box.dataset.target);
    const pvSel  = '#pv_' + (target.id.replace('site_logo_url','brand')
                              .replace('logo_section_',''));
    function up(file){
      const fd=new FormData();
      fd.append('file', file);
      const ct=document.querySelector('input[name="csrf_token"]'); if(ct) fd.append('csrf_token', ct.value);
      box.classList.add('drag');
      fetch('/arcadia/public/admin/upload_logo.php',{method:'POST',body:fd})
        .then(r=>r.json()).then(j=>{
          box.classList.remove('drag');
          if(!j.ok){ alert(j.msg||'Upload gagal'); return; }
          target.value=j.url; setPrev(pvSel, j.url);
        }).catch(()=>{ box.classList.remove('drag'); alert('Upload error'); });
    }
    box.addEventListener('click', ()=>{
      const f=document.createElement('input'); f.type='file'; f.accept='image/*';
      f.onchange=()=>{ if(f.files && f.files[0]) up(f.files[0]); }; f.click();
    });
    box.addEventListener('dragover',e=>{e.preventDefault(); box.classList.add('drag');});
    box.addEventListener('dragleave',()=>box.classList.remove('drag'));
    box.addEventListener('drop',e=>{e.preventDefault(); box.classList.remove('drag'); const file=e.dataTransfer.files?.[0]; if(file) up(file);});
  });

  // mini editor fokus cover
  document.querySelectorAll('img.cover-adjustable').forEach(img=>{
    let down=false;
    img.addEventListener('pointerdown',e=>{down=true;img.setPointerCapture(e.pointerId);img.style.cursor='grabbing';});
    img.addEventListener('pointerup',  ()=>{down=false;img.style.cursor='grab';});
    img.addEventListener('pointerleave',()=>{down=false;img.style.cursor='grab';});
    img.addEventListener('pointermove',e=>{
      if(!down) return;
      const r=img.getBoundingClientRect();
      const fx=Math.min(100,Math.max(0,((e.clientX-r.left)/r.width)*100));
      const fy=Math.min(100,Math.max(0,((e.clientY-r.top )/r.height)*100));
      img.style.objectPosition = fx.toFixed(0)+'% '+fy.toFixed(0)+'%';
    });
    img.addEventListener('dblclick', async ()=>{
      const [px,py]=getComputedStyle(img).objectPosition.split(' ');
      const fx=parseInt(px), fy=parseInt(py);
      const table=img.dataset.table, id=img.dataset.id;
      try{
        const res=await fetch('/arcadia/public/admin/cover_focus.php',{
          method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:new URLSearchParams({table,id,x:fx,y:fy})
        });
        alert((await res.text()).trim()||'Tersimpan!');
      }catch{ alert('Gagal menyimpan'); }
    });
  });
})();
</script>

<?php include __DIR__ . '/_footer.php';
    