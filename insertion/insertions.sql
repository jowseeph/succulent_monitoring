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