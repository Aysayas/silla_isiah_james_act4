-- LavaLust Database Initialization
-- This file runs automatically when MySQL container starts

-- Create lavalust_user if it doesn't exist
GRANT ALL PRIVILEGES ON lavalust_db.* TO 'lavalust_user'@'%';
FLUSH PRIVILEGES;

-- Use the database
USE lavalust_db;

-- Create a sample users table (you can modify this based on your app needs)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add more tables here as needed for your LavaLust app
