<?php
// /arcadia/public/admin/cover_focus.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/auth.php';
require_admin();
header('Content-Type: text/plain; charset=utf-8');

$table = $_POST['table'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$x = (int) max(0, min(100, (int) ($_POST['x'] ?? 50)));
$y = (int) max(0, min(100, (int) ($_POST['y'] ?? 50)));
$z = (int) max(60, min(400, (int) ($_POST['z'] ?? 100))); // zoom 60–400%

if ($table !== 'games' || $id <= 0) {
  http_response_code(400);
  echo 'Bad request';
  exit;
}

// pastikan kolom ada (MariaDB 10.4+ support IF NOT EXISTS)
$mysqli->query("ALTER TABLE games ADD COLUMN IF NOT EXISTS cover_focus_x INT DEFAULT 50");
$mysqli->query("ALTER TABLE games ADD COLUMN IF NOT EXISTS cover_focus_y INT DEFAULT 50");
$mysqli->query("ALTER TABLE games ADD COLUMN IF NOT EXISTS cover_zoom   INT DEFAULT 100");

db_exec(
  $mysqli,
  "UPDATE games SET cover_focus_x=?, cover_focus_y=?, cover_zoom=? WHERE id=?",
  [$x, $y, $z, $id],
  'iiii'
);

echo "Tersimpan ($x/$y, zoom $z%)";
