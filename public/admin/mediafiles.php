<?php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

$action = $_GET['action'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

$walks = db_all($mysqli, "SELECT id,title FROM walkthroughs ORDER BY id DESC");

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $walk_id = positive_int($_POST['walk_id'] ?? 0, 'Walkthrough');
    $file_type = required(str_trim($_POST['file_type'] ?? 'image'), 'Tipe');
    $file_url = required(str_trim($_POST['file_url'] ?? ''), 'URL');
    $caption = str_trim($_POST['caption'] ?? '');
    db_exec(
      $mysqli,
      "INSERT INTO mediafiles(walk_id,file_type,file_url,caption) VALUES(?,?,?,?)",
      [$walk_id, $file_type, $file_url, $caption],
      'isss'
    );
    flash('ok', 'Media ditambahkan.');
    header('Location: mediafiles.php');
    exit;
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM mediafiles WHERE id=?", [$id], 'i');
  flash('ok', 'Media dihapus.');
  header('Location: mediafiles.php');
  exit;
}

echo '<div class="card"><h1>Media</h1>';
if ($m = flash('ok'))
  echo '<div class="alert">' . e($m) . '</div>';
if ($m = flash('err'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '</div>';

echo '<div class="card"><h2>Tambah Media</h2><form method="post" action="mediafiles.php?action=create" class="grid">';
csrf_field();
echo '<label>Walkthrough<select name="walk_id">';
foreach ($walks as $w) {
  echo '<option value="' . $w['id'] . '">' . e($w['title']) . '</option>';
}
echo '</select></label>';
echo '<label>Tipe<select name="file_type"><option>image</option><option>video</option><option>pdf</option></select></label>';
echo '<label>URL Media<input class="input" name="file_url" placeholder="https://..."></label>';
echo '<label>Caption (opsional)<input class="input" name="caption"></label>';
echo '<button class="btn">Simpan</button></form></div>';

$rows = db_all($mysqli, "
  SELECT m.id, m.file_type, m.file_url, m.caption, w.title AS walk
  FROM mediafiles m JOIN walkthroughs w ON w.id=m.walk_id
  ORDER BY m.id DESC");
echo '<div class="card"><h2>Data</h2><table class="table"><tr><th>ID</th><th>Walkthrough</th><th>Tipe</th><th>URL</th><th>Caption</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['walk']) . '</td><td>' . e($r['file_type']) . '</td><td>' . e($r['file_url']) . '</td><td>' . e($r['caption']) . '</td>';
  echo '<td><a href="mediafiles.php?action=delete&id=' . $r['id'] . '" onclick="return confirm(\'Hapus?\')">Hapus</a></td></tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
