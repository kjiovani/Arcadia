<?php
// ===== BOOT =====
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
  session_start();
}
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

/* ---------- helper asset + upload (fallback aman) ---------- */
if (!defined('UPLOADS_PATH')) {
  define('UPLOADS_PATH', realpath(__DIR__ . '/../../public') . '/uploads');
}
if (!function_exists('asset_url')) {
  function asset_url(string $p = '')
  {
    return '/arcadia/public/' . ltrim($p, '/');
  }
}

/**
 * Upload image (seragam untuk avatar/banner)
 * - Terima: JPG/PNG/WEBP/GIF
 * - Validasi: MIME asli + getimagesize (pastikan benar2 gambar)
 * - Output: URL relatif web (/arcadia/public/uploads/...)
 */
function upload_image(array $file, string $subdir, string $seed, int $maxMB = 5): ?string
{
  if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
    return null;
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK)
    throw new Exception('Upload gagal (code ' . ($file['error'] ?? -1) . ').');

  $tmp = $file['tmp_name'] ?? '';
  $size = (int) ($file['size'] ?? 0);
  if (!$tmp || $size <= 0)
    return null;
  if ($size > $maxMB * 1024 * 1024)
    throw new Exception("Ukuran gambar > {$maxMB}MB.");

  // Validasi MIME asli
  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($tmp);

  $map = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
  ];
  if (!isset($map[$mime]))
    throw new Exception('Format didukung: JPG/PNG/WEBP/GIF.');

  // Pastikan benar-benar gambar (ambil dimensi)
  $info = @getimagesize($tmp);
  if (!$info || empty($info[0]) || empty($info[1])) {
    throw new Exception('File tidak terdeteksi sebagai gambar yang valid.');
  }

  $dir = rtrim(UPLOADS_PATH, '/') . "/{$subdir}";
  if (!is_dir($dir))
    @mkdir($dir, 0775, true);

  $seed = $seed ?: 'img';
  $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~i', '-', $seed), '-'));
  $name = $slug . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $map[$mime];

  if (!move_uploaded_file($tmp, $dir . '/' . $name))
    throw new Exception('Gagal menyimpan file.');
  return asset_url("uploads/{$subdir}/{$name}");
}

/* ---------- cek kolom ada/tidak ---------- */
function users_has_col(string $col): bool
{
  global $mysqli;
  if (!$mysqli || !@$mysqli->ping())
    return false;
  $col = mysqli_real_escape_string($mysqli, $col);
  $res = $mysqli->query("SHOW COLUMNS FROM `users` LIKE '{$col}'");
  if (!$res)
    return false;
  $row = $res->fetch_assoc();
  $res->free();
  return (bool) $row;
}
function table_has_col(string $table, string $col): bool
{
  global $mysqli;
  $table = mysqli_real_escape_string($mysqli, $table);
  $col = mysqli_real_escape_string($mysqli, $col);
  $res = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
  return $res && $res->fetch_assoc();
}

// flags
$HAS_BIO = $HAS_AVATAR = $HAS_BANNER = $HAS_PWHASH = false;
if ($mysqli && @$mysqli->ping()) {
  $HAS_BIO = users_has_col('bio');
  $HAS_AVATAR = users_has_col('avatar_url');
  $HAS_BANNER = users_has_col('banner_url');
  $HAS_PWHASH = users_has_col('password_hash');
}

/* ---------- user login ---------- */
$me = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
$user_id = (int) ($me['id'] ?? 0);
if ($user_id <= 0) {
  header('Location: /arcadia/public/auth/login.php');
  exit;
}

/* ---------- SELECT dinamis ---------- */
$select = ['id', 'name', 'email', 'role'];
if ($HAS_AVATAR)
  $select[] = 'avatar_url';
if ($HAS_BANNER)
  $select[] = 'banner_url';
if ($HAS_BIO)
  $select[] = 'bio';

$u = db_one($mysqli, "SELECT " . implode(',', $select) . " FROM users WHERE id=?", [$user_id], 'i');
if (!$u) {
  $u = ['id' => $user_id, 'name' => '', 'email' => '', 'role' => 'OWNER'];
  if ($HAS_AVATAR)
    $u['avatar_url'] = '';
  if ($HAS_BANNER)
    $u['banner_url'] = '';
  if ($HAS_BIO)
    $u['bio'] = '';
}

/* ---------- ACTION ---------- */
$action = $_POST['action'] ?? ($_GET['action'] ?? 'view');
if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
    $fields = ['name=?'];
    $bind = [$name];

    if ($HAS_BIO) {
      $bio = str_trim($_POST['bio'] ?? '');
      $fields[] = 'bio=?';
      $bind[] = $bio;
    }

    // hapus avatar/banner jika diminta
    if ($HAS_AVATAR && !empty($_POST['del_avatar'])) {
      $fields[] = 'avatar_url=?';
      $bind[] = '';
    }
    if ($HAS_BANNER && !empty($_POST['del_banner'])) {
      $fields[] = 'banner_url=?';
      $bind[] = '';
    }

    if ($HAS_PWHASH) {
      $newpass = str_trim($_POST['new_password'] ?? '');
      $repass = str_trim($_POST['re_password'] ?? '');
      if ($newpass !== '') {
        if (strlen($newpass) < 6)
          throw new Exception('Password minimal 6 karakter.');
        if ($newpass !== $repass)
          throw new Exception('Konfirmasi password tidak cocok.');
        $fields[] = 'password_hash=?';
        $bind[] = password_hash($newpass, PASSWORD_DEFAULT);
      }
    }

    // upload baru (menimpa jika ada file)
    if ($HAS_AVATAR) {
      if ($url = upload_image($_FILES['avatar'] ?? [], 'avatars', $name, 5)) {
        $fields[] = 'avatar_url=?';
        $bind[] = $url;
      }
    }
    if ($HAS_BANNER) {
      if ($url = upload_image($_FILES['banner'] ?? [], 'banners', $name, 8)) {
        $fields[] = 'banner_url=?';
        $bind[] = $url;
      }
    }

    $bind[] = $user_id;
    $types = str_repeat('s', count($bind) - 1) . 'i';

    db_exec($mysqli, "UPDATE users SET " . implode(',', $fields) . " WHERE id=?", $bind, $types);
    flash('ok', 'Profil diperbarui.');
    redirect('profile.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('profile.php?action=edit');
  }
}

/* ---------- STATS & INSIGHTS ---------- */
$stat_games = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM games")['c'] ?? 0);
$stat_walks = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM walkthroughs")['c'] ?? 0);
$stat_chapters = (int) (db_one($mysqli, "SELECT COUNT(*) c FROM chapters")['c'] ?? 0);

function dir_size_mb($path)
{
  if (!is_dir($path))
    return 0;
  $size = 0;
  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
  foreach ($it as $f) {
    if ($f->isFile())
      $size += $f->getSize();
  }
  return round($size / 1024 / 1024, 2);
}
$uploads_root = defined('UPLOADS_PATH') ? UPLOADS_PATH : (realpath(__DIR__ . '/../../public') . '/uploads');
$uploads_used_mb = dir_size_mb($uploads_root);

function ini_bytes($v)
{
  $v = trim((string) $v);
  if ($v === '')
    return 0;
  $u = strtolower(substr($v, -1));
  $n = (float) $v;
  if ($u === 'g')
    $n *= 1024;
  if ($u === 'm')
    $n *= 1024;
  return (int) $n * 1024;
}
$upload_limit_mb = round(ini_bytes(ini_get('upload_max_filesize')) / 1024 / 1024);
$post_limit_mb = round(ini_bytes(ini_get('post_max_size')) / 1024 / 1024);

$ip_addr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$agent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 160);
$loginTime = date('d M Y, H:i');

/* ---------- VIEW ---------- */
include __DIR__ . '/_header.php';
?>
<style>
  /* ===== Shared ===== */
  .section {
    margin-top: 18px;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    border: 1px solid rgba(255, 255, 255, .08);
    padding: 18px
  }

  /* ===== Hero ===== */
  .pro-hero {
    padding: 0;
    overflow: hidden;
    border-radius: 18px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .04), rgba(255, 255, 255, .02));
    border: 1px solid rgba(255, 255, 255, .08)
  }

  .pro-banner {
    position: relative;
    height: 260px
  }

  .pro-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    filter: saturate(105%) contrast(102%)
  }

  .pro-banner::after {
    content: "";
    position: absolute;
    inset: auto 0 0 0;
    height: 64%;
    background: linear-gradient(180deg, transparent 0%, rgba(10, 8, 20, .72) 48%, rgba(10, 8, 20, .92) 100%)
  }

  .pro-head {
    position: relative;
    padding: 0 22px 20px
  }

  .pro-avatar {
    position: absolute;
    left: 28px;
    top: -62px;
    width: 122px;
    height: 122px;
    border-radius: 26px;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, .55);
    box-shadow: 0 16px 36px rgba(0, 0, 0, .38);
    background: #0f0f16
  }

  .pro-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block
  }

  .pro-meta {
    padding-left: 170px;
    padding-top: 14px
  }

  .pro-name {
    font-size: 1.6rem;
    font-weight: 900;
    margin: .25rem 0 0;
    letter-spacing: .2px
  }

  .badge-role {
    display: inline-block;
    margin-top: .45rem;
    padding: .28rem .7rem;
    border-radius: 999px;
    font-weight: 900;
    font-size: .85rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
    border: 1px solid rgba(255, 255, 255, .12)
  }

  /* ===== Edit layout ===== */
  .edit-wrap {
    display: grid;
    gap: 18px
  }

  @media(min-width:980px) {
    .edit-wrap {
      grid-template-columns: 1.15fr .85fr
    }
  }

  .form-card {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, .08);
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    padding: 18px
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 0 0 14px
  }

  .help {
    font-size: .85rem;
    opacity: .75;
    margin-top: -4px
  }

  /* ===== Dropzone preview tweaks (sinkron dengan header) ===== */
  .dropzone [data-preview] {
    display: block;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, .1)
  }
</style>

<div class="card">
  <h1>Profil</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
</div>

<?php
$avatarSrc = ($HAS_AVATAR && !empty($u['avatar_url'])) ? $u['avatar_url'] : asset_url('assets/avatar-default.webp');
$bannerSrc = ($HAS_BANNER && !empty($u['banner_url'])) ? $u['banner_url'] : '';
$bioText = ($HAS_BIO && !empty($u['bio'])) ? $u['bio'] : '';
$modeEdit = (($action ?? 'view') === 'edit');
?>

<?php if (!$modeEdit): ?>
  <!-- VIEW MODE -->
  <div class="pro-hero card">
    <div class="pro-banner"><?php if ($bannerSrc): ?><img src="<?= e($bannerSrc) ?>" alt=""><?php endif; ?></div>
    <div class="pro-head">
      <div class="pro-avatar"><img src="<?= e($avatarSrc) ?>" alt=""></div>
      <div class="pro-meta">
        <div class="pro-name"><?= e($u['name'] ?: 'Owner') ?></div>
        <div class="pro-email"><?= e($u['email']) ?></div>
        <span class="badge-role">Role: <?= e($u['role'] ?? 'OWNER') ?></span>
        <?php if ($bioText): ?>
          <p style="margin:.6rem 0 0"><?= nl2br(e($bioText)) ?></p>
        <?php else: ?>
          <p style="margin:.6rem 0 0; opacity:.8">Tambahkan bio singkatmu supaya profil lebih hidup ✨</p><?php endif; ?>
        <div style="margin-top:12px"><a class="btn" href="profile.php?action=edit">Edit Profil</a></div>
      </div>
    </div>
  </div>

  <div class="section card">
    <h2>Insight Profil</h2>
    <div class="grid-3">
      <div class="stat">
        <div class="lbl">Total Konten</div>
        <div class="num"><?= number_format($stat_games + $stat_walks + $stat_chapters) ?></div>
      </div>
      <div class="stat">
        <div class="lbl">Uploads Terpakai</div>
        <div class="num"><?= $uploads_used_mb ?> MB</div>
        <div class="u-sub" style="margin-top:6px">Batas upload: <?= $upload_limit_mb ?> MB • POST: <?= $post_limit_mb ?>
          MB</div>
      </div>
      <div class="stat">
        <div class="lbl">Alamat IP</div>
        <div class="num" style="font-size:1.25rem"><?= e($ip_addr) ?></div>
        <div class="u-sub" style="margin-top:6px">Akses: <?= e($loginTime) ?></div>
      </div>
    </div>
  </div>

<?php else: ?>
  <!-- EDIT MODE -->
  <div class="edit-wrap">
    <!-- Kolom kiri -->
    <div class="form-card">
      <h2 style="margin:0 0 .6rem">Edit Profil</h2>
      <form method="post" action="profile.php" enctype="multipart/form-data" id="formProfile">
        <?php csrf_field(); ?>
        <input type="hidden" name="action" value="update">

        <div class="field">
          <label>Nama</label>
          <input class="input" name="name" value="<?= e($u['name']) ?>">
        </div>

        <?php if ($HAS_BIO): ?>
          <div class="field">
            <label>Bio</label>
            <textarea name="bio" placeholder="Ceritakan singkat tentangmu…"><?= e($bioText) ?></textarea>
            <div class="help">Contoh: “UI/UX enthusiast • JRPG enjoyer • suka ngulik gameplay & build”.</div>
          </div>
        <?php endif; ?>

        <?php if ($HAS_PWHASH): ?>
          <div class="help"
            style="padding:.6rem .8rem;border-left:3px solid var(--primary);background:rgba(167,139,250,.08);border-radius:10px;margin-bottom:12px">
            Ubah password hanya bila perlu. Kosongkan jika tidak ingin mengganti.
          </div>
          <div class="field">
            <label>Password baru (opsional)</label>
            <input class="input" type="password" name="new_password" autocomplete="new-password">
          </div>
          <div class="field" style="margin-bottom:0">
            <label>Ulangi password baru</label>
            <input class="input" type="password" name="re_password" autocomplete="new-password">
          </div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;margin-top:18px">
          <button class="btn">Simpan</button>
          <a class="btn gray" href="profile.php">Batal</a>
        </div>
      </form>
    </div>

    <!-- Kolom kanan : Gambar Profil (dropzone seragam) -->
    <div class="form-card">
      <h2 style="margin:0 0 .6rem">Gambar Profil</h2>

      <div class="media-grid">
        <?php if ($HAS_AVATAR): ?>
          <!-- AVATAR -->
          <div class="tile">
            <div class="tile-head square">
              <span class="badge">Avatar saat ini</span>
              <img class="frame" src="<?= e($avatarSrc) ?>" alt="">
            </div>
            <div class="tile-body">
              <div class="dropzone" data-dropzone>
                <img data-preview id="prev-avatar" alt=""
                  style="width:180px;height:180px;object-fit:cover;display:block;margin:8px auto 6px;border-radius:12px;<?= /* hide if no selection yet */ '' ?>">
                <div class="hint" data-label>Tarik & lepas atau klik untuk pilih (JPG/PNG/WEBP/GIF)</div>
                <input type="file" name="avatar" id="avatar" class="hidden" accept="image/*">
              </div>
              <div class="u-actions" style="margin-top:10px">
                <label class="switch"><input type="checkbox" name="del_avatar" value="1"> Hapus avatar saat simpan</label>
                <span class="note">Tips: pilih foto close-up agar jelas.</span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($HAS_BANNER): ?>
          <!-- BANNER -->
          <div class="tile">
            <div class="tile-head banner">
              <span class="badge">Banner saat ini</span>
              <?php if ($bannerSrc): ?>
                <img class="frame" src="<?= e($bannerSrc) ?>" alt="">
              <?php else: ?>
                <img class="frame"
                  src="data:image/svg+xml;charset=utf-8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'1200\' height=\'375\'><defs><linearGradient id=\'g\' x1=\'0\' y1=\'0\' x2=\'1\' y2=\'1\'><stop stop-color=\'#0f0f16\'/><stop offset=\'1\' stop-color=\'#1a1428\'/></linearGradient></defs><rect width=\'100%\' height=\'100%\' fill=\'url(#g)\'/><text x=\'50%\' y=\'52%\' fill=\'#777\' font-size=\'22\' font-family=\'Arial\' text-anchor=\'middle\'>Belum ada banner</text></svg>') ?>">
              <?php endif; ?>
            </div>
            <div class="tile-body">
              <div class="dropzone" data-dropzone>
                <img data-preview id="prev-banner" alt=""
                  style="width:100%;height:120px;object-fit:cover;margin:8px 0 6px;border-radius:12px;">
                <div class="hint" data-label>Tarik & lepas atau klik untuk pilih (JPG/PNG/WEBP/GIF)</div>
                <input type="file" name="banner" id="banner" class="hidden" accept="image/*">
              </div>
              <div class="u-actions" style="margin-top:10px">
                <label class="switch"><input type="checkbox" name="del_banner" value="1"> Hapus banner saat simpan</label>
                <span class="note">Hindari teks kecil pada banner.</span>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php';
