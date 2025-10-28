<?php
// /arcadia/public/admin/cover_crop.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_admin();

header('Content-Type: application/json; charset=utf-8');

// validasi input
$game_id = (int) ($_POST['game_id'] ?? 0);
if ($game_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'game_id invalid']);
    exit;
}

// ambil game
$g = db_one($mysqli, "SELECT id, image_url, COALESCE(image_original_url,'') AS orig FROM games WHERE id=?", [$game_id], 'i');
if (!$g) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'msg' => 'Game tidak ditemukan']);
    exit;
}

// file blob
if (!isset($_FILES['cover']) || ($_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Berkas crop tidak ada']);
    exit;
}

// pastikan folder
$publicRoot = realpath(__DIR__ . '/..'); // /arcadia/public
$saveDirRel = 'uploads/covers';
$saveDirAbs = $publicRoot . '/' . $saveDirRel;
if (!is_dir($saveDirAbs))
    @mkdir($saveDirAbs, 0775, true);

// cek mime & ekstensi
$fi = new finfo(FILEINFO_MIME_TYPE);
$mime = $fi->file($_FILES['cover']['tmp_name']);
$map = ['image/webp' => 'webp', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
$ext = $map[$mime] ?? 'webp'; // default webp

// nama file
$name = 'game_' . $game_id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
$abs = $saveDirAbs . '/' . $name;
$rel = '/arcadia/public/' . $saveDirRel . '/' . $name;

// pindahkan
if (!move_uploaded_file($_FILES['cover']['tmp_name'], $abs)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Gagal menyimpan crop']);
    exit;
}

// kalau belum punya original_url, simpan yang sekarang sebagai original
if (trim($g['orig']) === '') {
    db_exec($mysqli, "UPDATE games SET image_original_url=image_url WHERE id=?", [$game_id], 'i');
}

// update cover menjadi file baru
db_exec($mysqli, "UPDATE games SET image_url=? WHERE id=?", [$rel, $game_id], 'si');

// kasih balik url + cache buster
echo json_encode(['ok' => true, 'url' => $rel . '?v=' . time()]);
