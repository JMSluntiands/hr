<?php
/**
 * Activity Logger Helper
 * Usage: logActivity($conn, $action, $entityType, $entityId, $description)
 */

function logActivity($conn, $action, $entityType, $entityId = null, $description = null) {
    if (!$conn || !isset($_SESSION['user_id'])) {
        return false;
    }
    
    $userId = (int)$_SESSION['user_id'];
    $userName = $_SESSION['name'] ?? 'Unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_name, action, entity_type, entity_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('isssiss', $userId, $userName, $action, $entityType, $entityId, $description, $ipAddress);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
