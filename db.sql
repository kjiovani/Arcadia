
-- Arcadia schema (XAMPP MySQL). Create database then import this.
CREATE DATABASE IF NOT EXISTS arcadia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE arcadia_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('ADMIN','EDITOR','VIEWER') NOT NULL DEFAULT 'ADMIN',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  genre VARCHAR(80) DEFAULT NULL,
  platform VARCHAR(80) DEFAULT NULL,
  release_year INT DEFAULT NULL,
  image_url VARCHAR(255) DEFAULT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS walkthroughs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  game_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  overview TEXT,
  difficulty ENUM('Easy','Medium','Hard') DEFAULT 'Medium',
  FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chapters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  walk_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  content TEXT,
  order_number INT DEFAULT 1,
  FOREIGN KEY (walk_id) REFERENCES walkthroughs(id) ON DELETE CASCADE,
  INDEX (walk_id, order_number)
);

CREATE TABLE IF NOT EXISTS mediafiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  walk_id INT NOT NULL,
  file_type ENUM('image','video','pdf') NOT NULL DEFAULT 'image',
  file_url VARCHAR(255) NOT NULL,
  caption VARCHAR(200) DEFAULT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (walk_id) REFERENCES walkthroughs(id) ON DELETE CASCADE,
  INDEX (walk_id)
);

CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS walktag (
  id INT AUTO_INCREMENT PRIMARY KEY,
  walk_id INT NOT NULL,
  tag_id INT NOT NULL,
  UNIQUE KEY uniq_walk_tag (walk_id, tag_id),
  FOREIGN KEY (walk_id) REFERENCES walkthroughs(id) ON DELETE CASCADE,
  FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS searchlogs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(150) NOT NULL,
  searched_at DATETIME NOT NULL
);

-- Seed sample data
INSERT INTO games(title,genre,platform,release_year,image_url,description) VALUES
('Elden Ring','Action RPG','PC/Console',2022,'','Petualangan di Lands Between dengan bos menantang.'),
('Zelda: Tears of the Kingdom','Adventure','Switch',2023,'','Eksplorasi open world, crafting, dan shrine.');

INSERT INTO walkthroughs(game_id,title,overview,difficulty) VALUES
(1,'Boss Margit the Fell Omen','Strategi perisai, jaga stamina, gunakan Spirit Ash untuk aggro.', 'Hard'),
(2,'Shrine Tutorial – Ukouh','Belajar Ultrahand untuk membuat jembatan.', 'Easy');

INSERT INTO chapters(walk_id,title,content,order_number) VALUES
(1,'Persiapan','Naikkan Vigor minimal 15, bawa 2 Flask of Crimson Tears.',1),
(1,'Pola Serangan','Perhatikan telat tempo Margit, dodge ke kiri.',2),
(2,'Ambil Bahan','Kumpulkan balok kayu di sekitar shrine.',1),
(2,'Menyusun Jembatan','Gabungkan dua balok secara sejajar pakai Ultrahand.',2);
