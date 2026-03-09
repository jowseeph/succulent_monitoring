START TRANSACTION;

CREATE TABLE admin_dashboard(
    adminID INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    log_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (log_id) REFERENCES user_logs(log_id)

) ENGINE=InnoDB;

COMMIT;