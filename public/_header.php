
<?php require_once __DIR__.'/../config.php'; ?>
<?php require_once __DIR__.'/../lib/helpers.php'; ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Arcadia</title>
  <link rel="stylesheet" href="/arcadia/assets/styles.css"/>
</head>
<body>
<header class="nav v2">
  <div class="nav-inner">
    <a class="brand" href="/arcadia/public/">
      <span class="brand-mark">⌘</span> Arcadia
    </a>

    
<nav class="nav-menu">
  <a class="nav-link <?= strpos($path,'/public/index.php')!==false ? 'active':'' ?>" href="/arcadia/public/index.php">Beranda</a>
  <a class="nav-link <?= strpos($path,'/public/search.php')!==false ? 'active':'' ?>" href="/arcadia/public/search.php">Panduan</a>
  <a class="nav-link" href="/arcadia/public/index.php#genres">Genre</a>  <!-- NEW -->
  <a class="nav-link <?= (strpos($path,'/public/game.php')!==false || strpos($path,'/public/walkthrough.php')!==false) ? 'active':'' ?>" href="/arcadia/public/index.php#games">Game</a>
  <a class="nav-link" href="/arcadia/public/index.php#about">Tentang</a>
  <span class="nav-sep"></span>
  <a class="nav-cta" href="/arcadia/public/admin/">Admin</a>
</nav>


    <button class="nav-toggle" aria-label="Toggle Menu"
      onclick="document.querySelector('.nav-stack').classList.toggle('open')">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<div class="container">
