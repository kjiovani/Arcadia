<?php
// lib/csrf.php
function csrf_token(){
  if(empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32));
  return $_SESSION['_csrf'];
}
function csrf_field(){
  $t = csrf_token();
  echo '<input type="hidden" name="_csrf" value="'.htmlspecialchars($t,ENT_QUOTES,'UTF-8').'" />';
}
function csrf_verify(){
  if($_SERVER['REQUEST_METHOD']==='POST'){
    $ok = !empty($_POST['_csrf']) && hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf']);
    if(!$ok){ http_response_code(419); echo "CSRF token invalid."; exit; }
  }
}
?>
