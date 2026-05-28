<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

namespace Models;

use PDO;
use Exception;

class Order {
    private $conn;
    private $table_name = "orders";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Complete relational Checkout placement inside a transaction block
     */
    public function checkout($user_id, $shipping_address_id, $payment_method, $cart_items, $coupon_id = null) {
        $this->conn->beginTransaction();

        try {
            // 1. Calculate subtotal, verify stock counts
            $subtotal = 0;
            $items_to_save = [];

            foreach ($cart_items as $item) {
                // Fetch physical price
                $shoe_query = "SELECT price, discount_price, name FROM shoes WHERE id = :shoe_id";
                $shoe_stmt = $this->conn->prepare($shoe_query);
                $shoe_stmt->bindParam(':shoe_id', $item['shoe_id']);
                $shoe_stmt->execute();
                $shoe = $shoe_stmt->fetch();

                if (!$shoe) {
                    throw new Exception("Shoe ID " . $item['shoe_id'] . " does not exist.");
                }

                // Verify stock
                $size_query = "SELECT stock_quantity, size FROM shoe_sizes WHERE id = :size_id FOR UPDATE";
                $size_stmt = $this->conn->prepare($size_query);
                $size_stmt->bindParam(':size_id', $item['size_id']);
                $size_stmt->execute();
                $size = $size_stmt->fetch();

                if (!$size || $size['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for model " . $shoe['name'] . " size " . ($size ? $size['size'] : 'N/A'));
                }

                // Deduct stock quantity
                $deduct_query = "UPDATE shoe_sizes SET stock_quantity = stock_quantity - :qty WHERE id = :size_id";
                $deduct_stmt = $this->conn->prepare($deduct_query);
                $deduct_stmt->bindParam(':qty', $item['quantity'], PDO::PARAM_INT);
                $deduct_stmt->bindParam(':size_id', $item['size_id'], PDO::PARAM_INT);
                $deduct_stmt->execute();

                $unit_price = $shoe['discount_price'] !== null ? $shoe['discount_price'] : $shoe['price'];
                $subtotal += $unit_price * $item['quantity'];

                $items_to_save[] = [
                    'shoe_id' => $item['shoe_id'],
                    'size_id' => $item['size_id'],
                    'quantity' => $item['quantity'],
                    'price' => $unit_price
                ];
            }

            // Coupon discounts calculation
            $discount = 0;
            if ($coupon_id) {
                $coupon_query = "SELECT discount_percent FROM coupons WHERE id = :coupon_id AND active = TRUE";
                $coupon_stmt = $this->conn->prepare($coupon_query);
                $coupon_stmt->bindParam(':coupon_id', $coupon_id);
                $coupon_stmt->execute();
                $coupon = $coupon_stmt->fetch();
                if ($coupon) {
                    $discount = $subtotal * ($coupon['discount_percent'] / 100);
                }
            }

            $shipping = $subtotal > 150 ? 0.00 : 15.00;
            $final_total = $subtotal - $discount + $shipping;

            // 2. Insert primary Order
            $order_query = "INSERT INTO " . $this->table_name . " 
                            (user_id, total_price, shipping_price, payment_method, status, shipping_address_id, coupon_id) 
                            VALUES (:user_id, :total, :shipping, :pay_method, 'confirmed', :address_id, :coupon_id)";
            
            $order_stmt = $this->conn->prepare($order_query);
            $order_stmt->bindParam(':user_id', $user_id);
            $order_stmt->bindParam(':total', $final_total);
            $order_stmt->bindParam(':shipping', $shipping);
            $order_stmt->bindParam(':pay_method', $payment_method);
            $order_stmt->bindParam(':address_id', $shipping_address_id);
            $order_stmt->bindParam(':coupon_id', $coupon_id);
            $order_stmt->execute();

            $order_id = $this->conn->lastInsertId();

            // 3. Clear customer's carts
            $clear_cart = "DELETE FROM cart WHERE user_id = :user_id";
            $clear_stmt = $this->conn->prepare($clear_cart);
            $clear_stmt->bindParam(':user_id', $user_id);
            $clear_stmt->execute();

            // 4. Save items to order items table
            foreach ($items_to_save as $itm) {
                $item_query = "INSERT INTO order_items (order_id, shoe_id, size_id, quantity, price) 
                               VALUES (:order_id, :shoe_id, :size_id, :qty, :price)";
                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->bindParam(':order_id', $order_id);
                $item_stmt->bindParam(':shoe_id', $itm['shoe_id']);
                $item_stmt->bindParam(':size_id', $itm['size_id']);
                $item_stmt->bindParam(':qty', $itm['quantity']);
                $item_stmt->bindParam(':price', $itm['price']);
                $item_stmt->execute();
            }

            // 5. Establish payment records
            $txn_ref = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
            $pay_query = "INSERT INTO payments (order_id, amount, payment_method, payment_status, transaction_reference) 
                          VALUES (:order_id, :amount, :pay_method, 'completed', :ref)";
            $pay_stmt = $this->conn->prepare($pay_query);
            $pay_stmt->bindParam(':order_id', $order_id);
            $pay_stmt->bindParam(':amount', $final_total);
            $pay_stmt->bindParam(':pay_method', $payment_method);
            $pay_stmt->bindParam(':ref', $txn_ref);
            $pay_stmt->execute();

            $this->conn->commit();
            return $order_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /**
     * Get user history lists
     */
    public function getHistory($user_id, $is_admin = false) {
        $query = "SELECT o.*, u.full_name as buyer_name, u.email as buyer_email 
                  FROM " . $this->table_name . " o
                  LEFT JOIN users u ON o.user_id = u.id";
        
        if (!$is_admin) {
            $query .= " WHERE o.user_id = :user_id";
        }
        $query .= " ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($query);
        if (!$is_admin) {
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt;
    }
}
