<?php
require_once __DIR__ . '/init.php';

use App\Database;

$username = 'admin';
$newPassword = 'Admin@123';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$newHash, $username]);
    echo "Password updated for user 'admin'.\n";
    echo "New hash: $newHash\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 