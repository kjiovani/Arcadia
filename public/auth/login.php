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
$next = (string) ($_GET['next'] ?? '');

function safe_next(string $raw): string
{
  return ($raw !== '' && strpos($raw, '://') === false && str_starts_with($raw, '/arcadia/public')) ? $raw : '';
}

// Cek ketersediaan kolom "password" (legacy) sekali saja
function users_has_col(mysqli $mysqli, string $col): bool
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
$hasPlain = users_has_col($mysqli, 'password'); // true jika ada kolom plaintext

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify();

    $email = str_trim($_POST['email'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');
    $next = safe_next((string) ($_POST['next'] ?? $next));

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

    $user = db_one(
      $mysqli,
      "SELECT $selectCols FROM users WHERE email=? LIMIT 1",
      [$email],
      's'
    );
    if (!$user)
      throw new Exception('Email atau password tidak cocok.');

    // Verifikasi: utamakan password_hash; fallback ke kolom legacy bila ada
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
      'id' => (int) $user['id'],
      'name' => (string) $user['name'],
      'email' => (string) $user['email'],
      'role' => (string) ($user['role'] ?? 'USER'),
    ];
    $_SESSION['user_id'] = (int) $user['id'];
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
    border: 1px solid rgba(255, 255, 255, .12);
    background: rgba(255, 255, 255, .06);
    padding: .35rem .55rem;
    border-radius: 10px;
    cursor: pointer;
    font-size: .9rem
  }

  .pw-toggle:hover {
    background: rgba(255, 255, 255, .10)
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
      <h1 style="margin:0">Masuk</h1>
    </div>
    <p class="auth-sub">Selamat datang kembali! Lanjutkan progres dan jelajahi panduan favoritmu.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert error">
        <?php foreach ($errors as $e): ?>
          <div><?= e($e) ?></div><?php endforeach; ?>
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
            <span class="hint">Tip: password kuat berisi huruf besar, angka, & simbol.</span>
            <a class="hint" style="color:inherit;text-decoration:underline"
              href="/arcadia/public/auth/register.php<?= $next ? ('?next=' . urlencode($next)) : '' ?>">Daftar akun</a>
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