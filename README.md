# Arcadia — Game Walkthrough Portal

Portal panduan game (*walkthrough*) dengan antarmuka modern, fitur kategorisasi, komentar pengguna, serta **panel admin** untuk mengelola *game*, *walkthrough*, *chapter*, *tag*, dan tampilan situs (logo/warna). Dibangun dengan **PHP + MySQL** (tanpa framework besar) sehingga ringan, mudah dipasang di shared hosting maupun VPS.

> **Singkatnya:** Arcadia memudahkan komunitas gamer membuat dan membaca *walkthrough* yang rapi, lengkap, dan terstruktur per chapter.

---

## ✨ Fitur Utama

- **Katalog Game** — judul, genre, platform, tahun rilis, deskripsi, cover, fokus cover (x,y), dan *crop*.
- **Walkthrough & Chapter** — struktur per game; tiap chapter bisa memuat ringkasan, gambar, dan tautan YouTube.
- **Komentar** — pengguna dapat meninggalkan komentar; admin dapat moderasi *(PUBLISHED/HIDDEN)*.
- **Pencarian & Tag** — temukan *walkthrough* berdasarkan judul, tag, atau genre.
- **Panel Admin** — CRUD *games*, *walkthroughs*, *chapters*, *tags*, **users**, pengaturan tampilan (brand/logo/warna).
- **Manajemen Gambar** — *upload*, *crop*, dan **fokus cover**; simpan koordinat zoom/fokus agar tampilan cover estetis.
- **Keamanan Dasar** — form dengan **CSRF token**, *prepared statement* untuk SQL, *password_hash()* + *password_verify()*.
- **Konfigurasi Settings** — tabel `app_settings` (prioritas) dan `settings` (fallback) untuk tema, warna, dan preferensi tampilan.
- **Setup Cepat** — halaman `/setup.php` untuk membuat akun admin pertama kali (jika tabel `users` kosong).

---

## 🧱 Teknologi

- **Backend:** PHP 8.x (kompatibel 7.4+), ekstensi `mysqli`
- **Database:** MySQL/MariaDB
- **Frontend:** HTML/CSS (Tailwind-like utility), sedikit JS vanilla
- **Session & Auth:** PHP native session, role: `OWNER`, `ADMIN`, `USER`

---

## 📦 Struktur Direktori (disederhanakan)

> Catatan: paket ini berisi folder ganda: `Arcadia-main/Arcadia-main/`. Masuklah ke **Arcadia-main/Arcadia-main** sebagai root proyek.

```
Arcadia-main/
└─ Arcadia-main/
   ├─ config.php
   ├─ arcadia_db.sql                # dump skema + seed
   ├─ lib/
   │  ├─ db.php                     # helper DB (prepared)
   │  ├─ csrf.php                   # CSRF token/verify
   │  ├─ auth.php, auth_user.php    # guard route + helper auth
   │  ├─ helpers.php                # e(), redirect, upload helper, flash
   │  └─ settings.php               # app_settings + fallback settings
   └─ public/
      ├─ index.php, games.php, game.php, walkthrough.php
      ├─ login.php, logout.php, setup.php
      ├─ _header.php, _footer.php
      └─ admin/
         ├─ index.php, _header.php, _sidebar.php, _footer.php
         ├─ games.php, tags.php, chapters.php, users.php
         ├─ appearance.php          # logo/warna/brand
         ├─ cover_crop.php          # crop cover
         ├─ cover_focus.php         # atur fokus/zoom cover
         └─ ui_api.php
```

---

## 🗄️ Skema Database (ringkasan)

- **users**: `id, name, full_name, username, email, password_hash, role, avatar_url, banner_url, is_active, created_at, last_login_at`
- **games**: `id, title, genre, platform, release_year, image_url, description, cover_blob, cover_mime, cover_size, image_original_url, cover_focus_x, cover_focus_y`
- **walkthroughs**: `id, game_id, title, summary, cover_url, ...`
- **chapters**: `id, walk_id, order_number, title, body, youtube_url, image_url`
- **comments**: `id, game_id, user_id, body, status, created_at, updated_at`
- **tags** & **walktag** (pivot)
- **settings**, **app_settings**
- **searchlogs** (analitik pencarian)

> Lihat `arcadia_db.sql` untuk detail lengkap tipe data, indeks, dan seed.

---

## ⚙️ Variabel Lingkungan (opsional tapi direkomendasikan)

Anda bisa langsung mengedit `config.php`, namun lebih baik menggunakan environment:

```bash
# .env (contoh, gunakan sesuai hosting)
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=arcadia_db
DB_USER=arcadia_user
DB_PASS=supersecret

APP_DEBUG=false
APP_URL=http://localhost:8000        # untuk base URL
UPLOAD_MAX_MB=5                      # batas upload
```

> Jika tidak menggunakan `.env`, pastikan nilai yang sama disetel langsung di `config.php`.

---

## 🧪 Menjalankan Secara Lokal

1) **Siapkan database**
```bash
mysql -u root -p -e "CREATE DATABASE arcadia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p arcadia_db < arcadia_db.sql
```

2) **Konfigurasi koneksi**  
Perbarui kredensial DB di `config.php` **atau** gunakan variabel lingkungan di atas.

3) **Jalankan server dev (PHP built-in)**
```bash
cd Arcadia-main/Arcadia-main
php -S localhost:8000 -t public
```
Buka `http://localhost:8000`.

4) **Buat akun admin (pertama kali)**  
Kunjungi `http://localhost:8000/setup.php` untuk membuat akun admin awal. **Nonaktifkan/hapus** `setup.php` setelah admin dibuat di lingkungan produksi.

---

## 🚀 Deploy Produksi (ringkas)

- **Apache:** arahkan `DocumentRoot` ke direktori `public/`. Aktifkan `RewriteEngine On` jika perlu *routing* tambahan.
- **Nginx + PHP-FPM:** *server root* → `public/`; *location ~ \.php$* diteruskan ke `php-fpm`.
- **Keamanan header:** tambahkan **CSP**, **X-Frame-Options=SAMEORIGIN**, **X-Content-Type-Options=nosniff**, **Referrer-Policy=strict-origin-when-cross-origin**, dan **HSTS** (jika HTTPS).  
- **Session cookie:** aktifkan `secure` pada HTTPS dan **regenerate** ID sesi setelah login.
- **Backup:** jadwalkan dump DB harian + backup direktori `uploads/`.

Contoh blok Nginx minimal:
```nginx
server {
  listen 80;
  server_name example.com;
  root /var/www/arcadia/public;

  location / {
    try_files $uri $uri/ /index.php?$query_string;
  }

  location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
  }
}
```

---

## 🧭 Base URL & Struktur Folder

Kode saat ini mengasumsikan jalur seperti `/arcadia/public/...`. Jika Anda memasang aplikasi pada subfolder berbeda, buat helper `base_url()` atau set `APP_URL` dan **gunakan helper itu di semua tautan/form** untuk menghindari 404.

> Paket ZIP memiliki struktur ganda. Disarankan memindahkan isi **Arcadia-main/Arcadia-main** ke root repo agar rapi.

---

## 🖼️ Upload & Cover

- Upload gambar diverifikasi dengan `getimagesize()` + whitelist MIME.
- Disarankan membatasi ukuran (mis. `UPLOAD_MAX_MB=5`) dan **re-encode** ke JPEG/WebP.
- Fitur **Crop**: `admin/cover_crop.php`.
- Fitur **Fokus & Zoom**: `admin/cover_focus.php` menyimpan `cover_focus_x/cover_focus_y` (+ opsional `cover_zoom`).

> Demi keamanan, simpan file di direktori non-eksekusi dan acak nama file saat menyimpan.

---

## 🔐 Keamanan (checklist ringkas)

- [x] CSRF token pada form
- [x] SQL **prepared statements**
- [x] **password_hash / password_verify**
- [ ] **Regenerate** session ID setelah login
- [ ] `session.cookie_secure = 1` (jika HTTPS)
- [ ] Rate limiting untuk `/login.php` dan endpoint sensitif
- [ ] **CSP** ketat (hindari `unsafe-inline`), gunakan *nonce* bila perlu
- [ ] Validasi panjang input + *escape* ketat untuk mencegah *stored XSS*

---

## 👩‍💼 Peran & Akses

- **OWNER** — akses penuh termasuk pengaturan global dan manajemen admin lain.
- **ADMIN** — manajemen konten (*games/walkthroughs/chapters/tags/comments/users*).
- **USER** — membaca konten, menulis komentar (moderasi berlaku).

---

## 🧰 Troubleshooting

- **403/404 setelah deploy** → Pastikan *document root* menunjuk ke `public/`; periksa base URL/helper.
- **Gagal konek DB** → Cek host/port/kredensial; impor `arcadia_db.sql`. Pastikan hak akses user DB benar.
- **Gambar tidak tampil** → Periksa izin folder `public/uploads` (mis. `www-data`), dan `open_basedir` jika aktif.
- **CSS tidak memuat** → Pastikan `public/assets/*` tersedia dan jalur `<link>` benar.
- **“Table doesn’t exist”** → Pastikan impor `arcadia_db.sql` **sebelum** menjalankan `/setup.php`.
- **Login brute-force** → Tambah rate limit sederhana (counter DB/Redis + backoff).

---

## 🧑‍💻 Pengembangan Lanjutan

- Ekstrak CSS inline ke `public/assets/app.css` dan aktifkan *cache busting* (mis. query `?v=timestamp` dari `app_settings`).
- Indeks DB pada kolom FK dan kolom pencarian utama.
- Pagination pada daftar panjang (games, comments, walkthroughs).
- Tambah *search analytics* dari `searchlogs` ke dashboard admin.

---

## 🤝 Kontribusi

1. Fork repo dan buat branch fitur: `feature/nama-fitur`
2. Commit terstruktur: `feat: ...`, `fix: ...`, `docs: ...`
3. Buka Pull Request dengan deskripsi perubahan dan langkah uji.

---

## 📄 Lisensi

Jika file `LICENSE` tidak ada, proyek dianggap **All rights reserved** oleh pemilik repositori. Tambahkan lisensi pilihan Anda (mis. MIT) bila ingin membuka kontribusi publik.

---

## 📚 Kredit

- Tim pengembang dan kontributor Arcadia
- Komunitas gamer yang berbagi *walkthrough*

---

## 📑 Lampiran

- **Audit Kode Otomatis** (2 Nov 2025): tersedia sebagai artefak terpisah  
  - Laporan: `arcadia_report/Arcadia_Audit.md`  
  - Ringkasan struktur: `arcadia_report/TREE.txt`

