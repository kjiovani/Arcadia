<?php
// /arcadia/public/owner/cover_focus.php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../lib/db.php';
require_once __DIR__.'/../../lib/auth.php'; // supaya bisa pakai require_owner
if (session_status()===PHP_SESSION_NONE) session_start();

function is_owner(){
  $me = $_SESSION['user'] ?? null;
  return $me && strtoupper($me['role']??'')==='OWNER';
}
if (!is_owner()){ http_response_code(403); exit('Forbidden'); }

$table = $_POST['table'] ?? '';
$id    = (int)($_POST['id'] ?? 0);
$fx    = max(0,min(100,(int)($_POST['fx'] ?? 50)));
$fy    = max(0,min(100,(int)($_POST['fy'] ?? 50)));

$allowed = ['games','walkthroughs'];
if (!in_array($table,$allowed,true) || $id<=0) { http_response_code(400); exit('Bad request'); }

// pastikan kolom ada; bila belum ada, buat cepat (opsional)
$mysqli->query("ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS cover_focus_x INT DEFAULT 50");
$mysqli->query("ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS cover_focus_y INT DEFAULT 50");

$stmt = $mysqli->prepare("UPDATE `$table` SET cover_focus_x=?, cover_focus_y=? WHERE id=?");
$stmt->bind_param('iii',$fx,$fy,$id);
$stmt->execute();
echo $stmt->affected_rows>0 ? "Fokus tersimpan ($fx%, $fy%)." : "Tidak ada perubahan.";
