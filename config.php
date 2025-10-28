<?php
// config.php — Arcadia (XAMPP/localhost)

// =====================
// Database connection
// =====================
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'arcadia_db';
$DB_PORT = (int) (getenv('DB_PORT') ?: 3306);

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
  http_response_code(500);
  echo "DB connection failed: " . $mysqli->connect_error;
  exit;
}
$mysqli->set_charset('utf8mb4');

// =====================
// App paths & URLs
// =====================
// Dari screenshot kamu akses: http://localhost/arcadia/public/...
// Jadi BASE_URL nya seperti di bawah. Kalau beda, ganti sesuai aksesmu.
if (!defined('BASE_URL'))
  define('BASE_URL', '/arcadia/public');
if (!defined('PUBLIC_PATH'))
  define('PUBLIC_PATH', realpath(__DIR__ . '/public'));

// Folder uploads (di dalam /public agar bisa diakses browser)
if (!defined('UPLOADS_PATH'))
  define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
if (!is_dir(UPLOADS_PATH))
  @mkdir(UPLOADS_PATH, 0775, true);

if (!defined('COVERS_PATH'))
  define('COVERS_PATH', UPLOADS_PATH . '/covers');
if (!is_dir(COVERS_PATH))
  @mkdir(COVERS_PATH, 0775, true);

// Helper untuk membentuk URL aset (css/js/gambar)
if (!function_exists('asset_url')) {
  function asset_url(string $path = ''): string
  {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
  }
}

// =====================
// Session
// =====================
if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

// Optional (biar konsisten timestamp)
date_default_timezone_set('Asia/Jakarta');
