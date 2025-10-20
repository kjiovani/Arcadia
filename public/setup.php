<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/helpers.php';

$exists = db_one($mysqli, "SELECT id FROM users LIMIT 1");
if ($exists) {
  echo "User sudah ada. <a href='/arcadia/public/login.php'>Login</a>";
  exit;
}
$name = "Admin";
$email = "admin@arcadia.test";
$pass = "admin123";
$hash = password_hash($pass, PASSWORD_BCRYPT);
db_exec($mysqli, "INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'ADMIN')", [$name, $email, $hash]);
echo "Admin dibuat. Email: $email, Password: $pass. <a href='/arcadia/public/login.php'>Login sekarang</a>";
?>