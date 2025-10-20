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
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre = str_trim($_POST['genre'] ?? '');
    $platform = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $image_url = str_trim($_POST['image_url'] ?? '');
    $description = str_trim($_POST['description'] ?? '');
    db_exec(
      $mysqli,
      "INSERT INTO games(title,genre,platform,release_year,image_url,description) VALUES(?,?,?,?,?,?)",
      [$title, $genre, $platform, $release_year, $image_url, $description],
      'sssiss'
    );
    flash('ok', 'Game dibuat.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre = str_trim($_POST['genre'] ?? '');
    $platform = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $image_url = str_trim($_POST['image_url'] ?? '');
    $description = str_trim($_POST['description'] ?? '');
    db_exec(
      $mysqli,
      "UPDATE games SET title=?,genre=?,platform=?,release_year=?,image_url=?,description=? WHERE id=?",
      [$title, $genre, $platform, $release_year, $image_url, $description, $id],
      'sssissi'
    );
    flash('ok', 'Game diperbarui.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}
if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM games WHERE id=?", [$id], 'i');
  flash('ok', 'Game dihapus.');
  redirect('games.php');
}

echo '<div class="card"><h1>Games</h1>';
if ($m = flash('ok'))
  echo '<div class="alert">' . e($m) . '</div>';
if ($m = flash('err'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '</div>';

if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $g = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], 'i');
  if (!$g) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  echo '<div class="card"><h2>Edit Game</h2><form method="post" class="grid">';
  csrf_field();
  echo '<input type="hidden" name="id" value="' . $g['id'] . '"/>';
  echo '<label>Judul<input class="input" name="title" value="' . e($g['title']) . '"></label>';
  echo '<label>Genre<input class="input" name="genre" value="' . e($g['genre']) . '"></label>';
  echo '<label>Platform<input class="input" name="platform" value="' . e($g['platform']) . '"></label>';
  echo '<label>Tahun Rilis<input class="input" type="number" name="release_year" value="' . e($g['release_year']) . '"></label>';
  echo '<label>Gambar URL<input class="input" name="image_url" value="' . e($g['image_url']) . '"></label>';
  echo '<label>Deskripsi<textarea name="description" rows="5">' . e($g['description']) . '</textarea></label>';
  echo '<button class="btn">Simpan</button><a class="btn gray" href="games.php">Batal</a>';
  echo '<input type="hidden" name="action" value="update"></form></div>';
} else {
  echo '<div class="card"><h2>Tambah Game</h2><form method="post" action="games.php?action=create" class="grid">';
  csrf_field();
  echo '<label>Judul<input class="input" name="title"></label>';
  echo '<label>Genre<input class="input" name="genre"></label>';
  echo '<label>Platform<input class="input" name="platform"></label>';
  echo '<label>Tahun Rilis<input class="input" type="number" name="release_year"></label>';
  echo '<label>Gambar URL<input class="input" name="image_url"></label>';
  echo '<label>Deskripsi<textarea name="description" rows="5"></textarea></label>';
  echo '<button class="btn">Simpan</button>';
  echo '</form></div>';
}

$rows = db_all($mysqli, "SELECT * FROM games ORDER BY id DESC");
echo '<div class="card"><h2>Data</h2><table class="table"><tr><th>ID</th><th>Judul</th><th>Genre</th><th>Platform</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['title']) . '</td><td>' . e($r['genre']) . '</td><td>' . e($r['platform']) . '</td>';
  echo '<td><a href="games.php?action=edit&id=' . $r['id'] . '">Edit</a> · <a href="games.php?action=delete&id=' . $r['id'] . '" onclick="return confirm(\'Hapus data?\')">Hapus</a></td></tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
?>