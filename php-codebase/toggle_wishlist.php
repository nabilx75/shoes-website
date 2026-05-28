<?php
require_once __DIR__ . '/db.php';

// In case session is not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDBConnection();
$currentUser = null;

if (isset($_SESSION['user_id']) && $db) {
    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $currentUser = $stmt->fetch();
    } catch (PDOException $e) {}
}

if (!$currentUser) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit();
}

$shoe_id = intval($_REQUEST['shoe_id'] ?? 0);
header('Content-Type: application/json');

if ($shoe_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid Shoe ID']);
    exit();
}

if ($db) {
    try {
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :uid AND shoe_id = :sid LIMIT 1");
        $stmt->execute([':uid' => $currentUser['id'], ':sid' => $shoe_id]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $del = $db->prepare("DELETE FROM wishlist WHERE id = :id");
            $del->execute([':id' => $existing['id']]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $ins = $db->prepare("INSERT INTO wishlist (user_id, shoe_id) VALUES (:uid, :sid)");
            $ins->execute([':uid' => $currentUser['id'], ':sid' => $shoe_id]);
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
}
