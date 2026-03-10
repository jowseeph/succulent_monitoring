-- Succulent Monitoring Database Schema
CREATE DATABASE IF NOT EXISTS succulent_monitoring;
USE succulent_monitoring;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  role ENUM('admin','user') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS succulent (
  succulent_id INT AUTO_INCREMENT PRIMARY KEY,
  succulent_name VARCHAR(50) UNIQUE NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS humidity (
  humidity_id INT AUTO_INCREMENT PRIMARY KEY,
  humidity_percent DECIMAL(5,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  succulent_id INT NOT NULL,
  humidity_id INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (humidity_id) REFERENCES humidity(humidity_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admin_dashboard (
  adminID INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  user_id INT NOT NULL,
  log_id INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (log_id) REFERENCES user_logs(log_id)
) ENGINE=InnoDB;
