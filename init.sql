-- init.sql
CREATE TABLE IF NOT EXISTS t_user (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    online INT DEFAULT 0,
    last_heartbeat TIMESTAMP NULL
);

CREATE TABLE IF NOT EXISTS t_log (
    lid INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    action INT NOT NULL COMMENT '1=login, 0=logout',
    is_kiosk INT DEFAULT 0,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uid) REFERENCES t_user(uid) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_timestamp (timestamp)
);

CREATE TABLE IF NOT EXISTS t_niosh_evaluations (
    eval_id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    eval_name VARCHAR(255),
    weight DECIMAL(10,2),
    horizontal_distance DECIMAL(10,2),
    vertical_distance DECIMAL(10,2),
    vertical_height DECIMAL(10,2),
    distance_moved DECIMAL(10,2),
    asymmetric_angle DECIMAL(10,2),
    frequency DECIMAL(10,2),
    duration VARCHAR(50),
    grip_quality VARCHAR(50),
    rwl DECIMAL(10,2),
    li DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uid) REFERENCES t_user(uid) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_created_at (created_at)
);

-- Admin user (password: admin123)
INSERT IGNORE INTO t_user (uid, username, password) 
VALUES (1, 'admin', '$2y$10$YourHashedPasswordHere');
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' IDENTIFIED BY 'abcxyz' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY 'abcxyz' WITH GRANT OPTION;

FLUSH PRIVILEGES;

-- Crea le tabelle per l'applicazione
USE mydb;

CREATE TABLE IF NOT EXISTS t_user (
    uid INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash (già crittografato)',
    creation_date DATETIME NOT NULL,
    online TINYINT(1) DEFAULT 0,
    last_heartbeat DATETIME NULL,
    INDEX idx_username (username),
    INDEX idx_online (online)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tabella utenti con password crittografate (bcrypt)';

CREATE TABLE IF NOT EXISTS t_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    uid INT NOT NULL,
    op_type TINYINT(1) NOT NULL COMMENT '0=logout, 1=login',
    timestamp DATETIME NOT NULL,
    is_kiosk TINYINT(1) NOT NULL DEFAULT 0,
    ip_address VARCHAR(45) NULL COMMENT 'IP crittografato per privacy',
    user_agent VARCHAR(500) NULL COMMENT 'User agent crittografato',
    FOREIGN KEY (uid) REFERENCES t_user(uid) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_timestamp (timestamp),
    INDEX idx_op_type (op_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log accessi con dati sensibili crittografati';

-- Tabella per audit di sicurezza (opzionale)
CREATE TABLE IF NOT EXISTS t_security_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'failed_login, forced_logout, etc',
    uid INT NULL,
    details TEXT NULL,
    timestamp DATETIME NOT NULL,
    ip_address VARCHAR(45) NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit di sicurezza del sistema';