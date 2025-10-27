<?php
// /arcadia/public/auth/register.php
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
$next   = (string)($_GET['next'] ?? '');

function safe_next(string $raw): string {
  return ($raw !== '' && strpos($raw, '://') === false && str_starts_with($raw, '/arcadia/public')) ? $raw : '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    csrf_verify();

    // Ambil input
    $name  = str_trim($_POST['name']  ?? '');
    $email = str_trim($_POST['email'] ?? '');
    // FIX: ambil dari name="password" (bukan password_hash)
    $pass  = (string)($_POST['password'] ?? '');
    $next  = safe_next((string)($_POST['next'] ?? $next));

    // Validasi sederhana
    if ($name === '')  throw new Exception('Nama wajib diisi.');
    if ($email === '') throw new Exception('Email wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Format email tidak valid.');
    if (mb_strlen($pass) < 8) throw new Exception('Password minimal 8 karakter.');

    // Cek email duplikat
    $exists = db_one($mysqli, "SELECT id FROM users WHERE email=?", [$email], 's');
    if ($exists) throw new Exception('Email sudah terdaftar.');

    // Simpan user (gunakan password_hash)
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    db_exec(
      $mysqli,
      "INSERT INTO users(name,email,password_hash,role,created_at) VALUES(?,?,?,?,NOW())",
      [$name, $email, $hash, 'USER'],
      'ssss'
    );
    $uid = (int)mysqli_insert_id($mysqli);

    // Opsional: catat last_login_at saat auto-login pertama
    @db_exec($mysqli, "UPDATE users SET last_login_at = NOW() WHERE id=?", [$uid], 'i');

    // Auto-login (konsisten dengan login.php)
    session_regenerate_id(true);
    $_SESSION['user'] = [
      'id'    => $uid,
      'name'  => $name,
      'email' => $email,
      'role'  => 'USER',
    ];
    $_SESSION['user_id']   = $uid;
    $_SESSION['user_role'] = 'USER';

    // Redirect aman
    header('Location: ' . ($next ?: '/arcadia/public/index.php'));
    exit;

  } catch (Exception $e) {
    $errors[] = $e->getMessage();
  }
}

include __DIR__ . '/../_header.php';
?>
<style>
  .reg-wrap{max-width:940px;margin:24px auto 22px;padding:0 16px}
  .reg-card{
    padding:22px 20px;border-radius:18px;
    background:
      radial-gradient(120% 120% at 20% -10%, rgba(167,139,250,.16), transparent 60%),
      linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.015));
    border:1px solid rgba(255,255,255,.10);
    box-shadow:0 8px 24px rgba(0,0,0,.25) inset;
  }
  .reg-head{display:flex;align-items:center;gap:12px}
  .reg-logo{
    width:40px;height:40px;border-radius:12px;display:grid;place-items:center;
    background:radial-gradient(120% 120% at 30% 20%, rgba(167,139,250,.45), rgba(217,70,239,.18));
    border:1px solid rgba(255,255,255,.16);font-weight:800
  }
  .reg-sub{margin:.25rem 0 1rem;opacity:.9}

  .reg-grid{display:grid;gap:12px;grid-template-columns:1fr}
  @media(min-width:860px){.reg-grid{grid-template-columns:1fr 1fr}.reg-grid .full{grid-column:1/-1}}

  .input-group{display:flex;flex-direction:column;gap:8px}
  .input-group label{font-weight:700;letter-spacing:.2px}

  .pw-field{position:relative}
  .pw-toggle{
    position:absolute;right:10px;top:50%;transform:translateY(-50%);
    border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);
    padding:.35rem .55rem;border-radius:10px;cursor:pointer;font-size:.9rem
  }
  .pw-toggle:hover{background:rgba(255,255,255,.10)}

  .pw-meter{height:8px;border-radius:999px;background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.12);overflow:hidden}
  .pw-meter>i{display:block;height:100%;width:0%;transition:width .25s ease}
  .pw-0>i{width:0%}
  .pw-1>i{width:25%;background:#ef4444}
  .pw-2>i{width:50%;background:#f59e0b}
  .pw-3>i{width:75%;background:#22c55e}
  .pw-4>i{width:100%;background:#a78bfa}

  .hint{font-size:.9rem;opacity:.85}
  .reg-actions{margin-top:10px;display:flex;gap:12px;align-items:center}
  .alert{margin:.5rem 0 1rem;padding:.75rem .9rem;border-radius:12px}
  .alert.error{background:rgba(244,63,94,.08);border:1px solid rgba(244,63,94,.35)}
</style>

<div class="reg-wrap">
  <div class="reg-card">
    <div class="reg-head">
      <div class="reg-logo">☆</div>
      <h1 style="margin:0">Daftar Akun</h1>
    </div>
    <p class="reg-sub">Buat akun untuk menyimpan progres, ikut kontribusi, dan nikmati fitur komunitas.</p>

    <?php if (!empty($errors)): ?>
      <div class="alert error">
        <?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post" action="">
      <?php csrf_field(); ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">

      <div class="reg-grid">
        <div class="input-group">
          <label for="name">Nama</label>
          <input class="input" type="text" id="name" name="name"
                 placeholder="Nama tampilan" required
                 value="<?= e($_POST['name'] ?? '') ?>">
        </div>

        <div class="input-group">
          <label for="email">Email</label>
          <input class="input" type="email" id="email" name="email"
                 placeholder="nama@contoh.com" required
                 value="<?= e($_POST['email'] ?? '') ?>">
        </div>

        <div class="input-group full">
          <label for="password">Password</label>
          <div class="pw-field">
            <input class="input" type="password" id="password" name="password"
                   placeholder="Minimal 8 karakter" required>
            <button type="button" class="pw-toggle" id="btnShowPw" aria-label="Tampilkan password">Lihat</button>
          </div>
          <div class="pw-meter pw-0" id="pwMeter"><i></i></div>
          <div class="hint">Gunakan kombinasi <strong>huruf besar</strong>, <strong>angka</strong>, dan <strong>simbol</strong> untuk password yang kuat.</div>
        </div>

        <div class="full">
          <button class="btn" style="width:100%">Daftar</button>
        </div>
      </div>

      <div class="reg-actions hint">
        Sudah punya akun?
        <a href="/arcadia/public/auth/login.php<?= $next ? ('?next=' . urlencode($next)) : '' ?>" style="color:inherit;text-decoration:underline">Masuk</a>
      </div>
    </form>
  </div>
</div>

<script>
  (function(){
    const pw = document.getElementById('password');
    const btn = document.getElementById('btnShowPw');
    const meter = document.getElementById('pwMeter');

    if (btn && pw) {
      btn.addEventListener('click', ()=>{
        if(pw.type==='password'){ pw.type='text'; btn.textContent='Sembunyikan'; }
        else { pw.type='password'; btn.textContent='Lihat'; }
        pw.focus();
      });
    }
    function score(v){
      let s=0;
      if(v.length>=8) s++;
      if(/[A-Z]/.test(v)) s++;
      if(/\d/.test(v)) s++;
      if(/[^\w\s]/.test(v)) s++;
      return s;
    }
    pw.addEventListener('input', ()=>{
      meter.className = 'pw-meter pw-' + score(pw.value);
    });
  })();
</script>
