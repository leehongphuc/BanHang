<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'wishlistCount' => 0, 'message' => 'Lỗi không xác định'];

// Kiểm tra đăng nhập
if (empty($_SESSION['user_id'])) {
    $response['message'] = 'Vui lòng đăng nhập để lưu vào yêu thích.';
    $response['requireLogin'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($productId <= 0) {
    $response['message'] = 'Sản phẩm không hợp lệ.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Kiểm tra xem sản phẩm có tồn tại không
$pCheck = $conn->query("SELECT id, name FROM products WHERE id = $productId");
if ($pCheck->num_rows === 0) {
    $response['message'] = 'Sản phẩm không tồn tại.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
$product = $pCheck->fetch_assoc();
$pName = $product['name'];

// Kiểm tra xem đã yêu thích chưa
$wCheck = $conn->query("SELECT id FROM wishlist WHERE user_id = $userId AND product_id = $productId");

if ($wCheck->num_rows > 0) {
    // Đã có -> Xóa đi
    $conn->query("DELETE FROM wishlist WHERE user_id = $userId AND product_id = $productId");
    $response['success'] = true;
    $response['action'] = 'removed';
    $response['message'] = 'Đã xóa "' . $pName . '" khỏi danh sách yêu thích.';
} else {
    // Chưa có -> Thêm vào
    $conn->query("INSERT INTO wishlist (user_id, product_id) VALUES ($userId, $productId)");
    $response['success'] = true;
    $response['action'] = 'added';
    $response['message'] = 'Đã thêm "' . $pName . '" vào danh sách yêu thích.';
}

// Tính tổng số lượng yêu thích hiện tại của user
$countRes = $conn->query("SELECT COUNT(*) FROM wishlist WHERE user_id = $userId");
$wishlistCount = $countRes ? (int)$countRes->fetch_row()[0] : 0;
$response['wishlistCount'] = $wishlistCount;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
