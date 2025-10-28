<?php
// public/admin/profile.php — Owner/Admin Profile (tahan skema)
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/validation.php'; // <-- penting: biar str_trim tersedia
require_admin();
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('e')) {
  function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$uid = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
if ($uid <= 0) redirect('/arcadia/public/login.php');

// ---------- helper cek kolom ----------
function users_has_col(mysqli $mysqli, string $col): bool {
  $row = db_one(
    $mysqli,
    "SELECT COUNT(*) AS n
       FROM information_schema.COLUMNS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'users'
        AND COLUMN_NAME = ?",
    [$col],
    's'
  );
  return (int)($row['n'] ?? 0) > 0;
}

$hasAvatar    = users_has_col($mysqli, 'avatar_url');
$hasBanner    = users_has_col($mysqli, 'banner_url');
$hasBio       = users_has_col($mysqli, 'bio');
$hasCreated   = users_has_col($mysqli, 'created_at');
$hasLastLogin = users_has_col($mysqli, 'last_login_at');

// rakit SELECT aman berdasar kolom yang ada
$cols = ['id','name','email','role'];
if ($hasAvatar)    $cols[] = 'avatar_url';
if ($hasBanner)    $cols[] = 'banner_url';
if ($hasBio)       $cols[] = 'bio';
if ($hasCreated)   $cols[] = 'created_at';
if ($hasLastLogin) $cols[] = 'last_login_at';
$colList = implode(',', $cols);

function fetch_me(mysqli $mysqli, int $uid, string $colList) {
  return db_one($mysqli, "SELECT $colList FROM users WHERE id=?", [$uid], 'i');
}

$me  = fetch_me($mysqli, $uid, $colList) ?: [];
$ok = null; $err = null;

// direktori upload
$AVATAR_DIR = __DIR__ . '/../../public/uploads/avatars/';
$BANNER_DIR = __DIR__ . '/../../public/uploads/banners/';
$AVATAR_URL = '/arcadia/public/uploads/avatars/';
$BANNER_URL = '/arcadia/public/uploads/banners/';
@is_dir($AVATAR_DIR) || @mkdir($AVATAR_DIR, 0775, true);
@is_dir($BANNER_DIR) || @mkdir($BANNER_DIR, 0775, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');
    $allow = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    $finf  = new finfo(FILEINFO_MIME_TYPE);

    if ($action === 'upload_avatar') {
      if (!$hasAvatar) throw new Exception('Fitur avatar belum tersedia (kolom avatar_url tidak ada).');
      if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) throw new Exception('Gagal mengunggah avatar.');
      if ($_FILES['avatar']['size'] > 2*1024*1024) throw new Exception('Ukuran avatar maks 2 MB.');
      $mime = $finf->file($_FILES['avatar']['tmp_name']);
      if (!isset($allow[$mime])) throw new Exception('Format avatar harus PNG/JPG/WEBP.');
      if (!empty($me['avatar_url'])) @unlink($AVATAR_DIR . basename($me['avatar_url']));
      $fname = 'u'.$uid.'-'.date('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$allow[$mime];
      if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $AVATAR_DIR.$fname)) throw new Exception('Tidak bisa menyimpan avatar.');
      $url = $AVATAR_URL.$fname;
      db_exec($mysqli, "UPDATE users SET avatar_url=? WHERE id=?", [$url,$uid], 'si');
      $_SESSION['user']['avatar_url'] = $url;
      $ok='Avatar berhasil diperbarui.';
    }

    if ($action === 'remove_avatar') {
      if (!$hasAvatar) throw new Exception('Fitur avatar belum tersedia.');
      if (!empty($me['avatar_url'])) @unlink($AVATAR_DIR . basename($me['avatar_url']));
      db_exec($mysqli, "UPDATE users SET avatar_url=NULL WHERE id=?", [$uid], 'i');
      unset($_SESSION['user']['avatar_url']);
      $ok='Avatar dihapus.';
    }

    if ($action === 'upload_banner') {
      if (!$hasBanner) throw new Exception('Fitur banner belum tersedia (kolom banner_url tidak ada).');
      if (!isset($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) throw new Exception('Gagal mengunggah banner.');
      if ($_FILES['banner']['size'] > 3*1024*1024) throw new Exception('Ukuran banner maks 3 MB.');
      $mime = $finf->file($_FILES['banner']['tmp_name']);
      if (!isset($allow[$mime])) throw new Exception('Format banner harus PNG/JPG/WEBP.');
      if (!empty($me['banner_url'])) @unlink($BANNER_DIR . basename($me['banner_url']));
      $fname = 'b'.$uid.'-'.date('YmdHis').'-'.bin2hex(random_bytes(3)).'.'.$allow[$mime];
      if (!move_uploaded_file($_FILES['banner']['tmp_name'], $BANNER_DIR.$fname)) throw new Exception('Tidak bisa menyimpan banner.');
      $url = $BANNER_URL.$fname;
      db_exec($mysqli, "UPDATE users SET banner_url=? WHERE id=?", [$url,$uid], 'si');
      $ok='Banner berhasil diperbarui.';
    }

    if ($action === 'remove_banner') {
      if (!$hasBanner) throw new Exception('Fitur banner belum tersedia.');
      if (!empty($me['banner_url'])) @unlink($BANNER_DIR . basename($me['banner_url']));
      db_exec($mysqli, "UPDATE users SET banner_url=NULL WHERE id=?", [$uid], 'i');
      $ok='Banner dihapus.';
    }

    if ($action === 'save_profile') {
      // str_trim tersedia dari validation.php; bisa diganti trim() bila mau
      $name = str_trim($_POST['name'] ?? '');
      $pass = (string)($_POST['password'] ?? '');
      $bio  = $hasBio ? str_trim($_POST['bio'] ?? '') : null;

      if ($name === '') throw new Exception('Nama tidak boleh kosong.');
      if ($hasBio) {
        db_exec($mysqli, "UPDATE users SET name=?, bio=? WHERE id=?", [$name,$bio,$uid], 'ssi');
      } else {
        db_exec($mysqli, "UPDATE users SET name=? WHERE id=?", [$name,$uid], 'si');
      }
      $_SESSION['user']['name'] = $name;

      if ($pass !== '') {
        if (mb_strlen($pass) < 8) throw new Exception('Password minimal 8 karakter.');
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        db_exec($mysqli, "UPDATE users SET password_hash=? WHERE id=?", [$hash,$uid], 'si');
      }
      $ok='Profil berhasil diperbarui.';
    }

    // refresh data
    $me = fetch_me($mysqli, $uid, $colList);
  } catch (Exception $e) {
    $err = $e->getMessage();
  }
}

$mode = ($_GET['mode'] ?? '') === 'edit' ? 'edit' : 'view';

include __DIR__ . '/_header.php';
?>
<style>
  :root{--line:rgba(255,255,255,.10);--soft:rgba(255,255,255,.06)}
  .page-title{font-size:1.35rem;font-weight:900;margin:0 0 10px}
  .profile-layout{display:grid;gap:18px;grid-template-columns:1fr <?php echo $mode==='edit'?'420px':'1fr'; ?>}
  <?php if($mode!=='edit'):?>.profile-layout{grid-template-columns:1fr}<?php endif;?>
  .profile-card{border:1px solid var(--line);border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));padding:18px}
  .profile-inner{border-radius:18px;background:rgba(0,0,0,.18);padding:14px 14px 18px}
  .banner{width:100%;aspect-ratio:6/2;border-radius:14px;overflow:hidden;background:var(--soft)}
  .banner img{width:100%;height:100%;object-fit:cover}
  .ava-out{
    width:132px;height:132px;border-radius:28px;margin:-36px auto 8px;
    border:3px solid rgba(0,0,0,.35);overflow:hidden;
    background:rgba(255,255,255,.12);display:grid;place-items:center;
    font-weight:900;font-size:2rem;box-shadow:0 10px 24px rgba(0,0,0,.35)
  }
  .ava-out img{width:100%;height:100%;object-fit:cover}
  .profile-body{padding:8px 8px 8px;text-align:center}
  .name{font-size:1.4rem;font-weight:900}
  .muted{opacity:.9}
  .chips{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:10px}
  .chip{padding:.35rem .7rem;border-radius:999px;border:1px solid var(--line);background:var(--soft);font-size:.9rem}
  .btn-primary{display:inline-block;padding:.85rem 1rem;border-radius:12px;border:1px solid var(--primary);background:var(--primary);color:#0f0f16;box-shadow:0 10px 22px var(--ring);cursor:pointer}
  .btn-ghost{padding:.75rem 1rem;border-radius:12px;border:1px solid var(--line);background:transparent;color:inherit}
  .btn-ghost:hover{background:var(--soft)}
  .edit-panel{border:1px solid var(--line);border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.03),rgba(255,255,255,.015));padding:16px;justify-self:end;width:100%;max-width:420px}
  .panel-title{margin:0 0 .8rem;font-weight:900}
  .section{border-top:1px solid var(--line);margin-top:14px;padding-top:14px}
  .row{display:flex;flex-direction:column;gap:8px;margin-bottom:12px}
  .alert{margin:.4rem 0;padding:.6rem .8rem;border-radius:12px;border:1px solid var(--line)}
  .ok{background:rgba(34,197,94,.08);border-color:rgba(34,197,94,.35)}
  .err{background:rgba(244,63,94,.08);border-color:rgba(244,63,94,.35)}
  .drop{display:flex;gap:12px;align-items:center;border:1px dashed var(--line);border-radius:14px;padding:10px;cursor:pointer;background:rgba(255,255,255,.03)}
  .drop.is-drag{background:rgba(167,139,250,.08);border-color:var(--primary)}
  .drop-preview{width:84px;height:60px;border-radius:10px;overflow:hidden;background:var(--soft);display:grid;place-items:center}
  .drop-preview img{width:100%;height:100%;object-fit:cover}
  .hint{opacity:.85;font-size:.9rem}
  @media(max-width:1000px){.profile-layout{grid-template-columns:1fr}.edit-panel{justify-self:stretch;max-width:none}}
</style>

<div class="page-title">Profil</div>

<div class="profile-layout">
  <!-- Kartu profil -->
  <section class="profile-card">
    <div class="profile-inner">
      <div class="banner">
        <?php if ($hasBanner && !empty($me['banner_url'])): ?>
          <img src="<?= e($me['banner_url']) ?>" alt="banner">
        <?php endif; ?>
      </div>

      <!-- AVATAR DI LUAR BANNER -->
      <div class="ava-out">
        <?php if ($hasAvatar && !empty($me['avatar_url'])): ?>
          <img src="<?= e($me['avatar_url']) ?>" alt="avatar">
        <?php else: ?>
          <?= e(mb_strtoupper(mb_substr($me['name'] ?? 'A',0,1))) ?>
        <?php endif; ?>
      </div>

      <div class="profile-body">
        <div class="name"><?= e($me['name'] ?? 'Owner') ?></div>
        <div class="muted"><?= e($me['email'] ?? '') ?></div>
        <?php if ($hasBio && !empty($me['bio'])): ?>
          <div style="margin-top:8px"><?= nl2br(e($me['bio'])) ?></div>
        <?php endif; ?>
        <div class="chips">
          <span class="chip">Role: <?= e(strtoupper($me['role'] ?? 'OWNER')) ?></span>
          <?php if ($hasCreated && !empty($me['created_at'])): ?><span class="chip">Dibuat: <?= e($me['created_at']) ?></span><?php endif; ?>
          <?php if ($hasLastLogin && !empty($me['last_login_at'])): ?><span class="chip">Login terakhir: <?= e($me['last_login_at']) ?></span><?php endif; ?>
        </div>

        <?php if ($mode !== 'edit'): ?>
          <div style="margin-top:16px">
            <a class="btn-primary" href="/arcadia/public/admin/profile.php?mode=edit">Edit Profil</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($mode === 'edit'): ?>
  <!-- Panel edit kanan -->
  <aside class="edit-panel">
    <div class="panel-title">Edit Profil</div>
    <?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert err"><?= e($err) ?></div><?php endif; ?>

    <?php if ($hasAvatar): ?>
    <div class="section">
      <h4 style="margin:0 0 .6rem">Avatar</h4>
      <!-- Form Upload Avatar -->
      <form method="post" enctype="multipart/form-data" id="frmAvatar">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="upload_avatar">
        <label class="drop" id="dropAvatar">
          <div class="drop-preview" id="pvAvatar">
            <?php if (!empty($me['avatar_url'])): ?><img src="<?= e($me['avatar_url']) ?>" alt="">
            <?php else: ?><span>A</span><?php endif; ?>
          </div>
          <div>
            <div><strong>Tarik & jatuhkan</strong> gambar ke sini atau klik untuk pilih</div>
            <div class="hint">PNG/JPG/WEBP • maks 2 MB</div>
            <input type="file" name="avatar" id="inpAvatar" accept="image/png,image/jpeg,image/webp" hidden required>
          </div>
        </label>
        <button class="btn-primary" style="margin-top:10px">Upload</button>
      </form>

      <!-- Form Hapus Avatar (terpisah, tidak nested) -->
      <?php if (!empty($me['avatar_url'])): ?>
      <form method="post" style="margin-top:8px">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="remove_avatar">
        <button class="btn-ghost">Hapus Avatar</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($hasBanner): ?>
    <div class="section">
      <h4 style="margin:0 0 .6rem">Banner</h4>
      <!-- Form Upload Banner -->
      <form method="post" enctype="multipart/form-data" id="frmBanner">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="upload_banner">
        <label class="drop" id="dropBanner">
          <div class="drop-preview" id="pvBanner" style="width:140px;height:84px">
            <?php if (!empty($me['banner_url'])): ?><img src="<?= e($me['banner_url']) ?>" alt=""><?php endif; ?>
          </div>
          <div>
            <div><strong>Tarik & jatuhkan</strong> gambar banner ke sini atau klik untuk pilih</div>
            <div class="hint">PNG/JPG/WEBP • maks 3 MB</div>
            <input type="file" name="banner" id="inpBanner" accept="image/png,image/jpeg,image/webp" hidden required>
          </div>
        </label>
        <button class="btn-primary" style="margin-top:10px">Upload</button>
      </form>

      <!-- Form Hapus Banner (terpisah) -->
      <?php if (!empty($me['banner_url'])): ?>
      <form method="post" style="margin-top:8px">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="remove_banner">
        <button class="btn-ghost">Hapus Banner</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section">
      <h4 style="margin:0 0 .6rem">Data Profil</h4>
      <form method="post">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="save_profile">
        <div class="row">
          <label for="name"><strong>Nama</strong></label>
          <input class="input" id="name" name="name" value="<?= e($me['name'] ?? '') ?>" required>
        </div>
        <?php if ($hasBio): ?>
        <div class="row">
          <label for="bio"><strong>Bio</strong></label>
          <textarea class="input" id="bio" name="bio" rows="3" placeholder="Tulis bio singkat..."><?= e($me['bio'] ?? '') ?></textarea>
        </div>
        <?php endif; ?>
        <div class="row">
          <label for="password"><strong>Password baru (opsional)</strong></label>
          <input class="input" id="password" name="password" type="password" placeholder="Kosongkan jika tidak ingin mengubah">
          <div class="hint">Minimal 8 karakter, gunakan kombinasi huruf & angka.</div>
        </div>
        <div style="display:flex;gap:10px">
          <button class="btn-primary">Simpan</button>
          <a class="btn-ghost" href="/arcadia/public/admin/profile.php">Batal</a>
        </div>
      </form>
    </div>
  </aside>
  <?php endif; ?>
</div>

<script>
  function setPreview(el, file){
    const img=document.createElement('img'); img.src=URL.createObjectURL(file);
    img.onload=()=>URL.revokeObjectURL(img.src); el.innerHTML=''; el.appendChild(img);
  }
  function bindDrop(zoneId,inputId,previewId,maxBytes){
    const zone=document.getElementById(zoneId), inp=document.getElementById(inputId), pv=document.getElementById(previewId);
    if(!zone||!inp) return;
    zone.addEventListener('click',()=>inp.click());
    zone.addEventListener('dragover',e=>{e.preventDefault(); zone.classList.add('is-drag');});
    zone.addEventListener('dragleave',()=>zone.classList.remove('is-drag'));
    zone.addEventListener('drop',e=>{
      e.preventDefault(); zone.classList.remove('is-drag');
      const f=e.dataTransfer.files[0]; if(!f) return;
      if(!['image/png','image/jpeg','image/webp'].includes(f.type)) {alert('Format harus PNG/JPG/WEBP'); return;}
      if(f.size>maxBytes){alert('Ukuran file terlalu besar.'); return;}
      inp.files=e.dataTransfer.files; if(pv) setPreview(pv,f);
    });
    inp.addEventListener('change',e=>{const f=e.target.files[0]; if(f&&pv) setPreview(pv,f);});
  }
  bindDrop('dropAvatar','inpAvatar','pvAvatar', 2*1024*1024);
  bindDrop('dropBanner','inpBanner','pvBanner', 3*1024*1024);
</script>

<?php include __DIR__ . '/_footer.php';
