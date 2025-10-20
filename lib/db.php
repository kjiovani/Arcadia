<?php
// lib/db.php — tiny DB helpers (prepared statements only)
function db_all($mysqli, $sql, $params = [], $types = '') {
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) throw new Exception($mysqli->error);
  if ($params) $stmt->bind_param($types ?: str_repeat('s', count($params)), ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
  $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  $stmt->close();
  return $rows;
}
function db_one($mysqli, $sql, $params = [], $types = '') {
  $rows = db_all($mysqli,$sql,$params,$types);
  return $rows[0] ?? null;
}
function db_exec($mysqli, $sql, $params = [], $types = '') {
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) throw new Exception($mysqli->error);
  if ($params) $stmt->bind_param($types ?: str_repeat('s', count($params)), ...$params);
  $ok = $stmt->execute();
  $insert_id = $stmt->insert_id;
  $stmt->close();
  return $insert_id ?: $ok;
}
?>
