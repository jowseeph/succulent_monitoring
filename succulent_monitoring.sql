CREATE DATABASE IF NOT EXISTS succulent_monitoring;
USE succulent_monitoring;

CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  role ENUM('admin','user') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE IF NOT EXISTS humidity (
  humidity_id INT AUTO_INCREMENT PRIMARY KEY,
  humidity_percent DECIMAL(5,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP);

CREATE TABLE IF NOT EXISTS user_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  humidity_id INT NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (humidity_id) REFERENCES humidity(humidity_id) ON DELETE CASCADE);

INSERT INTO users (username, password, email, role) VALUES
('admin01', 'admin123', 'admin@succulent.com', 'admin'),
('joseph', 'joseph123', 'joseph@email.com', 'user'),
('rimond', 'rimond123', 'rimond@email.com', 'user');

INSERT INTO humidity (humidity_percent, status) VALUES
(25.50, 'Dry'),
(40.20, 'Ideal'),
(55.80, 'Humid'),
(33.40, 'Ideal'),
(60.10, 'Humid');

INSERT INTO user_logs (user_id, humidity_id) VALUES
(1,1),
(2,2),
(2,3),
(3,4),
(1,5);
-- Updated database schema for succulent monitoring