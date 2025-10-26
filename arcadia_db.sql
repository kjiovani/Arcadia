-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Okt 2025 pada 03.04
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
  `name` varchar(80) NOT NULL,
  `content` text NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(4, 'erewew', 'faaf', 'caaccaca', 2017, '', 'cds');

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
(10, 'RPG', '2025-10-20 19:02:02');

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
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('ADMIN','EDITOR','VIEWER') NOT NULL DEFAULT 'ADMIN',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@arcadia.test', '$2y$10$wR3lPU9gPuWyaujUqyup3Og/CAGiu1VE9/s6Oqoq8UjGhXjb96UfK', 'ADMIN', '2025-10-20 09:27:01');

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
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Medium'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `walkthroughs`
--

INSERT INTO `walkthroughs` (`id`, `game_id`, `title`, `overview`, `difficulty`) VALUES
(1, 1, 'Boss Margit the Fell Omen', 'Strategi perisai, jaga stamina, gunakan Spirit Ash untuk aggro.', 'Hard'),
(2, 2, 'Shrine Tutorial – Ukouh', 'Belajar Ultrahand untuk membuat jembatan.', 'Easy'),
(3, 4, 'feacacs', 'acac', 'Medium'),
(4, 3, 'cascascsaca', 'acscasasc', 'Easy');

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
  ADD PRIMARY KEY (`id`);

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
  ADD UNIQUE KEY `email` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `mediafiles`
--
ALTER TABLE `mediafiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `searchlogs`
--
ALTER TABLE `searchlogs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `tags`
--
ALTER TABLE `tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
