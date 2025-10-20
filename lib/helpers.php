<?php
// lib/helpers.php
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
function redirect($path){
  header("Location: ".$path);
  exit;
}
function flash($key,$msg=null){
  if($msg!==null){ $_SESSION['_flash'][$key]=$msg; return; }
  if(!empty($_SESSION['_flash'][$key])){ $m=$_SESSION['_flash'][$key]; unset($_SESSION['_flash'][$key]); return $m; }
  return null;
}
function current_path(){ return strtok($_SERVER['REQUEST_URI'],'?'); }
?>
