-- ZeroTrustBank Database Setup
-- Run this file to create the complete database schema

-- Create database
DROP DATABASE IF EXISTS zerotrustdb;
CREATE DATABASE zerotrustdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zerotrustdb;

-- ============================================
-- 1. Users Table (Main user accounts)
-- ============================================
CREATE TABLE Users (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) NOT NULL UNIQUE,
    Username VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    TransactionPasswordHash VARCHAR(255) NULL,
    IsVerified TINYINT(1) DEFAULT 0,
    PasswordAttempts INT DEFAULT 0,
    LastPasswordAttempt DATETIME NULL,
    PasswordBlockedUntil DATETIME NULL,
    OTPBlockedUntil DATETIME NULL,
    CreatedAt DATETIME NOT NULL,
    INDEX idx_email (Email),
    INDEX idx_verified (IsVerified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PendingUsers Table (Temporary during signup)
-- ============================================
CREATE TABLE PendingUsers (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) NOT NULL,
    Username VARCHAR(100) NOT NULL,
    Phone VARCHAR(15) NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    CreatedAt DATETIME NOT NULL,
    INDEX idx_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. OTPs Table (One-time passwords)
-- ============================================
CREATE TABLE otps (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    PendingUserId INT NULL,
    UserId INT NULL,
    Code VARCHAR(6) NOT NULL,
    Purpose ENUM('signup', 'login', 'send_money', 'account_settings') NOT NULL,
    ExpiresAt DATETIME NOT NULL,
    IsUsed TINYINT(1) DEFAULT 0,
    CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pending (PendingUserId),
    INDEX idx_user (UserId),
    INDEX idx_code (Code),
    INDEX idx_purpose (Purpose),
    FOREIGN KEY (PendingUserId) REFERENCES PendingUsers(Id) ON DELETE CASCADE,
    FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. TrustedDevices Table (Device fingerprinting)
-- ============================================
CREATE TABLE TrustedDevices (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    DeviceFingerprint VARCHAR(255) NOT NULL,
    FirstSeen DATETIME NOT NULL,
    LastSeen DATETIME NOT NULL,
    UNIQUE KEY unique_device (UserId, DeviceFingerprint),
    FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. LoginHistory Table (Track login attempts)
-- ============================================
CREATE TABLE LoginHistory (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    UserId INT NOT NULL,
    IpAddress VARCHAR(45) NOT NULL,
    LoginTime DATETIME NOT NULL,
    Success TINYINT(1) NOT NULL,
    INDEX idx_user (UserId),
    INDEX idx_ip (IpAddress),
    INDEX idx_time (LoginTime),
    FOREIGN KEY (UserId) REFERENCES Users(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. OTPResendTracking Table (Rate limiting)
-- ============================================
CREATE TABLE OTPResendTracking (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) NOT NULL,
    Purpose ENUM('signup', 'login', 'send_money', 'account_settings') NOT NULL,
    ResendCount INT DEFAULT 0,
    FirstOTPSentAt DATETIME NOT NULL,
    BlockedUntil DATETIME NULL,
    CreatedAt DATETIME NOT NULL,
    INDEX idx_email_purpose (Email, Purpose)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Sample Data (Optional - for testing)
-- ============================================
-- Uncomment below to insert test user
-- Password: Test@123
/*
INSERT INTO Users (Email, Username, Phone, PasswordHash, IsVerified, CreatedAt)
VALUES (
    'test@example.com',
    'Test User',
    '01012345678',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    1,
    NOW()
);
*/

-- ============================================
-- Verification Queries
-- ============================================
-- Run these to verify setup:
-- SHOW TABLES;
-- SELECT COUNT(*) FROM Users;
-- DESCRIBE Users;
