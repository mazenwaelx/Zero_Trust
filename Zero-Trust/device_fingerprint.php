<?php

function getDeviceFingerprint(): string {
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown';
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'unknown';
    
   
    $fingerprint = md5($userAgent . $acceptLanguage . $acceptEncoding);
    
    return $fingerprint;
}

function isKnownDevice(PDO $pdo, int $userId, string $fingerprint): bool {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM TrustedDevices 
        WHERE UserId = ? AND DeviceFingerprint = ?
    ");
    $stmt->execute([$userId, $fingerprint]);
    
    return $stmt->fetchColumn() > 0;
}

function trustDevice(PDO $pdo, int $userId, string $fingerprint): void {
    $stmt = $pdo->prepare("
        INSERT INTO TrustedDevices (UserId, DeviceFingerprint, FirstSeen, LastSeen)
        VALUES (?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE LastSeen = NOW()
    ");
    $stmt->execute([$userId, $fingerprint]);
}
