<?php
// /arcadia/public/admin/appearance.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';

// settings.php boleh ada / tidak — kita TIDAK mengandalkannya
require_admin();

// ==== Pastikan kolom-kolom games tersedia (sekali jalan aman dipanggil) ====
$__hasCol = function (mysqli $db, string $table, string $col): bool {
  $t = $db->real_escape_string($table);
  $c = $db->real_escape_string($col);
  $sql = "SELECT COUNT(*) c FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$t}' AND COLUMN_NAME='{$c}'";
  $r = $db->query($sql);
  $row = $r ? $r->fetch_assoc() : null;
  return (int) ($row['c'] ?? 0) > 0;
};

if (!$__hasCol($mysqli, 'games', 'image_original_url')) {
  @db_exec($mysqli, "ALTER TABLE games ADD COLUMN image_original_url TEXT NULL", [], "");
}
if (!$__hasCol($mysqli, 'games', 'cover_focus_x')) {
  @db_exec($mysqli, "ALTER TABLE games ADD COLUMN cover_focus_x INT NULL", [], "");
}
if (!$__hasCol($mysqli, 'games', 'cover_focus_y')) {
  @db_exec($mysqli, "ALTER TABLE games ADD COLUMN cover_focus_y INT NULL", [], "");
}


/* ---------- CSRF fallback ---------- */
$__csrf = __DIR__ . '/../../lib/csrf.php';
if (file_exists($__csrf))
  require_once $__csrf;
if (!function_exists('csrf_field')) {
  function csrf_field(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE)
      session_start();
    if (empty($_SESSION['csrf_token']))
      $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . '">';
  }
}
if (!function_exists('csrf_verify')) {
  function csrf_verify(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
      return;
    if (session_status() !== PHP_SESSION_ACTIVE)
      session_start();
    $ok = isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
      hash_equals($_SESSION['csrf_token'], (string) $_POST['csrf_token']);
    if (!$ok) {
      http_response_code(419);
      die('CSRF token mismatch');
    }
  }
}
if (!function_exists('e')) {
  function e($s)
  {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  }
}

/* ---------- pastikan app_settings ada ---------- */
$mysqli->query("
  CREATE TABLE IF NOT EXISTS app_settings (
    `key`   VARCHAR(64) PRIMARY KEY,
    `value` TEXT
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ---------- helper settings yang stabil ---------- */
function arc_table_has(mysqli $db, string $table): bool
{
  $t = $db->real_escape_string($table);
  $r = $db->query("SHOW TABLES LIKE '{$t}'");
  return $r && $r->num_rows > 0;
}
function arc_table_has_col(mysqli $db, string $table, string $col): bool
{
  $t = $db->real_escape_string($table);
  $c = $db->real_escape_string($col);
  $q = "SELECT COUNT(*) c FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{$t}' AND COLUMN_NAME='{$c}'";
  $r = $db->query($q);
  $row = $r ? $r->fetch_assoc() : null;
  return (int) ($row['c'] ?? 0) > 0;
}
function arc_setting_get(mysqli $db, string $key, string $default = ''): string
{
  $row = db_one($db, "SELECT `value` FROM app_settings WHERE `key`=?", [$key], 's');
  if ($row && $row['value'] !== null && $row['value'] !== '')
    return (string) $row['value'];

  // fallback kalau ada tabel settings lama
  if (arc_table_has($db, 'settings')) {
    if (arc_table_has_col($db, 'settings', 'key') && arc_table_has_col($db, 'settings', 'value')) {
      $row = db_one($db, "SELECT `value` FROM settings WHERE `key`=?", [$key], 's');
      if ($row && $row['value'] !== '')
        return (string) $row['value'];
    } elseif (arc_table_has_col($db, 'settings', 'name') && arc_table_has_col($db, 'settings', 'value')) {
      $row = db_one($db, "SELECT `value` FROM settings WHERE `name`=?", [$key], 's');
      if ($row && $row['value'] !== '')
        return (string) $row['value'];
    }
  }
  return $default;
}
function arc_setting_set(mysqli $db, string $key, string $val): void
{
  db_exec($db, "REPLACE INTO app_settings(`key`,`value`) VALUES(?,?)", [$key, $val], 'ss');
  // mirror opsional ke tabel settings kalau struktur cocok
  if (arc_table_has($db, 'settings')) {
    if (arc_table_has_col($db, 'settings', 'key') && arc_table_has_col($db, 'settings', 'value')) {
      db_exec($db, "REPLACE INTO settings(`key`,`value`) VALUES(?,?)", [$key, $val], 'ss');
    } elseif (arc_table_has_col($db, 'settings', 'name') && arc_table_has_col($db, 'settings', 'value')) {
      $row = db_one($db, "SELECT 1 x FROM settings WHERE `name`=? LIMIT 1", [$key], 's');
      if ($row)
        db_exec($db, "UPDATE settings SET `value`=? WHERE `name`=?", [$val, $key], 'ss');
      else
        db_exec($db, "INSERT INTO settings(`name`,`value`) VALUES(?,?)", [$key, $val], 'ss');
    }
  }
}

/* ---------- upload helper (pakai nama unik: TIDAK bentrok helpers.php) ---------- */
function arc_save_uploaded_image(string $field, string $destDir, string $prefix = 'img', int $maxMB = 6): ?string
{
  if (empty($_FILES[$field]['name']) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
    return null;
  if (($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK)
    throw new Exception('Upload gagal.');

  $tmp = $_FILES[$field]['tmp_name'] ?? '';
  $size = (int) ($_FILES[$field]['size'] ?? 0);
  if (!$tmp || $size <= 0)
    return null;
  if ($size > $maxMB * 1024 * 1024)
    throw new Exception("Ukuran gambar > {$maxMB}MB.");

  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($tmp);
  $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
  if (!isset($map[$mime]))
    throw new Exception('Format didukung: JPG/PNG/WEBP/GIF.');
  if (!@getimagesize($tmp))
    throw new Exception('Berkas bukan gambar valid.');

  // path filesystem di /public
  $rootPublic = realpath(__DIR__ . '/../../public');
  $dir = rtrim($rootPublic, DIRECTORY_SEPARATOR) . '/' . trim($destDir, '/');
  if (!is_dir($dir))
    @mkdir($dir, 0775, true);

  $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
  if (!move_uploaded_file($tmp, $dir . '/' . $name))
    throw new Exception('Gagal menyimpan file.');

  // base web dinamis: /arcadia/public
  $webBase = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/arcadia/public'));
  $webBase = rtrim($webBase, '/'); // ex: /arcadia/public
  return $webBase . '/' . trim($destDir, '/') . '/' . $name;
}

/* preview admin: normalkan uploads/... -> /arcadia/public/uploads/... */
function _norm_admin_icon(string $u): string
{
  $u = trim($u);
  if ($u === '')
    return '';
  if (preg_match('~^/?uploads/~', $u)) {
    $base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/arcadia/public'));
    $u = rtrim($base, '/') . '/' . ltrim($u, '/');
  }
  $u = preg_replace('~^/arcadia/public/(?:public/)+~', '/arcadia/public/', $u);
  return $u;
}

/* ---------- baca setting awal ---------- */
$brand_name = arc_setting_get($mysqli, 'brand_name', 'Arcadia');
$site_logo = arc_setting_get($mysqli, 'site_logo_url', '');
$hero_title = arc_setting_get($mysqli, 'hero_title', 'Arcadia');
$hero_sub = arc_setting_get($mysqli, 'hero_subtitle', 'Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.');
$logo_games = arc_setting_get($mysqli, 'logo_section_games', '');
$logo_feat = arc_setting_get($mysqli, 'logo_section_featured', '');
$logo_recent = arc_setting_get($mysqli, 'logo_section_recent', '');

$site_logo = _norm_admin_icon($site_logo);
$logo_games = _norm_admin_icon($logo_games);
$logo_feat = _norm_admin_icon($logo_feat);
$logo_recent = _norm_admin_icon($logo_recent);

/* ---------- simpan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['__form__'] ?? '') === 'settings') {
  csrf_verify();
  try {
    // text
    $brand_name = trim($_POST['brand_name'] ?? 'Arcadia');
    $hero_title = trim($_POST['hero_title'] ?? 'Arcadia');
    $hero_sub = trim($_POST['hero_subtitle'] ?? '');

    arc_setting_set($mysqli, 'brand_name', $brand_name);
    arc_setting_set($mysqli, 'hero_title', $hero_title);
    arc_setting_set($mysqli, 'hero_subtitle', $hero_sub);

    // files
    if (!empty($_FILES['site_logo_file']['name'])) {
      if ($u = arc_save_uploaded_image('site_logo_file', 'uploads/branding', 'logo', 6)) {
        arc_setting_set($mysqli, 'site_logo_url', $u);
        $site_logo = _norm_admin_icon($u);
      }
    }
    if (!empty($_FILES['games_icon_file']['name'])) {
      if ($u = arc_save_uploaded_image('games_icon_file', 'uploads/branding', 'games', 4)) {
        arc_setting_set($mysqli, 'logo_section_games', $u);
        $logo_games = _norm_admin_icon($u);
      }
    }
    if (!empty($_FILES['featured_icon_file']['name'])) {
      if ($u = arc_save_uploaded_image('featured_icon_file', 'uploads/branding', 'featured', 4)) {
        arc_setting_set($mysqli, 'logo_section_featured', $u);
        $logo_feat = _norm_admin_icon($u);
      }
    }
    if (!empty($_FILES['recent_icon_file']['name'])) {
      if ($u = arc_save_uploaded_image('recent_icon_file', 'uploads/branding', 'recent', 4)) {
        arc_setting_set($mysqli, 'logo_section_recent', $u);
        $logo_recent = _norm_admin_icon($u);
      }
    }

    flash('ok', 'Perubahan disimpan ✔');
    redirect('appearance.php');
  } catch (Exception $ex) {
    flash('err', $ex->getMessage());
    redirect('appearance.php');
  }
}

include __DIR__ . '/_header.php';
?>
<style>
  .app-wrap {
    display: grid;
    gap: 18px
  }

  .grid {
    display: grid;
    gap: 14px;
    grid-template-columns: 1fr 1fr
  }

  @media(max-width:980px) {
    .grid {
      grid-template-columns: 1fr
    }
  }

  .panel {
    border-radius: 16px;
    padding: 16px;
    background: var(--panel);
    border: 1px solid var(--border)
  }

  .input {
    width: 100%
  }

  .dz {
    border: 1px dashed rgba(255, 255, 255, .25);
    border-radius: 12px;
    padding: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015))
  }

  .dz .preview {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .12);
    background: #151521;
    display: grid;
    place-items: center
  }

  .dz .preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block
  }

  .dz .hint {
    opacity: .8
  }

  .dz.drag {
    outline: 2px solid rgba(167, 139, 250, .6);
    outline-offset: 4px
  }

  .input-real {
    position: absolute !important;
    width: 1px;
    height: 1px;
    opacity: 0;
    pointer-events: none
  }

  .toast {
    margin: 0 0 .75rem;
    padding: .55rem .8rem;
    border-radius: 10px;
    font-weight: 700;
    display: inline-block
  }

  .toast.ok {
    background: rgba(34, 197, 94, .16);
    border: 1px solid rgba(34, 197, 94, .28)
  }

  .toast.err {
    background: rgba(239, 68, 68, .16);
    border: 1px solid rgba(239, 68, 68, .28)
  }
</style>

<div class="admin-main">
  <div class="app-wrap">
    <div class="panel">
      <h2 style="margin-top:0">Tampilan Situs</h2>
      <?php if ($m = flash('ok')): ?>
        <div class="toast ok" id="toastOk"><?= e($m) ?></div><?php endif; ?>
      <?php if ($m = flash('err')): ?>
        <div class="toast err" id="toastErr"><?= e($m) ?></div><?php endif; ?>

      <form method="POST" action="appearance.php" class="grid" enctype="multipart/form-data">
        <?php csrf_field(); ?>
        <input type="hidden" name="__form__" value="settings">

        <div class="panel">
          <label class="small">Nama Brand</label>
          <input class="input" type="text" name="brand_name" value="<?= e($brand_name) ?>" placeholder="Arcadia">
        </div>

        <div class="panel">
          <label class="small">Logo Brand (Navbar)</label>
          <div class="dz" data-dz>
            <div class="preview"><?= $site_logo ? '<img src="' . e($site_logo) . '" onerror="this.remove()">' : '⟡' ?>
            </div>
            <div class="hint">Tarik & lepas atau klik untuk pilih (JPG/PNG/WEBP/GIF)</div>
            <input class="input-real" type="file" name="site_logo_file" accept="image/*">
          </div>
        </div>

        <div class="panel">
          <label class="small">Hero Title</label>
          <input class="input" type="text" name="hero_title" value="<?= e($hero_title) ?>">
        </div>
        <div class="panel">
          <label class="small">Hero Subtitle</label>
          <input class="input" type="text" name="hero_subtitle" value="<?= e($hero_sub) ?>">
        </div>

        <div class="panel">
          <label class="small">Logo “Daftar Game”</label>
          <div class="dz" data-dz>
            <div class="preview">
              <?= $logo_games ? '<img src="' . e($logo_games) . '" onerror="this.remove()">' : '🎮' ?>
            </div>
            <div class="hint">Tarik & lepas atau klik untuk pilih</div>
            <input class="input-real" type="file" name="games_icon_file" accept="image/*">
          </div>
        </div>

        <div class="panel">
          <label class="small">Logo “Panduan Unggulan”</label>
          <div class="dz" data-dz>
            <div class="preview"><?= $logo_feat ? '<img src="' . e($logo_feat) . '" onerror="this.remove()">' : '⭐' ?>
            </div>
            <div class="hint">Tarik & lepas atau klik untuk pilih</div>
            <input class="input-real" type="file" name="featured_icon_file" accept="image/*">
          </div>
        </div>

        <div class="panel">
          <label class="small">Logo “Baru Diupdate”</label>
          <div class="dz" data-dz>
            <div class="preview">
              <?= $logo_recent ? '<img src="' . e($logo_recent) . '" onerror="this.remove()">' : '✨' ?>
            </div>
            <div class="hint">Tarik & lepas atau klik untuk pilih</div>
            <input class="input-real" type="file" name="recent_icon_file" accept="image/*">
          </div>
        </div>

        <div style="grid-column:1/-1"><button class="btn">Simpan</button></div>
      </form>
    </div>
  </div>
</div>

<script>
  setTimeout(() => document.getElementById('toastOk')?.remove(), 2400);
  setTimeout(() => document.getElementById('toastErr')?.remove(), 3800);

  // Dropzone
  document.querySelectorAll('[data-dz]').forEach((dz) => {
    const input = dz.querySelector('input[type="file"]');
    const prev = dz.querySelector('.preview');

    function setPreview(file) {
      if (!file) return;
      if (!file.type.startsWith('image/')) { alert('File harus gambar'); return; }
      const r = new FileReader();
      r.onload = e => {
        prev.innerHTML = '';
        const im = document.createElement('img');
        im.src = e.target.result;
        im.style.width = '100%'; im.style.height = '100%'; im.style.objectFit = 'cover';
        prev.appendChild(im);
      };
      r.readAsDataURL(file);
    }

    dz.addEventListener('click', () => input.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
    dz.addEventListener('drop', e => {
      e.preventDefault(); dz.classList.remove('drag');
      const file = e.dataTransfer.files?.[0]; if (file) { input.files = e.dataTransfer.files; setPreview(file); }
    });
    input.addEventListener('change', () => input.files[0] && setPreview(input.files[0]));
  });
</script>

<?php
// ===== Data untuk editor crop (tanpa fitur fokus) =====
$gamesForCrop = db_all($mysqli, "SELECT id,title,image_url FROM games ORDER BY id DESC LIMIT 24");
?>
<section class="panel" style="margin-top:16px">
  <h3 style="margin:0 0 10px">Crop Cover Game</h3>
  <p class="small" style="opacity:.85;margin-top:0">
    Klik <b>Edit Crop</b> untuk zoom & crop sesuai kotak. (Fitur geser-fokus dinonaktifkan)
  </p>

  <style>
    .crop-grid {
      display: grid;
      gap: 16px;
      grid-template-columns: repeat(2, minmax(0, 1fr))
    }

    @media (max-width:980px) {
      .crop-grid {
        grid-template-columns: 1fr
      }
    }

    .crop-item {
      background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
      border: 1px solid rgba(255, 255, 255, .08);
      border-radius: 16px;
      padding: 12px
    }

    .crop-frame {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      aspect-ratio: 16/9;
      background: #0f0f16;
      border: 1px dashed rgba(255, 255, 255, .16)
    }

    .crop-frame img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      user-select: none
    }

    .crop-ctrl {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-top: 10px
    }

    .btn.sm {
      padding: .45rem .8rem;
      border-radius: 10px
    }

    /* Modal */
    .modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .55);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000
    }

    .modal.show {
      display: flex
    }

    .modal-card {
      width: min(980px, 92vw);
      background: #0f0f16;
      border: 1px solid rgba(255, 255, 255, .12);
      border-radius: 18px;
      overflow: hidden
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 14px;
      border-bottom: 1px solid rgba(255, 255, 255, .08)
    }

    .modal-body {
      padding: 12px
    }

    #cropperBox {
      max-height: 64vh
    }

    #cropperBox img {
      max-width: 100%;
      display: block
    }
  </style>

  <div class="crop-grid" id="cropGrid">
    <?php foreach ($gamesForCrop as $g): ?>
      <div class="crop-item" data-id="<?= (int) $g['id'] ?>">
        <div class="crop-frame">
          <img class="crop-img" src="<?= e($g['image_url']) ?>" alt="<?= e($g['title']) ?>">
        </div>
        <div class="crop-ctrl">
          <button class="btn sm" data-crop>Edit Crop</button>
        </div>
        <div class="small" style="opacity:.8;margin-top:6px"><?= e($g['title']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Modal Crop -->
  <div class="modal" id="cropperModal" aria-hidden="true">
    <div class="modal-card">
      <div class="modal-head">
        <strong>Edit Crop Cover</strong>
        <div>
          <button class="btn sm" id="btnCropSave">Simpan Crop</button>
          <button class="btn sm" id="btnCropClose">Tutup</button>
        </div>
      </div>
      <div class="modal-body">
        <div id="cropperBox"><img id="cropperImg" src="" alt=""></div>
        <p class="small" style="opacity:.85;margin-top:8px">
          Tips: drag untuk geser, scroll untuk zoom. Aspek otomatis mengikuti kotak (16:9).
        </p>
      </div>
    </div>
  </div>
</section>

<!-- Cropper.js -->
<link href="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
<script src="https://unpkg.com/cropperjs@1.6.2/dist/cropper.min.js"></script>

<script>
  (() => {
    const modal = document.getElementById('cropperModal');
    const imgEl = document.getElementById('cropperImg');
    const btnSave = document.getElementById('btnCropSave');
    const btnClose = document.getElementById('btnCropClose');
    let cropper = null, currentCard = null;

    // buka modal crop
    document.querySelectorAll('.crop-item [data-crop]').forEach(btn => {
      btn.addEventListener('click', () => {
        currentCard = btn.closest('.crop-item');
        const src = currentCard.querySelector('.crop-img').getAttribute('src');
        imgEl.src = src.replace(/\?v=\d+$/, ''); // hilangkan cache buster
        imgEl.onload = () => {
          modal.classList.add('show');
          cropper?.destroy();
          cropper = new Cropper(imgEl, {
            viewMode: 1,
            aspectRatio: 16 / 9,
            autoCropArea: 1,
            movable: true,
            zoomable: true,
            scalable: false,
            rotatable: false,
            responsive: true,
          });
        };
      });
    });

    const closeModal = () => { cropper?.destroy(); cropper = null; modal.classList.remove('show'); };
    btnClose.addEventListener('click', closeModal);
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

    // simpan crop -> upload blob ke cover_crop.php
    btnSave.addEventListener('click', () => {
      if (!cropper || !currentCard) return;
      const gid = currentCard.dataset.id;
      cropper.getCroppedCanvas({ width: 1600, height: 900 }).toBlob(async (blob) => {
        const fd = new FormData();
        fd.append('game_id', gid);
        fd.append('cover', blob, 'crop.webp');
        try {
          const res = await fetch('/arcadia/public/admin/cover_crop.php', { method: 'POST', body: fd });
          const j = await res.json();
          if (!j.ok) throw new Error(j.msg || 'Gagal menyimpan crop');
          // update preview
          currentCard.querySelector('.crop-img').src = j.url; // sudah berisi ?v=timestamp
          alert('Crop tersimpan!');
          closeModal();
        } catch (err) { alert(err.message || 'Gagal menyimpan crop'); }
      }, 'image/webp', 0.92);
    });
  })();
</script>


<?php include __DIR__ . '/_footer.php';
