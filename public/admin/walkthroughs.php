<?php
/* ========== BOOT: tanpa output HTML dulu ========== */
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';

require_admin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../lib/csrf.php';
require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../../lib/validation.php';

$action = $_GET['action'] ?? 'list';

// Verifikasi CSRF untuk semua POST (token sudah ada karena session aktif)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
}

/* ========== DATA PENDUKUNG FORM ========== */
$games = db_all($mysqli, "SELECT id,title FROM games ORDER BY title ASC");
$allTags = db_all($mysqli, "SELECT id,name FROM tags ORDER BY name ASC");

/* ========== ACTIONS ========== */
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
    redirect('walkthroughs.php');
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

    $tags = $_POST['tags'] ?? [];
    db_exec($mysqli, "DELETE FROM walktag WHERE walk_id=?", [$id], 'i');
    foreach ($tags as $tag_id) {
      db_exec($mysqli, "INSERT IGNORE INTO walktag(walk_id, tag_id) VALUES(?,?)", [(int) $id, (int) $tag_id], 'ii');
    }

    flash('ok', 'Walkthrough diperbarui.');
    redirect('walkthroughs.php');
  } catch (Exception $e) {
    flash('err', $e->getMessage());
    redirect('walkthroughs.php');
  }
}

if ($action === 'delete') {
  $id = (int) ($_GET['id'] ?? 0);
  db_exec($mysqli, "DELETE FROM walkthroughs WHERE id=?", [$id], 'i');
  db_exec($mysqli, "DELETE FROM walktag WHERE walk_id=?", [$id], 'i');
  flash('ok', 'Walkthrough dihapus.');
  redirect('walkthroughs.php');
}

/* ========== MULAI RENDER UI ========== */
require_once __DIR__ . '/_header.php';
?>
<style>
  /* Tabel elevated & pill actions – konsisten dengan Games */
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
    padding: 12px 14px;
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
    font-size: .95rem;
    transition: .18s transform ease, .18s box-shadow ease, .18s border-color ease;
    white-space: nowrap;
  }

  .chip-btn:hover {
    transform: translateY(-1px);
    border-color: var(--primary);
    box-shadow: 0 8px 22px rgba(167, 139, 250, .35)
  }

  .chip-icon {
    width: 16px;
    height: 16px;
    display: inline-block;
    border-radius: 4px;
    background: rgba(255, 255, 255, .18)
  }

  .chip-edit {
    border-color: rgba(167, 139, 250, .45);
    background: rgba(167, 139, 250, .15)
  }

  .chip-del {
    border-color: rgba(239, 68, 68, .45);
    background: rgba(239, 68, 68, .12)
  }
</style>

<div class="card">
  <h1>Walkthroughs</h1>
  <?php if ($m = flash('ok')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="alert"><?= e($m) ?></div><?php endif; ?>
</div>

<?php
/* ========== FORM ========== */
if ($action === 'edit') {
  $id = (int) ($_GET['id'] ?? 0);
  $w = db_one($mysqli, "SELECT * FROM walkthroughs WHERE id=?", [$id], 'i');
  if (!$w) {
    echo '<div class="card">Data tidak ditemukan</div>';
    require __DIR__ . '/_footer.php';
    exit;
  }
  $selected = db_all($mysqli, "SELECT tag_id FROM walktag WHERE walk_id=?", [$id], 'i');
  $selectedIds = array_column($selected, 'tag_id');
  ?>
  <div class="card">
    <h2>Edit</h2>
    <form method="post" class="grid">
      <?php csrf_field(); ?>
      <input type="hidden" name="id" value="<?= (int) $w['id'] ?>">
      <input type="hidden" name="action" value="update">

      <label>Game
        <select name="game_id">
          <?php foreach ($games as $g): ?>
            <option value="<?= (int) $g['id'] ?>" <?= $g['id'] == $w['game_id'] ? 'selected' : '' ?>>
              <?= e($g['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Judul
        <input class="input" name="title" value="<?= e($w['title']) ?>">
      </label>

      <label>Ringkasan
        <textarea name="overview" rows="4"><?= e($w['overview']) ?></textarea>
      </label>

      <label>Kesulitan
        <select name="difficulty">
          <?php foreach (['Easy', 'Medium', 'Hard'] as $d): ?>
            <option <?= $d === $w['difficulty'] ? 'selected' : '' ?>><?= $d ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <fieldset>
        <legend class="small">Tags</legend>
        <?php foreach ($allTags as $t):
          $chk = in_array($t['id'], $selectedIds) ? 'checked' : ''; ?>
          <label style="display:inline-flex;gap:.4rem;align-items:center;margin:.2rem 1rem .2rem 0">
            <input type="checkbox" name="tags[]" value="<?= (int) $t['id'] ?>" <?= $chk ?>>
            <span><?= e($t['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <button class="btn">Simpan</button>
      <a class="btn gray" href="walkthroughs.php">Batal</a>
    </form>
  </div>
  <?php
} else { ?>
  <div class="card">
    <h2>Tambah</h2>
    <form method="post" action="walkthroughs.php?action=create" class="grid">
      <?php csrf_field(); ?>

      <label>Game
        <select name="game_id">
          <?php foreach ($games as $g): ?>
            <option value="<?= (int) $g['id'] ?>"><?= e($g['title']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Judul
        <input class="input" name="title">
      </label>

      <label>Ringkasan
        <textarea name="overview" rows="4"></textarea>
      </label>

      <label>Kesulitan
        <select name="difficulty">
          <option>Easy</option>
          <option selected>Medium</option>
          <option>Hard</option>
        </select>
      </label>

      <fieldset>
        <legend class="small">Tags</legend>
        <?php foreach ($allTags as $t): ?>
          <label style="display:inline-flex;gap:.4rem;align-items:center;margin:.2rem 1rem .2rem 0">
            <input type="checkbox" name="tags[]" value="<?= (int) $t['id'] ?>">
            <span><?= e($t['name']) ?></span>
          </label>
        <?php endforeach; ?>
      </fieldset>

      <button class="btn">Simpan</button>
    </form>
  </div>
<?php } ?>

<?php
/* ========== TABEL DATA (No urut, bukan ID) ========== */
$rows = db_all(
  $mysqli,
  "SELECT w.id, w.title, w.difficulty, g.title AS game_title
     FROM walkthroughs w
     JOIN games g ON g.id = w.game_id
   ORDER BY w.id DESC"
);
?>
<div class="card">
  <h2>Data</h2>
  <table class="tbl">
    <thead>
      <tr>
        <th style="width:64px">No</th>
        <th>Game</th>
        <th>Judul</th>
        <th style="width:140px">Kesulitan</th>
        <th style="width:180px;text-align:right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no = 1;
      foreach ($rows as $r): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td><?= e($r['game_title']) ?></td>
          <td><?= e($r['title']) ?></td>
          <td><?= e($r['difficulty']) ?></td>
          <td>
            <div class="actions">
              <a class="chip-btn chip-edit" href="walkthroughs.php?action=edit&id=<?= (int) $r['id'] ?>">
                <span class="chip-icon"></span> Edit
              </a>
              <a class="chip-btn chip-del" href="walkthroughs.php?action=delete&id=<?= (int) $r['id'] ?>"
                onclick="return confirm('Hapus &quot;<?= e($r['title']) ?>&quot;?')">
                <span class="chip-icon"></span> Hapus
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($rows)): ?>
        <tr>
          <td colspan="5" style="opacity:.8">Belum ada data.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>