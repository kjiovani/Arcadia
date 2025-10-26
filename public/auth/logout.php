<?php
require_once __DIR__ . '/../../lib/auth_user.php';
$_SESSION['user'] = null;
unset($_SESSION['user']);
header('Location: /arcadia/public/index.php');
exit;
