<?php
// /arcadia/public/cover.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(404); exit; }

$row = db_one($mysqli, "SELECT cover_blob, cover_mime, cover_size FROM games WHERE id=?", [$id], 'i');
if (!$row || empty($row['cover_blob']) || (int)$row['cover_size'] <= 0) {
  http_response_code(404); exit;
}

$mime = $row['cover_mime'] ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . (int)$row['cover_size']);
header('Cache-Control: public, max-age=31536000, immutable');

echo $row['cover_blob'];
