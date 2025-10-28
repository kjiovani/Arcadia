<?php
// /arcadia/public/admin/_sidebar.php
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/helpers.php';

$me = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
$role = strtoupper($me['role'] ?? '');         // OWNER | ADMIN
$isOwner = ($role === 'OWNER');

$base = '/arcadia/public/admin/';

// Menu yang sama untuk OWNER & ADMIN
$common = [
    ['href' => $base . 'profile.php', 'label' => 'Profil'],
    ['href' => $base . 'index.php', 'label' => 'Dashboard'],
    ['href' => $base . 'games.php', 'label' => 'Games'],
    ['href' => $base . 'walkthroughs.php', 'label' => 'Walkthroughs'],
    ['href' => $base . 'chapters.php', 'label' => 'Chapters'],
    ['href' => $base . 'tags.php', 'label' => 'Tags'],
];

// Hanya muncul untuk OWNER (jika ada halaman terkait)
$owner_extra = [
    ['href' => $base . 'users.php', 'label' => 'Akun'],
    ['href' => $base . 'appearance.php', 'label' => 'Tampilan'],
    ['href' => $base . 'cover_crop.php', 'label' => 'Cover Crop'],
    ['href' => $base . 'ui.php', 'label' => 'Owner UI'],
];

$nav = $isOwner ? array_merge($common, $owner_extra) : $common;

// tandai menu aktif
$curr = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
function nav_active($href, $curr)
{
    $p = basename(parse_url($href, PHP_URL_PATH));
    return $p === $curr ? ' active' : '';
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <span class="badge"><?= $isOwner ? 'Owner Arcadia' : 'Admin Arcadia' ?></span>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php foreach ($nav as $it): ?>
                <li>
                    <a class="nav-link<?= nav_active($it['href'], $curr) ?>" href="<?= e($it['href']) ?>">
                        <?= e($it['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>