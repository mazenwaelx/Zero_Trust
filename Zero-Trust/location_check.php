<?php

function getUserIP(): string {
  
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

function isKnownLocation(PDO $pdo, int $userId, string $ipAddress): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM LoginHistory 
        WHERE UserId = ? AND IpAddress = ?
    ");
    $stmt->execute([$userId, $ipAddress]);
    
    return $stmt->fetchColumn() > 0;
}

function logLoginAttempt(PDO $pdo, int $userId, string $ipAddress, bool $success): void {
    $stmt = $pdo->prepare("
        INSERT INTO LoginHistory (UserId, IpAddress, LoginTime, Success)
        VALUES (?, ?, NOW(), ?)
    ");
    $stmt->execute([$userId, $ipAddress, $success ? 1 : 0]);
}
