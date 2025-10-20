<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../lib/helpers.php';
require_once __DIR__ . '/../../lib/auth.php';
require_admin();
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Arcadia • Admin</title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css" />
</head>

<body>
  <header class="nav">
    <div class="nav-inner">
      <div class="brand">Arcadia Admin</div>
      <nav>
        <a href="/arcadia/public/admin/">Dashboard</a> ·
        <a href="/arcadia/public/admin/games.php">Games</a> ·
        <a href="/arcadia/public/admin/walkthroughs.php">Walkthroughs</a> ·
        <a href="/arcadia/public/admin/chapters.php">Chapters</a> ·
        <a href="/arcadia/public/admin/tags.php">Tags</a> ·
        <a href="/arcadia/public/admin/mediafiles.php">Media</a> ·
        <a href="/arcadia/public/logout.php">Logout</a>
      </nav>

    </div>
  </header>
  <div class="container">