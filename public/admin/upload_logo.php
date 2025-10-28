<?php
// /arcadia/public/admin/upload_logo.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';

$csrf = __DIR__ . '/../../lib/csrf.php';
if (file_exists($csrf))
  require_once $csrf;
if (!function_exists('csrf_verify')) {
  function csrf_verify()
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST')
      return;
    if (session_status() !== PHP_SESSION_ACTIVE)
      session_start();
    $ok = isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
      hash_equals($_SESSION['csrf_token'], $_SESSION['csrf_token']);
    if (!$ok) {
      http_response_code(419);
      echo json_encode(['ok' => false, 'msg' => 'CSRF']);
      exit;
    }
  }
}
if (!function_exists('csrf_field')) {
  function csrf_field()
  {
    if (session_status() !== PHP_SESSION_ACTIVE)
      session_start();
    if (empty($_SESSION['csrf_token']))
      $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . '">';
  }
}

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
  exit;
}
csrf_verify();

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['ok' => false, 'msg' => 'Tidak ada file / upload error']);
  exit;
}

$f = $_FILES['file'];
$max = 3 * 1024 * 1024; // 3MB
if ($f['size'] > $max) {
  echo json_encode(['ok' => false, 'msg' => 'Maks 3MB']);
  exit;
}

$mime = @mime_content_type($f['tmp_name']) ?: '';
$allow = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/svg+xml' => 'svg'];
if (!isset($allow[$mime])) {
  echo json_encode(['ok' => false, 'msg' => 'Tipe gambar tidak didukung']);
  exit;
}
$ext = $allow[$mime];

if ($ext === 'svg') {
  $svg = file_get_contents($f['tmp_name']);
  if (stripos($svg, '<script') !== false) {
    echo json_encode(['ok' => false, 'msg' => 'SVG tidak valid']);
    exit;
  }
}

$dir = __DIR__ . '/../../uploads';
if (!is_dir($dir))
  @mkdir($dir, 0775, true);
$name = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$dest = $dir . '/' . $name;

if (!move_uploaded_file($f['tmp_name'], $dest)) {
  echo json_encode(['ok' => false, 'msg' => 'Gagal simpan']);
  exit;
}

echo json_encode(['ok' => true, 'url' => '/arcadia/uploads/' . $name]);
