-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Okt 2025 pada 17.49
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
-- Struktur dari tabel `chapters`
--

CREATE TABLE `chapters` (
  `id` int(11) NOT NULL,
  `walk_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text DEFAULT NULL,
  `order_number` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chapters`
--

INSERT INTO `chapters` (`id`, `walk_id`, `title`, `content`, `order_number`) VALUES
(1, 1, 'Persiapan', 'Naikkan Vigor minimal 15, bawa 2 Flask of Crimson Tears.', 1),
(2, 1, 'Pola Serangan', 'Perhatikan telat tempo Margit, dodge ke kiri.', 2),
(3, 2, 'Ambil Bahan', 'Kumpulkan balok kayu di sekitar shrine.', 1),
(4, 2, 'Menyusun Jembatan', 'Gabungkan dua balok secara sejajar pakai Ultrahand.', 2);

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
(3, 4, 4, 'dfghjkl;', 'PUBLISHED', '2025-10-26 21:25:44', '2025-10-26 21:25:44'),
(4, 4, 4, 'fghj', 'HIDDEN', '2025-10-26 21:36:17', '2025-10-26 21:43:34'),
(5, 1, 4, 'jhkc', 'HIDDEN', '2025-10-26 21:49:51', '2025-10-26 22:04:51'),
(6, 1, 4, 'JCAS', 'HIDDEN', '2025-10-26 22:04:43', '2025-10-26 22:04:47'),
(7, 1, 4, 'KDKSKKSS', 'HIDDEN', '2025-10-26 23:30:26', '2025-10-27 03:10:48'),
(8, 1, 4, 'HJS', 'HIDDEN', '2025-10-27 01:40:58', '2025-10-27 03:10:43'),
(9, 4, 4, 'fghj', 'PUBLISHED', '2025-10-27 02:21:38', '2025-10-27 02:21:38'),
(10, 4, 4, 'FGHBJK', 'PUBLISHED', '2025-10-27 02:27:38', '2025-10-27 02:27:38'),
(11, 1, 5, 'KJH', 'PUBLISHED', '2025-10-27 03:00:31', '2025-10-27 03:00:31'),
(12, 4, 5, 'K J H G F', 'HIDDEN', '2025-10-27 03:09:22', '2025-10-27 03:09:28'),
(13, 1, 4, 'kdnllkd', 'HIDDEN', '2025-10-27 03:15:21', '2025-10-27 03:50:18'),
(14, 1, 4, 'k j d h k d', 'PUBLISHED', '2025-10-27 07:59:43', '2025-10-27 07:59:43');

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
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `games`
--

INSERT INTO `games` (`id`, `title`, `genre`, `platform`, `release_year`, `image_url`, `description`) VALUES
(1, 'Elden Ring', 'Action RPG', 'PC/Console', 2022, '', 'Petualangan di Lands Between dengan bos menantang.'),
(2, 'Zelda: Tears of the Kingdom', 'Adventure', 'Switch', 2023, '', 'Eksplorasi open world, crafting, dan shrine.'),
(3, 'ndjdk', 'dknladkn', 'kdnldk', 2017, '', 'nc mnc'),
(4, 'erewew', 'faaf', 'caaccaca', 2017, '', 'cds'),
(5, 'dfghj', 'xrsdtfgyhjk', 'dfgh', 2017, '', 'fghj');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mediafiles`
--

CREATE TABLE `mediafiles` (
  `id` int(11) NOT NULL,
  `walk_id` int(11) NOT NULL,
  `file_type` enum('image','video','pdf') NOT NULL DEFAULT 'image',
  `file_url` varchar(255) NOT NULL,
  `caption` varchar(200) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mediafiles`
--

INSERT INTO `mediafiles` (`id`, `walk_id`, `file_type`, `file_url`, `caption`, `uploaded_at`) VALUES
(5, 1, 'image', 'http://localhost/arcadia/uploads/covers/zelda.webp', '', '2025-10-20 16:28:03'),
(6, 2, 'image', 'http://localhost/arcadia/uploads/covers/elden_ring.jpg', '', '2025-10-20 16:29:20');

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
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `full_name`, `username`, `email`, `password_hash`, `role`, `avatar_url`, `banner_url`, `created_at`, `last_login_at`) VALUES
(1, 'Aragorn', NULL, NULL, 'owner@arcadia.com', '$2y$10$DLN.d2z9VQoPbYmtUzeOj.hNJFk.AggyJjzNVHXwUwahXXPb3PdSG', 'OWNER', '/arcadia/public/uploads/avatars/u1-20251027170836-1ecfc0.png', '/arcadia/public/uploads/banners/b1-20251027171848-382de1.png', '2025-10-20 09:27:01', '2025-10-27 22:07:36'),
(4, 'Giovani', NULL, NULL, 'giovani@arca.com', '$2y$10$PdOFAzPutPW3ZvoP7x7fxuOcZu/aVvbJ1e5IZ63FqazUG60ursQWi', 'USER', NULL, NULL, '2025-10-26 09:41:30', '2025-10-27 09:06:33'),
(5, 'Shaila', NULL, NULL, 'shaila@arca.com', '$2y$10$NQLIcfNVUXvRw07DwV7M1Ob5E5wqbARYGLy54/fP/Uj/lJzBzLp2e', 'USER', NULL, NULL, '2025-10-26 19:59:48', NULL),
(6, 'Kaze', NULL, NULL, 'kaze@arca.com', '$2y$10$ccDa763SfGSRgFq0FXiUaO8shVCyb.7lWbFGDqdwtU1u8.s46bmw.', 'USER', NULL, NULL, '2025-10-27 03:11:46', '2025-10-27 10:33:52'),
(7, 'Kiryu', NULL, NULL, 'kiryu@arca.com', '$2y$10$e6oRZo.L3MnGjrDhraR2h.MDQCnSzT5eIAe9cKl/ig//Z6WNB.EmC', 'USER', NULL, NULL, '2025-10-27 14:57:58', '2025-10-27 21:58:11');

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
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `walkthroughs`
--

INSERT INTO `walkthroughs` (`id`, `game_id`, `title`, `overview`, `difficulty`, `created_at`, `updated_at`) VALUES
(1, 1, 'Boss Margit the Fell Omen', 'Strategi perisai, jaga stamina, gunakan Spirit Ash untuk aggro.', 'Hard', '2025-10-27 08:54:51', NULL),
(2, 2, 'Shrine Tutorial – Ukouh', 'Belajar Ultrahand untuk membuat jembatan.', 'Easy', '2025-10-27 08:54:51', NULL),
(3, 4, 'feacacs', 'acac', 'Medium', '2025-10-27 08:54:51', NULL),
(4, 3, 'cascascsaca', 'acscasasc', 'Easy', '2025-10-27 08:54:51', NULL);

--
-- Indexes for dumped tables
--

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
-- Indeks untuk tabel `mediafiles`
--
ALTER TABLE `mediafiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `walk_id` (`walk_id`);

--
-- Indeks untuk tabel `searchlogs`
--
ALTER TABLE `searchlogs`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `mediafiles`
--
ALTER TABLE `mediafiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- Ketidakleluasaan untuk tabel `mediafiles`
--
ALTER TABLE `mediafiles`
  ADD CONSTRAINT `mediafiles_ibfk_1` FOREIGN KEY (`walk_id`) REFERENCES `walkthroughs` (`id`) ON DELETE CASCADE;

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
