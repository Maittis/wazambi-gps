-- Wazambi GPS Agent Program — Application Database
-- Run this once, OR use install.php which does it automatically.

CREATE DATABASE IF NOT EXISTS wazambi_agents CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wazambi_agents;

DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS admins;

CREATE TABLE applications (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    fullname            VARCHAR(255)  NOT NULL,
    whatsapp            VARCHAR(80)   NOT NULL,
    email               VARCHAR(255)  NOT NULL,
    town                VARCHAR(255)  NOT NULL,
    age_18              ENUM('Yes','No') NOT NULL DEFAULT 'No',
    smartphone          ENUM('Yes','No') NOT NULL DEFAULT 'No',
    sales_experience    ENUM('Yes','No') NOT NULL DEFAULT 'No',
    experience_detail   TEXT,
    sales_methods       VARCHAR(255)  NOT NULL,
    knows_vehicles      ENUM('Yes','No') NOT NULL DEFAULT 'No',
    weekly_customers    INT           NOT NULL DEFAULT 0,
    first_five          TEXT          NOT NULL,
    why_you             TEXT          NOT NULL,
    attend_both         ENUM('Yes','No') NOT NULL DEFAULT 'No',
    travel_own_cost     ENUM('Yes','No') NOT NULL DEFAULT 'No',
    understands_commission ENUM('Yes','No') NOT NULL DEFAULT 'No',
    cv_filename         VARCHAR(255)  DEFAULT NULL,
    agree_declaration   ENUM('Yes','No') NOT NULL DEFAULT 'No',
    status              ENUM('pending','shortlisted','contacted','accepted','rejected')
                        NOT NULL DEFAULT 'pending',
    notes               TEXT          DEFAULT NULL,
    created_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status  (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    created_at TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- The install.php script creates the admin user with a properly hashed password.