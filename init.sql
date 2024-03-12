# init.sql
CREATE DATABASE IF NOT EXISTS your_database_name;
USE your_database_name;

-- ユーザーテーブルの作成
CREATE TABLE users (
    user_id CHAR(32) NOT NULL,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    PRIMARY KEY (user_id)
);

-- 短縮URLテーブルの作成
CREATE TABLE short_urls (
    id INT AUTO_INCREMENT NOT NULL,
    short_url VARCHAR(255) NOT NULL,
    original_url VARCHAR(2048) NOT NULL,
    user_id CHAR(32),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- アクセスログテーブルの作成
CREATE TABLE url_accesses (
    id INT AUTO_INCREMENT NOT NULL,
    short_url_id INT NOT NULL,
    accessed_at DATETIME NOT NULL,
    referrer VARCHAR(2048),
    client_ip VARCHAR(45),
    PRIMARY KEY (id),
    FOREIGN KEY (short_url_id) REFERENCES short_urls(id)
);