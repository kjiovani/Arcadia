<?php
// /arcadia/lib/settings.php
require_once __DIR__ . '/db.php';

if (!function_exists('e')) {
  function e($s)
  {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
  }
}

/* Util pengecekan tabel/kolom yang aman (tanpa prepared ke kolom) */
if (!function_exists('arc_table_exists')) {
  function arc_table_exists(mysqli $db, string $table): bool
  {
    $t = $db->real_escape_string($table);
    $q = $db->query("SHOW TABLES LIKE '{$t}'");
    return $q && $q->num_rows > 0;
  }
}
if (!function_exists('arc_table_has_col')) {
  function arc_table_has_col(mysqli $db, string $table, string $col): bool
  {
    $t = $db->real_escape_string($table);
    $c = $db->real_escape_string($col);
    $sql = "SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '{$t}'
              AND COLUMN_NAME = '{$c}'
            LIMIT 1";
    $r = $db->query($sql);
    return $r && $r->num_rows > 0;
  }
}

/* Pastikan app_settings ada */
if (!function_exists('arc_settings_boot')) {
  function arc_settings_boot(mysqli $db): void
  {
    $db->query("CREATE TABLE IF NOT EXISTS app_settings (
      `key`   VARCHAR(64) PRIMARY KEY,
      `value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  }
}

/* GET dengan fallback multi skema */
if (!function_exists('setting_get')) {
  function setting_get(mysqli $db, string $key, $default = '')
  {
    arc_settings_boot($db);
    $def = (string) $default;

    // 1) app_settings
    $row = db_one($db, "SELECT `value` FROM app_settings WHERE `key`=? LIMIT 1", [$key], 's');
    if ($row && array_key_exists('value', $row))
      return (string) $row['value'];

    // 2) settings (opsional) — deteksi kolom
    if (arc_table_exists($db, 'settings')) {
      if (arc_table_has_col($db, 'settings', 'key')) {
        $row = db_one($db, "SELECT `value` FROM settings WHERE `key`=? LIMIT 1", [$key], 's');
        if ($row && isset($row['value']))
          return (string) $row['value'];
      } elseif (arc_table_has_col($db, 'settings', 'name')) {
        $row = db_one($db, "SELECT `value` FROM settings WHERE `name`=? LIMIT 1", [$key], 's');
        if ($row && isset($row['value']))
          return (string) $row['value'];
      }
    }
    return $def;
  }
}

/* SET dengan sinkronisasi aman */
if (!function_exists('setting_set')) {
  function setting_set(mysqli $db, string $key, string $value): void
  {
    arc_settings_boot($db);
    db_exec($db, "REPLACE INTO app_settings(`key`,`value`) VALUES(?,?)", [$key, $value], 'ss');

    if (arc_table_exists($db, 'settings')) {
      if (arc_table_has_col($db, 'settings', 'key')) {
        db_exec($db, "REPLACE INTO settings(`key`,`value`) VALUES(?,?)", [$key, $value], 'ss');
      } elseif (arc_table_has_col($db, 'settings', 'name')) {
        db_exec($db, "REPLACE INTO settings(`name`,`value`) VALUES(?,?)", [$key, $value], 'ss');
      }
    }
  }
}
