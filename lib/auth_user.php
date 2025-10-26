<?php
// /arcadia/lib/auth_user.php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function current_user(): ?array
{
  return $_SESSION['user'] ?? null;
}
function is_user_logged_in(): bool
{
  return !empty($_SESSION['user']);
}
function require_user_login(string $next = null): void
{
  if (is_user_logged_in())
    return;
  $loginUrl = '/arcadia/public/auth/login.php';
  if ($next)
    $loginUrl .= '?next=' . urlencode($next);
  header('Location: ' . $loginUrl);
  exit;
}
