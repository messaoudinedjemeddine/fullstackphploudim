<?php
require_once __DIR__ . '/init.php';

use App\Database;

$username = 'admin';
$password = 'Admin@123';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    echo "User found: " . ($user ? "Yes" : "No") . "\n";
    if ($user) {
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Password hash: " . $user['password'] . "\n";
        echo "Password verification: " . (password_verify($password, $user['password']) ? "Success" : "Failed") . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 