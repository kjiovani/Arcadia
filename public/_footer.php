</div>

<?php
// /arcadia/public/_footer.php — full-bleed, rata kiri-kanan, kolom seimbang
$logged = function_exists('is_user_logged_in') && is_user_logged_in();
$authHref = $logged
    ? '/arcadia/public/auth/logout.php'
    : '/arcadia/public/auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/arcadia/public/');
$authText = $logged ? 'Logout' : 'Login';
?>
<style>
    .site-footer {
        margin-top: 28px;
        background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
        border-top: 1px solid rgba(255, 255, 255, .10);
        text-align: left;
    }

    .site-footer * {
        text-align: left
    }

    /* FULL-BLEED: isi melebar ke tepi layar, tetap ada padding responsif */
    .site-footer .wrap {
        max-width: none;
        width: 100%;
        margin: 0;
        padding: 22px clamp(16px, 4vw, 36px);
    }

    /* kolom seimbang */
    .f-top {
        display: grid;
        gap: 18px;
        grid-template-columns: 1fr 1fr 1fr;
        /* tiga kolom sama lebar */
        align-items: flex-start;
    }

    /* helper: paragraf justify */
    .justify {
        text-align: justify;
        text-justify: inter-word
    }

    .f-brand {
        display: flex;
        gap: 12px
    }

    .f-logo {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: grid;
        place-items: center;
        background: radial-gradient(120% 120% at 30% 20%, rgba(167, 139, 250, .45), rgba(217, 70, 239, .18));
        border: 1px solid rgba(255, 255, 255, .16);
        font-weight: 800;
    }

    .f-brand h3 {
        margin: 0 0 6px
    }

    .f-brand p {
        margin: 0;
        opacity: .88;
        line-height: 1.55
    }

    .f-col h4 {
        margin: 0 0 8px;
        font-size: 1rem
    }

    .f-nav {
        display: grid;
        gap: 8px
    }

    .f-nav a {
        color: inherit;
        opacity: .9;
        text-decoration: none
    }

    .f-nav a:hover {
        text-decoration: underline;
        opacity: 1
    }

    .f-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid rgba(255, 255, 255, .08);
        margin-top: 16px;
        padding-top: 12px;
        font-size: .95rem;
        opacity: .95;
        width: 100%;
    }

    .f-bottom a {
        color: inherit;
        opacity: .9;
        text-decoration: none
    }

    .f-bottom a:hover {
        text-decoration: underline;
        opacity: 1
    }

    @media (max-width:860px) {
        .f-top {
            grid-template-columns: 1fr
        }

        .f-bottom {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px
        }
    }
</style>

<footer class="site-footer" role="contentinfo">
    <div class="wrap">
        <div class="f-top">
            <!-- Brand singkat -->
            <div class="f-brand">

                <div>
                    <h3>Arcadia</h3>
                    <p class="justify">
                        Pusat panduan game yang rapi: Game → Walkthrough → Chapter. Cepat dicari, enak dibaca.
                    </p>
                </div>
            </div>

            <!-- Navigasi utama -->
            <div class="f-col">
                <h4>Navigasi</h4>
                <nav class="f-nav" aria-label="Navigasi footer">
                    <a href="/arcadia/public/index.php">Beranda</a>
                    <a href="/arcadia/public/games.php">Game</a>
                    <a href="/arcadia/public/search.php">Cari</a>
                    <a href="/arcadia/public/about.php">Tentang</a>
                </nav>
            </div>

            <!-- Akun/Bantuan ringkas -->
            <div class="f-col">
                <h4>Akun & Bantuan</h4>
                <div class="f-nav">
                    <?php if ($logged): ?>
                        <a href="/arcadia/public/profile.php">Profil</a>
                    <?php else: ?>
                        <a href="/arcadia/public/auth/register.php">Daftar</a>
                    <?php endif; ?>
                    <a href="<?= e($authHref) ?>"><?= e($authText) ?></a>
                    <a href="/arcadia/public/about.php#faq">FAQ</a>
                    <a href="mailto:admin@example.com">Kontak</a>
                </div>
            </div>
        </div>

        <div class="f-bottom">
            <div>© <span id="y"></span> Arcadia</div>
            <div>
                <a href="/arcadia/public/about.php#kontribusi">Kontribusi</a> ·
                <a href="#">Privasi</a> ·
                <a href="#">Syarat</a>
            </div>
        </div>
    </div>
</footer>

<script>
    (document.getElementById('y') || {}).textContent = new Date().getFullYear();
</script>
</body>

</html>