<?php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

$action = $_GET['action'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama tag');
    db_exec($mysqli, "INSERT INTO tags(name) VALUES(?)", [$name]);
    flash('ok', 'Tag dibuat.');
    header('Location: tags.php');
    exit;
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama tag');
    db_exec($mysqli, "UPDATE tags SET name=? WHERE id=?", [$name, $id], 'si');
    flash('ok', 'Tag diperbarui.');
    header('Location: tags.php');
    exit;
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM tags WHERE id=?", [$id], 'i');
  flash('ok', 'Tag dihapus.');
  header('Location: tags.php');
  exit;
}

echo '<div class="card"><h1>Tags</h1>';
if ($m = flash('ok'))
  echo '<div class="alert">' . e($m) . '</div>';
if ($m = flash('err'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '</div>';

if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $t = db_one($mysqli, "SELECT * FROM tags WHERE id=?", [$id], 'i');
  if (!$t) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  echo '<div class="card"><h2>Edit Tag</h2><form method="post" class="grid">';
  csrf_field();
  echo '<input type="hidden" name="id" value="' . $t['id'] . '"/>';
  echo '<label>Nama Tag<input class="input" name="name" value="' . e($t['name']) . '"></label>';
  echo '<button class="btn">Simpan</button> <a class="btn gray" href="tags.php">Batal</a>';
  echo '<input type="hidden" name="action" value="update"></form></div>';
} else {
  echo '<div class="card"><h2>Tambah Tag</h2><form method="post" action="tags.php?action=create" class="grid">';
  csrf_field();
  echo '<label>Nama Tag<input class="input" name="name"></label>';
  echo '<button class="btn">Simpan</button></form></div>';
}

$rows = db_all($mysqli, "SELECT * FROM tags ORDER BY name ASC");
echo '<div class="card"><h2>Data</h2><table class="table"><tr><th>ID</th><th>Nama</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['name']) . '</td><td><a href="tags.php?action=edit&id=' . $r['id'] . '">Edit</a> · <a href="tags.php?action=delete&id=' . $r['id'] . '" onclick="return confirm(\'Hapus?\')">Hapus</a></td></tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
