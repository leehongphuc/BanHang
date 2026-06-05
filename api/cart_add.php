<?php
session_start();
require '../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'cartCount' => 0, 'message' => 'Lỗi không xác định'];

// Kiểm tra đăng nhập
if (empty($_SESSION['user_id'])) {
    $response['message'] = 'Vui lòng đăng nhập để thêm vào giỏ hàng.';
    $response['requireLogin'] = true;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// Admin không được mua hàng
if (($_SESSION['user_role'] ?? '') === 'admin') {
    $response['message'] = 'Tài khoản admin không thể thực hiện mua hàng.';
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    
    if (!isset($_SESSION['cart'][$pid])) {
        $p = $conn->query("SELECT id,name,price,image,stock FROM products WHERE id=$pid")->fetch_assoc();
        if ($p && $p['stock'] > 0) {
            $_SESSION['cart'][$pid] = [
                'id' => $pid,
                'name' => $p['name'],
                'price' => $p['price'],
                'image' => $p['image'],
                'stock' => $p['stock'],
                'qty' => 0
            ];
        }
    }
    
    if (isset($_SESSION['cart'][$pid])) {
        $new_qty = $_SESSION['cart'][$pid]['qty'] + $qty;
        $_SESSION['cart'][$pid]['qty'] = min($new_qty, $_SESSION['cart'][$pid]['stock']);
        $response['success'] = true;
        $response['message'] = 'Đã thêm vào giỏ hàng';
    } else {
        $response['message'] = 'Sản phẩm không tồn tại hoặc hết hàng';
    }
}

// Calculate total items in cart
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['qty'];
    }
}
$response['cartCount'] = $cartCount;

echo json_encode($response, JSON_UNESCAPED_UNICODE);
