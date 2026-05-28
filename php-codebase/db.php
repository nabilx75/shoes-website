<?php
/**
 * StrideHub - PHP Database Connection
 * Uses standard PDO for secure and fast SQL queries.
 */

define('DB_HOST', 'mysql-2628de66-shoes-website.l.aivencloud.com');
define('DB_PORT', '12196');
define('DB_NAME', 'stridehub');
define('DB_USER', 'avnadmin');
define('DB_PASS', 'AVNS_CkqJP70s48bHC_GQ6rI');

function getDBConnection() {
    static $conn = null;
    if ($conn !== null) {
        return $conn;
    }

    try {
        // Updated driver from pgsql to mysql for XAMPP compatibility
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    } catch (PDOException $e) {
        // Let it log the failure and return null, enabling graceful fallback to the built-in mocks/sandboxed lists
        error_log("Database connection failure: " . $e->getMessage());
        return null;
    }

    return $conn;
}
