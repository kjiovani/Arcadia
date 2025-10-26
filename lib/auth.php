<?php
// /arcadia/lib/auth.php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Anggap status login disimpan di $_SESSION['admin'] (admin panel-mu sudah pakai ini)
 * Sesuaikan jika nama variabelnya beda.
 */
function is_logged_in(): bool {
  return !empty($_SESSION['admin']);
}

/**
 * Paksa login. Jika belum login, arahkan ke halaman login dan sertakan "next" untuk kembali.
 */
function require_login(string $next = null): void {
  if (is_logged_in()) return;
  $loginUrl = '/arcadia/public/admin/login.php'; // SESUAIKAN jika path login beda
  if ($next) {
    $loginUrl .= '?next=' . urlencode($next);
  }
  header('Location: ' . $loginUrl);
  exit;
}
