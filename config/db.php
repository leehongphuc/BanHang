<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // 
define('DB_NAME', 'shop');
define('DB_PORT', 3307);
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Hàm format tiền VND
function formatPrice($price)
{
    return number_format($price, 0, ',', '.') . 'đ';
}

// Lấy giỏ hàng từ session
function getCart()
{
    return $_SESSION['cart'] ?? [];
}

// Tổng số lượng trong giỏ
function cartCount()
{
    $cart = getCart();
    return array_sum(array_column($cart, 'qty'));
}
?>