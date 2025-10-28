-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Okt 2025 pada 21.44
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `arcadia_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `app_settings`
--

CREATE TABLE `app_settings` (
  `key` varchar(64) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `app_settings`
--

INSERT INTO `app_settings` (`key`, `value`, `updated_at`) VALUES
('brand_name', 'Arcadia', '2025-10-29 02:32:18'),
('hero_subtitle', 'Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.', '2025-10-29 02:32:18'),
('hero_title', 'Arcadia', '2025-10-29 02:32:18'),
('logo_section_featured', '/arcadia/public/uploads/branding/featured_20251029023204_86cc5775.png', '2025-10-29 02:32:04'),
('logo_section_games', '/arcadia/public/uploads/branding/games_20251029012454_7f2c2ca3.png', '2025-10-29 01:24:54'),
('logo_section_recent', '/arcadia/public/uploads/branding/recent_20251029023218_a5f19348.png', '2025-10-29 02:32:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `chapters`
--

CREATE TABLE `chapters` (
  `id` int(11) NOT NULL,
  `walk_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text DEFAULT NULL,
  `order_number` int(11) DEFAULT 1,
  `youtube_url` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chapters`
--

INSERT INTO `chapters` (`id`, `walk_id`, `title`, `content`, `order_number`, `youtube_url`, `image_url`) VALUES
(1, 1, 'Persiapan', 'Naikkan Vigor minimal 15, bawa 2 Flask of Crimson Tears.', 1, NULL, NULL),
(2, 1, 'Pola Serangan', 'Perhatikan telat tempo Margit, dodge ke kiri.', 2, NULL, NULL),
(3, 2, 'Ambil Bahan', 'Kumpulkan balok kayu di sekitar shrine.', 1, NULL, NULL),
(4, 2, 'Menyusun Jembatan', 'Gabungkan dua balok secara sejajar pakai Ultrahand.', 2, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `status` enum('PUBLISHED','HIDDEN') NOT NULL DEFAULT 'PUBLISHED',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `comments`
--

INSERT INTO `comments` (`id`, `game_id`, `user_id`, `body`, `status`, `created_at`, `updated_at`) VALUES
(5, 1, 4, 'jhkc', 'HIDDEN', '2025-10-26 21:49:51', '2025-10-26 22:04:51'),
(6, 1, 4, 'JCAS', 'HIDDEN', '2025-10-26 22:04:43', '2025-10-26 22:04:47'),
(7, 1, 4, 'KDKSKKSS', 'HIDDEN', '2025-10-26 23:30:26', '2025-10-27 03:10:48'),
(8, 1, 4, 'HJS', 'HIDDEN', '2025-10-27 01:40:58', '2025-10-27 03:10:43'),
(11, 1, 5, 'KJH', 'HIDDEN', '2025-10-27 03:00:31', '2025-10-28 02:42:32'),
(13, 1, 4, 'kdnllkd', 'HIDDEN', '2025-10-27 03:15:21', '2025-10-27 03:50:18'),
(14, 1, 4, 'k j d h k d', 'HIDDEN', '2025-10-27 07:59:43', '2025-10-28 01:35:48'),
(15, 1, 1, 'L H H G F', 'HIDDEN', '2025-10-28 11:33:00', '2025-10-28 11:33:07');

-- --------------------------------------------------------

--
-- Struktur dari tabel `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `genre` varchar(80) DEFAULT NULL,
  `platform` varchar(80) DEFAULT NULL,
  `release_year` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `cover_blob` longblob DEFAULT NULL,
  `cover_mime` varchar(40) DEFAULT NULL,
  `cover_size` int(11) DEFAULT NULL,
  `image_original_url` text DEFAULT NULL,
  `cover_focus_x` int(11) DEFAULT NULL,
  `cover_focus_y` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `games`
--

INSERT INTO `games` (`id`, `title`, `genre`, `platform`, `release_year`, `image_url`, `description`, `cover_blob`, `cover_mime`, `cover_size`, `image_original_url`, `cover_focus_x`, `cover_focus_y`) VALUES
(1, 'Elden Ring', 'Action RPG', 'PC/Console', 2022, '/arcadia/public/uploads/covers/game_1_20251029033204_b9ad87.webp', 'Petualangan di Lands Between dengan bos menantang.', NULL, NULL, NULL, '/arcadia/public/uploads/covers/elden-ring-20251028081323-ee9a12.jpg', NULL, NULL),
(2, 'Zelda: Tears of the Kingdom', 'Adventure', 'Switch', 2023, '/arcadia/public/uploads/covers/game_2_20251029033144_9b3dcd.webp', 'Eksplorasi open world, crafting, dan shrine.', NULL, NULL, NULL, '/arcadia/public/uploads/covers/zelda-tears-of-the-kingdom-20251028081449-bc8790.jpg', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `searchlogs`
--

CREATE TABLE `searchlogs` (
  `id` int(11) NOT NULL,
  `keyword` varchar(150) NOT NULL,
  `searched_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `searchlogs`
--

INSERT INTO `searchlogs` (`id`, `keyword`, `searched_at`) VALUES
(1, 'Elden Ring', '2025-10-20 16:49:53'),
(2, 'New', '2025-10-20 17:32:19'),
(3, 'Action', '2025-10-20 18:16:30'),
(4, 'knasklc', '2025-10-20 18:36:24'),
(5, 'Action', '2025-10-20 18:49:14'),
(6, 'Action', '2025-10-20 18:57:07'),
(7, 'Action', '2025-10-20 18:57:07'),
(8, 'Action', '2025-10-20 18:57:07'),
(9, 'Action', '2025-10-20 18:57:08'),
(10, 'RPG', '2025-10-20 19:02:02'),
(11, 'Elden Ring', '2025-10-27 03:50:10'),
(12, 'Elden Ring', '2025-10-27 08:55:56'),
(13, 'Action', '2025-10-27 08:55:59'),
(14, 'knasklc', '2025-10-27 09:39:08'),
(15, 'New', '2025-10-27 09:39:12'),
(16, 'Action', '2025-10-27 09:39:16'),
(17, 'Action', '2025-10-27 09:39:27'),
(18, 'New', '2025-10-27 22:06:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `settings`
--

CREATE TABLE `settings` (
  `key` varchar(64) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `settings`
--

INSERT INTO `settings` (`key`, `value`, `updated_at`) VALUES
('brand_name', 'Arcadia', '2025-10-28 19:32:18'),
('hero_subtitle', 'Cari walkthrough, chapter, dan tips yang jelas untuk menamatkan game favoritmu.', '2025-10-28 19:32:18'),
('hero_title', 'Arcadia', '2025-10-28 19:32:18'),
('logo_section_featured', '/arcadia/public/uploads/branding/featured_20251029023204_86cc5775.png', '2025-10-28 19:32:04'),
('logo_section_games', '/arcadia/public/uploads/branding/games_20251029012454_7f2c2ca3.png', '2025-10-28 18:24:54'),
('logo_section_recent', '/arcadia/public/uploads/branding/recent_20251029023218_a5f19348.png', '2025-10-28 19:32:18'),
('site_logo_url', '/arcadia/public/uploads/branding/logo_20251028141129_840cf4e3.png', '2025-10-28 07:11:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tags`
--

CREATE TABLE `tags` (
  `id` int(11) NOT NULL,
  `name` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('OWNER','ADMIN','USER') NOT NULL DEFAULT 'USER',
  `avatar_url` varchar(255) DEFAULT NULL,
  `banner_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `full_name`, `username`, `email`, `password_hash`, `role`, `avatar_url`, `banner_url`, `created_at`, `last_login_at`, `bio`, `is_active`, `updated_at`) VALUES
(1, 'Aragorn', NULL, NULL, 'owner@arcadia.com', '$2y$10$DLN.d2z9VQoPbYmtUzeOj.hNJFk.AggyJjzNVHXwUwahXXPb3PdSG', 'OWNER', '/arcadia/public/uploads/avatars/u1-20251027170836-1ecfc0.png', '/arcadia/public/uploads/banners/b1-20251027171848-382de1.png', '2025-10-20 09:27:01', '2025-10-29 01:02:05', 'Owner Website Arcadia', 1, '2025-10-28 18:02:05'),
(4, 'Giovani', NULL, NULL, 'giovani@arca.com', '$2y$10$PdOFAzPutPW3ZvoP7x7fxuOcZu/aVvbJ1e5IZ63FqazUG60ursQWi', 'USER', NULL, NULL, '2025-10-26 09:41:30', '2025-10-28 23:16:12', NULL, 1, '2025-10-28 16:16:12'),
(5, 'Shaila', NULL, NULL, 'shaila@arca.com', '$2y$10$.aPIxIFjtS1ucJk89mWoueSlaU2ZjXJSNK3t3t.MQGQkQhj4iWtjq', 'USER', NULL, NULL, '2025-10-26 19:59:48', NULL, NULL, 1, '2025-10-28 03:37:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `walktag`
--

CREATE TABLE `walktag` (
  `id` int(11) NOT NULL,
  `walk_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `walkthroughs`
--

CREATE TABLE `walkthroughs` (
  `id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `overview` text DEFAULT NULL,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Medium',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `cover_focus_x` tinyint(4) DEFAULT 50,
  `cover_focus_y` tinyint(4) DEFAULT 50
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `walkthroughs`
--

INSERT INTO `walkthroughs` (`id`, `game_id`, `title`, `overview`, `difficulty`, `created_at`, `updated_at`, `cover_focus_x`, `cover_focus_y`) VALUES
(1, 1, 'Boss Margit the Fell Omen', 'Strategi perisai, jaga stamina, gunakan Spirit Ash untuk aggro.', 'Hard', '2025-10-27 08:54:51', NULL, 50, 50),
(2, 2, 'Shrine Tutorial – Ukouh', 'Belajar Ultrahand untuk membuat jembatan.', 'Easy', '2025-10-27 08:54:51', NULL, 50, 50);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `chapters`
--
ALTER TABLE `chapters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `walk_id` (`walk_id`,`order_number`);

--
-- Indeks untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_game` (`game_id`),
  ADD KEY `idx_comments_user` (`user_id`);

--
-- Indeks untuk tabel `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `searchlogs`
--
ALTER TABLE `searchlogs`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indeks untuk tabel `tags`
--
ALTER TABLE `tags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `idx_users_email_unique` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `walktag`
--
ALTER TABLE `walktag`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_walk_tag` (`walk_id`,`tag_id`),
  ADD KEY `tag_id` (`tag_id`);

--
-- Indeks untuk tabel `walkthroughs`
--
ALTER TABLE `walkthroughs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `chapters`
--
ALTER TABLE `chapters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `searchlogs`
--
ALTER TABLE `searchlogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `walktag`
--
ALTER TABLE `walktag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `walkthroughs`
--
ALTER TABLE `walkthroughs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `chapters`
--
ALTER TABLE `chapters`
  ADD CONSTRAINT `chapters_ibfk_1` FOREIGN KEY (`walk_id`) REFERENCES `walkthroughs` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `fk_comments_game` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `walktag`
--
ALTER TABLE `walktag`
  ADD CONSTRAINT `walktag_ibfk_1` FOREIGN KEY (`walk_id`) REFERENCES `walkthroughs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `walktag_ibfk_2` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `walkthroughs`
--
ALTER TABLE `walkthroughs`
  ADD CONSTRAINT `walkthroughs_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
