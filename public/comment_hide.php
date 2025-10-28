<?php
// /arcadia/public/comment_hide.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';
require_once __DIR__ . '/../lib/auth.php';

require_admin('/arcadia/public/');

$cid = (int) ($_GET['id'] ?? 0);
$gid = (int) ($_GET['game'] ?? 0);
if ($cid > 0) {
  db_exec($mysqli, "UPDATE comments SET status='HIDDEN' WHERE id=?", [$cid], "i");
  flash_set('ok', 'Komentar disembunyikan.');
}
header('Location: /arcadia/public/game.php?id=' . $gid . '#komentar');
exit;
