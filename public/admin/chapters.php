<?php
/* ========================================================================
   Admin • Chapters (YouTube + Gambar opsional, no urut 1..n)
   ======================================================================== */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';      // <-- untuk flash(), redirect(), e()
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
  session_start();
}

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';

/* ✅ Utamakan POST agar submit Edit tidak mentok di action=edit (GET) */
$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

/* ✅ Verifikasi CSRF hanya untuk POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

/* ---------- util: cek kolom ada ---------- */
function table_has_col(string $table, string $col): bool
{
  global $mysqli;
  $row = db_one(
    $mysqli,
    "SELECT COUNT(*) n FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?",
    [$table, $col],
    'ss'
  );
  return (int) ($row['n'] ?? 0) > 0;
}
$HAS_YT = table_has_col('chapters', 'youtube_url');
$HAS_IMG = table_has_col('chapters', 'image_url');

/* ---------- util: ambil id YouTube (untuk preview) ---------- */
function yt_id_from_url(?string $u): string
{
  $u = trim((string) $u);
  if ($u === '')
    return '';
  if (preg_match('~(?:v=|youtu\.be/)([A-Za-z0-9_-]{6,})~', $u, $m))
    return $m[1];
  return '';
}

/* ---------- upload gambar (opsional) ---------- */
function upload_chapter_image(string $seed): ?string
{
  if (empty($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE)
    return null;
  if ($_FILES['image']['error'] !== UPLOAD_ERR_OK)
    throw new Exception('Upload gambar gagal.');

  $tmp = $_FILES['image']['tmp_name'];
  $size = (int) $_FILES['image']['size'];
  if ($size > 3 * 1024 * 1024)
    throw new Exception('Gambar > 3MB.');

  $fi = new finfo(FILEINFO_MIME_TYPE);
  $mime = $fi->file($tmp);
  $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  if (!isset($map[$mime]))
    throw new Exception('Format harus JPG/PNG/WEBP.');

  // === simpan di /public/uploads/chapters (webroot), auto-mkdir ===
  if (!defined('UPLOADS_PATH'))
    define('UPLOADS_PATH', realpath(__DIR__ . '/../../public') . '/uploads');
  $uploadRoot = UPLOADS_PATH . '/chapters';
  if (!is_dir($uploadRoot))
    @mkdir($uploadRoot, 0775, true);

  $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~i', '-', $seed ?: 'chapter'), '-'));
  $name = $slug . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $map[$mime];

  if (!move_uploaded_file($tmp, $uploadRoot . '/' . $name)) {
    throw new Exception('Gagal menyimpan gambar.');
  }

  // URL publik
  if (!function_exists('asset_url')) {
    function asset_url(string $p = '')
    {
      return '/arcadia/public/' . ltrim($p, '/');
    } // fallback bila belum didefinisikan
  }
  return asset_url('uploads/chapters/' . $name);
}

/* ---------- referensi walkthrough ---------- */
$walks = db_all($mysqli, "SELECT id,title FROM walkthroughs ORDER BY id DESC");

/* ========================= ACTIONS (tanpa output) ========================= */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $walk_id = positive_int($_POST['walk_id'] ?? 0, 'Walkthrough');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $order_number = (int) ($_POST['order_number'] ?? 1);
    $content = str_trim($_POST['content'] ?? '');
    $youtube_url = $HAS_YT ? str_trim($_POST['youtube_url'] ?? '') : null;

    // upload dulu (jika ada)
    $imgUrl = $HAS_IMG ? upload_chapter_image($title) : null;

    if ($HAS_YT && $HAS_IMG) {
      db_exec(
        $mysqli,
        "INSERT INTO chapters(walk_id,title,content,order_number,youtube_url,image_url)
         VALUES(?,?,?,?,?,?)",
        [$walk_id, $title, $content, $order_number, $youtube_url, $imgUrl ?: ''],
        'ississ'
      );
    } elseif ($HAS_YT) {
      db_exec(
        $mysqli,
        "INSERT INTO chapters(walk_id,title,content,order_number,youtube_url)
         VALUES(?,?,?,?,?)",
        [$walk_id, $title, $content, $order_number, $youtube_url],
        'issis'
      );
    } elseif ($HAS_IMG) {
      db_exec(
        $mysqli,
        "INSERT INTO chapters(walk_id,title,content,order_number,image_url)
         VALUES(?,?,?,?,?)",
        [$walk_id, $title, $content, $order_number, $imgUrl ?: ''],
        'issis'
      );
    } else {
      db_exec(
        $mysqli,
        "INSERT INTO chapters(walk_id,title,content,order_number)
         VALUES(?,?,?,?)",
        [$walk_id, $title, $content, $order_number],
        'issi'
      );
    }

    flash('ok', 'Chapter dibuat.');
    redirect('chapters.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('chapters.php');
  }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $walk_id = positive_int($_POST['walk_id'] ?? 0, 'Walkthrough');
    $title = required(str_trim($_POST['title'] ?? ''), 'Judul');
    $order_number = (int) ($_POST['order_number'] ?? 1);
    $content = str_trim($_POST['content'] ?? '');
    $youtube_url = $HAS_YT ? str_trim($_POST['youtube_url'] ?? '') : null;

    // cek apakah ada gambar baru
    $imgUrl = $HAS_IMG ? upload_chapter_image($title) : null;

    if ($HAS_YT) {
      if ($imgUrl !== null && $HAS_IMG) {
        db_exec(
          $mysqli,
          "UPDATE chapters SET walk_id=?, title=?, content=?, order_number=?, youtube_url=?, image_url=? WHERE id=?",
          [$walk_id, $title, $content, $order_number, $youtube_url, $imgUrl, $id],
          'ississi'
        );
      } else {
        db_exec(
          $mysqli,
          "UPDATE chapters SET walk_id=?, title=?, content=?, order_number=?, youtube_url=? WHERE id=?",
          [$walk_id, $title, $content, $order_number, $youtube_url, $id],
          'issisi'
        );
      }
    } else {
      if ($imgUrl !== null && $HAS_IMG) {
        db_exec(
          $mysqli,
          "UPDATE chapters SET walk_id=?, title=?, content=?, order_number=?, image_url=? WHERE id=?",
          [$walk_id, $title, $content, $order_number, $imgUrl, $id],
          'issisi'
        );
      } else {
        db_exec(
          $mysqli,
          "UPDATE chapters SET walk_id=?, title=?, content=?, order_number=? WHERE id=?",
          [$walk_id, $title, $content, $order_number, $id],
          'issii'
        );
      }
    }

    flash('ok', 'Chapter diperbarui.');
    redirect('chapters.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('chapters.php');
  }
}

if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM chapters WHERE id=?", [$id], 'i');
  flash('ok', 'Chapter dihapus.');
  redirect('chapters.php');
}

/* ========================= MULAI RENDER UI ========================= */
require_once __DIR__ . '/_header.php';
?>
<style>
  /* ===== Table v2: clean + aesthetic ===== */
  .tbl {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
    table-layout: fixed;
  }

  .tbl thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    padding: 14px 16px;
    text-align: left;
    letter-spacing: .2px;
    font-weight: 800;
    background: rgba(17, 17, 24, .7);
    backdrop-filter: saturate(160%) blur(6px);
    border-bottom: 1px solid rgba(255, 255, 255, .08);
  }

  .tbl colgroup col.c-no {
    width: 72px;
  }

  .tbl colgroup col.c-walk {
    width: 26%;
  }

  .tbl colgroup col.c-title {
    width: auto;
  }

  .tbl colgroup col.c-prev {
    width: 128px;
  }

  .tbl colgroup col.c-order {
    width: 82px;
  }

  .tbl colgroup col.c-actions {
    width: 190px;
  }

  .tbl tbody tr {
    transition: .16s transform ease, .16s box-shadow ease
  }

  .tbl tbody tr:hover {
    transform: translateY(-2px)
  }

  .tbl td {
    padding: 14px 16px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .01));
    border: 1px solid rgba(255, 255, 255, .08);
  }

  .tbl tbody td:first-child {
    border-radius: 14px 0 0 14px
  }

  .tbl tbody td:last-child {
    border-radius: 0 14px 14px 0
  }

  .cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
  }

  .text-clip {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .thumb {
    width: 96px;
    height: 60px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .12);
    background: #0f0f16;
    position: relative;
  }

  .thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block
  }

  .thumb .badge-yt {
    position: absolute;
    right: 6px;
    bottom: 6px;
    padding: .15rem .45rem;
    font-size: .7rem;
    font-weight: 800;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .18);
    background: rgba(239, 68, 68, .18);
  }

  .badge {
    display: inline-block;
    min-width: 32px;
    text-align: center;
    padding: .28rem .6rem;
    border-radius: 999px;
    font-weight: 800;
    border: 1px solid rgba(255, 255, 255, .15);
    background: rgba(255, 255, 255, .06);
  }

  .actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
  }

  .chip-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    font-weight: 800;
    border: 1px solid rgba(255, 255, 255, .15);
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
    color: #eee;
    text-decoration: none;
    transition: .16s transform ease, .16s border-color ease, .16s box-shadow ease;
  }

  .chip-btn:hover {
    transform: translateY(-1px);
    border-color: var(--primary);
    box-shadow: 0 8px 22px rgba(167, 139, 250, .35)
  }

  .chip-del {
    border-color: rgba(239, 68, 68, .45);
    background: rgba(239, 68, 68, .12)
  }

  .chip-del:hover {
    box-shadow: 0 8px 22px rgba(239, 68, 68, .35)
  }

  /* drag & drop area (form atas) */
  .dz {
    position: relative;
    border: 1px dashed rgba(167, 139, 250, .55);
    border-radius: 14px;
    padding: 12px;
    background: linear-gradient(180deg, rgba(255, 255, 255, .03), rgba(255, 255, 255, .015));
    cursor: pointer;
    text-align: center
  }

  .dz.drag {
    border-color: #a78bfa;
    box-shadow: 0 0 0 3px rgba(167, 139, 250, .25) inset
  }

  .dz .thumb-preview {
    display: none;
    margin-top: 10px;
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, .1)
  }

  .dz img {
    display: block;
    max-width: 100%
  }

  .hidden {
    display: none
  }

  .tip {
    border-left: 3px solid var(--primary);
    padding: .6rem .8rem;
    margin: .2rem 0;
    background: rgba(167, 139, 250, .08);
    border-radius: 10px;
    opacity: .95
  }

  @media (max-width: 880px) {
    .tbl colgroup col.c-actions {
      width: 160px
    }

    .thumb {
      width: 80px;
      height: 50px
    }
  }
</style>

<div class="card">
  <h1>Chapters</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
</div>

<?php
/* ========================= FORM ========================= */
if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $c = db_one($mysqli, "SELECT * FROM chapters WHERE id=?", [$id], 'i');
  if (!$c) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  ?>
  <div class="card">
    <h2>Edit</h2>
    <!-- ✅ submit ke file yang sama, aksi via hidden -->
    <form method="post" action="chapters.php" enctype="multipart/form-data" class="grid">
      <?php csrf_field(); ?>
      <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
      <input type="hidden" name="action" value="update">

      <label>Walkthrough
        <select name="walk_id">
          <?php foreach ($walks as $w): ?>
            <option value="<?= (int) $w['id'] ?>" <?= $w['id'] == $c['walk_id'] ? 'selected' : '' ?>><?= e($w['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Judul
        <input class="input" name="title" value="<?= e($c['title']) ?>">
      </label>

      <label>Nomor Urut
        <input class="input" type="number" name="order_number" value="<?= (int) $c['order_number'] ?>">
      </label>

      <label>Konten
        <textarea name="content" rows="8"><?= e($c['content']) ?></textarea>
      </label>
      <div class="tip"><strong>Tip:</strong> Tulis langkah singkat & bernomor supaya gamer cepat paham.</div>

      <?php if ($HAS_YT): ?>
        <label>YouTube URL (opsional)
          <input class="input" name="youtube_url" placeholder="https://www.youtube.com/watch?v=…"
            value="<?= e($c['youtube_url'] ?? '') ?>">
        </label>
      <?php endif; ?>

      <?php if ($HAS_IMG): ?>
        <label>Gambar ilustrasi (opsional) <span style="opacity:.7">(JPG/PNG/WEBP ≤ 3MB)</span></label>
        <?php if (!empty($c['image_url'])): ?>
          <div class="small">Gambar saat ini: <a target="_blank" href="<?= e($c['image_url']) ?>">lihat</a></div>
        <?php endif; ?>
        <div class="dz" id="dz-img">
          <div><strong>Tarik & letakkan</strong> gambar di sini atau klik untuk pilih</div>
          <div class="thumb-preview" id="thumb"><img id="thumbImg" alt="preview"></div>
          <input class="hidden" type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      <?php endif; ?>

      <button class="btn">Simpan</button>
      <a class="btn gray" href="chapters.php">Batal</a>
    </form>
  </div>
  <?php
} else {
  ?>
  <div class="card">
    <h2>Tambah</h2>
    <!-- ✅ submit ke file yang sama; aksi via hidden -->
    <form method="post" action="chapters.php" enctype="multipart/form-data" class="grid">
      <?php csrf_field(); ?>
      <input type="hidden" name="action" value="create">

      <label>Walkthrough
        <select name="walk_id">
          <?php foreach ($walks as $w): ?>
            <option value="<?= (int) $w['id'] ?>"><?= e($w['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Judul
        <input class="input" name="title">
      </label>

      <label>Nomor Urut
        <input class="input" type="number" name="order_number" value="1">
      </label>

      <label>Konten
        <textarea name="content" rows="8" placeholder="Tulis langkah per langkah…"></textarea>
      </label>
      <div class="tip"><strong>Tip:</strong> Pecah jadi 3–6 langkah jelas; sebutkan arah, item, musuh, & tujuan.</div>

      <?php if ($HAS_YT): ?>
        <label>YouTube URL (opsional)
          <input class="input" name="youtube_url" placeholder="https://www.youtube.com/watch?v=…">
        </label>
      <?php endif; ?>

      <?php if ($HAS_IMG): ?>
        <label>Gambar ilustrasi (opsional) <span style="opacity:.7">(JPG/PNG/WEBP ≤ 3MB)</span></label>
        <div class="dz" id="dz-img">
          <div><strong>Tarik & letakkan</strong> gambar di sini atau klik untuk pilih</div>
          <div class="thumb-preview" id="thumb"><img id="thumbImg" alt="preview"></div>
          <input class="hidden" type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
        </div>
      <?php endif; ?>

      <button class="btn">Simpan</button>
    </form>
  </div>
<?php } ?>

<?php
/* ====== Tabel data (No urut 1..n) + thumbnail ====== */
$rows = db_all($mysqli, "
  SELECT c.id,c.title,c.order_number,c.youtube_url,c.image_url,w.title AS walk
  FROM chapters c
  JOIN walkthroughs w ON w.id=c.walk_id
  ORDER BY c.id DESC
");
?>
<div class="card">
  <h2>Data</h2>
  <table class="tbl">
    <colgroup>
      <col class="c-no">
      <col class="c-walk">
      <col class="c-title">
      <col class="c-prev">
      <col class="c-order">
      <col class="c-actions">
    </colgroup>
    <thead>
      <tr>
        <th>No</th>
        <th>Walkthrough</th>
        <th>Judul</th>
        <th>Preview</th>
        <th>#</th>
        <th style="text-align:right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="6" style="text-align:center;opacity:.7;padding:24px">Belum ada chapter.</td>
        </tr>
      <?php endif; ?>

      <?php $no = 1;
      foreach ($rows as $r):
        $yt = yt_id_from_url($r['youtube_url'] ?? ''); ?>
        <tr>
          <td><span class="badge"><?= $no++ ?></span></td>
          <td>
            <div class="cell"><span class="text-clip" title="<?= e($r['walk']) ?>"><?= e($r['walk']) ?></span></div>
          </td>
          <td>
            <div class="cell"><span class="text-clip" title="<?= e($r['title']) ?>"><?= e($r['title']) ?></span></div>
          </td>
          <td>
            <div class="thumb" title="<?= $yt ? 'YouTube' : (empty($r['image_url']) ? 'Tidak ada preview' : 'Gambar') ?>">
              <?php if (!empty($r['image_url'])): ?>
                <img src="<?= e($r['image_url']) ?>" alt="">
              <?php elseif ($yt): ?>
                <img src="https://img.youtube.com/vi/<?= e($yt) ?>/hqdefault.jpg" alt="">
                <span class="badge-yt">YT</span>
              <?php else: ?>
                <img
                  src="data:image/svg+xml;charset=utf-8,<?= rawurlencode('<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'192\' height=\'120\'><rect width=\'100%\' height=\'100%\' fill=\'#0f0f16\'/><text x=\'50%\' y=\'54%\' fill=\'#888\' font-size=\'12\' font-family=\'Arial\' text-anchor=\'middle\'>no preview</text></svg>') ?>">
              <?php endif; ?>
            </div>
          </td>
          <td><span class="badge"><?= (int) $r['order_number'] ?></span></td>
          <td>
            <div class="actions">
              <a class="chip-btn" href="chapters.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
              <a class="chip-btn chip-del" href="chapters.php?action=delete&id=<?= (int) $r['id'] ?>"
                onclick="return confirm('Hapus &quot;<?= e($r['title']) ?>&quot;?')">Hapus</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
  /* Drag & Drop image (opsional) untuk form */
  (function () {
    const dz = document.getElementById('dz-img');
    if (!dz) return;
    const input = document.getElementById('image');
    const thumb = document.getElementById('thumb');
    const img = document.getElementById('thumbImg');

    function preview(file) {
      const ok = ['image/jpeg', 'image/png', 'image/webp'];
      if (!ok.includes(file.type)) { alert('Format harus JPG/PNG/WEBP'); return; }
      if (file.size > 3 * 1024 * 1024) { alert('Ukuran > 3MB'); return; }
      const r = new FileReader();
      r.onload = e => { img.src = e.target.result; thumb.style.display = 'block'; };
      r.readAsDataURL(file);
    }
    dz.addEventListener('click', () => input.click());
    dz.addEventListener('dragover', e => { e.preventDefault(); dz.classList.add('drag'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('drag'));
    dz.addEventListener('drop', e => {
      e.preventDefault(); dz.classList.remove('drag');
      if (e.dataTransfer.files && e.dataTransfer.files[0]) {
        input.files = e.dataTransfer.files;
        preview(input.files[0]);
      }
    });
    input.addEventListener('change', () => input.files[0] && preview(input.files[0]));
  })();
</script>

<?php require_once __DIR__ . '/_footer.php';
