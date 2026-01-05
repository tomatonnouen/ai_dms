-- 資料自動仕分けシステム データベーススキーマ

-- データベース作成
CREATE DATABASE IF NOT EXISTS document_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE document_system;

-- カテゴリテーブル
CREATE TABLE IF NOT EXISTS categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL UNIQUE COMMENT 'カテゴリ名',
  display_order INT DEFAULT 0 COMMENT '表示順序',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL UNIQUE COMMENT 'ユーザー名',
  password_hash VARCHAR(255) NOT NULL COMMENT 'bcryptハッシュ',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME COMMENT '最終ログイン日時',

  INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ドキュメントテーブル
CREATE TABLE IF NOT EXISTS documents (
  id INT PRIMARY KEY AUTO_INCREMENT,
  filename VARCHAR(255) NOT NULL UNIQUE COMMENT 'ファイルシステム上のファイル名',
  original_filename VARCHAR(255) COMMENT '元のファイル名',
  category_id INT DEFAULT NULL COMMENT 'カテゴリID (NULLなら未分類)',
  title VARCHAR(255) COMMENT 'LLM抽出タイトル',
  summary TEXT COMMENT 'LLM抽出概要',
  document_date DATE COMMENT '文書内の日付',
  upload_date DATETIME NOT NULL COMMENT 'アップロード日時',
  file_path VARCHAR(512) NOT NULL COMMENT '相対パス',
  file_size BIGINT COMMENT 'ファイルサイズ(bytes)',
  page_count INT COMMENT 'ページ数',
  upload_source ENUM('scan', 'web') NOT NULL COMMENT 'アップロード元',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  INDEX idx_category (category_id),
  INDEX idx_upload_date (upload_date),
  INDEX idx_document_date (document_date),
  INDEX idx_title (title),
  FULLTEXT INDEX ft_summary (summary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 初期カテゴリデータ
INSERT INTO categories (name, display_order) VALUES
('中山間', 1),
('稲作生産組合', 2),
('認定農業者', 3),
('役場', 4),
('稲作', 5),
('トマト部会', 6)
ON DUPLICATE KEY UPDATE name=name;

-- 初期ユーザーデータ（パスワード: password）
-- bcryptハッシュ: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username=username;
