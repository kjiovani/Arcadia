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
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $order_number = (int) ($_POST['order_number'] ?? 1);
    $content = str_trim($_POST['content'] ?? '');
    db_exec(
      $mysqli,
      "INSERT INTO chapters(walk_id,title,content,order_number) VALUES(?,?,?,?)",
      [$walk_id, $title, $content, $order_number],
      'issi'
    );
    flash('ok', 'Chapter dibuat.');
    redirect('chapters.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $walk_id = positive_int($_POST['walk_id'] ?? 0, 'Walkthrough');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $order_number = (int) ($_POST['order_number'] ?? 1);
    $content = str_trim($_POST['content'] ?? '');
    db_exec(
      $mysqli,
      "UPDATE chapters SET walk_id=?, title=?, content=?, order_number=? WHERE id=?",
      [$walk_id, $title, $content, $order_number, $id],
      'issii'
    );
    flash('ok', 'Chapter diperbarui.');
    redirect('chapters.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM chapters WHERE id=?", [$id], 'i');
  flash('ok', 'Chapter dihapus.');
  redirect('chapters.php');
}

echo '<div class="card"><h1>Chapters</h1>';
if ($m = flash('ok'))
  echo '<div class="alert">' . e($m) . '</div>';
if ($m = flash('err'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '</div>';

if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $c = db_one($mysqli, "SELECT * FROM chapters WHERE id=?", [$id], 'i');
  if (!$c) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  echo '<div class="card"><h2>Edit</h2><form method="post" class="grid">';
  csrf_field();
  echo '<input type="hidden" name="id" value="' . $c['id'] . '"/>';
  echo '<label>Walkthrough<select name="walk_id">';
  foreach ($walks as $w) {
    $sel = $w['id'] == $c['walk_id'] ? 'selected' : '';
    echo '<option value="' . $w['id'] . '" ' . $sel . '>' . e($w['title']) . '</option>';
  }
  echo '</select></label>';
  echo '<label>Judul<input class="input" name="title" value="' . e($c['title']) . '"></label>';
  echo '<label>Nomor Urut<input class="input" type="number" name="order_number" value="' . e($c['order_number']) . '"></label>';
  echo '<label>Konten<textarea name="content" rows="6">' . e($c['content']) . '</textarea></label>';
  echo '<button class="btn">Simpan</button> <a class="btn gray" href="chapters.php">Batal</a>';
  echo '<input type="hidden" name="action" value="update"></form></div>';
} else {
  echo '<div class="card"><h2>Tambah</h2><form method="post" action="chapters.php?action=create" class="grid">';
  csrf_field();
  echo '<label>Walkthrough<select name="walk_id">';
  foreach ($walks as $w) {
    echo '<option value="' . $w['id'] . '">' . e($w['title']) . '</option>';
  }
  echo '</select></label>';
  echo '<label>Judul<input class="input" name="title"></label>';
  echo '<label>Nomor Urut<input class="input" type="number" name="order_number" value="1"></label>';
  echo '<label>Konten<textarea name="content" rows="6"></textarea></label>';
  echo '<button class="btn">Simpan</button>';
  echo '</form></div>';
}

$rows = db_all($mysqli, "SELECT c.id,c.title,c.order_number,w.title AS walk FROM chapters c JOIN walkthroughs w ON w.id=c.walk_id ORDER BY c.id DESC");
echo '<div class="card"><h2>Data</h2><table class="table"><tr><th>ID</th><th>Walkthrough</th><th>Judul</th><th>#</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['walk']) . '</td><td>' . e($r['title']) . '</td><td>' . e($r['order_number']) . '</td>';
  echo '<td><a href="chapters.php?action=edit&id=' . $r['id'] . '">Edit</a> · <a href="chapters.php?action=delete&id=' . $r['id'] . '" onclick="return confirm(\'Hapus?\')">Hapus</a></td></tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
?>