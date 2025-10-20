<?php
// lib/auth.php
function require_admin(){
  if(empty($_SESSION['user']) || ($_SESSION['user']['role']!=='ADMIN' && $_SESSION['user']['role']!=='EDITOR')){
    header('Location: /arcadia/public/login.php?next='.urlencode($_SERVER['REQUEST_URI']));
    exit;
  }
}
?>
