<?php
require_once __DIR__ . '/_header.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

/**
 * Simpan file cover (opsional).
 * Mengembalikan URL publik (string) jika sukses, null jika tidak ada file.
 * Lempar Exception jika gagal validasi/menyimpan.
 */
function handle_cover_upload(string $title): ?string {
  if (empty($_FILES['cover']) || $_FILES['cover']['error'] === UPLOAD_ERR_NO_FILE) {
    return null; // tidak ada file
  }
  if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
    $code = (int)$_FILES['cover']['error'];
    throw new Exception("Upload cover gagal (code $code).");
  }

  $tmp  = $_FILES['cover']['tmp_name'];
  $size = (int)$_FILES['cover']['size'];
  if ($size > 2*1024*1024) { // 2MB
    throw new Exception('Cover terlalu besar (>2MB).');
  }

  $fi = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($fi, $tmp);
  finfo_close($fi);
  $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($map[$mime])) {
    throw new Exception('Format cover harus jpg/png/webp.');
  }

  // Pastikan folder upload tersedia
  $uploadRoot = $_SERVER['DOCUMENT_ROOT'] . '/arcadia/uploads/covers';
  if (!is_dir($uploadRoot)) {
    @mkdir($uploadRoot, 0775, true);
  }

  // Nama file unik
  $slug = preg_replace('~[^a-z0-9]+~i', '-', $title ?: 'cover');
  $fname = strtolower(trim($slug, '-')) . '-' . time() . '.' . $map[$mime];

  $destFs  = $uploadRoot . '/' . $fname;                 // path di disk
  $destUrl = '/arcadia/uploads/covers/' . $fname;        // URL publik

  if (!move_uploaded_file($tmp, $destFs)) {
    throw new Exception('Gagal menyimpan cover.');
  }
  return $destUrl;
}

/* ========================= CREATE ========================= */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $title        = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre        = str_trim($_POST['genre'] ?? '');
    $platform     = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $image_url    = str_trim($_POST['image_url'] ?? ''); // opsional (manual)
    $description  = str_trim($_POST['description'] ?? '');

    db_exec(
      $mysqli,
      "INSERT INTO games(title,genre,platform,release_year,image_url,description) VALUES(?,?,?,?,?,?)",
      [$title, $genre, $platform, $release_year, $image_url, $description],
      'sssiss'
    );
    $newId = mysqli_insert_id($mysqli);

    // Coba simpan cover (jika ada file)
    try {
      if ($url = handle_cover_upload($title)) {
        db_exec($mysqli, "UPDATE games SET image_url=? WHERE id=?", [$url, $newId], 'si');
      }
    } catch (Exception $eUp) {
      // Upload gagal tapi data game sudah tersimpan — tampilkan pesan merah, tetap lanjut
      flash('err', $eUp->getMessage());
    }

    flash('ok', 'Game dibuat.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}

/* ========================= UPDATE ========================= */
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id           = positive_int($_POST['id'] ?? 0, 'ID');
    $title        = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre        = str_trim($_POST['genre'] ?? '');
    $platform     = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $image_url    = str_trim($_POST['image_url'] ?? ''); // opsional (manual)
    $description  = str_trim($_POST['description'] ?? '');

    db_exec(
      $mysqli,
      "UPDATE games SET title=?,genre=?,platform=?,release_year=?,image_url=?,description=? WHERE id=?",
      [$title, $genre, $platform, $release_year, $image_url, $description, $id],
      'sssissi'
    );

    // Coba simpan cover baru (jika ada file)
    try {
      if ($url = handle_cover_upload($title)) {
        db_exec($mysqli, "UPDATE games SET image_url=? WHERE id=?", [$url, $id], 'si');
      }
    } catch (Exception $eUp) {
      flash('err', $eUp->getMessage());
    }

    flash('ok', 'Game diperbarui.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
  }
}

/* ========================= DELETE ========================= */
if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM games WHERE id=?", [$id], 'i');
  flash('ok', 'Game dihapus.');
  redirect('games.php');
}

/* ========================= UI ========================= */
echo '<div class="card"><h1>Games</h1>';
if ($m = flash('ok'))  echo '<div class="alert">'.e($m).'</div>';
if ($m = flash('err')) echo '<div class="alert">'.e($m).'</div>';
echo '</div>';

if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $g = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], 'i');
  if (!$g) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }

  echo '<div class="card"><h2>Edit</h2>
        <form method="post" class="grid" enctype="multipart/form-data">';
  csrf_field();
  echo '<input type="hidden" name="id" value="'.$g['id'].'"/>';
  echo '<label>Judul<input class="input" name="title" value="'.e($g['title']).'"></label>';
  echo '<label>Genre<input class="input" name="genre" value="'.e($g['genre']).'"></label>';
  echo '<label>Platform<input class="input" name="platform" value="'.e($g['platform']).'"></label>';
  echo '<label>Tahun Rilis<input class="input" type="number" name="release_year" value="'.e($g['release_year']).'"></label>';
  echo '<label>Gambar URL (opsional, isi manual jika perlu)<input class="input" name="image_url" value="'.e($g['image_url']).'"></label>';
  echo '<label>Deskripsi<textarea name="description" rows="5">'.e($g['description']).'</textarea></label>';

  if (!empty($g['image_url'])) {
    echo '<div class="small">Cover saat ini: <a href="'.e($g['image_url']).'" target="_blank">lihat</a></div>';
  }
  echo '<label>Ganti Cover (jpg/png/webp, ≤ 2MB)
          <input class="input" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
        </label>';

  echo '<button class="btn">Simpan</button>
        <a class="btn gray" href="games.php">Batal</a>
        <input type="hidden" name="action" value="update">
        </form></div>';

} else {
  echo '<div class="card"><h2>Tambah</h2>
        <form method="post" action="games.php?action=create" class="grid" enctype="multipart/form-data">';
  csrf_field();
  echo '<label>Judul<input class="input" name="title"></label>';
  echo '<label>Genre<input class="input" name="genre"></label>';
  echo '<label>Platform<input class="input" name="platform"></label>';
  echo '<label>Tahun Rilis<input class="input" type="number" name="release_year"></label>';
  echo '<label>Gambar URL (opsional, isi manual jika perlu)<input class="input" name="image_url"></label>';
  echo '<label>Deskripsi<textarea name="description" rows="5"></textarea></label>';
  echo '<label>Cover Image (jpg/png/webp, ≤ 2MB)
          <input class="input" type="file" name="cover" accept=".jpg,.jpeg,.png,.webp">
        </label>';
  echo '<button class="btn">Simpan</button>';
  echo '</form></div>';
}

$rows = db_all($mysqli, "SELECT * FROM games ORDER BY id DESC");
echo '<div class="card"><h2>Data</h2>
      <table class="table"><tr><th>ID</th><th>Judul</th><th>Genre</th><th>Platform</th><th></th></tr>';
foreach ($rows as $r) {
  echo '<tr>
          <td>'.$r['id'].'</td>
          <td>'.e($r['title']).'</td>
          <td>'.e($r['genre']).'</td>
          <td>'.e($r['platform']).'</td>
          <td><a href="games.php?action=edit&id='.$r['id'].'">Edit</a> ·
              <a href="games.php?action=delete&id='.$r['id'].'" onclick="return confirm(\'Hapus data?\')">Hapus</a></td>
        </tr>';
}
echo '</table></div>';

require_once __DIR__ . '/_footer.php';
