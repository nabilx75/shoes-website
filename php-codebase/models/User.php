<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

namespace Models;

use PDO;
use Exception;

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $full_name;
    public $email;
    public $password;
    public $phone;
    public $role;
    public $status;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create user registration
     */
    public function register($full_name, $email, $password, $phone = '') {
        // Check duplicate
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':email', $email);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            throw new Exception("An account with this email address already exists.");
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  (full_name, email, password, phone, role, status) 
                  VALUES (:full_name, :email, :password, :phone, 'customer', 'active')";

        $stmt = $this->conn->prepare($query);

        // Clean input
        $full_name = htmlspecialchars(strip_tags($full_name));
        $email = htmlspecialchars(strip_tags($email));
        $phone = htmlspecialchars(strip_tags($phone));
        
        // Secure Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone', $phone);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Log in verification
     */
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            if ($row['status'] === 'blocked') {
                throw new Exception("Your account is currently blocked by an administrator.");
            }

            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->full_name = $row['full_name'];
                $this->email = $row['email'];
                $this->phone = $row['phone'];
                $this->role = $row['role'];
                $this->status = $row['status'];
                $this->created_at = $row['created_at'];
                return $row;
            }
        }
        return false;
    }
}
