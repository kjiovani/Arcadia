<?php
// lib/helpers.php
function e($s)
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function redirect($path)
{
  header("Location: " . $path);
  exit;
}
function flash($key, $msg = null)
{
  if ($msg !== null) {
    $_SESSION['_flash'][$key] = $msg;
    return;
  }
  if (!empty($_SESSION['_flash'][$key])) {
    $m = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $m;
  }
  return null;
}
function current_path()
{
  return strtok($_SERVER['REQUEST_URI'], '?');
}
?>

<?php
// ==== Image Upload Helper ====
// Pakai ini di profile & appearance agar seragam.
function save_uploaded_image(string $field, string $destDir = __DIR__ . '/../public/uploads', string $prefix = 'img', int $maxMB = 5): ?string {
  if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;

  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) return null;

  // Batas ukuran (MB)
  $maxBytes = $maxMB * 1024 * 1024;
  if ($f['size'] > $maxBytes) {
    // tolong tampilkan pesan sesuai sistem flash message milikmu
    return null;
  }

  // Validasi MIME asli (bukan cuma ekstensi)
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $f['tmp_name']);
  finfo_close($finfo);

  $allowed = [
    'image/jpeg' => '.jpg',
    'image/png'  => '.png',
    'image/webp' => '.webp',
    'image/gif'  => '.gif',
  ];
  if (!isset($allowed[$mime])) {
    return null; // bukan gambar yang valid
  }

  // Validasi dimensi (pastikan benar-benar gambar)
  $info = @getimagesize($f['tmp_name']);
  if (!$info) return null;

  // Siapkan folder tujuan (di dalam /public agar bisa diakses)
  $publicRoot = realpath(__DIR__ . '/../public');
  if (!is_dir($destDir)) {
    @mkdir($destDir, 0775, true);
  }

  // Nama file acak aman
  $ext  = $allowed[$mime];
  $name = $prefix . '_' . bin2hex(random_bytes(8)) . $ext;
  $dest = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;

  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    return null;
  }

  // Kembalikan path relatif web (/uploads/xxx)
  $rel = str_replace($publicRoot, '', realpath($dest));
  // normalisasi slash untuk URL
  $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
  return $rel;
}
