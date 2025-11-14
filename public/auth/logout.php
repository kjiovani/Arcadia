<?php
// /arcadia/public/auth/logout.php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/*
  PENTING:
  - Tulis flash() SELAGI session masih hidup
  - Jangan session_destroy(), cukup hapus data user saja
*/
flash('ok', 'Kamu sudah berhasil logout dari Arcadia. Sampai jumpa lagi 👋');

// bersihkan data user dari session, tapi BIARKAN session untuk flash
unset($_SESSION['user'], $_SESSION['user_id'], $_SESSION['user_role']);

// opsional: kalau mau, bisa regenerate ID supaya aman
session_regenerate_id(true);

// redirect ke beranda
header('Location: /arcadia/public/index.php');
exit;
