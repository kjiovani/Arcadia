<?php
// /arcadia/public/auth/login.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';
require_once __DIR__ . '/../../lib/auth_user.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$errors = [];

// ambil raw next dari query string
$rawNext = (string) ($_GET['next'] ?? '');

function safe_next(string $raw): string
{
  return ($raw !== '' && strpos($raw, '://') === false && str_starts_with($raw, '/arcadia/public')) ? $raw : '';
}

// next yang sudah disanitasi
$next = safe_next($rawNext);

// flag: ini login ke panel admin/owner atau bukan
$isAdminLogin = (strpos($next, '/arcadia/public/admin') === 0);

/* ---------- helper cek kolom users ---------- */
function users_has_col_login(mysqli $mysqli, string $col): bool
{
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
  return (int) ($row['n'] ?? 0) > 0;
}

function users_active_col_login(mysqli $mysqli): ?string
{
  foreach (['is_active', 'active', 'status'] as $c) {
    if (users_has_col_login($mysqli, $c)) {
      return $c;
    }
  }
  return null;
}

/**
 * Konversi nilai status ke boolean "aktif?"
 * - 1 / "1"
 * - "active", "aktif", "on", "yes", "true"
 * selain itu dianggap nonaktif.
 */
function user_is_on_login($val): bool
{
  if ($val === null) return true;
  if (is_bool($val)) return $val;

  $v = strtolower(trim((string) $val));
  if ($v === '') return true;
  if (is_numeric($v)) return ((int) $v) === 1;

  return in_array($v, ['active', 'aktif', 'on', 'yes', 'y', 'true'], true);
}

$hasPlain  = users_has_col_login($mysqli, 'password');         // true jika ada kolom plaintext
$C_ACTIVE  = users_active_col_login($mysqli);                  // nama kolom status aktif (bisa null)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify();

    $email = str_trim($_POST['email'] ?? '');
    $pass  = (string) ($_POST['password'] ?? '');
    $next  = safe_next((string) ($_POST['next'] ?? $next));
    $isAdminLogin = (strpos($next, '/arcadia/public/admin') === 0);

    if ($email === '')
      throw new Exception('Email wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
      throw new Exception('Format email tidak valid.');
    if ($pass === '')
      throw new Exception('Password wajib diisi.');

    // Susun SELECT hanya dengan kolom yang tersedia
    $selectCols = 'id,name,email,role,password_hash';
    if ($hasPlain)
      $selectCols .= ',password';
    if ($C_ACTIVE)
      $selectCols .= ',' . $C_ACTIVE;

    $user = db_one(
      $mysqli,
      "SELECT $selectCols FROM users WHERE email=? LIMIT 1",
      [$email],
      's'
    );
    if (!$user)
      throw new Exception('Email atau password tidak cocok.');

    // Jika ada kolom status, cek dulu apakah akun aktif
    if ($C_ACTIVE && array_key_exists($C_ACTIVE, $user)) {
      if (!user_is_on_login($user[$C_ACTIVE])) {
        throw new Exception('Akun kamu sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.');
      }
    }

    // Verifikasi password: utamakan password_hash; fallback ke kolom legacy bila ada
    $ok = false;
    if (!empty($user['password_hash'])) {
      $ok = password_verify($pass, $user['password_hash']);
    }
    if (!$ok && $hasPlain && isset($user['password'])) {
      $ok = hash_equals((string) $user['password'], $pass);
    }
    if (!$ok)
      throw new Exception('Email atau password tidak cocok.');

    // Update last_login_at bila kolom ada (abaikan error kecil)
    @db_exec($mysqli, "UPDATE users SET last_login_at = NOW() WHERE id=?", [$user['id']], 'i');

    // Set sesi (dua format, agar kompatibel)
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id'    => (int) $user['id'],
      'name'  => (string) $user['name'],
      'email' => (string) $user['email'],
      'role'  => (string) ($user['role'] ?? 'USER'),
    ];
    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['user_role'] = (string) ($user['role'] ?? 'USER');

    header('Location: ' . ($next ?: '/arcadia/public/index.php'));
    exit;

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
  }
}

include __DIR__ . '/../_header.php';
?>
<style>
  .auth-wrap {
    max-width: 820px;
    margin: 24px auto 22px;
    padding: 0 16px
  }

  .auth-card {
    padding: 22px 20px;
    border-radius: 18px;
    background:
      radial-gradient(120% 120% at 20% -10%, rgba(167, 139, 250, .16), transparent 60%),
      linear-gradient(180deg, rgba(255, 255, 255, .035), rgba(255, 255, 255, .015));
    border: 1px solid rgba(255, 255, 255, .10);
    box-shadow: 0 8px 24px rgba(0, 0, 0, .25) inset;
  }

  .auth-head {
    display: flex;
    align-items: center;
    gap: 12px
  }

  .auth-logo {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: radial-gradient(120% 120% at 30% 20%, rgba(167, 139, 250, .45), rgba(217, 70, 239, .18));
    border: 1px solid rgba(255, 255, 255, .16);
    font-weight: 800
  }

  .auth-sub {
    margin: .25rem 0 1rem;
    opacity: .9
  }

  .grid {
    display: grid;
    gap: 12px
  }

  .input-group {
    display: flex;
    flex-direction: column;
    gap: 8px
  }

  .input-group label {
    font-weight: 700;
    letter-spacing: .2px
  }

  .pw-field {
    position: relative
  }

  .pw-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    padding: .35rem .7rem;
    border-radius: 10px;
    cursor: pointer;
    font-size: .9rem;
    border: 1px solid var(--primary);
    background: rgba(167, 139, 250, .2);
    color: #f5f3ff;
    font-weight: 600;
  }

  .pw-toggle:hover,
  .pw-toggle:focus-visible {
    background: var(--primary);
    border-color: var(--primary);
    color: #0f0f16;
    box-shadow: 0 6px 16px var(--ring);
  }

  .row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-top: 6px
  }

  .hint {
    font-size: .9rem;
    opacity: .9
  }

  .alert {
    margin: 10px 0 6px;
    padding: .75rem .9rem;
    border-radius: 12px
  }

  .alert.error {
    background: rgba(244, 63, 94, .08);
    border: 1px solid rgba(244, 63, 94, .35)
  }
</style>

<div class="auth-wrap">
  <div class="auth-card">
    <div class="auth-head">
      <div class="auth-logo">⟡</div>
      <h1><?= $isAdminLogin ? 'Masuk Sebagai Admin/Owner' : 'Masuk' ?></h1>
    </div>
    <p class="auth-sub">
      <?= $isAdminLogin ? 'Masuk ke panel Admin/Owner Arcadia.' : 'Masuk untuk melanjutkan panduanmu.' ?>
    </p>

    <?php if (!empty($errors)): ?>
      <div class="alert error">
        <?php foreach ($errors as $e): ?>
          <div><?= e($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?php csrf_field(); ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <div class="grid">
        <div class="input-group">
          <label for="email">Email</label>
          <input class="input" type="email" id="email" name="email" placeholder="nama@contoh.com" required
            value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="password">Password</label>
          <div class="pw-field">
            <input class="input" type="password" id="password" name="password" placeholder="Masukkan password" required>
            <button type="button" class="pw-toggle" id="btnShowPw" aria-label="Tampilkan password">Lihat</button>
          </div>
          <div class="row">
            <span class="hint">Tip: gunakan huruf, angka, dan simbol.</span>

            <?php if (!$isAdminLogin): ?>
              <a class="hint" style="color:inherit;text-decoration:underline"
                href="/arcadia/public/auth/register.php<?= $next ? ('?next=' . urlencode($next)) : '' ?>">Daftar akun</a>
            <?php endif; ?>
          </div>
        </div>

        <button class="btn" style="width:100%">Masuk</button>
      </div>
    </form>
  </div>
</div>

<script>
  (function () {
    const pw = document.getElementById('password');
    const btn = document.getElementById('btnShowPw');
    if (btn && pw) {
      btn.addEventListener('click', () => {
        if (pw.type === 'password') { pw.type = 'text'; btn.textContent = 'Sembunyikan'; }
        else { pw.type = 'password'; btn.textContent = 'Lihat'; }
        pw.focus();
      });
    }
  })();
</script>
