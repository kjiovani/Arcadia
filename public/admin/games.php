<?php
// ===== BOOT (tanpa output HTML) =====
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

if (session_status() === PHP_SESSION_NONE)
  session_start();
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/validation.php';

/* ✅ Utamakan POST lalu GET agar submit Edit tidak “mentok” di action=edit (GET) */
$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

/* ===================== Upload ke FOLDER (return URL) ===================== */
function handle_cover_upload(string $title): ?string
{
  if (empty($_FILES['cover']) || $_FILES['cover']['error'] === UPLOAD_ERR_NO_FILE)
    return null;
  if ($_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
    $code = (int) $_FILES['cover']['error'];
    throw new Exception("Upload cover gagal (code $code).");
  }

  $tmp = $_FILES['cover']['tmp_name'];
  $size = (int) $_FILES['cover']['size'];
  if ($size <= 0)
    return null;
  if ($size > 2 * 1024 * 1024)
    throw new Exception('Cover terlalu besar (>2MB).');

  $fi = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($fi, $tmp);
  finfo_close($fi);

  $ok = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  if (!isset($ok[$mime]))
    throw new Exception('Format cover harus jpg/png/webp.');

  // nama file aman
  $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~i', '-', $title ?: 'cover'), '-'));

  // ==== pakai konstanta & helper dari config.php ====
  $uploadRoot = COVERS_PATH;                           // path file di disk
  if (!is_dir($uploadRoot))
    @mkdir($uploadRoot, 0775, true);

  $name = $slug . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ok[$mime];
  $destFs = $uploadRoot . '/' . $name;
  $destUrl = asset_url('uploads/covers/' . $name);  // URL publik

  if (!move_uploaded_file($tmp, $destFs))
    throw new Exception('Gagal menyimpan cover.');
  return $destUrl;
}

/* ========================= ACTIONS (tanpa output) ========================= */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre = str_trim($_POST['genre'] ?? '');
    $platform = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $description = str_trim($_POST['description'] ?? '');

    // ✅ Upload dulu (kalau ada). Kalau gagal akan throw dan ketangkap di catch.
    $url = handle_cover_upload($title);  // bisa NULL jika user tidak memilih file

    db_exec(
      $mysqli,
      "INSERT INTO games(title, genre, platform, release_year, image_url, description)
       VALUES(?,?,?,?,?,?)",
      [$title, $genre, $platform, $release_year, $url ?: '', $description],
      'sssiss'
    );

    flash('ok', 'Game dibuat.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('games.php');
  }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $genre = str_trim($_POST['genre'] ?? '');
    $platform = str_trim($_POST['platform'] ?? '');
    $release_year = (int) ($_POST['release_year'] ?? 0);
    $description = str_trim($_POST['description'] ?? '');

    // ✅ Coba upload. Kalau tidak ada file baru: $url === NULL → image tidak diubah.
    $url = handle_cover_upload($title);

    if ($url !== null) {
      db_exec(
        $mysqli,
        "UPDATE games
         SET title=?, genre=?, platform=?, release_year=?, description=?, image_url=?
         WHERE id=?",
        [$title, $genre, $platform, $release_year, $description, $url, $id],
        'sssissi'
      );
    } else {
      db_exec(
        $mysqli,
        "UPDATE games
         SET title=?, genre=?, platform=?, release_year=?, description=?
         WHERE id=?",
        [$title, $genre, $platform, $release_year, $description, $id],
        'sssisi'
      );
    }

    flash('ok', 'Game diperbarui.');
    redirect('games.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('games.php');
  }
}

if ($action === 'delete' && isset($_GET['id'])) {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM games WHERE id=?", [$id], 'i');
  flash('ok', 'Game dihapus.');
  redirect('games.php');
}

/* ========================= MULAI RENDER UI ========================= */
require_once __DIR__ . '/_header.php';
?>
<style>
  .dz {
    position: relative;
    border: 1px dashed rgba(167, 139, 250, .55);
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    border-radius: 16px;
    padding: 18px;
    text-align: center;
    cursor: pointer;
    transition: .15s
  }

  .dz:hover {
    border-color: var(--primary);
    box-shadow: 0 10px 30px var(--ring)
  }

  .dz.dragover {
    border-color: #a78bfa;
    box-shadow: 0 0 0 3px rgba(167, 139, 250, .25) inset
  }

  .dz .thumb {
    display: none;
    margin-top: 12px;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid var(--border)
  }

  .dz .thumb img {
    display: block;
    width: 100%;
    height: auto
  }

  .dz .err {
    margin-top: 10px;
    color: #f87171;
    font-weight: 700;
    display: none
  }

  .hidden-file {
    display: none
  }

  .tbl {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 10px
  }

  .tbl thead th {
    padding: 10px 14px;
    text-align: left;
    opacity: .8
  }

  .tbl tbody tr {
    transition: .15s transform
  }

  .tbl tbody tr:hover {
    transform: translateY(-2px)
  }

  .tbl tbody td {
    background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
    border: 1px solid rgba(255, 255, 255, .08);
    padding: 12px 14px
  }

  .tbl tbody td:first-child {
    border-radius: 12px 0 0 12px
  }

  .tbl tbody td:last-child {
    border-radius: 0 12px 12px 0
  }

  .actions {
    display: flex;
    gap: 10px;
    align-items: center;
    justify-content: flex-end
  }

  .chip-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .12);
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
    text-decoration: none;
    color: #eee;
    font-weight: 700;
    font-size: .95rem
  }

  .chip-btn:hover {
    transform: translateY(-1px);
    border-color: var(--primary);
    box-shadow: 0 8px 22px var(--ring)
  }

  .chip-edit {
    border-color: rgba(167, 139, 250, .45);
    background: rgba(167, 139, 250, .15)
  }

  .chip-edit:hover {
    box-shadow: 0 8px 24px rgba(167, 139, 250, .35)
  }

  .chip-del {
    border-color: rgba(239, 68, 68, .45);
    background: rgba(239, 68, 68, .12)
  }

  .chip-del:hover {
    box-shadow: 0 8px 24px rgba(239, 68, 68, .35)
  }

  .tbl td.act {
    white-space: nowrap
  }
</style>

<div class="card">
  <h1>Games</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
</div>

<?php
if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $g = db_one($mysqli, "SELECT * FROM games WHERE id=?", [$id], 'i');
  if (!$g) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  ?>
  <div class="card">
    <h2>Edit</h2>
    <!-- ✅ form submit ke file yang sama TANPA query; aksi ditentukan oleh input hidden -->
    <form method="post" action="games.php" class="grid" enctype="multipart/form-data">
      <?php csrf_field(); ?>
      <input type="hidden" name="id" value="<?= (int) $g['id'] ?>" />
      <input type="hidden" name="action" value="update">

      <label>Judul <input class="input" name="title" value="<?= e($g['title']) ?>"></label>
      <label>Genre <input class="input" name="genre" value="<?= e($g['genre']) ?>"></label>
      <label>Platform <input class="input" name="platform" value="<?= e($g['platform']) ?>"></label>
      <label>Tahun Rilis <input class="input" type="number" name="release_year"
          value="<?= e($g['release_year']) ?>"></label>
      <label>Deskripsi <textarea name="description" rows="5"><?= e($g['description']) ?></textarea></label>

      <?php if (!empty($g['image_url'])): ?>
        <div class="small">Cover saat ini: <a href="<?= e($g['image_url']) ?>" target="_blank">lihat</a></div>
      <?php endif; ?>

      <label>Ganti Cover <span style="opacity:.7">(jpg/png/webp, ≤ 2MB, drag & drop)</span></label>
      <div class="dz" id="dz-cover">
        <div>Tarik & letakkan gambar di sini atau klik untuk memilih file</div>
        <div class="err" id="dz-err"></div>
        <div class="thumb" id="dz-thumb"><img id="dz-img" alt="preview"></div>
        <input class="hidden-file" type="file" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <button class="btn">Simpan</button>
      <a class="btn gray" href="games.php">Batal</a>
    </form>
  </div>
<?php } else { ?>
  <div class="card">
    <h2>Tambah</h2>
    <!-- ✅ hapus query; pakai hidden action=create -->
    <form method="post" action="games.php" class="grid" enctype="multipart/form-data">
      <?php csrf_field(); ?>
      <input type="hidden" name="action" value="create">

      <label>Judul <input class="input" name="title"></label>
      <label>Genre <input class="input" name="genre"></label>
      <label>Platform <input class="input" name="platform"></label>
      <label>Tahun Rilis <input class="input" type="number" name="release_year"></label>
      <label>Deskripsi <textarea name="description" rows="5"></textarea></label>

      <label>Cover Image <span style="opacity:.7">(jpg/png/webp, ≤ 2MB, drag & drop)</span></label>
      <div class="dz" id="dz-cover">
        <div>Tarik & letakkan gambar di sini atau klik untuk memilih file</div>
        <div class="err" id="dz-err"></div>
        <div class="thumb" id="dz-thumb"><img id="dz-img" alt="preview"></div>
        <input class="hidden-file" type="file" id="cover" name="cover" accept=".jpg,.jpeg,.png,.webp">
      </div>

      <button class="btn">Simpan</button>
    </form>
  </div>
<?php } ?>

<?php
$rows = db_all($mysqli, "SELECT id,title,genre,platform FROM games ORDER BY id DESC");
?>
<div class="card">
  <h2>Data</h2>
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:64px">No</th>
        <th>Judul</th>
        <th>Genre</th>
        <th>Platform</th>
        <th style="width:180px;text-align:right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1;
      foreach ($rows as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['genre']) ?></td>
          <td><?= e($r['platform']) ?></td>
          <td class="act">
            <div class="actions">
              <a class="chip-btn chip-edit" href="games.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
              <a class="chip-btn chip-del" href="games.php?action=delete&id=<?= (int) $r['id'] ?>"
                onclick="return confirm('Hapus &quot;<?= e($r['title']) ?>&quot;?')">Hapus</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  (function () {
    const dz = document.getElementById('dz-cover'); if (!dz) return;
    const input = document.getElementById('cover');
    const err = document.getElementById('dz-err');
    const thumb = document.getElementById('dz-thumb');
    const img = document.getElementById('dz-img');
    const max = 2 * 1024 * 1024; const ok = ['image/jpeg', 'image/png', 'image/webp'];
    const setErr = m => { err.textContent = m || ''; err.style.display = m ? 'block' : 'none' };
    const prev = f => { const r = new FileReader(); r.onload = e => { img.src = e.target.result; thumb.style.display = 'block' }; r.readAsDataURL(f) };
    function handle(f) {
      setErr(''); if (!f) return;
      if (!ok.includes(f.type)) { setErr('Format harus jpg/png/webp.'); input.value = ''; return; }
      if (f.size > max) { setErr('Ukuran > 2MB.'); input.value = ''; return; }
      prev(f);
    }
    dz.addEventListener('click', () => input.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('dragover') });
    dz.addEventListener('dragleave', () => dz.classList.remove('dragover'));
    dz.addEventListener('drop', e => { e.preventDefault(); dz.classList.remove('dragover'); if (e.dataTransfer.files && e.dataTransfer.files[0]) { input.files = e.dataTransfer.files; handle(input.files[0]); } });
    input.addEventListener('change', () => handle(input.files[0]));
  })();
</script>

<?php require_once __DIR__ . '/_footer.php';
