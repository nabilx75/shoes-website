<?php
/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

namespace Models;

use PDO;

class Shoe {
    private $conn;
    private $table_name = "shoes";

    // Properties
    public $id;
    public $name;
    public $brand_id;
    public $category_id;
    public $description;
    public $price;
    public $discount_price;
    public $gender;
    public $color;
    public $material;
    public $stock;
    public $rating_average;
    public $featured;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Read shoes with filters and sorting
     */
    public function readFiltered($params) {
        $query = "SELECT s.*, b.name as brand_name, c.name as category_name,
                  (SELECT image_url FROM shoe_images WHERE shoe_id = s.id AND is_primary = TRUE LIMIT 1) as primary_image
                  FROM " . $this->table_name . " s
                  LEFT JOIN brands b ON s.brand_id = b.id
                  LEFT JOIN categories c ON s.category_id = c.id
                  WHERE s.is_active = TRUE";

        $conditions = [];
        $bind_values = [];

        // Apply filters
        if (!empty($params['brand_id'])) {
            $conditions[] = "s.brand_id = :brand_id";
            $bind_values[':brand_id'] = $params['brand_id'];
        }
        if (!empty($params['category_id'])) {
            $conditions[] = "s.category_id = :category_id";
            $bind_values[':category_id'] = $params['category_id'];
        }
        if (!empty($params['gender'])) {
            $conditions[] = "s.gender = :gender";
            $bind_values[':gender'] = $params['gender'];
        }
        if (!empty($params['color'])) {
            $conditions[] = "s.color LIKE :color";
            $bind_values[':color'] = '%' . $params['color'] . '%';
        }
        if (isset($params['min_price'])) {
            $conditions[] = "COALESCE(s.discount_price, s.price) >= :min_price";
            $bind_values[':min_price'] = $params['min_price'];
        }
        if (isset($params['max_price'])) {
            $conditions[] = "COALESCE(s.discount_price, s.price) <= :max_price";
            $bind_values[':max_price'] = $params['max_price'];
        }

        if (count($conditions) > 0) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        // Apply Sorting
        $sort = isset($params['sort']) ? $params['sort'] : 'newest';
        switch ($sort) {
            case 'price_low_high':
                $query .= " ORDER BY COALESCE(s.discount_price, s.price) ASC";
                break;
            case 'price_high_low':
                $query .= " ORDER BY COALESCE(s.discount_price, s.price) DESC";
                break;
            case 'best_selling':
                // Subquery or simplified ordering: in active shop orders_items count decides best sellers
                $query .= " ORDER BY s.rating_average DESC, s.created_at DESC";
                break;
            case 'newest':
            default:
                $query .= " ORDER BY s.created_at DESC";
                break;
        }

        // Pagination
        $limit = isset($params['limit']) ? (int)$params['limit'] : 12;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;
        $query .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);

        // Bind basic conditions
        foreach ($bind_values as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        // Bind limit and offset
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt;
    }

    /**
     * Get specific details including sizes, reviews, images
     */
    public function getDetails($id) {
        $query = "SELECT s.*, b.name as brand_name, c.name as category_name 
                  FROM " . $this->table_name . " s
                  LEFT JOIN brands b ON s.brand_id = b.id
                  LEFT JOIN categories c ON s.category_id = c.id
                  WHERE s.id = :id AND s.is_active = TRUE LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $shoe = $stmt->fetch();
        if (!$shoe) return null;

        // Fetch sizes
        $sizes_query = "SELECT * FROM shoe_sizes WHERE shoe_id = :shoe_id ORDER BY size ASC";
        $sizes_stmt = $this->conn->prepare($sizes_query);
        $sizes_stmt->bindParam(':shoe_id', $id, PDO::PARAM_INT);
        $sizes_stmt->execute();
        $shoe['sizes'] = $sizes_stmt->fetchAll();

        // Fetch images
        $imgs_query = "SELECT * FROM shoe_images WHERE shoe_id = :shoe_id ORDER BY is_primary DESC";
        $imgs_stmt = $this->conn->prepare($imgs_query);
        $imgs_stmt->bindParam(':shoe_id', $id, PDO::PARAM_INT);
        $imgs_stmt->execute();
        $shoe['images'] = $imgs_stmt->fetchAll();

        return $shoe;
    }
}
