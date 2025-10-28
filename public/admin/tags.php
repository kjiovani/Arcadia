<?php
/* ========================================================================
   Admin • Tags (No urut 1..n, clean & aesthetic)
   ======================================================================== */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/validation.php';


$action = $_GET['action'] ?? 'list';
if ($_SERVER['REQUEST_METHOD'] === 'POST')
  csrf_verify();

/* ========================= ACTIONS (tanpa output HTML) ========================= */
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama Tag');

    // coba insert; jika tabel punya UNIQUE(name) maka IGNORE akan mencegah duplikat crash
    db_exec($mysqli, "INSERT IGNORE INTO tags(name) VALUES(?)", [$name], 's');

    // kalau tidak unique dan sudah ada, kita cek manual
    if (mysqli_affected_rows($mysqli) === 0) {
      $exists = db_one($mysqli, "SELECT id FROM tags WHERE name=?", [$name], 's');
      if ($exists)
        throw new Exception('Tag sudah ada.');
    }

    flash('ok', 'Tag ditambahkan.');
    redirect('tags.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('tags.php');
  }
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $id = positive_int($_POST['id'] ?? 0, 'ID');
    $name = required(str_trim($_POST['name'] ?? ''), 'Nama Tag');

    // cegah nama kembar
    $dupe = db_one($mysqli, "SELECT id FROM tags WHERE name=? AND id<>?", [$name, $id], 'si');
    if ($dupe)
      throw new Exception('Nama tag sudah dipakai.');

    db_exec($mysqli, "UPDATE tags SET name=? WHERE id=?", [$name, $id], 'si');
    flash('ok', 'Tag diperbarui.');
    redirect('tags.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('tags.php');
  }
}

if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  // bersihkan relasi di pivot jika ada
  if (table_exists($mysqli, 'walktag')) {
    db_exec($mysqli, "DELETE FROM walktag WHERE tag_id=?", [$id], 'i');
  }
  db_exec($mysqli, "DELETE FROM tags WHERE id=?", [$id], 'i');
  flash('ok', 'Tag dihapus.');
  redirect('tags.php');
}

/* ---------- util kecil ---------- */
function table_exists(mysqli $mysqli, string $table): bool
{
  $row = db_one(
    $mysqli,
    "SELECT COUNT(*) n FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME=?",
    [$table],
    's'
  );
  return (int) ($row['n'] ?? 0) > 0;
}

/* ========================= MULAI RENDER UI ========================= */
require_once __DIR__ . '/_header.php';

/* data untuk tabel + cloud chips */
$rows = db_all($mysqli, "
  SELECT t.id, t.name, COALESCE(cnt.c,0) AS used_count
  FROM tags t
  LEFT JOIN (
    SELECT tag_id, COUNT(*) c FROM walktag GROUP BY tag_id
  ) cnt ON cnt.tag_id = t.id
  ORDER BY t.id DESC
");
$all_for_cloud = $rows; // pakai ulang
?>
<style>
  /* ===== Aesthetic styling ===== */
  .tag-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 10px
  }

  .tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 800;
    border: 1px solid rgba(255, 255, 255, .14);
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
  }

  .tag-chip .dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    background: #a78bfa;
    box-shadow: 0 0 0 3px rgba(167, 139, 250, .18)
  }

  .card h2 .hint {
    font-size: .9rem;
    opacity: .7;
    font-weight: 600;
    margin-left: .5rem
  }

  .tbl {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 12px;
    table-layout: fixed;
  }

  .tbl colgroup col.c-no {
    width: 72px
  }

  .tbl colgroup col.c-name {
    width: auto
  }

  .tbl colgroup col.c-used {
    width: 110px
  }

  .tbl colgroup col.c-act {
    width: 190px
  }

  .tbl thead th {
    position: sticky;
    top: 0;
    z-index: 5;
    padding: 14px 16px;
    text-align: left;
    font-weight: 800;
    background: rgba(17, 17, 24, .7);
    backdrop-filter: saturate(160%) blur(6px);
    border-bottom: 1px solid rgba(255, 255, 255, .08)
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

  .badge {
    display: inline-block;
    min-width: 28px;
    text-align: center;
    padding: .26rem .6rem;
    border-radius: 999px;
    border: 1px solid rgba(255, 255, 255, .15);
    background: rgba(255, 255, 255, .06);
    font-weight: 800
  }

  .actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end
  }

  .chip-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border-radius: 999px;
    font-weight: 800;
    color: #eee;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, .15);
    background: linear-gradient(180deg, rgba(255, 255, 255, .06), rgba(255, 255, 255, .03));
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

  .tip {
    border-left: 3px solid var(--primary);
    padding: .6rem .8rem;
    margin: .2rem 0;
    background: rgba(167, 139, 250, .08);
    border-radius: 10px;
    opacity: .95
  }
</style>

<div class="card">
  <h1>Tags</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
</div>

<!-- CLOUD PREVIEW -->
<?php if ($all_for_cloud): ?>
  <div class="card">
    <h2>Semua Tag <span class="hint">— klik Edit di tabel untuk ubah</span></h2>
    <div class="tag-cloud">
      <?php foreach ($all_for_cloud as $t): ?>
        <span class="tag-chip"><span class="dot"></span><?= e($t['name']) ?><?php if ($t['used_count']): ?> ·
            <?= (int) $t['used_count'] ?>    <?php endif; ?></span>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>

<?php
/* ========================= FORM ========================= */
if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $t = db_one($mysqli, "SELECT * FROM tags WHERE id=?", [$id], 'i');
  if (!$t) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  ?>
  <div class="card">
    <h2>Edit Tag</h2>
    <form method="post" class="grid">
      <?php csrf_field(); ?>
      <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <input type="hidden" name="action" value="update">
      <label>Nama Tag
        <input class="input" name="name" value="<?= e($t['name']) ?>" placeholder="contoh: Boss, Weapon, Puzzle">
      </label>
      <div class="tip">Gunakan nama singkat & mudah dicari (contoh: <em>Boss</em>, <em>Build</em>, <em>Early Game</em>).
      </div>
      <button class="btn">Simpan</button>
      <a class="btn gray" href="tags.php">Batal</a>
    </form>
  </div>
  <?php
} else {
  ?>
  <div class="card">
    <h2>Tambah Tag</h2>
    <form method="post" action="tags.php?action=create" class="grid">
      <?php csrf_field(); ?>
      <label>Nama Tag
        <input class="input" name="name" placeholder="contoh: Boss, Weapon, Puzzle">
      </label>
      <button class="btn">Simpan</button>
    </form>
  </div>
<?php } ?>

<!-- DATA TABLE -->
<div class="card">
  <h2>Data</h2>
  <table class="tbl">
    <colgroup>
      <col class="c-no">
      <col class="c-name">
      <col class="c-used">
      <col class="c-act">
    </colgroup>
    <thead>
      <tr>
        <th>No</th>
        <th>Nama</th>
        <th>Dipakai</th>
        <th style="text-align:right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="4" style="text-align:center;opacity:.7;padding:24px">Belum ada tag.</td>
        </tr>
      <?php endif; ?>

      <?php $no = 1;
      foreach ($rows as $r): ?>
        <tr>
          <td><span class="badge"><?= $no++ ?></span></td>
          <td><?= e($r['name']) ?></td>
          <td><span class="badge"><?= (int) $r['used_count'] ?></span></td>
          <td>
            <div class="actions">
              <a class="chip-btn" href="tags.php?action=edit&id=<?= (int) $r['id'] ?>">Edit</a>
              <a class="chip-btn chip-del" href="tags.php?action=delete&id=<?= (int) $r['id'] ?>"
                onclick="return confirm('Hapus tag &quot;<?= e($r['name']) ?>&quot;?')">Hapus</a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/_footer.php';
