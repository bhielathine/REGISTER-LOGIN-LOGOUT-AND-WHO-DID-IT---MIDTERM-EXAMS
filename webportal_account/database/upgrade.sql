-- Run this ONLY if you already imported the older schema and want to keep your data.
USE account_portal;

ALTER TABLE users
    ADD COLUMN first_name     VARCHAR(50)  NOT NULL DEFAULT ''  AFTER user_id,
    ADD COLUMN last_name      VARCHAR(50)  NOT NULL DEFAULT ''  AFTER first_name,
    ADD COLUMN age            TINYINT UNSIGNED NOT NULL DEFAULT 18 AFTER last_name,
    ADD COLUMN address        VARCHAR(255) NULL AFTER age,
    ADD COLUMN contact_number VARCHAR(20)  NULL AFTER address,
    ADD COLUMN email          VARCHAR(100) NULL AFTER contact_number,
    ADD COLUMN added_by       INT NULL,
    ADD COLUMN updated_by     INT NULL,
    ADD COLUMN last_updated   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ADD CONSTRAINT fk_users_added   FOREIGN KEY (added_by)   REFERENCES users(user_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_users_updated FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL;

UPDATE users SET added_by = user_id, updated_by = user_id;

ALTER TABLE accounts
    ADD COLUMN added_by   INT NULL,
    ADD COLUMN updated_by INT NULL,
    ADD CONSTRAINT fk_acc_added   FOREIGN KEY (added_by)   REFERENCES users(user_id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_acc_updated FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL;

UPDATE accounts SET added_by = seller_id, updated_by = seller_id;

CREATE TABLE IF NOT EXISTS activity_log (
    log_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NULL,
    username   VARCHAR(50) NOT NULL,
    action     VARCHAR(20) NOT NULL,
    table_name VARCHAR(30) NOT NULL,
    record_id  INT NULL,
    details    VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;
