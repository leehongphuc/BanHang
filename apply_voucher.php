<?php
session_start();
require 'config/db.php';

if (isset($_GET['remove'])) {
    unset($_SESSION['voucher']);
    $_SESSION['checkout_msg'] = "Đã bỏ mã giảm giá.";
    $_SESSION['checkout_msg_type'] = "success";
    header('Location: checkout.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['voucher_code'] ?? ''));
    
    if (!$code) {
        $_SESSION['checkout_msg'] = "Vui lòng nhập mã giảm giá.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    $ecode = $conn->real_escape_string($code);
    $res = $conn->query("SELECT * FROM vouchers WHERE code = '$ecode'");
    
    if (!$res || $res->num_rows === 0) {
        $_SESSION['checkout_msg'] = "Mã giảm giá không tồn tại.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    $v = $res->fetch_assoc();

    // Check active
    if (!$v['is_active']) {
        $_SESSION['checkout_msg'] = "Mã giảm giá đã bị khóa.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    // Check end date
    if ($v['end_date'] && strtotime($v['end_date']) < time()) {
        $_SESSION['checkout_msg'] = "Mã giảm giá đã hết hạn.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    // Check usage limit
    if ($v['usage_limit'] > 0 && $v['used_count'] >= $v['usage_limit']) {
        $_SESSION['checkout_msg'] = "Mã giảm giá đã hết lượt sử dụng.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    // Check cart subtotal for valid categories
    $cart = $_SESSION['cart'] ?? [];
    $subtotal = 0;
    $eligible_subtotal = 0;
    
    foreach ($cart as $key => $item) {
        $item_total = $item['price'] * $item['qty'];
        $subtotal += $item_total;
        
        // Fetch category_id if missing (for older carts)
        $item_cat = $item['category_id'] ?? 0;
        if (!$item_cat) {
            $pid = (int)$item['id'];
            $res_cat = $conn->query("SELECT category_id FROM products WHERE id = $pid");
            if ($res_cat && $res_cat->num_rows > 0) {
                $item_cat = $res_cat->fetch_assoc()['category_id'];
                $_SESSION['cart'][$key]['category_id'] = $item_cat; // save back
            }
        }
        
        // If voucher has no category restriction, or item matches voucher category
        if (!$v['category_id'] || $item_cat == $v['category_id']) {
            $eligible_subtotal += $item_total;
        }
    }

    if ($eligible_subtotal == 0) {
        $_SESSION['checkout_msg'] = "Giỏ hàng không có sản phẩm nào thuộc danh mục áp dụng mã này.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    if ($eligible_subtotal < $v['min_order_value']) {
        $_SESSION['checkout_msg'] = "Đơn hàng tối thiểu để áp dụng mã này là " . number_format($v['min_order_value']) . "đ.";
        $_SESSION['checkout_msg_type'] = "error";
        header('Location: checkout.php');
        exit;
    }

    // All good
    $_SESSION['voucher'] = $v;
    $_SESSION['checkout_msg'] = "Áp dụng mã giảm giá thành công!";
    $_SESSION['checkout_msg_type'] = "success";
    header('Location: checkout.php');
    exit;
}
