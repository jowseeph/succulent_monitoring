CREATE DATABASE IF NOT EXISTS succulent_monitoring;
USE succulent_monitoring;

-- USERS TABLE
CREATE TABLE IF NOT EXISTS users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  role ENUM('admin','user') NOT NULL,
  last_login DATETIME,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- PLANTS TABLE
CREATE TABLE IF NOT EXISTS plants (
  plant_id INT AUTO_INCREMENT PRIMARY KEY,
  plant_name VARCHAR(100) NOT NULL,
  plant_type VARCHAR(100),
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- DEVICES TABLE (Sensors)
CREATE TABLE IF NOT EXISTS devices (
  device_id INT AUTO_INCREMENT PRIMARY KEY,
  device_name VARCHAR(100) NOT NULL,
  location VARCHAR(100),
  added_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- HUMIDITY TABLE
CREATE TABLE IF NOT EXISTS humidity (
  humidity_id INT AUTO_INCREMENT PRIMARY KEY,
  plant_id INT,
  device_id INT,
  humidity_percent DECIMAL(5,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  notes VARCHAR(255),
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (plant_id) REFERENCES plants(plant_id) ON DELETE CASCADE,
  FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
);

-- TEMPERATURE TABLE
CREATE TABLE IF NOT EXISTS temperature (
  temperature_id INT AUTO_INCREMENT PRIMARY KEY,
  device_id INT,
  temperature_celsius DECIMAL(5,2) NOT NULL,
  status VARCHAR(20) NOT NULL,
  recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (device_id) REFERENCES devices(device_id) ON DELETE CASCADE
);

-- USER LOGS TABLE
CREATE TABLE IF NOT EXISTS user_logs (
  log_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  humidity_id INT NOT NULL,
  logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (humidity_id) REFERENCES humidity(humidity_id) ON DELETE CASCADE
);

-- INSERT USERS
INSERT INTO users (username, password, email, role) VALUES
('admin01', 'admin123', 'admin@succulent.com', 'admin'),
('joseph', 'joseph123', 'joseph@email.com', 'user'),
('rimond', 'rimond123', 'rimond@email.com', 'user');

-- INSERT PLANTS
INSERT INTO plants (plant_name, plant_type) VALUES
('Aloe Vera', 'Succulent'),
('Echeveria', 'Succulent'),
('Jade Plant', 'Succulent');

-- INSERT DEVICES
INSERT INTO devices (device_name, location) VALUES
('Sensor A', 'Greenhouse 1'),
('Sensor B', 'Greenhouse 2');

-- INSERT HUMIDITY DATA
INSERT INTO humidity (plant_id, device_id, humidity_percent, status, notes) VALUES
(1, 1, 25.50, 'Dry', 'Needs watering'),
(2, 1, 40.20, 'Ideal', 'Healthy condition'),
(3, 2, 55.80, 'Humid', 'Too much moisture'),
(1, 2, 33.40, 'Ideal', 'Stable humidity'),
(2, 1, 60.10, 'Humid', 'Check ventilation');

-- INSERT TEMPERATURE DATA
INSERT INTO temperature (device_id, temperature_celsius, status) VALUES
(1, 24.50, 'Normal'),
(1, 30.10, 'Hot'),
(2, 18.30, 'Cool');

-- INSERT USER LOGS
INSERT INTO user_logs (user_id, humidity_id) VALUES
(1,1),
(2,2),
(2,3),
(3,4),
(1,5);
--modified: succulent_monitoring.sql