<?php

namespace App;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private ?PDO $conn = null;

    /**
     * Private constructor to prevent direct instantiation
     * Establishes the PDO connection to the database
     */
    private function __construct() {
        $this->conn = $GLOBALS['pdo'];
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }

    /**
     * Get the singleton instance of the Database class
     * 
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the PDO connection object
     * 
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->conn;
    }

    /**
     * Execute a query and return the statement
     * 
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Begin a transaction
     * 
     * @return bool
     */
    public function beginTransaction(): bool {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit a transaction
     * 
     * @return bool
     */
    public function commit(): bool {
        return $this->conn->commit();
    }

    /**
     * Rollback a transaction
     * 
     * @return bool
     */
    public function rollBack(): bool {
        return $this->conn->rollBack();
    }

    /**
     * Get the last inserted ID
     * 
     * @return string
     */
    public function lastInsertId(): string {
        return $this->conn->lastInsertId();
    }
}

define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');
define('DB_PASS', '');
?>