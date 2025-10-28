<?php
// lib/settings.php
require_once __DIR__ . '/db.php';

// get: ambil nilai setting (fallback default)
function setting_get(mysqli $mysqli, string $key, string $default = ''): string {
  $row = db_one($mysqli, "SELECT `value` FROM settings WHERE `key`=?", [$key], 's');
  return isset($row['value']) ? (string)$row['value'] : $default;
}

// set: simpan nilai setting
function setting_set(mysqli $mysqli, string $key, string $value): bool {
  return db_exec($mysqli,
    "INSERT INTO settings(`key`,`value`) VALUES(?,?)
     ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)",
    [$key, $value], 'ss'
  );
}
