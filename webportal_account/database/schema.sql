CREATE DATABASE IF NOT EXISTS account_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE account_portal;

-- USER ENTITY (PARENT): personal information + login credentials + tracking columns
CREATE TABLE IF NOT EXISTS users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    first_name     VARCHAR(50)  NOT NULL,
    last_name      VARCHAR(50)  NOT NULL,
    age            TINYINT UNSIGNED NOT NULL,
    address        VARCHAR(255) NULL,
    contact_number VARCHAR(20)  NULL,
    email          VARCHAR(100) NULL,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    added_by       INT NULL,                                  -- user who inserted this record
    updated_by     INT NULL,                                  -- user who last updated this record
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_updated   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_added   FOREIGN KEY (added_by)   REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_users_updated FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS games (
    game_id   INT AUTO_INCREMENT PRIMARY KEY,
    game_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO games (game_name) VALUES
    ('Genshin Impact'), ('Zenless Zone Zero'), ('Mobile Legends');

-- CHILDREN ENTITY: one user (seller) has many account records
CREATE TABLE IF NOT EXISTS accounts (
    account_id       INT AUTO_INCREMENT PRIMARY KEY,
    seller_id        INT NOT NULL,
    game_id          INT NOT NULL,
    pseudonym        VARCHAR(100) NOT NULL,
    selling_price    DECIMAL(10,2) NOT NULL,
    account_level    INT NOT NULL,
    rank_tier        ENUM('Mythic','Legend','Epic') NULL,
    featured_items   TEXT NULL,
    description      TEXT NULL,
    birthday_binded  TINYINT(1) NOT NULL DEFAULT 0,
    username_binded  TINYINT(1) NOT NULL DEFAULT 0,
    email_binded     TINYINT(1) NOT NULL DEFAULT 0,
    account_password VARCHAR(255) NOT NULL,
    user_notes       TEXT NULL,
    status           ENUM('draft','available','sold') NOT NULL DEFAULT 'draft',
    trade_posted     DATETIME NULL,
    added_by         INT NULL,                                -- user who inserted this record
    updated_by       INT NULL,                                -- user who last updated this record
    last_updated     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_acc_seller  FOREIGN KEY (seller_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_acc_game    FOREIGN KEY (game_id)    REFERENCES games(game_id),
    CONSTRAINT fk_acc_added   FOREIGN KEY (added_by)   REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_acc_updated FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_market (status, selling_price)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS offers (
    offer_id   INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    buyer_id   INT NOT NULL,
    status     ENUM('pending','cancelled','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_off_acc   FOREIGN KEY (account_id) REFERENCES accounts(account_id) ON DELETE CASCADE,
    CONSTRAINT fk_off_buyer FOREIGN KEY (buyer_id)   REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB;

-- WHO DID WHAT: also records deletions, which leave no row behind to carry added_by/updated_by
CREATE TABLE IF NOT EXISTS activity_log (
    log_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,                                      -- becomes NULL if that user closes their account
    username   VARCHAR(50) NOT NULL,                          -- snapshot, so the log still names them
    action     VARCHAR(20) NOT NULL,                          -- INSERT / UPDATE / DELETE
    table_name VARCHAR(30) NOT NULL,
    record_id  INT NULL,
    details    VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
