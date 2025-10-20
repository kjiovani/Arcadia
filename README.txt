
# Arcadia (MVP, XAMPP + MySQL)

## Cara Jalankan (Windows XAMPP)
1. Buka **XAMPP Control Panel** → start **Apache** dan **MySQL**.
2. Buat database:
   - Buka **phpMyAdmin** → **Import** file: `arcadia/db.sql`.
3. Salin folder **arcadia** ini ke: `C:/xampp/htdocs/`.
4. Akses di browser: `http://localhost/arcadia/public/`.
5. Buat admin default:
   - Buka `http://localhost/arcadia/public/setup.php` (sekali saja).
   - Login: email **admin@arcadia.test** / password **admin123**.
6. Kelola data di **Admin**:
   - `http://localhost/arcadia/public/admin/` (Games, Walkthroughs, Chapters).

## Struktur Folder
arcadia/
├─ assets/            # CSS
├─ lib/               # helper kecil (db, csrf, auth, validation)
├─ public/            # halaman publik + admin
│  ├─ admin/          # dashboard & CRUD
│  ├─ index.php       # daftar game
│  ├─ game.php        # detail game + daftar walkthrough
│  ├─ walkthrough.php # detail walkthrough + chapters
│  ├─ search.php      # pencarian sederhana
│  ├─ login.php, logout.php, setup.php
├─ config.php         # sesuaikan kredensial MySQL (jika perlu)
├─ db.sql             # skema & seed contoh

## Catatan
- Semua query **prepared** (aman dari SQL injection).
- Form admin memakai **CSRF token**.
- Skema sesuai analisis (Games/Walkthroughs/Chapters + logging search).
- Nanti bisa ditambah MediaFiles, Tags, dan relasinya (tabel sudah ada).

— Generated 2025-10-20T09:19:06.357321
