-- ============================================================
--  Prototype Bank — MySQL Database Schema
--  Run this file once via phpMyAdmin or MySQL CLI.
--  AppServ / MySQL 5.7+ / 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS prototype_bank
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE prototype_bank;

-- ============================================================
--  TABLE: clients
-- ============================================================
CREATE TABLE IF NOT EXISTS clients (
    account_number  VARCHAR(20)     NOT NULL,
    pin_code        VARCHAR(255)    NOT NULL,
    full_name       VARCHAR(100)    NOT NULL,
    phone           VARCHAR(20)     NOT NULL DEFAULT '',
    balance         DECIMAL(15,2)   NOT NULL DEFAULT 0.00,
    is_deleted      TINYINT(1)      NOT NULL DEFAULT 0,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (account_number),
    KEY idx_is_deleted (is_deleted),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    username    VARCHAR(50)     NOT NULL,
    password    VARCHAR(255)    NOT NULL,
    permissions INT             NOT NULL DEFAULT 0,
    is_deleted  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (username),
    KEY idx_is_deleted (is_deleted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE: transactions
-- ============================================================
CREATE TABLE IF NOT EXISTS transactions (
    id              INT             NOT NULL AUTO_INCREMENT,
    account_number  VARCHAR(20)     NOT NULL,
    type            ENUM('Deposit','Withdraw','Transfer') NOT NULL,
    amount          DECIMAL(15,2)   NOT NULL,
    target_account  VARCHAR(20)     DEFAULT NULL,
    timestamp       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_account   (account_number),
    KEY idx_timestamp (timestamp),
    CONSTRAINT fk_txn_account
        FOREIGN KEY (account_number)
        REFERENCES clients (account_number)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED: Admin User   username=root  password=12345678 (MD5)
-- ============================================================
INSERT IGNORE INTO users (username, password, permissions) VALUES
('root', MD5('12345678'), -1);

-- ============================================================
--  SEED: Sample Clients
-- ============================================================
INSERT IGNORE INTO clients
    (account_number, pin_code, full_name, phone, balance, created_at)
VALUES
('ACC-10001', '1234', 'Alice Johnson',    '+1-555-0101', 15000.00, DATE_SUB(NOW(), INTERVAL 7 DAY)),
('ACC-10002', '5678', 'Bob Martinez',     '+1-555-0102',  8500.50, DATE_SUB(NOW(), INTERVAL 6 DAY)),
('ACC-10003', '9012', 'Carol Williams',   '+1-555-0103', 32000.75, DATE_SUB(NOW(), INTERVAL 5 DAY)),
('ACC-10004', '3456', 'David Chen',       '+1-555-0104',  2200.00, DATE_SUB(NOW(), INTERVAL 4 DAY)),
('ACC-10005', '7890', 'Eva Thompson',     '+1-555-0105', 47500.00, DATE_SUB(NOW(), INTERVAL 3 DAY)),
('ACC-10006', '2345', 'Frank Rodriguez',  '+1-555-0106',  9100.25, DATE_SUB(NOW(), INTERVAL 2 DAY)),
('ACC-10007', '6789', 'Grace Kim',        '+1-555-0107', 21300.00, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- ============================================================
--  SEED: Sample Transactions
-- ============================================================
INSERT IGNORE INTO transactions
    (account_number, type, amount, target_account, timestamp)
VALUES
('ACC-10001', 'Deposit',  5000.00, NULL,         DATE_SUB(NOW(), INTERVAL 6 DAY)),
('ACC-10002', 'Withdraw', 1500.00, NULL,         DATE_SUB(NOW(), INTERVAL 5 DAY)),
('ACC-10003', 'Transfer', 2000.00, 'ACC-10004',  DATE_SUB(NOW(), INTERVAL 4 DAY)),
('ACC-10004', 'Deposit',  3000.00, NULL,         DATE_SUB(NOW(), INTERVAL 3 DAY)),
('ACC-10005', 'Withdraw',  500.00, NULL,         DATE_SUB(NOW(), INTERVAL 2 DAY)),
('ACC-10001', 'Transfer', 1000.00, 'ACC-10002',  DATE_SUB(NOW(), INTERVAL 1 DAY)),
('ACC-10006', 'Deposit',  4500.00, NULL,         DATE_SUB(NOW(), INTERVAL 12 HOUR)),
('ACC-10007', 'Withdraw',  800.00, NULL,         DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- ============================================================
--  TABLE: news
-- ============================================================
CREATE TABLE IF NOT EXISTS news (
    id          INT             NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255)    NOT NULL,
    content     TEXT            NOT NULL,
    author      VARCHAR(50)     NOT NULL,
    is_deleted  TINYINT(1)      NOT NULL DEFAULT 0,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_is_deleted (is_deleted),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  SEED: Sample News
-- ============================================================
INSERT IGNORE INTO news (id, title, content, author, created_at) VALUES
(1, 'System Maintenance Notice', 'The banking system will undergo scheduled maintenance this Sunday from 2 AM to 4 AM EST. Some services may be temporarily unavailable.', 'root', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'New Interest Rates Announced', 'We are pleased to announce an increase in savings account interest rates by 0.5% effective next month.', 'root', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 'Welcome to Prototype Bank v2', 'The new management dashboard is now live! Enjoy faster transactions and an improved UI.', 'root', NOW());
