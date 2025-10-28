<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();

function is_admin(): bool
{
  $u = $_SESSION['user'] ?? null;
  return $u && in_array($u['role'] ?? '', ['OWNER', 'ADMIN'], true);
}

function require_admin(string $next = null): void
{
  if (is_admin())
    return;
  $loginUrl = '/arcadia/public/auth/login.php';
  if ($next)
    $loginUrl .= '?next=' . urlencode($next);
  header('Location: ' . $loginUrl);
  exit;
}

function require_owner()
{
  if (session_status() === PHP_SESSION_NONE)
    session_start();
  $u = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
  if (!$u || !isset($u['role']) || strtoupper($u['role']) !== 'OWNER') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
  }
}
