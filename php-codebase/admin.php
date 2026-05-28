<?php
/**
 * StrideHub - Premium Admin Control Panel
 * Comprehensive ledger for Orders, Users Directory, and Customized Sneakers Inventory.
 */
require_once __DIR__ . '/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check logged in user
$currentUser = null;
if (isset($_SESSION['user_id'])) {
    $currentUser = [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Admin User',
        'email' => $_SESSION['user_email'] ?? 'admin@stridehub.com',
        'role' => $_SESSION['user_role'] ?? 'admin'
    ];
}

// Redirect or warn if not logged in or not admin
if (!$currentUser || $currentUser['role'] !== 'admin') {
    if (!isset($_GET['demo'])) {
        header("Location: login.php?msg=" . urlencode("Admin privileges required. Sign in or add ?demo=1 to current URL to bypass."));
        exit();
    }
    // Set a dummy admin session for demo/bypass testing
    $currentUser = [
        'id' => 1,
        'name' => 'StrideHub Demo Admin',
        'email' => 'admin@stridehub.com',
        'role' => 'admin'
    ];
    // Persist to session so subsequent AJAX / upload endpoints recognize this administrator session
    $_SESSION['user_id'] = $currentUser['id'];
    $_SESSION['user_name'] = $currentUser['name'];
    $_SESSION['user_email'] = $currentUser['email'];
    $_SESSION['user_role'] = $currentUser['role'];
}

$db = getDBConnection();
$using_fallback = ($db === null);

// --- Persistent Fallbacks in Sessions so Demo CRUD is fully stateful even without MySQL connection ---
if (!isset($_SESSION['demo_users'])) {
    $_SESSION['demo_users'] = [
        ['id' => 1, 'full_name' => 'System Administrator', 'email' => 'admin@stridehub.com', 'role' => 'admin', 'status' => 'active', 'phone' => '1-800-ADMIN', 'created_at' => '2026-01-01'],
        ['id' => 2, 'full_name' => 'John Doe', 'email' => 'customer@stridehub.com', 'role' => 'customer', 'status' => 'active', 'phone' => '555-0199', 'created_at' => '2026-05-10'],
        ['id' => 3, 'full_name' => 'Jane Vance', 'email' => 'janev@example.com', 'role' => 'customer', 'status' => 'blocked', 'phone' => '555-5678', 'created_at' => '2026-05-18']
    ];
}

if (!isset($_SESSION['demo_orders'])) {
    $_SESSION['demo_orders'] = [
        ['id' => 7041, 'user_id' => 2, 'buyer_name' => 'John Doe', 'buyer_email' => 'customer@stridehub.com', 'total_price' => 159.00, 'payment_method' => 'credit_card', 'status' => 'delivered', 'created_at' => '2026-05-10 14:32:00', 'items_summary' => 'Velocity Aether Max (Size 10) x1'],
        ['id' => 7042, 'user_id' => 3, 'buyer_name' => 'Jane Vance', 'buyer_email' => 'janev@example.com', 'total_price' => 189.99, 'payment_method' => 'paypal', 'status' => 'pending', 'created_at' => '2026-05-24 09:12:00', 'items_summary' => 'Velocity Aether Max (Size 9) x1']
    ];
}

if (!isset($_SESSION['demo_shoes'])) {
    $_SESSION['demo_shoes'] = [
        ['id' => 1, 'name' => 'Velocity Aether Max', 'brand_id' => 1, 'brand_name' => 'Nike', 'category_id' => 1, 'category_name' => 'Running', 'description' => 'Ultra-comfortable specialized running foam cushion with responsive carbon plates for speed.', 'price' => 189.99, 'discount_price' => 159.00, 'gender' => 'men', 'color' => 'Crimson Red', 'material' => 'Flyknit Mesh', 'stock' => 24, 'rating_average' => 4.85, 'featured' => 1, 'is_active' => 1, 'primary_image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80'],
        ['id' => 2, 'name' => 'Classic Heritage Low', 'brand_id' => 2, 'brand_name' => 'Adidas', 'category_id' => 4, 'category_name' => 'Casual', 'description' => 'Sleek low-cut leather icon with comfortable cupsole.', 'price' => 95.00, 'discount_price' => null, 'gender' => 'unisex', 'color' => 'Cloud White', 'material' => 'Genuine Leather', 'stock' => 45, 'rating_average' => 4.70, 'featured' => 0, 'is_active' => 1, 'primary_image' => 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?auto=format&fit=crop&w=600&q=80']
    ];
}

// Preset Brands & Categories fallback
$brands_opt = [
    ['id' => 1, 'name' => 'Nike'],
    ['id' => 2, 'name' => 'Adidas'],
    ['id' => 3, 'name' => 'Puma'],
    ['id' => 4, 'name' => 'New Balance'],
    ['id' => 5, 'name' => 'Jordan']
];
$categories_opt = [
    ['id' => 1, 'name' => 'Running'],
    ['id' => 2, 'name' => 'Basketball'],
    ['id' => 3, 'name' => 'Sneakers'],
    ['id' => 4, 'name' => 'Casual'],
    ['id' => 5, 'name' => 'Boots']
];

$success_message = "";
$error_message = "";
$active_tab = isset($_POST['current_tab']) ? $_POST['current_tab'] : (isset($_GET['tab']) ? $_GET['tab'] : 'summary');

// --- ACTION ROUTER & CONTROLLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. UPDATE ORDER STATUS
    if ($action === 'update_order_status') {
        $oid = (int)$_POST['order_id'];
        $status = $_POST['status'] ?? 'pending';
        
        if (!$using_fallback) {
            try {
                $stmt = $db->prepare("UPDATE orders SET status = :status WHERE id = :id");
                $stmt->execute([':status' => $status, ':id' => $oid]);
                $success_message = "Order #SH-{$oid} successfully set to " . strtoupper($status) . ".";
            } catch (PDOException $e) { $error_message = "Database error: " . $e->getMessage(); }
        } else {
            foreach ($_SESSION['demo_orders'] as &$ord) {
                if ($ord['id'] === $oid) {
                    $ord['status'] = $status;
                    $success_message = "[Demo Mode] Order #SH-{$oid} successfully set to " . strtoupper($status) . ".";
                    break;
                }
            }
        }
    }

    // 2. TOGGLE USER STATUS (active/blocked)
    elseif ($action === 'toggle_user_status') {
        $uid = (int)$_POST['user_id'];
        $status = $_POST['status'] === 'blocked' ? 'active' : 'blocked';

        if ($uid === (int)($currentUser['id'] ?? 1)) {
            $error_message = "Integrity Warning: You cannot suspend your own session.";
        } else {
            if (!$using_fallback) {
                try {
                    $stmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
                    $stmt->execute([':status' => $status, ':id' => $uid]);
                    $success_message = "Account status successfully updated.";
                } catch (PDOException $e) { $error_message = "Database error: " . $e->getMessage(); }
            } else {
                foreach ($_SESSION['demo_users'] as &$u) {
                    if ($u['id'] === $uid) {
                        $u['status'] = $status;
                        $success_message = "[Demo Mode] Account status updated.";
                        break;
                    }
                }
            }
        }
    }

    // 3. REMOVE USER
    elseif ($action === 'delete_user') {
        $uid = (int)$_POST['user_id'];

        if ($uid === (int)($currentUser['id'] ?? 0)) {
            $error_message = "Integrity Warning: You cannot delete your current session.";
        } else {
            if (!$using_fallback) {
                try {
                    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
                    $stmt->execute([':id' => $uid]);
                    $success_message = "User account removed completely.";
                } catch (PDOException $e) { $error_message = "Cannot delete: User has order history or address linked in DB."; }
            } else {
                $_SESSION['demo_users'] = array_filter($_SESSION['demo_users'], function($u) use ($uid) {
                    return $u['id'] !== $uid;
                });
                $success_message = "[Demo Mode] User account removed completely.";
            }
        }
    }

    // 4. ADD NEW USER
    elseif ($action === 'add_user') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'] ?? 'active';
        $pass = $_POST['password'] ?? 'password';

        if (empty($name) || empty($email)) {
            $error_message = "Name and Email are required fields.";
        } else {
            if (!$using_fallback) {
                try {
                    $hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
                    $stmt = $db->prepare("INSERT INTO users (full_name, email, password, phone, role, status, created_at) VALUES (:name, :email, :pass, :phone, :role, :status, NOW())");
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':pass' => $hashed_pass,
                        ':phone' => $phone,
                        ':role' => $role,
                        ':status' => $status
                    ]);
                    $success_message = "Master account for '{$name}' registered successfully.";
                } catch (PDOException $e) { $error_message = "E-mail already exists in database registry."; }
            } else {
                $new_id = time();
                $_SESSION['demo_users'][] = [
                    'id' => $new_id,
                    'full_name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'status' => $status,
                    'phone' => $phone,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $success_message = "[Demo Mode] Master account registered successfully.";
            }
        }
    }

    // 5. UPDATE EXISTING USER DETAIL (EDIT)
    elseif ($action === 'edit_user') {
        $uid = (int)$_POST['user_id'];
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? 'customer';
        $status = $_POST['status'] ?? 'active';

        if (empty($name) || empty($email)) {
            $error_message = "Name and Email are required.";
        } else {
            if (!$using_fallback) {
                try {
                    $stmt = $db->prepare("UPDATE users SET full_name = :name, email = :email, phone = :phone, role = :role, status = :status WHERE id = :id");
                    $stmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':role' => $role,
                        ':status' => $status,
                        ':id' => $uid
                    ]);
                    $success_message = "Account information saved.";
                } catch (PDOException $e) { $error_message = "Error: " . $e->getMessage(); }
            } else {
                foreach ($_SESSION['demo_users'] as &$u) {
                    if ($u['id'] === $uid) {
                        $u['full_name'] = $name;
                        $u['email'] = $email;
                        $u['phone'] = $phone;
                        $u['role'] = $role;
                        $u['status'] = $status;
                        $success_message = "[Demo Mode] Account information saved.";
                        break;
                    }
                }
            }
        }
    }

    // 6. DELETE SNEAKER PRODUCT
    elseif ($action === 'delete_shoe') {
        $sid = (int)$_POST['shoe_id'];

        if (!$using_fallback) {
            try {
                $stmt = $db->prepare("DELETE FROM shoes WHERE id = :id");
                $stmt->execute([':id' => $sid]);
                $success_message = "Sneaker deleted from the system.";
            } catch (PDOException $e) { $error_message = "Cannot delete: Sneaker has active purchases in the order items log."; }
        } else {
            $_SESSION['demo_shoes'] = array_filter($_SESSION['demo_shoes'], function($shoe) use ($sid) {
                return $shoe['id'] !== $sid;
            });
            $success_message = "[Demo Mode] Sneaker deleted.";
        }
    }

    // 7. ADD NEW SNEAKER
    elseif ($action === 'add_shoe') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $brand_id = (int)($_POST['brand_id'] ?? 1);
        $category_id = (int)($_POST['category_id'] ?? 1);
        $price = (float)($_POST['price'] ?? 0);
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $gender = $_POST['gender'] ?? 'unisex';
        $color = trim($_POST['color'] ?? '');
        $material = trim($_POST['material'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        
        $image_urls = isset($_POST['image_urls']) ? $_POST['image_urls'] : [];
        $image_urls = array_filter(array_map('trim', $image_urls));
        
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || $price <= 0) {
            $error_message = "Sneaker Name and a valid positive Price are required.";
        } elseif (count($image_urls) < 5) {
            $error_message = "An administrator must insert at least 5 pictures of the shoe product.";
        } else {
            $primary_image = $image_urls[0];
            if (!$using_fallback) {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO shoes (name, brand_id, category_id, description, price, discount_price, gender, color, material, stock, featured, is_active, rating_average, created_at) 
                        VALUES (:name, :brand, :category, :description, :price, :discount, :gender, :color, :material, :stock, :featured, :active, 5.00, NOW())
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':brand' => $brand_id,
                        ':category' => $category_id,
                        ':description' => $description,
                        ':price' => $price,
                        ':discount' => $discount_price,
                        ':gender' => $gender,
                        ':color' => $color,
                        ':material' => $material,
                        ':stock' => $stock,
                        ':featured' => $featured,
                        ':active' => $is_active
                    ]);
                    $new_shoe_id = $db->lastInsertId();

                    // Seed primary and other images
                    $stmt_img = $db->prepare("INSERT INTO shoe_images (shoe_id, image_url, is_primary) VALUES (:shoe_id, :url, :is_prim)");
                    foreach ($image_urls as $idx => $url) {
                        $stmt_img->execute([
                            ':shoe_id' => $new_shoe_id,
                            ':url' => $url,
                            ':is_prim' => ($idx === 0 ? 1 : 0)
                        ]);
                    }

                    // Seed sizes for catalog checkout
                    $std_sizes = ['8', '9', '10', '11'];
                    foreach ($std_sizes as $sz) {
                        $stmt_sz = $db->prepare("INSERT INTO shoe_sizes (shoe_id, size, stock_quantity) VALUES (:shoe_id, :size, :qty)");
                        $stmt_sz->execute([
                            ':shoe_id' => $new_shoe_id,
                            ':size' => $sz,
                            ':qty' => (int)($stock / 4)
                        ]);
                    }

                    $success_message = "Product '{$name}' created with " . count($image_urls) . " product images.";
                } catch (PDOException $e) { $error_message = "Database Error: " . $e->getMessage(); }
            } else {
                // Find brand name and category name options
                $b_name = "Brand";
                foreach ($brands_opt as $bo) { if ($bo['id'] == $brand_id) $b_name = $bo['name']; }
                $c_name = "Category";
                foreach ($categories_opt as $co) { if ($co['id'] == $category_id) $c_name = $co['name']; }

                $new_id = time();
                $_SESSION['demo_shoes'][] = [
                    'id' => $new_id,
                    'name' => $name,
                    'brand_id' => $brand_id,
                    'brand_name' => $b_name,
                    'category_id' => $category_id,
                    'category_name' => $c_name,
                    'description' => $description,
                    'price' => $price,
                    'discount_price' => $discount_price,
                    'gender' => $gender,
                    'color' => $color,
                    'material' => $material,
                    'stock' => $stock,
                    'rating_average' => 5.00,
                    'featured' => $featured,
                    'is_active' => $is_active,
                    'primary_image' => $primary_image,
                    'images' => array_values($image_urls)
                ];
                $success_message = "[Demo Mode] Product added with " . count($image_urls) . " images.";
            }
        }
    }

    // 8. EDIT SNEAKER
    elseif ($action === 'edit_shoe') {
        $sid = (int)$_POST['shoe_id'];
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $brand_id = (int)($_POST['brand_id'] ?? 1);
        $category_id = (int)($_POST['category_id'] ?? 1);
        $price = (float)($_POST['price'] ?? 0);
        $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
        $gender = $_POST['gender'] ?? 'unisex';
        $color = trim($_POST['color'] ?? '');
        $material = trim($_POST['material'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        
        $image_urls = isset($_POST['image_urls']) ? $_POST['image_urls'] : [];
        $image_urls = array_filter(array_map('trim', $image_urls));
        
        $featured = isset($_POST['featured']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($name) || $price <= 0) {
            $error_message = "Sneaker Name and a valid Price are required.";
        } elseif (count($image_urls) < 5) {
            $error_message = "An administrator must insert at least 5 pictures of the shoe product.";
        } else {
            $primary_image = $image_urls[0];
            if (!$using_fallback) {
                try {
                    $stmt = $db->prepare("
                        UPDATE shoes SET name = :name, brand_id = :brand, category_id = :category, description = :description, 
                        price = :price, discount_price = :discount, gender = :gender, color = :color, material = :material, 
                        stock = :stock, featured = :featured, is_active = :active WHERE id = :id
                    ");
                    $stmt->execute([
                        ':name' => $name,
                        ':brand' => $brand_id,
                        ':category' => $category_id,
                        ':description' => $description,
                        ':price' => $price,
                        ':discount' => $discount_price,
                        ':gender' => $gender,
                        ':color' => $color,
                        ':material' => $material,
                        ':stock' => $stock,
                        ':featured' => $featured,
                        ':active' => $is_active,
                        ':id' => $sid
                    ]);

                    // Completely update the shoe images by deletion and re-insertion (purely relational cascade)
                    $stmt_del_imgs = $db->prepare("DELETE FROM shoe_images WHERE shoe_id = :uid");
                    $stmt_del_imgs->execute([':uid' => $sid]);

                    $stmt_img = $db->prepare("INSERT INTO shoe_images (shoe_id, image_url, is_primary) VALUES (:shoe_id, :url, :is_prim)");
                    foreach ($image_urls as $idx => $url) {
                        $stmt_img->execute([
                            ':shoe_id' => $sid,
                            ':url' => $url,
                            ':is_prim' => ($idx === 0 ? 1 : 0)
                        ]);
                    }

                    $success_message = "Product details and " . count($image_urls) . " images saved.";
                } catch (PDOException $e) { $error_message = "Database Error: " . $e->getMessage(); }
            } else {
                // Read names
                $b_name = "Brand";
                foreach ($brands_opt as $bo) { if ($bo['id'] == $brand_id) $b_name = $bo['name']; }
                $c_name = "Category";
                foreach ($categories_opt as $co) { if ($co['id'] == $category_id) $c_name = $co['name']; }

                foreach ($_SESSION['demo_shoes'] as &$shoe) {
                    if ($shoe['id'] === $sid) {
                        $shoe['name'] = $name;
                        $shoe['brand_id'] = $brand_id;
                        $shoe['brand_name'] = $b_name;
                        $shoe['category_id'] = $category_id;
                        $shoe['category_name'] = $c_name;
                        $shoe['description'] = $description;
                        $shoe['price'] = $price;
                        $shoe['discount_price'] = $discount_price;
                        $shoe['gender'] = $gender;
                        $shoe['color'] = $color;
                        $shoe['material'] = $material;
                        $shoe['stock'] = $stock;
                        $shoe['featured'] = $featured;
                        $shoe['is_active'] = $is_active;
                        $shoe['primary_image'] = $primary_image;
                        $shoe['images'] = array_values($image_urls);
                        $success_message = "[Demo Mode] Details with " . count($image_urls) . " images saved.";
                        break;
                    }
                }
            }
        }
    }
}

// --- LOAD VIEW DATA ---
$users = [];
$orders = [];
$shoes = [];
$brands = $brands_opt;
$categories = $categories_opt;

$stats = [
    'revenue' => 0.00,
    'users_count' => 0,
    'orders_count' => 0,
    'shoes_count' => 0,
];

if (!$using_fallback) {
    try {
        $users = $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
        $db_orders = $db->query("
            SELECT o.*, u.full_name as buyer_name, u.email as buyer_email 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC
        ")->fetchAll();
        
        // Enrich orders list with item summary
        foreach ($db_orders as $ord) {
            $items_stmt = $db->prepare("
                SELECT oi.quantity, s.name, ss.size 
                FROM order_items oi
                LEFT JOIN shoes s ON oi.shoe_id = s.id 
                LEFT JOIN shoe_sizes ss ON oi.size_id = ss.id
                WHERE oi.order_id = :id
            ");
            $items_stmt->execute([':id' => $ord['id']]);
            $items = $items_stmt->fetchAll();
            $sum_parts = [];
            foreach ($items as $itm) {
                $sum_parts[] = "{$itm['name']} (Size {$itm['size']}) x{$itm['quantity']}";
            }
            $ord['items_summary'] = $sum_parts ? implode(', ', $sum_parts) : 'Custom Sneaker lines';
            $orders[] = $ord;
        }

        $shoes = $db->query("
            SELECT s.*, b.name as brand_name, c.name as category_name 
            FROM shoes s 
            LEFT JOIN brands b ON s.brand_id = b.id 
            LEFT JOIN categories c ON s.category_id = c.id 
            ORDER BY s.id DESC
        ")->fetchAll();

        foreach ($shoes as &$shoe) {
            $img_stmt = $db->prepare("SELECT image_url, is_primary FROM shoe_images WHERE shoe_id = :id ORDER BY is_primary DESC, id ASC");
            $img_stmt->execute([':id' => $shoe['id']]);
            $sho_imgs = $img_stmt->fetchAll();
            
            $shoe_images_arr = [];
            foreach ($sho_imgs as $img) {
                $shoe_images_arr[] = $img['image_url'];
            }
            $shoe['images'] = $shoe_images_arr;
            $shoe['primary_image'] = !empty($shoe_images_arr) ? $shoe_images_arr[0] : 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80';
        }

        $brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
        $categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

        // Calculate actual stats
        $stats['revenue'] = $db->query("SELECT SUM(total_price) FROM orders WHERE status != 'cancelled'")->fetchColumn() ?? 0.00;
        $stats['users_count'] = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['orders_count'] = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $stats['shoes_count'] = $db->query("SELECT COUNT(*) FROM shoes")->fetchColumn();

    } catch (PDOException $e) {
        $using_fallback = true;
    }
}

if ($using_fallback) {
    $users = $_SESSION['demo_users'];
    $orders = $_SESSION['demo_orders'];
    $shoes = $_SESSION['demo_shoes'];
    
    // Fallback metrics stats
    $rev = 0.00;
    foreach ($orders as $o) {
        if ($o['status'] !== 'cancelled') {
            $rev += (float)$o['total_price'];
        }
    }
    $stats['revenue'] = $rev;
    $stats['users_count'] = count($users);
    $stats['orders_count'] = count($orders);
    $stats['shoes_count'] = count($shoes);
}

require_once __DIR__ . '/header.php';
?>

<div class="max-w-7xl mx-auto px-6 py-12">
    <!-- FLASH COMPONENT TOASTS -->
    <?php if (!empty($success_message)): ?>
        <div class="bg-emerald-950/40 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl mb-8 flex items-center gap-3 animate-fade-in text-xs font-bold uppercase tracking-wider">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="bg-red-950/40 border border-red-500/30 text-red-400 p-4 rounded-xl mb-8 flex items-center gap-3 animate-fade-in text-xs font-bold uppercase tracking-wider">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <!-- HEADER TITLE BANNER -->
    <div class="border-l-4 border-[#FF4E00] pl-6 mb-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
            <h1 class="text-3xl md:text-5xl font-black uppercase tracking-tight text-white flex items-center gap-3">
                ADMIN COMMANDS
                <?php if ($using_fallback): ?>
                    <span class="bg-amber-500/10 border border-amber-500/35 text-amber-400 text-[10px] px-2.5 py-1 rounded font-black tracking-widest block">SANDBOX DEVICING</span>
                <?php else: ?>
                    <span class="bg-[#FF4E00]/10 border border-[#FF4E00]/30 text-[#FF4E00] text-[10px] px-2.5 py-1 rounded font-black tracking-widest block">SQL LIVE DATABASE</span>
                <?php endif; ?>
            </h1>
            <p class="text-[10px] text-white/40 uppercase tracking-widest mt-1.5 font-bold">Manage system orders, user directory files, and customizable footwear collections</p>
        </div>
    </div>

    <!-- MAIN GRID CONTAINER -->
    <div class="flex flex-col lg:flex-row gap-10">
        
        <!-- SIDE ACTIONS NAVIGATION PANEL -->
        <div class="w-full lg:w-64 shrink-0 space-y-2">
            <div class="bg-black/80 border border-white/10 rounded-2xl p-4 space-y-1.5">
                <span class="text-[9px] uppercase tracking-widest text-[#FF4E00] font-black block px-3.5 mb-2">Systems Directory</span>
                
                <button onclick="switchTab('summary')" id="tabBtn-summary" class="admin-tab-btn block w-full text-left font-black text-xs tracking-widest uppercase py-3 px-4 rounded-xl transition-all flex items-center justify-between">
                    <span>Metrics Overview</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" /></svg>
                </button>

                <button onclick="switchTab('orders')" id="tabBtn-orders" class="admin-tab-btn block w-full text-left font-black text-xs tracking-widest uppercase py-3 px-4 rounded-xl transition-all flex items-center justify-between">
                    <span>Manage Orders</span>
                    <span class="bg-[#FF4E00]/10 text-[#FF4E00] text-[9px] font-bold px-2 py-0.5 rounded-full border border-[#FF4E00]/25"><?= count($orders) ?></span>
                </button>

                <button onclick="switchTab('users')" id="tabBtn-users" class="admin-tab-btn block w-full text-left font-black text-xs tracking-widest uppercase py-3 px-4 rounded-xl transition-all flex items-center justify-between">
                    <span>Customers Directory</span>
                    <span class="bg-white/5 text-white/50 text-[9px] font-bold px-2 py-0.5 rounded-full border border-white/10"><?= count($users) ?></span>
                </button>

                <button onclick="switchTab('products')" id="tabBtn-products" class="admin-tab-btn block w-full text-left font-black text-xs tracking-widest uppercase py-3 px-4 rounded-xl transition-all flex items-center justify-between">
                    <span>Sneakers Catalog</span>
                    <span class="bg-white/5 text-white/50 text-[9px] font-bold px-2 py-0.5 rounded-full border border-white/10"><?= count($shoes) ?></span>
                </button>
            </div>

            <!-- QUICK CREATE FOOTER -->
            <div class="bg-neutral-950/50 border border-white/5 p-4 rounded-2xl block text-center">
                <span class="text-[9px] font-bold text-white/30 uppercase block mb-3">Instant Creation</span>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="openAddShoeModal()" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white font-black text-[9px] tracking-wider uppercase py-2 px-1.5 rounded-lg transition-colors">
                        + Sneaker
                    </button>
                    <button onclick="openAddUserModal()" class="bg-white/10 hover:bg-white/20 text-white font-black text-[9px] tracking-wider uppercase py-2 px-1.5 rounded-lg border border-white/10 transition-colors">
                        + User
                    </button>
                </div>
            </div>
        </div>

        <!-- RIGHT TAB DYNAMIC VIEWER -->
        <div class="flex-grow">
            
            <!-- SECTION 1: METRICS OVERVIEW -->
            <div id="section-summary" class="admin-section space-y-8 hidden">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-black/60 border border-white/10 rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute -right-10 -bottom-10 w-28 h-28 bg-[#FF4E00]/5 rounded-full blur-2xl group-hover:scale-150 transition-transform"></div>
                        <span class="text-[9px] font-black uppercase text-white/40 tracking-widest block">Aggregate Revenue</span>
                        <span class="text-3xl font-black text-[#FF4E00] mt-3 block">$<?= number_format($stats['revenue'], 2) ?></span>
                    </div>
                    <div class="bg-black/60 border border-white/10 rounded-2xl p-5 relative overflow-hidden group">
                        <span class="text-[9px] font-black uppercase text-white/40 tracking-widest block">Active Users Registered</span>
                        <span class="text-3xl font-black text-white mt-3 block"><?= $stats['users_count'] ?></span>
                    </div>
                    <div class="bg-black/60 border border-white/10 rounded-2xl p-5 relative overflow-hidden group">
                        <span class="text-[9px] font-black uppercase text-white/40 tracking-widest block">Total Checkout Orders</span>
                        <span class="text-3xl font-black text-white mt-3 block"><?= $stats['orders_count'] ?></span>
                    </div>
                    <div class="bg-black/60 border border-white/10 rounded-2xl p-5 relative overflow-hidden group">
                        <span class="text-[9px] font-black uppercase text-white/40 tracking-widest block">Exclusive Styles Cataloged</span>
                        <span class="text-3xl font-black text-white mt-3 block"><?= $stats['shoes_count'] ?></span>
                    </div>
                </div>

                <div class="bg-black/40 border border-white/5 rounded-3xl p-8 relative">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#FF4E00]/5 rounded-full blur-3xl pointer-events-none"></div>
                    <h3 class="text-lg font-black uppercase tracking-wide text-white mb-3">Enterprise Dashboard Central</h3>
                    <p class="text-xs text-white/50 leading-relaxed max-w-2xl mb-6">Select from the left navigation panel to perform complete CRUD actions. You can track customer order statuses, register users with custom roles, modify existing products, adjust stocks, and list new exclusive footwear releases.</p>
                    
                    <div class="border-t border-white/5 pt-6 flex gap-8 items-center text-left">
                        <div>
                            <span class="text-[9px] uppercase tracking-wider block text-white/30 font-bold">SQL Integration</span>
                            <span class="text-[11px] font-bold text-white mt-1 block">PDO MySQL Connector Ready</span>
                        </div>
                        <div class="border-l border-white/5 pl-8">
                            <span class="text-[9px] uppercase tracking-wider block text-white/30 font-bold">Server Standard Time</span>
                            <span class="text-[11px] font-bold text-amber-500 mt-1 block font-mono"><?= date('Y-m-d H:i:s') ?> UTC</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ORDERS SECTION -->
            <div id="section-orders" class="admin-section space-y-6 hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-black/80 border border-white/5 p-4 rounded-2xl">
                    <div class="space-y-1">
                        <h2 class="text-lg font-black uppercase tracking-tight text-white leading-none">Checkout Orders Ledger</h2>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-white/40">Filter and control shipment lifecycles</span>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                        <!-- Search & filter inputs -->
                        <input type="text" id="orderSearch" onkeyup="filterOrders()" placeholder="Search buyer or #ID..." class="bg-neutral-900 border border-white/10 text-white placeholder-white/30 text-xs px-3.5 py-2 rounded-xl focus:outline-none focus:border-[#FF4E00] w-full md:w-44 uppercase tracking-widest font-bold">
                        <select id="orderStatusFilter" onchange="filterOrders()" class="bg-neutral-900 border border-white/10 text-white text-xs px-3.5 py-2 rounded-xl focus:outline-none tracking-widest uppercase font-bold text-xs">
                            <option value="all">ALL STATUSES</option>
                            <option value="pending">PENDING</option>
                            <option value="confirmed">CONFIRMED</option>
                            <option value="shipped">SHIPPED</option>
                            <option value="delivered">DELIVERED</option>
                            <option value="cancelled">CANCELLED</option>
                        </select>
                    </div>
                </div>

                <div class="bg-black/40 border border-white/5 rounded-2xl overflow-hidden p-2">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-white/10 text-white/40 text-[9px] tracking-widest uppercase font-black">
                                    <th class="py-4.5 px-4 font-black">ORDER ID</th>
                                    <th class="py-4.5 px-4 font-black">BUYER DETAILS</th>
                                    <th class="py-4.5 px-4 font-black">PURCHASES SUMMARY</th>
                                    <th class="py-4.5 px-4 font-black">TOTAL VALUE</th>
                                    <th class="py-4.5 px-4 font-black">STATUS STATE</th>
                                    <th class="py-4.5 px-4 font-black text-right">ACTION</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <?php foreach ($orders as $ord): ?>
                                    <tr class="order-row border-b border-white/5 hover:bg-white/5 transition-colors" data-id="<?= $ord['id'] ?>" data-buyer="<?= htmlspecialchars(strtolower($ord['buyer_name'] ?? '')) ?>" data-status="<?= htmlspecialchars($ord['status']) ?>">
                                        <td class="py-4 px-4 font-mono font-black text-[#FF4E00]">
                                            #SH-<?= $ord['id'] ?>
                                        </td>
                                        <td class="py-4 px-4">
                                            <div class="font-bold text-white uppercase"><?= htmlspecialchars($ord['buyer_name'] ?? 'Guest account') ?></div>
                                            <div class="text-[10px] text-white/40 lowercase select-all mt-0.5"><?= htmlspecialchars($ord['buyer_email'] ?? 'guest@stridehub.co') ?></div>
                                        </td>
                                        <td class="py-4 px-4 text-white/80 max-w-xs truncate" title="<?= htmlspecialchars($ord['items_summary']) ?>">
                                            <?= htmlspecialchars($ord['items_summary']) ?>
                                        </td>
                                        <td class="py-4 px-4 font-bold text-white text-[13px]">
                                            $<?= number_format($ord['total_price'], 2) ?>
                                        </td>
                                        <td class="py-4 px-4 font-bold uppercase text-[9px] tracking-widest">
                                            <?php if ($ord['status'] === 'delivered'): ?>
                                                <span class="px-2 py-1.5 rounded-lg bg-emerald-950/40 border border-emerald-500/25 text-emerald-400">Delivered</span>
                                            <?php elseif ($ord['status'] === 'shipped'): ?>
                                                <span class="px-2 py-1.5 rounded-lg bg-indigo-950/40 border border-indigo-500/25 text-indigo-400">Shipped</span>
                                            <?php elseif ($ord['status'] === 'confirmed'): ?>
                                                <span class="px-2 py-1.5 rounded-lg bg-amber-950/40 border border-amber-500/25 text-amber-400">Confirmed</span>
                                            <?php elseif ($ord['status'] === 'cancelled'): ?>
                                                <span class="px-2 py-1.5 rounded-lg bg-red-950/40 border border-red-500/25 text-red-400">Cancelled</span>
                                            <?php else: ?>
                                                <span class="px-2 py-1.5 rounded-lg bg-neutral-905/30 border border-white/10 text-white/50">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-4 text-right">
                                            <form action="" method="POST" class="flex gap-2.5 items-center justify-end">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                                <input type="hidden" name="current_tab" value="orders">

                                                <select name="status" class="bg-neutral-900 border border-white/10 text-white text-[9px] font-black px-2 py-2 focus:outline-none uppercase tracking-widest rounded-lg">
                                                    <option value="pending" <?= $ord['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="confirmed" <?= $ord['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                                    <option value="shipped" <?= $ord['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="delivered" <?= $ord['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="cancelled" <?= $ord['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                                
                                                <button type="submit" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-[8px] font-black px-3 py-2.5 uppercase tracking-widest rounded-lg transition-colors shadow-lg shadow-[#FF4E00]/10">
                                                    SAVE
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: USERS DIRECTORY -->
            <div id="section-users" class="admin-section space-y-6 hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-black/80 border border-white/5 p-4 rounded-2xl">
                    <div class="space-y-1">
                        <h2 class="text-lg font-black uppercase tracking-tight text-white leading-none">Accounts Directory Registry</h2>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-white/40">Register, modify, or block system accounts</span>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                        <input type="text" id="userSearch" onkeyup="filterUsers()" placeholder="Search user details..." class="bg-neutral-900 border border-white/10 text-white placeholder-white/30 text-xs px-3.5 py-2 rounded-xl focus:outline-none focus:border-[#FF4E00] w-full md:w-44 uppercase tracking-widest font-bold">
                        <button onclick="openAddUserModal()" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs font-black px-4 py-2 uppercase tracking-wider rounded-xl transition-colors shadow-lg shadow-[#FF4E00]/10">
                            + REGISTER USER
                        </button>
                    </div>
                </div>

                <div class="bg-black/60 border border-white/10 rounded-2xl p-4 md:p-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="usersGrid">
                        <?php foreach ($users as $u): ?>
                            <div class="user-card bg-neutral-950/60 border border-white/5 hover:border-white/20 p-4 rounded-2xl flex flex-col justify-between transition-all" data-name="<?= htmlspecialchars(strtolower($u['full_name'])) ?>" data-email="<?= htmlspecialchars(strtolower($u['email'])) ?>">
                                <div class="space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-0.5">
                                            <span class="text-xs font-black text-white uppercase block flex items-center gap-2">
                                                <?= htmlspecialchars($u['full_name']) ?>
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="bg-amber-500/10 border border-amber-500/40 text-[7px] text-amber-500 px-1.5 py-0.5 rounded tracking-widest font-black uppercase">ADMIN</span>
                                                <?php else: ?>
                                                    <span class="bg-blue-500/10 border border-blue-500/30 text-[7px] text-blue-400 px-1.5 py-0.5 rounded tracking-widest font-black uppercase">Customer</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="text-[10px] text-white/50 block select-all font-medium select-none text-white/40 lowercase"><?= htmlspecialchars($u['email']) ?></span>
                                            <?php if (!empty($u['phone'])): ?>
                                                <span class="text-[9px] text-white/30 font-mono block select-none">Phone: <?= htmlspecialchars($u['phone']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- User status Badge -->
                                        <?php if ($u['status'] === 'blocked'): ?>
                                            <span class="bg-red-950/40 border border-red-500/30 text-[7px] text-red-400 px-1.5 py-0.5 rounded-lg tracking-widest uppercase font-black">Suspended</span>
                                        <?php else: ?>
                                            <span class="bg-emerald-950/40 border border-emerald-500/30 text-[7px] text-emerald-400 px-1.5 py-0.5 rounded-lg tracking-widest uppercase font-black">Active</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-[9px] text-white/20 block select-none uppercase font-bold">REGISTRY NO: #00<?= $u['id'] ?></span>
                                </div>

                                <div class="border-t border-white/5 pt-4 mt-3 flex items-center justify-between gap-2 text-right">
                                    <div class="flex gap-2">
                                        <!-- Suspend toggle -->
                                        <?php if ($u['id'] !== (int)($currentUser['id'] ?? 0)): ?>
                                            <form action="" method="POST">
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <input type="hidden" name="status" value="<?= $u['status'] ?>">
                                                <input type="hidden" name="current_tab" value="users">
                                                <button type="submit" class="text-[9px] font-black uppercase tracking-wider py-1.5 px-3 rounded-lg border transition-all 
                                                    <?= $u['status'] === 'blocked' 
                                                        ? 'bg-red-500/10 border-red-500/30 text-red-500 hover:bg-red-500 hover:text-white' 
                                                        : 'bg-white/5 border-white/10 text-white/50 hover:bg-white/10 hover:border-white/20' ?>"
                                                >
                                                    <?= $u['status'] === 'blocked' ? 'UNBLOCK' : 'BLOCK' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Edit trigger -->
                                        <button 
                                            onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u)) ?>)"
                                            class="bg-white/5 border border-white/10 text-[9px] font-black text-white/60 uppercase tracking-wider py-1.5 px-3 rounded-lg hover:border-white hover:text-white transition-colors"
                                        >
                                            EDIT
                                        </button>
                                    </div>

                                    <!-- Delete button -->
                                    <?php if ($u['id'] !== (int)($currentUser['id'] ?? 0) && $u['role'] !== 'admin'): ?>
                                        <form action="" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently remove this customer account?');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="current_tab" value="users">
                                            <button type="submit" class="text-[9px] font-black text-red-500/70 hover:text-red-500 uppercase tracking-widest block font-bold transition-all p-1.5">
                                                REMOVE
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: SNEAKERS CATALOG -->
            <div id="section-products" class="admin-section space-y-6 hidden">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-black/80 border border-white/5 p-4 rounded-2xl">
                    <div class="space-y-1">
                        <h2 class="text-lg font-black uppercase tracking-tight text-white leading-none">Limited Sneakers Inventory</h2>
                        <span class="text-[9px] font-bold uppercase tracking-widest text-white/40">Edit pricing, adjust size stocks, and add customized footwear styles</span>
                    </div>
                    <div class="flex flex-wrap gap-2 items-center w-full md:w-auto">
                        <input type="text" id="shoeSearch" onkeyup="filterShoes()" placeholder="Search styles details..." class="bg-neutral-900 border border-white/10 text-white placeholder-white/30 text-xs px-3.5 py-2 rounded-xl focus:outline-none focus:border-[#FF4E00] w-full md:w-44 uppercase tracking-widest font-bold">
                        <button onclick="openAddShoeModal()" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs font-black px-4 py-2 uppercase tracking-wider rounded-xl transition-colors shadow-lg shadow-[#FF4E00]/10">
                            + NEW SNEAKER
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="shoesGrid">
                    <?php foreach ($shoes as $shoe): ?>
                        <?php 
                            $hasDiscount = !empty($shoe['discount_price']) && (float)$shoe['discount_price'] > 0;
                            $dispPrice = $hasDiscount ? $shoe['discount_price'] : $shoe['price'];
                            $origPrice = $shoe['price'];
                        ?>
                        <div class="shoe-card bg-black/80 border border-white/10 rounded-2xl overflow-hidden flex flex-col justify-between relative group hover:border-[#FF4E00]/40 transition-all" data-name="<?= htmlspecialchars(strtolower($shoe['name'])) ?>" data-color="<?= htmlspecialchars(strtolower($shoe['color'] ?? '')) ?>" data-brand="<?= htmlspecialchars(strtolower($shoe['brand_name'] ?? '')) ?>">
                            
                            <!-- Badges -->
                            <div class="absolute top-3 left-3 z-10 flex flex-col gap-1.5">
                                <span class="bg-black/60 border border-white/10 text-[8px] px-2 py-0.5 rounded font-black uppercase tracking-widest text-[#FF4E00]">
                                    <?= htmlspecialchars($shoe['brand_name'] ?? 'StrideHub') ?>
                                </span>
                                <?php if ($shoe['featured']): ?>
                                    <span class="bg-amber-500/10 border border-amber-500/40 text-[7px] px-2 py-0.5 rounded font-black uppercase tracking-widest text-amber-500">
                                        FEATURED
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="absolute top-3 right-3 z-10">
                                <?php if ($shoe['is_active']): ?>
                                    <span class="bg-emerald-950/40 border border-emerald-500/30 text-[7px] px-2 py-0.5 rounded font-black uppercase tracking-widest text-emerald-400">ACTIVE</span>
                                <?php else: ?>
                                    <span class="bg-red-950/40 border border-red-500/30 text-[7px] px-2 py-0.5 rounded font-black uppercase tracking-widest text-red-400">DRAFT</span>
                                <?php endif; ?>
                            </div>

                            <!-- Sneaker Thumbnail Layout -->
                            <div class="w-full bg-neutral-900 border-b border-white/5 relative aspect-square p-4 flex items-center justify-center">
                                <img 
                                    src="<?= htmlspecialchars($shoe['primary_image'] ?? 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=600&q=80') ?>" 
                                    alt="<?= htmlspecialchars($shoe['name']) ?>" 
                                    class="h-28 w-auto object-contain transform group-hover:scale-110 transition-transform duration-500"
                                    referrerpolicy="no-referrer"
                                >
                            </div>

                            <!-- Sneaker Specs Body -->
                            <div class="p-4 flex-grow flex flex-col justify-between space-y-4">
                                <div class="space-y-1">
                                    <span class="text-[8px] text-[#FF4E00]/80 font-bold uppercase tracking-widest block">
                                        <?= htmlspecialchars($shoe['category_name'] ?? 'Footwear') ?> &bull; <?= htmlspecialchars($shoe['gender'] ?? 'unisex') ?>
                                    </span>
                                    <h3 class="text-sm font-black uppercase tracking-wide text-white leading-tight group-hover:text-[#FF4E00] transition-colors">
                                        <?= htmlspecialchars($shoe['name']) ?>
                                    </h3>
                                    <p class="text-[10px] text-white/40 line-clamp-2 h-7 leading-relaxed">
                                        <?= htmlspecialchars($shoe['description'] ?? 'Curated premium footwear limited edition line.') ?>
                                    </p>
                                    <div class="pt-1 select-none flex items-center gap-4 text-[9px] text-white/30 font-bold uppercase">
                                        <span>Color: <strong class="text-white/60"><?= htmlspecialchars($shoe['color'] ?? 'Custom') ?></strong></span>
                                        <span>Stock: <strong class="text-white/60"><?= $shoe['stock'] ?> pairs</strong></span>
                                    </div>
                                </div>

                                <div class="border-t border-white/5 pt-3.5 flex items-end justify-between">
                                    <div class="flex flex-col">
                                        <?php if ($hasDiscount): ?>
                                            <span class="text-[9px] text-white/30 line-through font-bold leading-none">$<?= number_format($origPrice, 2) ?></span>
                                            <span class="text-base font-black text-[#FF4E00] tracking-tight leading-none mt-0.5">$<?= number_format($dispPrice, 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-base font-black text-white tracking-tight leading-none">$<?= number_format($dispPrice, 2) ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action buttons layout -->
                                    <div class="flex gap-1.5">
                                        <button 
                                            onclick="openEditShoeModal(<?= htmlspecialchars(json_encode($shoe)) ?>)"
                                            class="bg-white/5 border border-white/10 hover:border-white text-white text-[8px] font-black tracking-widest px-2.5 py-1.5 uppercase rounded-lg transition-colors"
                                        >
                                            EDIT
                                        </button>
                                        <form action="" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to permanently remove this Sneaker from the catalog?');">
                                            <input type="hidden" name="action" value="delete_shoe">
                                            <input type="hidden" name="shoe_id" value="<?= $shoe['id'] ?>">
                                            <input type="hidden" name="current_tab" value="products">
                                            <button type="submit" class="bg-red-500/10 border border-red-500/20 hover:bg-red-500 hover:text-white text-red-400 text-[8px] font-black tracking-widest px-2.5 py-1.5 uppercase rounded-lg transition-colors">
                                                DELETE
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================ -->
<!--               MODALS SECTION                 -->
<!-- ============================================ -->

<!-- 1. ADD SNEAKER MODAL -->
<div id="addShoeModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-6 hidden">
    <div class="bg-neutral-950 border border-white/15 w-full max-w-2xl rounded-3xl p-6 relative overflow-y-auto max-h-[90vh]">
        <button onclick="closeAddShoeModal()" class="absolute top-5 right-5 text-white/50 hover:text-white text-sm font-bold uppercase transition-colors select-none">&times; Close</button>
        
        <div class="border-b border-white/10 pb-4 mb-5">
            <h3 class="text-base font-black uppercase tracking-widest text-[#FF4E00]">Catalog new customized sneaker</h3>
            <p class="text-[9px] text-white/40 uppercase tracking-wider font-bold">Registers style, uploads image, and pre-seeds size lists automatically</p>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_shoe">
            <input type="hidden" name="current_tab" value="products">

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Sneaker Name *</label>
                <input type="text" name="name" required placeholder="e.g. Air Force Custom Gold" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div class="border border-white/5 bg-white/[0.02] p-4 rounded-xl space-y-3">
                <span class="block text-[10px] font-black uppercase text-[#FF4E00] tracking-wider mb-1">Product Images (Minimum 5 required)</span>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Pic 1 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">1. Primary image URL *</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" required oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>

                    <!-- Pic 2 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">2. Image URL *</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" required oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>

                    <!-- Pic 3 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">3. Image URL *</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" required oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>

                    <!-- Pic 4 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">4. Image URL *</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" required oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>

                    <!-- Pic 5 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">5. Image URL *</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" required oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>

                    <!-- Pic 6 -->
                    <div class="image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl">
                        <div class="flex justify-between items-center mb-1">
                            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">6. Image URL (Optional)</label>
                            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                                <img src="" class="hidden w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                                <svg class="w-4 h-4 text-white/20 image-placeholder-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                            </div>
                            <div class="flex-1">
                                <input type="text" name="image_urls[]" oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                            </div>
                            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                                Select
                                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                            </label>
                        </div>
                    </div>
                </div>
                <div id="additional_images_container_add" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
                <button type="button" onclick="addMoreImageUrlField('additional_images_container_add')" class="text-[9px] font-black uppercase text-[#FF4E00] tracking-widest hover:underline mt-1">+ Add Optional Image Field</button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Brand Option *</label>
                    <select name="brand_id" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px] tracking-wider">
                        <?php foreach ($brands as $bo): ?>
                            <option value="<?= $bo['id'] ?>"><?= htmlspecialchars($bo['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Category Class *</label>
                    <select name="category_id" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px] tracking-wider">
                        <?php foreach ($categories as $co): ?>
                            <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Retail Price *</label>
                    <input type="number" step="0.01" name="price" required placeholder="140.00" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Discount Price</label>
                    <input type="number" step="0.01" name="discount_price" placeholder="110.00" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Gender Fit</label>
                    <select name="gender" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <option value="unisex">Unisex</option>
                        <option value="men">Mens</option>
                        <option value="women">Womens</option>
                        <option value="kids">Kids</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Shoe Color</label>
                    <input type="text" name="color" placeholder="e.g. Volt Lime" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Premium Material</label>
                    <input type="text" name="material" placeholder="e.g. Suede & Canvas" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Pairs Stock</label>
                    <input type="number" name="stock" value="40" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Description Specs</label>
                <textarea name="description" rows="3" placeholder="Write exclusive details, performance stats, or design concept specs..." class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]"></textarea>
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs uppercase text-white/80 select-none">
                    <input type="checkbox" name="featured" value="1" class="rounded border-white/10 bg-[#111] focus:ring-[#FF4E00] text-[#FF4E00]">
                    Featured Display
                </label>
                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs uppercase text-white/80 select-none">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-white/10 bg-[#111] focus:ring-[#FF4E00] text-[#FF4E00]">
                    Immediately Active
                </label>
            </div>

            <div class="border-t border-white/10 pt-4 flex justify-end gap-3.5">
                <button type="button" onclick="closeAddShoeModal()" class="bg-white/5 border border-white/15 text-white text-xs uppercase font-black px-6 py-3.5 rounded-xl tracking-widest hover:bg-white/10 transition-colors">Cancel</button>
                <button type="submit" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs uppercase font-black px-6 py-3.5 rounded-xl tracking-widest transition-all shadow-lg shadow-[#FF4E00]/10">Create Product</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT SNEAKER MODAL -->
<div id="editShoeModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-6 hidden">
    <div class="bg-neutral-950 border border-white/15 w-full max-w-2xl rounded-3xl p-6 relative overflow-y-auto max-h-[90vh]">
        <button onclick="closeEditShoeModal()" class="absolute top-5 right-5 text-white/50 hover:text-white text-sm font-bold uppercase transition-colors select-none">&times; Close</button>
        
        <div class="border-b border-white/10 pb-4 mb-5">
            <h3 class="text-base font-black uppercase tracking-widest text-[#FF4E00]">Edit customized sneaker details</h3>
            <p class="text-[9px] text-white/40 uppercase tracking-wider font-bold">Modify product values inside catalog</p>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit_shoe">
            <input type="hidden" id="edit_shoe_id" name="shoe_id">
            <input type="hidden" name="current_tab" value="products">

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Sneaker Name *</label>
                <input type="text" id="edit_shoe_name" name="name" required class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div class="border border-white/5 bg-white/[0.02] p-4 rounded-xl space-y-3">
                <span class="block text-[10px] font-black uppercase text-[#FF4E00] tracking-wider mb-1">Product Images (Minimum 5 required)</span>
                <div id="edit_image_fields_container" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <!-- Dynamic fields populated from JavaScript -->
                </div>
                <button type="button" onclick="addMoreImageUrlField('edit_image_fields_container')" class="text-[9px] font-black uppercase text-[#FF4E00] tracking-widest hover:underline mt-1">+ Add Optional Image Field</button>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Brand Option *</label>
                    <select id="edit_shoe_brand" name="brand_id" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <?php foreach ($brands as $bo): ?>
                            <option value="<?= $bo['id'] ?>"><?= htmlspecialchars($bo['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Category Class *</label>
                    <select id="edit_shoe_category" name="category_id" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <?php foreach ($categories as $co): ?>
                            <option value="<?= $co['id'] ?>"><?= htmlspecialchars($co['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Retail Price *</label>
                    <input type="number" step="0.01" id="edit_shoe_price" name="price" required class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Discount Price</label>
                    <input type="number" step="0.01" id="edit_shoe_discount" name="discount_price" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Gender Fit</label>
                    <select id="edit_shoe_gender" name="gender" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <option value="unisex">Unisex</option>
                        <option value="men">Mens</option>
                        <option value="women">Womens</option>
                        <option value="kids">Kids</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Shoe Color</label>
                    <input type="text" id="edit_shoe_color" name="color" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Premium Material</label>
                    <input type="text" id="edit_shoe_material" name="material" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Pairs Stock</label>
                    <input type="number" id="edit_shoe_stock" name="stock" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Description Specs</label>
                <textarea id="edit_shoe_desc" name="description" rows="3" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]"></textarea>
            </div>

            <div class="flex items-center gap-6 pt-2">
                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs uppercase text-white/80 select-none">
                    <input type="checkbox" id="edit_shoe_featured" name="featured" value="1" class="rounded border-white/10 bg-[#111] focus:ring-[#FF4E00] text-[#FF4E00]">
                    Featured Display
                </label>
                <label class="flex items-center gap-2 cursor-pointer font-bold text-xs uppercase text-white/80 select-none">
                    <input type="checkbox" id="edit_shoe_active" name="is_active" value="1" class="rounded border-white/10 bg-[#111] focus:ring-[#FF4E00] text-[#FF4E00]">
                    Immediately Active
                </label>
            </div>

            <div class="border-t border-white/10 pt-4 flex justify-end gap-3.5">
                <button type="button" onclick="closeEditShoeModal()" class="bg-white/5 border border-white/15 text-white text-xs uppercase font-black px-6 py-3.5 rounded-xl tracking-widest hover:bg-white/10 transition-colors">Cancel</button>
                <button type="submit" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs uppercase font-black px-6 py-3.5 rounded-xl tracking-widest transition-all">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. REGISTER USER MODAL -->
<div id="addUserModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-6 hidden">
    <div class="bg-neutral-950 border border-white/15 w-full max-w-md rounded-3xl p-6 relative">
        <button onclick="closeAddUserModal()" class="absolute top-5 right-5 text-white/50 hover:text-white text-sm font-bold uppercase transition-colors select-none">&times; Close</button>
        
        <div class="border-b border-white/10 pb-4 mb-5">
            <h3 class="text-base font-black uppercase tracking-widest text-[#FF4E00]">Register Custom Admin / User</h3>
            <p class="text-[9px] text-white/40 uppercase tracking-wider font-bold">Register a staff account or regular customer details securely</p>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="add_user">
            <input type="hidden" name="current_tab" value="users">

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Full Human Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Jacob Sneakerhead" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Email Address *</label>
                <input type="email" name="email" required placeholder="e.g. customer@stridehub.com" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Password Credentials *</label>
                <input type="text" name="password" value="password" required class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5 font-mono">Phone Number</label>
                    <input type="text" name="phone" placeholder="e.g. 555-0101" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Role Privileges</label>
                    <select name="role" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <option value="customer">Customer</option>
                        <option value="admin">Administrator Staff</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Initial Status</label>
                <select name="status" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                    <option value="active">Active (Access Enabled)</option>
                    <option value="blocked">Blocked / Suspended (No Login)</option>
                </select>
            </div>

            <div class="border-t border-white/10 pt-4 mt-5 flex justify-end gap-3.5">
                <button type="button" onclick="closeAddUserModal()" class="bg-white/5 border border-white/15 text-white text-xs uppercase font-black px-5 py-3 rounded-xl tracking-widest hover:bg-white/10 transition-colors">Cancel</button>
                <button type="submit" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs uppercase font-black px-5 py-3 rounded-xl tracking-widest transition-all">Add Account</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. EDIT USER DETAILS MODAL -->
<div id="editUserModal" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-sm flex items-center justify-center p-6 hidden">
    <div class="bg-neutral-950 border border-white/15 w-full max-w-md rounded-3xl p-6 relative">
        <button onclick="closeEditUserModal()" class="absolute top-5 right-5 text-white/50 hover:text-white text-sm font-bold uppercase transition-colors select-none">&times; Close</button>
        
        <div class="border-b border-white/10 pb-4 mb-5">
            <h3 class="text-base font-black uppercase tracking-widest text-[#FF4E00]">Update registered user info</h3>
            <p class="text-[9px] text-white/40 uppercase tracking-wider font-bold">Edit master user records</p>
        </div>

        <form action="" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" id="edit_user_id" name="user_id">
            <input type="hidden" name="current_tab" value="users">

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Full Human Name *</label>
                <input type="text" id="edit_user_name" name="full_name" required class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5 font-bold text-white/50">Email Address *</label>
                <input type="email" id="edit_user_email" name="email" required class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none focus:border-[#FF4E00]">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5 font-mono">Phone Details</label>
                    <input type="text" id="edit_user_phone" name="phone" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Role Privileges</label>
                    <select id="edit_user_role" name="role" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                        <option value="customer">Customer</option>
                        <option value="admin">Administrator Staff</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-[9px] font-bold text-white/50 uppercase tracking-wider mb-1.5">Status (Block Switch)</label>
                <select id="edit_user_status" name="status" class="w-full bg-[#111] border border-white/10 text-white text-xs px-3.5 py-2.5 rounded-xl focus:outline-none uppercase font-bold text-[9px]">
                    <option value="active">Active (Access Enabled)</option>
                    <option value="blocked">Blocked / Suspended</option>
                </select>
            </div>

            <div class="border-t border-white/10 pt-4 mt-5 flex justify-end gap-3.5">
                <button type="button" onclick="closeEditUserModal()" class="bg-white/5 border border-white/15 text-white text-xs uppercase font-black px-5 py-3 rounded-xl tracking-widest hover:bg-white/10 transition-colors">Cancel</button>
                <button type="submit" class="bg-[#FF4E00] hover:bg-[#FF5D14] text-white text-xs uppercase font-black px-5 py-3 rounded-xl tracking-widest transition-all">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- INLINE STYLES FOR THE ACTIVE TAB STATE AND HEART TRANSITION -->
<style>
.admin-tab-btn {
    border: 1px solid rgba(255,255,255,0.05);
    background-color: transparent;
    color: rgba(255, 255, 255, 0.5);
}
.admin-tab-btn:hover {
    background-color: rgba(255,255,255,0.03);
    color: white;
}
.admin-tab-btn.active {
    background-color: rgba(255, 78, 0, 0.1);
    border-color: rgba(255, 78, 0, 0.35);
    color: #FF4E00;
}
tr.order-row.hidden, .user-card.hidden, .shoe-card.hidden {
    display: none !important;
}
</style>

<script>
// Tab management state
let currentActiveTab = '<?= $active_tab ?>';

function switchTab(tabName) {
    currentActiveTab = tabName;
    
    // Hide all sections
    document.querySelectorAll('.admin-section').forEach(sec => {
        sec.classList.add('hidden');
    });

    // Remove active styles from keys
    document.querySelectorAll('.admin-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected section
    const targetSec = document.getElementById('section-' + tabName);
    if (targetSec) targetSec.classList.remove('hidden');

    // Add highlighted active style to button
    const targetBtn = document.getElementById('tabBtn-' + tabName);
    if (targetBtn) targetBtn.classList.add('active');

    // Update URL Hash discretely to maintain tabs state over reload
    window.location.hash = tabName;
}

// Filter Order Ledger
function filterOrders() {
    const q = document.getElementById('orderSearch').value.toLowerCase().trim();
    const filter = document.getElementById('orderStatusFilter').value;
    const rows = document.querySelectorAll('.order-row');

    rows.forEach(row => {
        const idText = row.getAttribute('data-id');
        const buyerText = row.getAttribute('data-buyer');
        const statusText = row.getAttribute('data-status');

        const matchesQuery = (idText.includes(q) || buyerText.includes(q));
        const matchesFilter = (filter === 'all' || statusText === filter);

        if (matchesQuery && matchesFilter) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
}

// Filter Users ledger
function filterUsers() {
    const q = document.getElementById('userSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.user-card');

    cards.forEach(card => {
        const nameText = card.getAttribute('data-name');
        const emailText = card.getAttribute('data-email');
        const matchesQuery = (nameText.includes(q) || emailText.includes(q));

        if (matchesQuery) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

// Filter Sneakers collections
function filterShoes() {
    const q = document.getElementById('shoeSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.shoe-card');

    cards.forEach(card => {
        const nameText = card.getAttribute('data-name');
        const colorText = card.getAttribute('data-color');
        const brandText = card.getAttribute('data-brand');
        const matchesQuery = (nameText.includes(q) || colorText.includes(q) || brandText.includes(q));

        if (matchesQuery) {
            card.classList.remove('hidden');
        } else {
            card.classList.add('hidden');
        }
    });
}

// Modal Toggle Helpers
function openAddShoeModal() {
    document.getElementById('addShoeModal').classList.remove('hidden');
}
function closeAddShoeModal() {
    document.getElementById('addShoeModal').classList.add('hidden');
}

function openAddUserModal() {
    document.getElementById('addUserModal').classList.remove('hidden');
}
function closeAddUserModal() {
    document.getElementById('addUserModal').classList.add('hidden');
}

function updateImagePreview(inputElem) {
    const parent = inputElem.closest('.image-upload-wrapper');
    if (!parent) return;
    const previewImg = parent.querySelector('.image-preview-elem');
    const placeholderIcon = parent.querySelector('.image-placeholder-icon');
    const url = inputElem.value.trim();
    if (url) {
        previewImg.src = url;
        previewImg.classList.remove('hidden');
        placeholderIcon.classList.add('hidden');
    } else {
        previewImg.src = '';
        previewImg.classList.add('hidden');
        placeholderIcon.classList.remove('hidden');
    }
}

function uploadLocalImageInput(filePicker) {
    const file = filePicker.files[0];
    if (!file) return;

    const parent = filePicker.closest('.image-upload-wrapper');
    if (!parent) return;

    const statusLabel = parent.querySelector('.image-status-label');
    const inputElem = parent.querySelector('.image-url-input');
    const previewImg = parent.querySelector('.image-preview-elem');
    const placeholderIcon = parent.querySelector('.image-placeholder-icon');

    if (statusLabel) {
        statusLabel.textContent = "Uploading...";
        statusLabel.className = "text-[8px] font-mono text-amber-400 font-bold uppercase tracking-wider image-status-label animate-pulse";
    }

    const formData = new FormData();
    formData.append('image', file);

    fetch('upload_image.php', {
        method: 'POST',
        body: formData
    })
    .then(async res => {
        const text = await res.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            throw new Error(`Server returned invalid response (${res.status}): ${text.substring(0, 300)}`);
        }
    })
    .then(data => {
        if (data.success && data.url) {
            inputElem.value = data.url;
            if (statusLabel) {
                statusLabel.textContent = "Stored";
                statusLabel.className = "text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label";
            }
            // Trigger pre-load / show
            updateImagePreview(inputElem);
        } else {
            alert(data.error || 'Failed to upload local image.');
            if (statusLabel) {
                statusLabel.textContent = "Error";
                statusLabel.className = "text-[8px] font-mono text-red-400 font-bold uppercase tracking-wider image-status-label";
            }
        }
    })
    .catch(err => {
        console.error('File transmission failed:', err);
        alert(err.message || 'File transmission failed. Ensure the server is connected and PHP is running.');
        if (statusLabel) {
            statusLabel.textContent = "Failed";
            statusLabel.className = "text-[8px] font-mono text-red-400 font-bold uppercase tracking-wider image-status-label";
        }
    });
}

function addMoreImageUrlField(containerId, value = '') {
    const container = document.getElementById(containerId);
    if (!container) return;
    const count = container.children.length + 1;
    const div = document.createElement('div');
    div.className = "image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl";
    div.innerHTML = `
        <div class="flex justify-between items-center mb-1">
            <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">Image Class / Optional</label>
            <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                <img src="${value}" class="${value ? '' : 'hidden'} w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                <svg class="w-4 h-4 text-white/20 image-placeholder-icon ${value ? 'hidden' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
            <div class="flex-1">
                <input type="text" name="image_urls[]" value="${value}" oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
            </div>
            <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                Select
                <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
            </label>
        </div>
    `;
    container.appendChild(div);
}

function openEditShoeModal(shoe) {
    document.getElementById('edit_shoe_id').value = shoe.id;
    document.getElementById('edit_shoe_name').value = shoe.name;
    document.getElementById('edit_shoe_desc').value = shoe.description || '';
    document.getElementById('edit_shoe_price').value = shoe.price;
    document.getElementById('edit_shoe_discount').value = shoe.discount_price || '';
    document.getElementById('edit_shoe_brand').value = shoe.brand_id;
    document.getElementById('edit_shoe_category').value = shoe.category_id;
    document.getElementById('edit_shoe_gender').value = shoe.gender || 'unisex';
    document.getElementById('edit_shoe_color').value = shoe.color || '';
    document.getElementById('edit_shoe_material').value = shoe.material || '';
    document.getElementById('edit_shoe_stock').value = shoe.stock || 0;
    document.getElementById('edit_shoe_featured').checked = parseInt(shoe.featured) === 1;
    document.getElementById('edit_shoe_active').checked = parseInt(shoe.is_active) === 1;

    // Dynamically render the image fields
    const container = document.getElementById('edit_image_fields_container');
    if (container) {
        container.innerHTML = '';
        const images = shoe.images || [];
        // Make sure we have at least 5 image slots rendered
        const totalToRender = Math.max(5, images.length);
        for (let i = 0; i < totalToRender; i++) {
            const val = images[i] || '';
            const isRequired = i < 5;
            const div = document.createElement('div');
            div.className = "image-upload-wrapper relative flex flex-col gap-1 bg-white/[0.01] p-3 border border-white/5 rounded-xl";
            div.innerHTML = `
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-[9px] font-bold text-white/40 uppercase tracking-wider">${i + 1}. Image URL ${isRequired ? '*' : '(Optional)'}</label>
                    <span class="text-[8px] font-mono text-emerald-400 font-bold uppercase tracking-wider image-status-label"></span>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-10 h-10 bg-black/50 border border-white/10 rounded flex items-center justify-center shrink-0 overflow-hidden relative">
                        <img src="${val}" class="${val ? '' : 'hidden'} w-full h-full object-contain image-preview-elem" onerror="this.classList.add('hidden'); this.nextElementSibling.classList.remove('hidden');" onLoad="this.classList.remove('hidden'); this.nextElementSibling.classList.add('hidden');">
                        <svg class="w-4 h-4 text-white/20 image-placeholder-icon ${val ? 'hidden' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    </div>
                    <div class="flex-1">
                        <input type="text" name="image_urls[]" value="${val}" ${isRequired ? 'required' : ''} oninput="updateImagePreview(this)" placeholder="https://... or select local file" class="image-url-input w-full bg-[#111] border border-white/10 text-white text-xs px-3 py-2 rounded-lg focus:outline-none focus:border-[#FF4E00]">
                    </div>
                    <label class="relative bg-white/5 hover:bg-[#FF4E00]/10 text-white/80 border border-white/10 hover:border-[#FF4E00]/40 rounded-lg px-2.5 py-2 text-[10px] font-bold cursor-pointer transition-all shrink-0">
                        Select
                        <input type="file" accept="image/*" class="hidden image-file-picker" onchange="uploadLocalImageInput(this)">
                    </label>
                </div>
            `;
            container.appendChild(div);
        }
    }

    document.getElementById('editShoeModal').classList.remove('hidden');
}
function closeEditShoeModal() {
    document.getElementById('editShoeModal').classList.add('hidden');
}

function openEditUserModal(user) {
    document.getElementById('edit_user_id').value = user.id;
    document.getElementById('edit_user_name').value = user.full_name;
    document.getElementById('edit_user_email').value = user.email;
    document.getElementById('edit_user_phone').value = user.phone || '';
    document.getElementById('edit_user_role').value = user.role;
    document.getElementById('edit_user_status').value = user.status;

    document.getElementById('editUserModal').classList.remove('hidden');
}
function closeEditUserModal() {
    document.getElementById('editUserModal').classList.add('hidden');
}

// Dom Boot
document.addEventListener('DOMContentLoaded', () => {
    // If URL hash matches a section, load on that section
    const hash = window.location.hash.replace('#', '');
    if (hash && ['summary', 'orders', 'users', 'products'].includes(hash)) {
        currentActiveTab = hash;
    }
    switchTab(currentActiveTab);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
