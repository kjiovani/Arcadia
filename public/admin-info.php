<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';
include __DIR__ . '/_header.php';

// ganti dengan emailmu
$ownerEmail = 'kdekgvani@gmail.com';
$gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1'
    . '&to=' . urlencode($ownerEmail)
    . '&su=' . urlencode('Pengajuan Admin Arcadia')
    . '&body=' . urlencode(
    "Halo Owner Arcadia,

Saya ingin mendaftar sebagai Admin Arcadia.

Berikut data singkat saya:
Nama:
Email:
Akun / portofolio (opsional):
Alasan ingin jadi admin:

Terima kasih."
);
?>

<style>
    /* sentuhan kecil agar rapi tanpa ganggu style global */
    .admin-hero {
        text-align: center;
        padding: 1.25rem 1rem 0;
    }

    .admin-hero h1 {
        margin: 0;
        font-size: 2rem;
        letter-spacing: .5px;
    }

    .admin-hero .sub {
        color: #bdb7d9;
        margin-top: .4rem;
    }

    .admin-grid {
        display: grid;
        gap: .8rem;
        grid-template-columns: 1fr;
    }

    @media(min-width:720px) {
        .admin-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .admin-card {
        border-radius: 14px;
        padding: 1rem;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
    }

    .admin-card h3 {
        margin: .1rem 0 .35rem;
        font-size: 1.05rem;
    }

    .admin-cta {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        justify-content: center;
    }

    .admin-note {
        text-align: center;
        color: #bdb7d9;
        font-size: .95rem;
        margin-top: .4rem;
    }

    /* === Highlight tombol Daftar Admin (ghost) supaya tidak terlalu gelap === */
    .admin-cta .btn.ghost {
        background: rgba(167, 139, 250, .16);
        border-color: var(--primary);
        color: #f5f3ff;
        font-weight: 600;
    }

    .admin-cta .btn.ghost:hover,
    .admin-cta .btn.ghost:focus-visible {
        background: var(--primary);
        border-color: var(--primary);
        color: #0f0f16;
        box-shadow: 0 8px 22px var(--ring);
        transform: translateY(-1px);
    }

    .admin-cta .btn.ghost:active {
        transform: translateY(0);
        box-shadow: 0 4px 12px var(--ring);
    }
</style>

<div class="container card" style="max-width:960px">

    <!-- Judul tengah -->
    <div class="admin-hero">
        <h1>Admin Arcadia</h1>
        <div class="sub">Info singkat untuk calon admin.</div>
    </div>

    <!-- Tiga poin ringkas -->
    <div class="admin-grid" style="margin-top:1rem">
        <div class="admin-card">
            <h3>Peran</h3>
            <p class="small">
                Admin menjaga kualitas dan kerapian panduan.
            </p>
        </div>
        <div class="admin-card">
            <h3>Syarat Singkat</h3>
            <ul class="small" style="margin:.25rem 0 0 1rem">
                <li>Suka game & menulis.</li>
                <li>Teliti dan komunikatif.</li>
                <li>Dasar HTML/Markdown (opsional).</li>
            </ul>
        </div>
        <div class="admin-card">
            <h3>Tugas Inti Admin</h3>
            <ul class="small" style="margin:.25rem 0 0 1rem">
                <li>Menambah dan mengedit walkthrough.</li>
                <li>Merapikan struktur chapter.</li>
                <li>Merespon masukan dan update panduan.</li>
            </ul>
        </div>

    </div>

    <!-- CTA -->
    <div class="card" style="margin:1rem 0; text-align:center">
        <h2 style="margin:.2rem 0 0">Tertarik jadi admin?</h2>
        <p class="small" style="margin:.25rem 0 .8rem">
            Kirim pengajuan singkat. Admin Owner akan cek dan membuat akun jika disetujui.
        </p>
        <div class="admin-cta">
            <a class="btn" href="/arcadia/public/auth/login.php?next=/arcadia/public/admin/">Login Admin</a>
            <a class="btn ghost" href="<?= e($gmailUrl) ?>" target="_blank" rel="noopener">Daftar Admin via Gmail</a>
        </div>

    </div>

</div>

