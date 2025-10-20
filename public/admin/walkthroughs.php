<?php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

$games = db_all($mysqli, "SELECT id,title FROM games ORDER BY title ASC");
$allTags = db_all($mysqli, "SELECT id,name FROM tags ORDER BY name ASC");

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $game_id = positive_int($_POST['game_id'] ?? 0, 'Game');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $overview = str_trim($_POST['overview'] ?? '');
    $difficulty = str_trim($_POST['difficulty'] ?? 'Medium');

    db_exec(
      $mysqli,
      "INSERT INTO walkthroughs(game_id,title,overview,difficulty) VALUES(?,?,?,?)",
      [$game_id, $title, $overview, $difficulty],
      'isss'
    );

    // === Simpan relasi TAGS ===
    $walk_id = mysqli_insert_id($mysqli);
    $tags = $_POST['tags'] ?? [];
    db_exec($mysqli, "DELETE FROM walktag WHERE walk_id=?", [$walk_id], 'i');
    foreach ($tags as $tag_id) {
      db_exec($mysqli, "INSERT IGNORE INTO walktag(walk_id, tag_id) VALUES(?,?)", [(int) $walk_id, (int) $tag_id], 'ii');
    }

    flash('ok', 'Walkthrough dibuat.');
    redirect('walkthroughs.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $game_id = positive_int($_POST['game_id'] ?? 0, 'Game');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $overview = str_trim($_POST['overview'] ?? '');
    $difficulty = str_trim($_POST['difficulty'] ?? 'Medium');

    db_exec(
      $mysqli,
      "UPDATE walkthroughs SET game_id=?, title=?, overview=?, difficulty=? WHERE id=?",
      [$game_id, $title, $overview, $difficulty, $id],
      'isssi'
    );

    // === Perbarui relasi TAGS ===
    $tags = $_POST['tags'] ?? [];
    db_exec($mysqli, "DELETE FROM walktag WHERE walk_id=?", [$id], 'i');
    foreach ($tags as $tag_id) {
      db_exec($mysqli, "INSERT IGNORE INTO walktag(walk_id, tag_id) VALUES(?,?)", [(int) $id, (int) $tag_id], 'ii');
    }

    flash('ok', 'Walkthrough diperbarui.');
    redirect('walkthroughs.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}

if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM walkthroughs WHERE id=?", [$id], 'i');
  db_exec($mysqli, "DELETE FROM walktag WHERE walk_id=?", [$id], 'i'); // bersihkan relasi
  flash('ok', 'Walkthrough dihapus.');
  redirect('walkthroughs.php');
}

echo '<div class="card"><h1>Walkthroughs</h1>';
if ($m = flash('ok'))
  echo '<div class="alert">' . e($m) . '</div>';
if ($m = flash('err'))
  echo '<div class="alert">' . e($m) . '</div>';
echo '</div>';

if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $w = db_one($mysqli, "SELECT * FROM walkthroughs WHERE id=?", [$id], 'i');
  if (!$w) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }

  // tag yang terpilih
  $selected = db_all($mysqli, "SELECT tag_id FROM walktag WHERE walk_id=?", [$id], 'i');
  $selectedIds = array_column($selected, 'tag_id');

  echo '<div class="card"><h2>Edit</h2><form method="post" class="grid">';
  csrf_field();
  echo '<input type="hidden" name="id" value="' . $w['id'] . '"/>';

  echo '<label>Game<select name="game_id">';
  foreach ($games as $g) {
    $sel = $g['id'] == $w['game_id'] ? 'selected' : '';
    echo '<option value="' . $g['id'] . '" ' . $sel . '>' . e($g['title']) . '</option>';
  }
  echo '</select></label>';

  echo '<label>Judul<input class="input" name="title" value="' . e($w['title']) . '"></label>';
  echo '<label>Ringkasan<textarea name="overview" rows="4">' . e($w['overview']) . '</textarea></label>';

  echo '<label>Kesulitan<select name="difficulty">';
  foreach (['Easy', 'Medium', 'Hard'] as $d) {
    $sel = $d === $w['difficulty'] ? 'selected' : '';
    echo '<option ' . $sel . '>' . $d . '</option>';
  }
  echo '</select></label>';

  // === Checklist TAGS ===
  echo '<fieldset><legend class="small">Tags</legend>';
  foreach ($allTags as $t) {
    $chk = in_array($t['id'], $selectedIds) ? 'checked' : '';
    echo '<label style="display:inline-flex;gap:.4rem;align-items:center;margin:.2rem 1rem .2rem 0">';
    echo '<input type="checkbox" name="tags[]" value="' . $t['id'] . '" ' . $chk . '> <span>' . e($t['name']) . '</span>';
    echo '</label>';
  }
  echo '</fieldset>';

  echo '<button class="btn">Simpan</button> <a class="btn gray" href="walkthroughs.php">Batal</a>';
  echo '<input type="hidden" name="action" value="update"></form></div>';

} else {
  echo '<div class="card"><h2>Tambah</h2><form method="post" action="walkthroughs.php?action=create" class="grid">';
  csrf_field();

  echo '<label>Game<select name="game_id">';
  foreach ($games as $g) {
    echo '<option value="' . $g['id'] . '">' . e($g['title']) . '</option>';
  }
  echo '</select></label>';

  echo '<label>Judul<input class="input" name="title"></label>';
  echo '<label>Ringkasan<textarea name="overview" rows="4"></textarea></label>';
  echo '<label>Kesulitan<select name="difficulty"><option>Easy</option><option selected>Medium</option><option>Hard</option></select></label>';

  // === Checklist TAGS (create) ===
  echo '<fieldset><legend class="small">Tags</legend>';
  foreach ($allTags as $t) {
    echo '<label style="display:inline-flex;gap:.4rem;align-items:center;margin:.2rem 1rem .2rem 0">';
    echo '<input type="checkbox" name="tags[]" value="' . $t['id'] . '"> <span>' . e($t['name']) . '</span>';
    echo '</label>';
  }
  echo '</fieldset>';

  echo '<button class="btn">Simpan</button>';
  echo '</form></div>';
}

$rows = db_all($mysqli, "
  SELECT w.id, w.title, w.difficulty, g.title AS game
  FROM walkthroughs w
  JOIN games g ON g.id=w.game_id
  ORDER BY w.id DESC
");
echo '<div class="card"><h2>Data</h2><table class="table"><tr><th>ID</th><th>Game</th><th>Judul</th><th>Kesulitan</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr><td>' . $r['id'] . '</td><td>' . e($r['game']) . '</td><td>' . e($r['title']) . '</td><td>' . e($r['difficulty']) . '</td>';
  echo '<td><a href="walkthroughs.php?action=edit&id=' . $r['id'] . '">Edit</a> · <a href="walkthroughs.php?action=delete&id=' . $r['id'] . '" onclick="return confirm(\'Hapus?\')">Hapus</a></td></tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
?>