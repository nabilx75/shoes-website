<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

namespace Models;

use PDO;

class Cart {
    private $conn;
    private $table_name = "cart";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Get user cart items with shoe details
     */
    public function getItems($user_id) {
        $query = "SELECT c.*, s.name as shoe_name, s.price, s.discount_price, sz.size,
                  (SELECT image_url FROM shoe_images WHERE shoe_id = s.id AND is_primary = TRUE LIMIT 1) as primary_image
                  FROM " . $this->table_name . " c
                  LEFT JOIN shoes s ON c.shoe_id = s.id
                  LEFT JOIN shoe_sizes sz ON c.size_id = sz.id
                  WHERE c.user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    /**
     * Add or update quantity to cart
     */
    public function addItem($user_id, $shoe_id, $size_id, $qty = 1) {
        // Double check inventory
        $check_inventory = "SELECT stock_quantity FROM shoe_sizes WHERE id = :size_id LIMIT 1";
        $sz_stmt = $this->conn->prepare($check_inventory);
        $sz_stmt->bindParam(':size_id', $size_id, PDO::PARAM_INT);
        $sz_stmt->execute();
        $sz = $sz_stmt->fetch();
        if (!$sz || $sz['stock_quantity'] < $qty) {
            return false;
        }

        // Check if already in cart
        $check_query = "SELECT id, quantity FROM " . $this->table_name . " 
                        WHERE user_id = :user_id AND shoe_id = :shoe_id AND size_id = :size_id LIMIT 1";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':shoe_id', $shoe_id, PDO::PARAM_INT);
        $stmt->bindParam(':size_id', $size_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Update quantity
            $row = $stmt->fetch();
            $new_qty = $row['quantity'] + $qty;
            
            $update_query = "UPDATE " . $this->table_name . " SET quantity = :qty WHERE id = :id";
            $up_stmt = $this->conn->prepare($update_query);
            $up_stmt->bindParam(':qty', $new_qty, PDO::PARAM_INT);
            $up_stmt->bindParam(':id', $row['id'], PDO::PARAM_INT);
            return $up_stmt->execute();
        } else {
            // New insert
            $insert_query = "INSERT INTO " . $this->table_name . " (user_id, shoe_id, size_id, quantity) 
                             VALUES (:user_id, :shoe_id, :size_id, :qty)";
            $ins_stmt = $this->conn->prepare($insert_query);
            $ins_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $ins_stmt->bindParam(':shoe_id', $shoe_id, PDO::PARAM_INT);
            $ins_stmt->bindParam(':size_id', $size_id, PDO::PARAM_INT);
            $ins_stmt->bindParam(':qty', $qty, PDO::PARAM_INT);
            return $ins_stmt->execute();
        }
    }

    /**
     * Delete from cart
     */
    public function removeItem($cart_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $cart_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
