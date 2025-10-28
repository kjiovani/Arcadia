<?php
// /arcadia/public/admin/ui_api.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/db.php';

require_admin();
$me = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
if (!$me || strtoupper($me['role'] ?? '') !== 'OWNER') {
  http_response_code(403);
  header('Content-Type: application/json');
  echo json_encode(['ok' => false, 'msg' => 'Forbidden']);
  exit;
}
if (session_status() === PHP_SESSION_NONE)
  session_start();
header('Content-Type: application/json; charset=utf-8');

function set_setting($k, $v)
{
  global $mysqli;
  $row = db_one($mysqli, "SELECT `key` FROM app_settings WHERE `key`=?", [$k], 's');
  if ($row) {
    db_exec($mysqli, "UPDATE app_settings SET `value`=? WHERE `key`=?", [$v, $k], 'ss');
  } else {
    db_exec($mysqli, "INSERT INTO app_settings(`key`,`value`) VALUES(?,?)", [$k, $v], 'ss');
  }
}
function ext_from_mime($mime)
{
  return ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'][$mime] ?? null;
}
function upload_image_local($file, $subdir = 'branding', $seed = 'img')
{
  if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK)
    return null;
  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($file['tmp_name']);
  $ext = ext_from_mime($mime);
  if (!$ext)
    throw new Exception('Format harus JPG/PNG/WEBP.');
  $dir = realpath(__DIR__ . '/..') . '/uploads/' . $subdir;
  if (!is_dir($dir))
    @mkdir($dir, 0775, true);
  $name = strtolower(preg_replace('~[^a-z0-9]+~i', '-', $seed)) . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
  if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name))
    throw new Exception('Gagal menyimpan file.');
  return '/arcadia/public/uploads/' . $subdir . '/' . $name;
}

$act = $_POST['act'] ?? $_GET['act'] ?? '';
try {
  if ($act === 'set_logo') {
    if (empty($_FILES['logo']))
      throw new Exception('File tidak ada.');
    $url = upload_image_local($_FILES['logo'], 'branding', 'site-logo');
    set_setting('site_logo_url', $url);
    echo json_encode(['ok' => true, 'url' => $url]);
    exit;
  }
  if ($act === 'set_colors') {
    $p1 = trim($_POST['p1'] ?? '');
    $p2 = trim($_POST['p2'] ?? '');
    $p3 = trim($_POST['p3'] ?? '');
    set_setting('brand_color_p1', $p1);
    set_setting('brand_color_p2', $p2);
    set_setting('brand_color_p3', $p3);
    echo json_encode(['ok' => true]);
    exit;
  }
  if ($act === 'cover_focus') {
    $table = ($_POST['table'] ?? 'walkthroughs') === 'games' ? 'games' : 'walkthroughs';
    $id = (int) ($_POST['id'] ?? 0);
    $fx = max(0, min(100, (int) ($_POST['fx'] ?? 50)));
    $fy = max(0, min(100, (int) ($_POST['fy'] ?? 50)));
    db_exec($mysqli, "UPDATE {$table} SET cover_focus_x=?, cover_focus_y=? WHERE id=?", [$fx, $fy, $id], 'iii');
    echo json_encode(['ok' => true]);
    exit;
  }
  if ($act === 'set_favicon') {
    if (empty($_FILES['ico']))
      throw new Exception('File tidak ada.');
    $url = upload_image_local($_FILES['ico'], 'branding', 'favicon');
    set_setting('site_favicon_url', $url);
    echo json_encode(['ok' => true, 'url' => $url]);
    exit;
  }
  echo json_encode(['ok' => false, 'msg' => 'Unknown act']);
} catch (Exception $e) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
