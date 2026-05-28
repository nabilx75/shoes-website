<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

namespace Config;

use PDO;
use PDOException;

class Database {
    private $host = "localhost";
    private $db_name = "stridehub";
    private $username = "postgres";
    private $password = "password";
    private $port = "5432";
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // PostgreSQL PDO DSN
            $dsn = "pgsql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // Set error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode to object/associative array
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
