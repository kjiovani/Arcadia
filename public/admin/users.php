<?php
/* ========================================================================
   Arcadia • Admin • Users
   ======================================================================== */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

require_admin();
if (session_status() === PHP_SESSION_NONE)
  session_start();

/* ---------- util kolom dinamis ---------- */
function users_has_col(string $col): bool
{
  global $mysqli;
  $col = mysqli_real_escape_string($mysqli, $col);
  $res = $mysqli->query("SHOW COLUMNS FROM `users` LIKE '{$col}'");
  if (!$res)
    return false;
  $ok = (bool) $res->fetch_assoc();
  $res->free();
  return $ok;
}
function col_active()
{
  return users_has_col('is_active') ? 'is_active'
    : (users_has_col('active') ? 'active'
      : (users_has_col('status') ? 'status' : null));
}
function col_created()
{
  foreach (['created_at', 'created_on', 'created'] as $c) {
    if (users_has_col($c))
      return $c;
  }
  return null;
}
function col_lastlogin()
{
  foreach (['last_login_at', 'last_login', 'login_at'] as $c) {
    if (users_has_col($c))
      return $c;
  }
  return null;
}

$C_ACTIVE = col_active();
$C_CREATED = col_created();
$C_LASTLOG = col_lastlogin();

/* ---------- info user yang sedang login ---------- */
$me = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? []);
$myId   = (int)($me['id'] ?? 0);
$myRole = strtoupper($me['role'] ?? 'USER');

/* ---------- helpers badge/format ---------- */

/**
 * Konversi nilai kolom status ke boolean "aktif?"
 * Support:
 * - 1 / "1"
 * - "active", "aktif", "on", "yes", "true"
 * Selain itu dianggap nonaktif.
 */
function user_is_on($val): bool
{
  if ($val === null) return true;
  if (is_bool($val)) return $val;

  $v = strtolower(trim((string)$val));
  if ($v === '') return true;
  if (is_numeric($v)) return ((int)$v) === 1;

  return in_array($v, ['active', 'aktif', 'on', 'yes', 'y', 'true'], true);
}

function badge_role(string $role): string
{
  return '<span class="badge badge--role">' . e(strtoupper($role)) . '</span>';
}
function badge_status(array $row, ?string $C_ACTIVE): string
{
  $on = $C_ACTIVE ? user_is_on($row[$C_ACTIVE] ?? null) : true;
  return '<span class="badge ' . ($on ? 'badge--ok' : 'badge--off') . '">' . ($on ? 'Aktif' : 'Nonaktif') . '</span>';
}
function fmt_dt($s): string
{
  return $s ? e(date('Y-m-d H:i:s', strtotime($s))) : '-';
}

/* ---------- ACTIONS (POST) ---------- */
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
    $email = required(str_trim($_POST['email'] ?? ''), 'Email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new Exception('Format email tidak valid.');
    $role = strtoupper(str_trim($_POST['role'] ?? 'USER'));
    if (!in_array($role, ['USER', 'ADMIN', 'OWNER'], true))
      $role = 'USER';
    $pass = required(str_trim($_POST['password'] ?? ''), 'Password awal');
    if (strlen($pass) < 6)
      throw new Exception('Password minimal 6 karakter.');
    $isOn = isset($_POST['is_active']) ? 1 : 0;

    // email unik
    $dup = db_one($mysqli, "SELECT id FROM users WHERE email=?", [$email], 's');
    if ($dup)
      throw new Exception('Email sudah dipakai akun lain.');

    $cols = ['name', 'email', 'role', 'password_hash'];
    $vals = [$name, $email, $role, password_hash($pass, PASSWORD_DEFAULT)];
    $types = 'ssss';
    if ($C_ACTIVE) {
      $cols[] = $C_ACTIVE;
      $vals[] = $isOn;
      $types .= 'i';
    }
    if ($C_CREATED) {
      $cols[] = $C_CREATED;
      $vals[] = date('Y-m-d H:i:s');
      $types .= 's';
    }

    db_exec(
      $mysqli,
      "INSERT INTO users(" . implode(',', $cols) . ") VALUES(" . str_repeat('?,', count($cols) - 1) . "?)",
      $vals,
      $types
    );
    flash('ok', 'Akun baru berhasil dibuat.');
    redirect('users.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('users.php?action=new');
  }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = (int) ($_POST['id'] ?? 0);
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama');
    $email = required(str_trim($_POST['email'] ?? ''), 'Email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new Exception('Format email tidak valid.');
    $role = strtoupper(str_trim($_POST['role'] ?? 'USER'));
    if (!in_array($role, ['USER', 'ADMIN', 'OWNER'], true))
      $role = 'USER';
    $isOn = isset($_POST['is_active']) ? 1 : 0;

    $dup = db_one($mysqli, "SELECT id FROM users WHERE email=? AND id<>?", [$email, $id], 'si');
    if ($dup)
      throw new Exception('Email sudah dipakai akun lain.');

    $fields = ['name=?', 'email=?', 'role=?'];
    $bind = [$name, $email, $role];
    $types = 'sss';
    if ($C_ACTIVE) {
      $fields[] = "{$C_ACTIVE}=?";
      $bind[] = $isOn;
      $types .= 'i';
    }

    $newpass = str_trim($_POST['password'] ?? '');
    if ($newpass !== '') {
      if (strlen($newpass) < 6)
        throw new Exception('Password baru minimal 6 karakter.');
      $fields[] = 'password_hash=?';
      $bind[] = password_hash($newpass, PASSWORD_DEFAULT);
      $types .= 's';
    }

    $bind[] = $id;
    $types .= 'i';
    db_exec($mysqli, "UPDATE users SET " . implode(',', $fields) . " WHERE id=?", $bind, $types);
    flash('ok', 'Akun diperbarui.');
    redirect('users.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('users.php?action=edit&id=' . (int) ($_POST['id'] ?? 0));
  }
}

/* ---------- ACTIONS (GET ringan) ---------- */
if ($action === 'toggle' && isset($_GET['id'])) {
  if ($C_ACTIVE) {
    $id = (int) $_GET['id'];

    // cegah ubah status akun sendiri via toggle
    if ($id === $myId) {
      flash('err', 'Kamu tidak bisa mengubah status akunmu sendiri di sini.');
      redirect('users.php');
    }

    // ambil value sekarang
    $row = db_one(
      $mysqli,
      "SELECT {$C_ACTIVE} AS onx, role FROM users WHERE id=?",
      [$id],
      'i'
    );

    if ($row) {
      // jangan nonaktifkan OWNER
      if (strcasecmp($row['role'] ?? '', 'OWNER') === 0 && user_is_on($row['onx'] ?? null)) {
        flash('err', 'Akun OWNER tidak boleh dinonaktifkan.');
        redirect('users.php');
      }

      $raw   = $row['onx'] ?? null;
      $curOn = user_is_on($raw);

      // siapkan nilai baru sambil menjaga tipe data kolom
      if ($raw === null || $raw === '' || is_numeric($raw)) {
        // kolom numerik (0 / 1)
        $new = $curOn ? 0 : 1;
        db_exec($mysqli, "UPDATE users SET {$C_ACTIVE}=? WHERE id=?", [$new, $id], 'ii');
      } else {
        // kolom teks (misal: 'active' / 'inactive', 'Aktif' / 'Nonaktif')
        $low = strtolower(trim((string)$raw));
        if ($curOn) {
          // dari aktif -> nonaktif
          if (in_array($low, ['aktif', 'nonaktif'], true)) {
            $new = 'nonaktif';
          } elseif (in_array($low, ['active', 'inactive'], true)) {
            $new = 'inactive';
          } else {
            $new = '0';
          }
        } else {
          // dari nonaktif -> aktif
          if (in_array($low, ['aktif', 'nonaktif'], true)) {
            $new = 'aktif';
          } elseif (in_array($low, ['active', 'inactive'], true)) {
            $new = 'active';
          } else {
            $new = '1';
          }
        }
        db_exec($mysqli, "UPDATE users SET {$C_ACTIVE}=? WHERE id=?", [$new, $id], 'si');
      }

      flash('ok', $curOn ? 'Akun dinonaktifkan.' : 'Akun diaktifkan.');
    }
  }
  redirect('users.php');
}

if ($action === 'resetpw' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  $pwd = bin2hex(random_bytes(3)); // 6 hex chars
  db_exec($mysqli, "UPDATE users SET password_hash=? WHERE id=?", [password_hash($pwd, PASSWORD_DEFAULT), $id], 'si');
  flash('ok', "Password baru: {$pwd}");
  redirect('users.php');
}
if ($action === 'delete' && isset($_GET['id'])) {
  db_exec($mysqli, "DELETE FROM users WHERE id=?", [(int) $_GET['id']], 'i');
  flash('ok', 'Akun dihapus.');
  redirect('users.php');
}

/* ---------- DATA (LIST) ---------- */
$q = trim($_GET['q'] ?? '');
$select = ['id', 'name', 'email', 'role'];
if ($C_ACTIVE)
  $select[] = "{$C_ACTIVE} AS active_flag";
if ($C_CREATED)
  $select[] = "{$C_CREATED} AS created_at";
if ($C_LASTLOG)
  $select[] = "{$C_LASTLOG} AS last_login_at";

$sql = "SELECT " . implode(',', $select) . " FROM users";
$params = [];
$types = '';
$w = [];
if ($q !== '') {
  $w[] = "(name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%'))";
  $params[] = $q;
  $params[] = $q;
  $types .= 'ss';
}
if ($w)
  $sql .= " WHERE " . implode(' AND ', $w);
$sql .= " ORDER BY role DESC," . ($C_CREATED ? " created_at DESC," : "") . " id DESC";
$rows = db_all($mysqli, $sql, $params, $types);

/* kelompokkan */
$groups = ['OWNER' => [], 'ADMIN' => [], 'USER' => []];
foreach ($rows as $r) {
  $role = strtoupper($r['role'] ?? 'USER');
  if ($C_ACTIVE)
    $r[$C_ACTIVE] = $r['active_flag'];
  $groups[$role][] = $r;
}

/* ---------- VIEW ---------- */
include __DIR__ . '/_header.php';
?>
<style>
  :root {
    --purple-1: #c9b3ff;
    --purple-2: #9a78ff;
    --purple-3: #7a5cff;
    --slate-2: #191a22;
    --slate-3: #23242f;
    --text: #eaeaf1;
    --muted: #b7b8c5;
  }

  .section-card {
    border-radius: 18px;
    border: 1px solid rgba(255, 255, 255, .08);
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    padding: 16px;
    margin-top: 16px
  }

  .toolbar {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center
  }

  .toolbar .search {
    flex: 1;
    min-width: 260px
  }

  /* Buttons (clean, no shadow) */
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .72rem 1.05rem;
    border-radius: 16px;
    font-weight: 800;
    border: 1px solid transparent;
    transition: transform .12s ease, filter .18s ease, border-color .18s ease;
    color: #0f0f16
  }

  .btn:hover {
    transform: translateY(-1px)
  }

  .btn:active {
    transform: translateY(0)
  }

  .btn--primary {
    background: linear-gradient(135deg, var(--purple-1), var(--purple-2) 55%, var(--purple-3));
    border-color: rgba(255, 255, 255, .18)
  }

  .btn--primary:hover {
    filter: brightness(1.03) saturate(1.04);
    border-color: rgba(255, 255, 255, .28)
  }

  .btn--muted {
    color: var(--text);
    background: linear-gradient(180deg, var(--slate-2), var(--slate-3));
    border-color: rgba(255, 255, 255, .12)
  }

  .btn--muted:hover {
    border-color: rgba(255, 255, 255, .2)
  }

  .btn--ghost {
    color: var(--text);
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
    border-color: rgba(255, 255, 255, .12)
  }

  .btn--ghost:hover {
    border-color: rgba(255, 255, 255, .18)
  }

  .btn--danger {
    color: #fff;
    background: linear-gradient(135deg, #f87171, #ef4444 65%, #b91c1c);
    border-color: rgba(255, 255, 255, .14)
  }

  .btn--sm {
    padding: .55rem .85rem;
    border-radius: 14px
  }

  .btn--lg {
    padding: .9rem 1.25rem;
    border-radius: 18px
  }

  .btn-group {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    justify-content: flex-end
  }

  /* Table */
  .table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
    border: 1px solid rgba(255, 255, 255, .08);
    border-radius: 18px;
    overflow: hidden
  }

  .table thead th {
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
    color: var(--muted);
    text-align: left;
    font-weight: 800;
    letter-spacing: .2px;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, .08);
    position: sticky;
    top: 0;
    z-index: 1
  }

  .table tbody td {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, .06)
  }

  .table tbody tr:last-child td {
    border-bottom: none
  }

  .table tbody tr {
    transition: background .18s ease
  }

  .table tbody tr:hover {
    background: rgba(139, 92, 246, .06)
  }

  .cell-no {
    width: 72px
  }

  .badge-no {
    display: inline-grid;
    place-items: center;
    width: 34px;
    height: 34px;
    border-radius: 999px;
    font-weight: 800;
    color: var(--text);
    background: linear-gradient(180deg, rgba(255, 255, 255, .07), rgba(255, 255, 255, .04));
    border: 1px solid rgba(255, 255, 255, .12)
  }

  /* Badges */
  .badge {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    font-weight: 900;
    font-size: .85rem;
    letter-spacing: .2px;
    border: 1px solid transparent
  }

  .badge--role {
    color: #f3e8ff;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    border-color: rgba(255, 255, 255, .14)
  }

  .badge--ok {
    color: #dcfce7;
    background: linear-gradient(180deg, rgba(22, 163, 74, .25), rgba(22, 163, 74, .18));
    border-color: rgba(34, 197, 94, .35)
  }

  .badge--off {
    color: #fde68a;
    background: linear-gradient(180deg, rgba(217, 119, 6, .2), rgba(217, 119, 6, .14));
    border-color: rgba(245, 158, 11, .35)
  }

  .td-actions {
    width: 1%;
    white-space: nowrap
  }

  /* Kolom Aksi: cukup ramping */
  .tbl col.actions {
    width: 340px;
  }

  /* dulu 440px, sekarang 340px agar selaras tabel */
  .td-actions {
    padding-right: 8px;
  }

  /* Grid tombol 2×2 yang compact */
  .td-actions .btn-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(128px, 1fr));
    /* 2 kolom, min 128px */
    gap: .6rem;
    align-items: stretch;
    justify-items: stretch;
  }

  /* Tombol versi compact agar tidak melebar */
  .btn,
  .btn--sm {
    font-size: .9rem;
    padding: .6rem .9rem;
    /* lebih ramping */
    border-radius: 14px;
    white-space: nowrap;
  }

  /* Responsif: layar kecil -> 1 kolom */
  @media (max-width: 640px) {
    .tbl col.actions {
      width: 100%;
    }

    .td-actions .btn-grid {
      grid-template-columns: 1fr;
    }
  }
</style>

<div class="card">
  <h1>Akun</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert error"><?= e($m) ?></div><?php endif; ?>
</div>

<div class="card">
  <form method="get" class="toolbar" style="gap:12px">
    <input class="input search" name="q" placeholder="Nama atau email" value="<?= e($q) ?>">
    <div class="btn-group">
      <button class="btn btn--primary btn--sm" name="qbtn" value="search">Cari</button>
      <a href="users.php" class="btn btn--muted btn--sm">Reset</a>
      <a href="users.php?action=new" class="btn btn--primary btn--lg">Buat Akun</a>
    </div>
  </form>
</div>

<?php
/* ====== FORM TAMBAH ====== */
if ($action === 'new') { ?>
  <div class="section-card">
    <h2 style="margin:0 0 10px">Buat Akun</h2>
    <form method="post" action="users.php?action=create" class="grid-two"
      style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
      <?php csrf_field(); ?>
      <label class="field"><span>Nama</span><input class="input" name="name" required></label>
      <label class="field"><span>Email</span><input class="input" name="email" type="email" required></label>
      <label class="field"><span>Role</span>
        <select name="role" class="input">
          <option value="USER">USER</option>
          <option value="ADMIN">ADMIN</option>
          <option value="OWNER">OWNER</option>
        </select>
      </label>
      <label class="field"><span>Password awal</span><input class="input" name="password" type="password"
          placeholder="min 6 karakter" required></label>
      <label class="field" style="grid-column:1 / -1">
        <input type="checkbox" name="is_active" checked> Aktifkan akun
        <div class="help" style="font-size:.85rem;opacity:.8">Kalau tidak dicentang, akun dibuat sebagai nonaktif.</div>
      </label>
      <div style="grid-column:1 / -1;display:flex;gap:10px;justify-content:flex-end">
        <button class="btn btn--primary">Simpan</button>
        <a class="btn btn--muted" href="users.php">Batal</a>
      </div>
    </form>
  </div>
<?php }

/* ====== FORM EDIT ====== */
if ($action === 'edit' && isset($_GET['id'])) {
  $eid = (int) $_GET['id'];
  $u = db_one($mysqli, "SELECT id,name,email,role" . ($C_ACTIVE ? ",$C_ACTIVE AS active_flag" : '') . " FROM users WHERE id=?", [$eid], 'i');
  if ($u) { ?>
    <div class="section-card">
      <h2 style="margin:0 0 10px">Kelola Akun</h2>
      <form method="post" action="users.php?action=update" class="grid-two"
        style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
        <?php csrf_field(); ?>
        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
        <label class="field"><span>Nama</span><input class="input" name="name" value="<?= e($u['name']) ?>"
            required></label>
        <label class="field"><span>Email</span><input class="input" name="email" type="email" value="<?= e($u['email']) ?>"
            required></label>
        <label class="field"><span>Role</span>
          <select name="role" class="input">
            <?php foreach (['USER', 'ADMIN', 'OWNER'] as $r): ?>
              <option value="<?= $r ?>" <?= strtoupper($u['role']) === $r ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field"><span>Password baru (opsional)</span>
          <input class="input" name="password" type="password" placeholder="kosongkan bila tidak ganti">
        </label>
        <label class="field" style="grid-column:1 / -1">
          <input type="checkbox" name="is_active" <?= ($C_ACTIVE && user_is_on($u['active_flag'] ?? 1)) ? 'checked' : ''; ?>>
          Aktif
        </label>
        <div style="grid-column:1 / -1;display:flex;gap:10px;justify-content:flex-end">
          <button class="btn btn--primary">Simpan</button>
          <a class="btn btn--muted" href="users.php">Batal</a>
        </div>
      </form>
    </div>
  <?php }
} ?>

<?php
/* ===== renderer list per role ===== */
function render_group(string $title, array $rows, $C_ACTIVE, $C_CREATED, $C_LASTLOG)
{
  ?>
  <div class="section card">
    <h2 style="margin:0 0 10px"><?= e($title) ?></h2>
    <?php if (!$rows): ?>
      <div style="opacity:.8">Tidak ada data.</div>
    <?php else: ?>
      <table class="table">
        <thead>
          <tr>
            <th class="cell-no">No</th>
            <th>Nama</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Dibuat</th>
            <th>Login Terakhir</th>
            <th class="td-actions" style="text-align:right">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1;
          foreach ($rows as $r):
            $id = (int) $r['id'];
            $on = $C_ACTIVE ? user_is_on($r[$C_ACTIVE] ?? null) : true; ?>
            <tr>
              <td class="cell-no"><span class="badge-no"><?= $no++ ?></span></td>
              <td><?= e($r['name'] ?? '-') ?></td>
              <td><?= e($r['email'] ?? '-') ?></td>
              <td><?= badge_role($r['role'] ?? 'USER') ?></td>
              <td><?= badge_status($r, $C_ACTIVE) ?></td>
              <td><?= $C_CREATED ? fmt_dt($r['created_at'] ?? null) : '-' ?></td>
              <td><?= $C_LASTLOG ? fmt_dt($r['last_login_at'] ?? null) : '-' ?></td>
              <td class="td-actions">
                <div class="btn-grid">
                  <!-- Baris atas -->
                  <a href="users.php?action=edit&id=<?= $id ?>" class="btn btn--ghost btn--sm">Kelola</a>
                  <a href="users.php?action=toggle&id=<?= $id ?>"
                    class="btn btn--muted btn--sm"><?= $on ? 'Nonaktifkan' : 'Aktifkan' ?></a>
                  <!-- Baris bawah -->
                  <a href="users.php?action=resetpw&id=<?= $id ?>" class="btn btn--ghost btn--sm">Reset PW</a>
                  <a href="users.php?action=delete&id=<?= $id ?>" class="btn btn--danger btn--sm"
                    onclick="return confirm('Yakin hapus akun ini?')">Hapus</a>
                </div>
              </td>

            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php
}
render_group('Owner', $groups['OWNER'], $C_ACTIVE, $C_CREATED, $C_LASTLOG);
render_group('Admin', $groups['ADMIN'], $C_ACTIVE, $C_CREATED, $C_LASTLOG);
render_group('User', $groups['USER'], $C_ACTIVE, $C_CREATED, $C_LASTLOG);

include __DIR__ . '/_footer.php';
