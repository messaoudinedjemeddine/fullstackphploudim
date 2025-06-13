<?php

namespace App;

use PDO;
use PDOException;

class Auth {
    /**
     * Attempt to log in a user
     * 
     * @param string $username
     * @param string $password
     * @return bool
     */
    public static function login(string $username, string $password): bool {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log out the current user
     * 
     * @return void
     */
    public static function logout(): void {
        // Unset all session variables
        $_SESSION = array();

        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy the session
        session_unset();
        session_destroy();
    }

    /**
     * Check if a user is logged in
     * 
     * @return bool
     */
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get the current logged-in user's data
     * 
     * @return array|null
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();

            $stmt = $conn->prepare("
                SELECT id, username, email, full_name, phone, role, created_at, updated_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            return $user ?: null;
        } catch (PDOException $e) {
            error_log("User Data Fetch Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the current user has the required role
     * 
     * @param string $requiredRole
     * @return bool
     */
    public static function checkRole(string $requiredRole): bool {
        if (!self::check()) {
            return false;
        }

        return $_SESSION['user_role'] === $requiredRole;
    }

    /**
     * Hash a password using PHP's password_hash function
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Check if the current user has any of the required roles
     * 
     * @param array $roles
     * @return bool
     */
    public static function hasAnyRole(array $roles): bool {
        if (!self::check()) {
            return false;
        }

        return in_array($_SESSION['user_role'], $roles);
    }

    /**
     * Get the current user's role
     * 
     * @return string|null
     */
    public static function getRole(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Check if the current user is a super admin
     * 
     * @return bool
     */
    public static function isSuperAdmin(): bool {
        return self::checkRole('super_admin');
    }

    /**
     * Delete a user by ID
     * 
     * @param int $userId
     * @return bool
     */
    public static function deleteUser(int $userId): bool {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("User Deletion Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all users
     * 
     * @return array
     */
    public static function getAllUsers(): array {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->query("SELECT id, username, full_name, email, phone, role FROM users ORDER BY id DESC");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get Users Error: " . $e->getMessage());
            return [];
        }
    }

    public static function getUserById(int $id): ?array
    {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log("Error fetching user by ID: " . $e->getMessage());
            return null;
        }
    }

    public static function updateUser(int $userId, array $userData): bool
    {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $fields = [];
            $params = [];
            
            foreach ($userData as $key => $value) {
                if ($key === 'password' && !empty($value)) {
                    $fields[] = "$key = ?";
                    $params[] = self::hashPassword($value);
                } elseif ($key !== 'password') {
                    $fields[] = "$key = ?";
                    $params[] = $value;
                }
            }
            
            $params[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("User Update Error: " . $e->getMessage());
            return false;
        }
    }

    public static function createUser(array $userData): bool
    {
        try {
            $db = Database::getInstance();
            $conn = $db->getConnection();
            
            $userData['password'] = self::hashPassword($userData['password']);
            
            $fields = array_keys($userData);
            $placeholders = array_fill(0, count($fields), '?');
            
            $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $conn->prepare($sql);
            return $stmt->execute(array_values($userData));
        } catch (PDOException $e) {
            error_log("User Creation Error: " . $e->getMessage());
            return false;
        }
    }
} 