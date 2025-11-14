<?php
// ===== BOOT =====
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin(); // OWNER/ADMIN only

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
 * Upload image seragam untuk avatar/banner
 * - Format: JPG/PNG/WEBP/GIF
 * - Validasi MIME + getimagesize
 * - Output: URL relatif (/arcadia/public/uploads/..)
 */
function upload_image(array $file, string $subdir, string $seed, int $maxMB = 8): ?string
{
  if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE)
    return null;
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK)
    throw new Exception('Upload gagal (code ' . $file['error'] . ').');

  $tmp = $file['tmp_name'] ?? '';
  $size = (int)($file['size'] ?? 0);
  if (!$tmp || $size <= 0)
    return null;
  if ($size > $maxMB * 1024 * 1024)
    throw new Exception("Ukuran gambar > {$maxMB}MB.");

  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($tmp);
  $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
  if (!isset($map[$mime]))
    throw new Exception('Format didukung: JPG/PNG/WEBP/GIF.');

  $info = @getimagesize($tmp);
  if (!$info || empty($info[0]) || empty($info[1]))
    throw new Exception('File tidak terdeteksi sebagai gambar yang valid.');

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
  return (bool)$row;
}
function table_has_col(string $table, string $col): bool
{
  global $mysqli;
  $table = mysqli_real_escape_string($mysqli, $table);
  $col = mysqli_real_escape_string($mysqli, $col);
  $res = $mysqli->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
  return $res && $res->fetch_assoc();
}

// flags kolom
$HAS_BIO = $HAS_AVATAR = $HAS_BANNER = $HAS_PWHASH = false;
$HAS_CREATED_AT = $HAS_LAST_LOGIN = false;

if ($mysqli && @$mysqli->ping()) {
  $HAS_BIO        = users_has_col('bio');
  $HAS_AVATAR     = users_has_col('avatar_url');
  $HAS_BANNER     = users_has_col('banner_url');
  $HAS_PWHASH     = users_has_col('password_hash');
  $HAS_CREATED_AT = table_has_col('users', 'created_at');
  $HAS_LAST_LOGIN = table_has_col('users', 'last_login_at');
}

/* ---------- user login ---------- */
$me = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
$user_id = (int)($me['id'] ?? 0);
if ($user_id <= 0) {
  header('Location: /arcadia/public/auth/login.php');
  exit;
}

/* ---------- SELECT dinamis ---------- */
$select = ['id', 'name', 'email', 'role'];
if ($HAS_AVATAR)     $select[] = 'avatar_url';
if ($HAS_BANNER)     $select[] = 'banner_url';
if ($HAS_BIO)        $select[] = 'bio';
if ($HAS_CREATED_AT) $select[] = 'created_at';
if ($HAS_LAST_LOGIN) $select[] = 'last_login_at';

$u = db_one($mysqli, "SELECT " . implode(',', $select) . " FROM users WHERE id=?", [$user_id], 'i');
if (!$u) {
  $u = ['id' => $user_id, 'name' => '', 'email' => '', 'role' => 'OWNER'];
  if ($HAS_AVATAR)     $u['avatar_url']   = '';
  if ($HAS_BANNER)     $u['banner_url']   = '';
  if ($HAS_BIO)        $u['bio']          = '';
  if ($HAS_CREATED_AT) $u['created_at']   = null;
  if ($HAS_LAST_LOGIN) $u['last_login_at'] = null;
}

/* ---------- ACTION ---------- */
$action = $_POST['action'] ?? ($_GET['action'] ?? 'view');
if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $name   = required(str_trim($_POST['name'] ?? ''), 'Nama');
    $fields = ['name=?'];
    $bind   = [$name];

    if ($HAS_BIO) {
      $bio = str_trim($_POST['bio'] ?? '');
      if (mb_strlen($bio) > 240)
        throw new Exception('Bio maksimal 240 karakter.');
      $fields[] = 'bio=?';
      $bind[]   = $bio;
    }

    // hapus avatar/banner jika diminta
    if ($HAS_AVATAR && !empty($_POST['del_avatar'])) {
      $fields[] = 'avatar_url=?';
      $bind[]   = '';
    }
    if ($HAS_BANNER && !empty($_POST['del_banner'])) {
      $fields[] = 'banner_url=?';
      $bind[]   = '';
    }

    // password baru (opsional)
    if ($HAS_PWHASH) {
      $newpass = str_trim($_POST['new_password'] ?? '');
      $repass  = str_trim($_POST['re_password'] ?? '');
      if ($newpass !== '') {
        if (strlen($newpass) < 8)
          throw new Exception('Password minimal 8 karakter.');
        if ($newpass !== $repass)
          throw new Exception('Konfirmasi password tidak cocok.');
        $fields[] = 'password_hash=?';
        $bind[]   = password_hash($newpass, PASSWORD_DEFAULT);
      }
    }

    // upload baru menimpa
    if ($HAS_AVATAR) {
      if ($url = upload_image($_FILES['avatar'] ?? [], 'avatars', $name, 5)) {
        $fields[] = 'avatar_url=?';
        $bind[]   = $url;
      }
    }
    if ($HAS_BANNER) {
      if ($url = upload_image($_FILES['banner'] ?? [], 'banners', $name, 8)) {
        $fields[] = 'banner_url=?';
        $bind[]   = $url;
      }
    }

    $bind[] = $user_id;
    $types  = str_repeat('s', count($bind) - 1) . 'i';
    db_exec($mysqli, "UPDATE users SET " . implode(',', $fields) . " WHERE id=?", $bind, $types);

    flash('ok', 'Profil diperbarui.');
    redirect('profile.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('profile.php?action=edit');
  }
}

/* ---------- STATS & INSIGHTS ---------- */
$lastLoginRaw = $HAS_LAST_LOGIN ? ($u['last_login_at'] ?? null) : null;
$joinedAtRaw  = $HAS_CREATED_AT ? ($u['created_at'] ?? null) : null;
$roleLabel    = strtoupper($u['role'] ?? 'OWNER');

if (!function_exists('arc_fmt_dt_short')) {
  function arc_fmt_dt_short(?string $ts): string
  {
    if (!$ts) return '-';
    $t = strtotime($ts);
    if ($t === false) return '-';
    return date('d M Y, H:i', $t);
  }
}

/* ---------- VIEW ---------- */
include __DIR__ . '/_header.php';

/**
 * Tambah query ?v=timestamp supaya browser tidak pakai cache lama.
 * Untuk data:image (placeholder SVG) tidak ditambah apa-apa.
 */
if (!function_exists('arc_img_cache_bust')) {
  function arc_img_cache_bust(?string $url): string {
    if (!$url) return '';
    if (strpos($url, 'data:') === 0) return $url; // biarkan data-uri apa adanya
    $sep = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $sep . 'v=' . time();
  }
}

// data untuk view
$avatarRaw = ($HAS_AVATAR && !empty($u['avatar_url']))
  ? $u['avatar_url']
  : asset_url('assets/avatar-default.webp');

$bannerRaw = ($HAS_BANNER && !empty($u['banner_url']))
  ? $u['banner_url']
  : '';

$avatarSrc = arc_img_cache_bust($avatarRaw);
$bannerSrc = $bannerRaw ? arc_img_cache_bust($bannerRaw) : '';

$bioText  = ($HAS_BIO && !empty($u['bio'])) ? $u['bio'] : '';
$modeEdit = (($action ?? 'view') === 'edit');

?>
<style>
  :root {
    --surface-1: rgba(255, 255, 255, .03);
    --surface-2: rgba(255, 255, 255, .02);
    --border: rgba(255, 255, 255, .10);
    --muted: rgba(255, 255, 255, .68);
    --primary: #8b5cf6;
    --ring: rgba(139, 92, 246, .22);
  }

  /* Kartu & tombol umum */
  .profile-wrap .card {
    border-radius: 18px;
    padding: 18px;
    border: 1px solid var(--border);
    background: linear-gradient(180deg, var(--surface-1), var(--surface-2));
    box-shadow: 0 10px 24px rgba(0, 0, 0, .22);
  }

  .alert {
    border-radius: 12px;
    padding: 10px 12px;
    margin: 10px 0;
    font-weight: 600
  }

  .alert:not(.err) {
    background: rgba(16, 185, 129, .14);
    border: 1px solid rgba(16, 185, 129, .35)
  }

  .alert.err {
    background: rgba(239, 68, 68, .14);
    border: 1px solid rgba(239, 68, 68, .35)
  }

  .btn {
    display: inline-block;
    border: 0;
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 700;
    cursor: pointer;
    transition: transform .12s ease, box-shadow .12s ease
  }

  .btn.primary {
    color: #fff;
    background: linear-gradient(135deg, #8b5cf6, #6366f1);
    box-shadow: 0 8px 18px rgba(99, 102, 241, .35)
  }

  .btn.primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(99, 102, 241, .45)
  }

  .btn.ghost {
    color: #fff;
    background: transparent;
    border: 1px solid var(--border)
  }

  .btn.gray {
    background: rgba(255, 255, 255, .06);
    border: 1px solid var(--border);
    color: #fff
  }

  /* HERO (view mode) */
  .pro-hero {
    padding: 0;
    overflow: hidden
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
    padding: 0 22px 22px
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

  /* GRID kecil untuk insight */
  .grid-3 {
    display: grid;
    gap: 12px
  }

  @media(min-width:860px) {
    .grid-3 {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  /* EDIT LAYOUT */
  .edit-wrap {
    display: grid;
    gap: 22px
  }

  @media(min-width:980px) {
    .edit-wrap {
      grid-template-columns: 1.1fr .9fr;
    }
  }

  .form-card {
    border-radius: 18px;
    border: 1px solid var(--border);
    background: linear-gradient(180deg, var(--surface-1), var(--surface-2));
    padding: 18px
  }

  .form-card h2 {
    margin: 0 0 .8rem
  }

  .field {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin: 0 0 16px
  }

  label {
    font-weight: 700;
    letter-spacing: .2px
  }

  .input,
  textarea,
  input[type="password"] {
    width: 100%;
    border-radius: 12px;
    border: 1px solid var(--border);
    background: rgba(255, 255, 255, .02);
    color: #fff;
    padding: 12px 14px;
    transition: border .15s, box-shadow .15s, background .15s
  }

  .input:focus,
  textarea:focus,
  input[type="password"]:focus {
    outline: 0;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px var(--ring);
    background: rgba(255, 255, 255, .04)
  }

  .help {
    font-size: .9rem;
    color: var(--muted);
    margin-top: 4px
  }

  .counter {
    font-size: .8rem;
    color: var(--muted);
    text-align: right;
    margin-top: 6px
  }

  /* MEDIA PANE */
  .media-pane .card {
    box-shadow: none
  }

  .media-grid {
    display: grid;
    gap: 18px
  }

  .tile {
    display: block
  }

  .tile-head {
    position: relative;
    margin-bottom: 10px
  }

  .pill {
    position: absolute;
    left: 10px;
    top: 8px;
    font-size: .75rem;
    padding: .18rem .5rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid var(--border);
    font-weight: 700
  }

  .frame.avatar {
    width: 320px;
    height: 320px;
    border-radius: 28px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: #111;
    display: block;
    margin: auto
  }

  .frame.banner {
    width: 100%;
    aspect-ratio: 16/5;
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: #111;
    display: block
  }

  .drop {
    border: 1px dashed var(--border);
    border-radius: 14px;
    padding: 14px;
    text-align: center;
    background: rgba(255, 255, 255, .02);
    transition: background .12s ease, border-color .12s ease
  }

  .drop:hover {
    background: rgba(255, 255, 255, .03)
  }

  .drop.drag {
    background: rgba(255, 255, 255, .05);
    border-color: var(--primary)
  }

  .drop strong {
    display: block;
    margin-top: 4px
  }

  .drop small {
    color: var(--muted);
    display: block;
    margin-top: 6px
  }

  .preview {
    display: none;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, .1);
    margin: 10px auto 6px
  }

  .actions-inline {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-top: 8px
  }

  /* Sticky actions */
  .form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 10px;
    position: sticky;
    bottom: 8px;
    padding-top: 8px;
    backdrop-filter: blur(6px)
  }

  /* ====== Polishing & ukuran avatar/banner (duplikat dari blok keduanya) ====== */
  .media-pane {
    max-width: 420px;
    margin-left: auto
  }

  .media-grid {
    display: grid;
    gap: 16px
  }

  .tile {
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 12px;
    background: linear-gradient(180deg, var(--surface-1), var(--surface-2))
  }

  .frame.avatar {
    width: 156px;
    height: 156px;
    border-radius: 22px;
    overflow: hidden;
    border: 1px solid var(--border);
    background: #111;
    display: block;
    margin: 10px auto 8px
  }

  #avatarPreview {
    width: 140px !important;
    height: 140px !important;
    display: none;
    border-radius: 14px;
    border: 1px solid rgba(255, 255, 255, .1);
    margin: 8px auto 6px;
    object-fit: cover
  }

  .frame.banner {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, .10);
    background: #111;
    display: block;
  }

  #bannerPreview {
    width: 100%;
    height: 200px;
    object-fit: cover;
    display: none;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, .10);
    margin: 8px 0 6px;
  }

  @media (max-width: 480px) {

    .frame.banner,
    #bannerPreview {
      height: 180px;
    }
  }

  @media (min-width: 1200px) {

    .frame.banner,
    #bannerPreview {
      height: 240px;
    }

    .frame.avatar {
      width: 176px;
      height: 176px
    }

    #avatarPreview {
      width: 156px !important;
      height: 156px !important
    }
  }
</style>

<div class="profile-wrap">
  <div class="card">
    <h1>Profil</h1>
    <?php if ($m = flash('ok')): ?>
      <div class="alert"><?= e($m) ?></div><?php endif; ?>
    <?php if ($m = flash('err')): ?>
      <div class="alert err"><?= e($m) ?></div><?php endif; ?>
  </div>

  <?php if (!$modeEdit): ?>
    <!-- ===== VIEW MODE ===== -->
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
            <p style="margin:.6rem 0 0; opacity:.8">Tambahkan bio singkatmu supaya profil lebih hidup ✨</p>
          <?php endif; ?>
          <div style="margin-top:12px"><a class="btn primary" href="profile.php?action=edit">Edit Profil</a></div>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Insight Profil</h2>
      <div class="grid-3">
        <div class="stat card" style="box-shadow:none">
          <div class="label-sub">Login Terakhir</div>
          <div style="font-size:1.4rem;font-weight:900">
            <?= e(arc_fmt_dt_short($lastLoginRaw)) ?>
          </div>
          <div class="label-sub" style="margin-top:6px">
            Jika kosong, belum pernah tercatat.
          </div>
        </div>
        <div class="stat card" style="box-shadow:none">
          <div class="label-sub">Bergabung Sejak</div>
          <div style="font-size:1.4rem;font-weight:900">
            <?= e(arc_fmt_dt_short($joinedAtRaw)) ?>
          </div>
          <div class="label-sub" style="margin-top:6px"></div>
        </div>
        <div class="stat card" style="box-shadow:none">
          <div class="label-sub">Status Akun</div>
          <div style="font-size:1.25rem;font-weight:900">
            <?= e($roleLabel) ?>
          </div>
          <div class="label-sub" style="margin-top:6px">
            Email: <?= e($u['email']) ?>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- ===== EDIT MODE (refined) ===== -->
    <!-- FORM dibungkus di luar edit-wrap supaya file input di aside ikut terkirim -->
    <form method="post" action="profile.php" enctype="multipart/form-data" id="formProfile" novalidate>
      <?php csrf_field(); ?>
      <input type="hidden" name="action" value="update">

      <div class="edit-wrap">
        <!-- Kolom KIRI: Form teks -->
        <div class="form-card">
          <h2>Edit Profil</h2>

          <div class="field">
            <label for="name">Nama</label>
            <input class="input" id="name" name="name" value="<?= e($u['name']) ?>" maxlength="80" required>
          </div>

          <?php if ($HAS_BIO): ?>
            <div class="field">
              <label for="bio">Bio</label>
              <textarea class="input" id="bio" name="bio" rows="4" maxlength="240"
                        placeholder="Contoh: UI/UX enthusiast · JRPG enjoyer · suka ngulik gameplay & build."><?= e($bioText) ?></textarea>
              <div class="counter" id="bioCounter">0/240</div>
              <div class="help">Tampilkan 1–2 kalimat singkat tentangmu. Maks 240 karakter.</div>
            </div>
          <?php endif; ?>

          <?php if ($HAS_PWHASH): ?>
            <div class="help" style="padding:.6rem .8rem;border-left:3px solid var(--primary);background:rgba(139,92,246,.08);border-radius:10px;margin-bottom:12px">
              Ubah password hanya bila perlu. Kosongkan jika tidak ingin mengganti.
            </div>
            <div class="field">
              <label for="new_password">Password baru <span class="help">(opsional)</span></label>
              <input class="input" id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="8" placeholder="••••••••">
            </div>
            <div class="field">
              <label for="re_password">Ulangi password baru</label>
              <input class="input" id="re_password" type="password" name="re_password" autocomplete="new-password" placeholder="••••••••">
            </div>
          <?php endif; ?>

          <div class="form-actions">
            <a class="btn ghost" href="profile.php">Batal</a>
            <button class="btn primary" type="submit">Simpan Perubahan</button>
          </div>
        </div>

        <!-- Kolom KANAN: Media (Avatar & Banner) -->
        <aside class="media-pane">
          <div class="card">
            <h2>Gambar Profil</h2>

            <div class="media-grid">
              <?php if ($HAS_AVATAR): ?>
                <!-- AVATAR -->
                <div class="tile">
                  <div class="tile-head">
                    <span class="pill">Avatar saat ini</span>
                    <img class="frame avatar" id="avatarCurrent" src="<?= e($avatarSrc) ?>" alt="">
                  </div>
                  <div>
                    <div class="drop" id="avatarDrop">
                      <strong>Avatar (rasio 1:1)</strong>
                      <small>Rekomendasi ≥ 512×512 px. Format: JPG/PNG/WebP/GIF.</small>
                      <img id="avatarPreview" class="preview" width="180" height="180" style="object-fit:cover;">
                      <input type="file" name="avatar" id="avatarInput" accept="image/*" hidden>
                      <div class="actions-inline">
                        <!-- onclick DIHAPUS: cukup 1 kali klik -->
                        <button class="btn ghost" type="button">Pilih Gambar</button>
                        <label class="help" style="margin-left:auto;">
                          <input type="checkbox" name="del_avatar" value="1"> Hapus saat simpan
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <?php if ($HAS_BANNER): ?>
                <!-- BANNER -->
                <div class="tile">
                  <div class="tile-head">
                    <span class="pill">Banner saat ini</span>
                    <?php if ($bannerSrc): ?>
                      <img class="frame banner" id="bannerCurrent" src="<?= e($bannerSrc) ?>" alt="">
                    <?php else: ?>
                      <img class="frame banner" id="bannerCurrent"
                           src="data:image/svg+xml;charset=utf-8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'1200\' height=\'375\'><defs><linearGradient id=\'g\' x1=\'0\' y1=\'0\' x2=\'1\' y2=\'1\'><stop stop-color=\'#0f0f16\'/><stop offset=\'1\' stop-color=\'#1a1428\'/></linearGradient></defs><rect width=\'100%\' height=\'100%\' fill=\'url(#g)\'/><text x=\'50%\' y=\'52%\' fill=\'#777\' font-size=\'22\' font-family=\'Arial\' text-anchor=\'middle\'>Belum ada banner</text></svg>') ?>">
                    <?php endif; ?>
                  </div>
                  <div>
                    <div class="drop" id="bannerDrop">
                      <strong>Banner (rasio 16:5)</strong>
                      <small>Rekomendasi ≥ 1600×500 px. Format: JPG/PNG/WebP/GIF.</small>
                      <img id="bannerPreview" class="preview" style="width:100%;height:120px;object-fit:cover;">
                      <input type="file" name="banner" id="bannerInput" accept="image/*" hidden>
                      <div class="actions-inline">
                        <!-- onclick DIHAPUS: cukup 1 kali klik -->
                        <button class="btn ghost" type="button">Pilih Gambar</button>
                        <label class="help" style="margin-left:auto;">
                          <input type="checkbox" name="del_banner" value="1"> Hapus saat simpan
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </aside>
      </div>
    </form>
  <?php endif; ?>


<script>
  // === Bio counter ===
  (function () {
    const bio = document.getElementById('bio');
    const counter = document.getElementById('bioCounter');
    if (!bio || !counter) return;
    const update = () => counter.textContent = `${bio.value.length}/240`;
    bio.addEventListener('input', update); update();
  })();

  // === Preview + Drag & Drop helper (sekaligus update gambar utama) ===
  function bindPreview(inputId, previewId, dropId, currentId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    const drop    = document.getElementById(dropId);
    const current = currentId ? document.getElementById(currentId) : null;
    if (!input || !preview || !drop) return;

    const show = (file) => {
      if (!file) return;
      const url = URL.createObjectURL(file);
      preview.src = url;
      preview.style.display = 'block';
      if (current) {
        current.src = url; // update "Avatar/banner saat ini" juga
      }
    };

    // Klik di area drop / tombol "Pilih Gambar" -> buka dialog file
    drop.addEventListener('click', (e) => {
      const tag = e.target.tagName.toLowerCase();
      // kalau yang diklik adalah INPUT atau LABEL (checkbox hapus), jangan buka dialog
      if (tag === 'input' || tag === 'label') return;
      input.click();
    });

    ['dragenter', 'dragover'].forEach(ev =>
      drop.addEventListener(ev, e => {
        e.preventDefault();
        drop.style.background = 'rgba(255,255,255,.04)';
      })
    );
    ['dragleave', 'drop'].forEach(ev =>
      drop.addEventListener(ev, e => {
        e.preventDefault();
        drop.style.background = 'rgba(255,255,255,.02)';
      })
    );
    drop.addEventListener('drop', e => {
      const f = e.dataTransfer.files?.[0];
      if (f) {
        input.files = e.dataTransfer.files;
        show(f);
      }
    });
    input.addEventListener('change', () => {
      const f = input.files?.[0];
      show(f);
    });
  }

  // panggil dengan id gambar utama
  bindPreview('avatarInput', 'avatarPreview', 'avatarDrop', 'avatarCurrent');
  bindPreview('bannerInput', 'bannerPreview', 'bannerDrop', 'bannerCurrent');
</script>
