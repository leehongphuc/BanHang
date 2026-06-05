<?php
session_start();
require './config/db.php';
require './config/auth.php';
require './includes/email_helper.php';

// Bắt buộc đăng nhập
requireLogin('checkout.php');
$user = currentUser();

// Admin không được phép mua hàng
if (($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: /BanHang/admin/index.php');
    exit;
}

$cart = getCart();
if (empty($cart)) { header('Location: cart.php'); exit; }

$subtotal = 0;
foreach ($cart as $item) $subtotal += $item['price'] * $item['qty'];

$discount = 0;
$voucherCode = '';
if (isset($_SESSION['voucher'])) {
    $v = $_SESSION['voucher'];
    
    $eligible_subtotal = 0;
    foreach ($cart as $key => $item) {
        $item_cat = $item['category_id'] ?? 0;
        if (!$item_cat) {
            $pid = (int)$item['id'];
            $res_cat = $conn->query("SELECT category_id FROM products WHERE id = $pid");
            if ($res_cat && $res_cat->num_rows > 0) {
                $item_cat = $res_cat->fetch_assoc()['category_id'];
                $_SESSION['cart'][$key]['category_id'] = $item_cat;
            }
        }

        if (!$v['category_id'] || $item_cat == $v['category_id']) {
            $eligible_subtotal += $item['price'] * $item['qty'];
        }
    }
    
    if ($eligible_subtotal >= $v['min_order_value'] && $eligible_subtotal > 0) {
        $voucherCode = $v['code'];
        if ($v['discount_type'] === 'percent') {
            $discount = ($eligible_subtotal * $v['discount_value']) / 100;
        } else {
            $discount = min($v['discount_value'], $eligible_subtotal);
        }
    } else {
        unset($_SESSION['voucher']);
        $errors[] = "Đơn hàng không còn đủ điều kiện dùng mã giảm giá.";
    }
}

$total = max(0, $subtotal - $discount);
$errors = $errors ?? [];

// Auto-fill từ profile (có thể override bằng POST)
$prefill = [
    'name'    => $user['fullname'] ?? '',
    'phone'   => $user['phone'] ?? '',
    'address' => $user['address'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['receiver_name']    ?? '');
    $phone   = trim($_POST['receiver_phone']   ?? '');
    $address = trim($_POST['receiver_address'] ?? '');
    $payment = in_array($_POST['payment_method'], ['COD','online']) ? $_POST['payment_method'] : 'COD';

    $prefill = ['name' => $name, 'phone' => $phone, 'address' => $address];

    if (!$name)    $errors[] = "Vui lòng nhập họ và tên.";
    if (!preg_match('/^[0-9]{9,11}$/', $phone)) $errors[] = "Số điện thoại không hợp lệ (9–11 chữ số).";
    if (!$address) $errors[] = "Vui lòng nhập địa chỉ giao hàng.";

    if (empty($errors)) {
        $en = $conn->real_escape_string($name);
        $ep = $conn->real_escape_string($phone);
        $ea = $conn->real_escape_string($address);
        $vc = $voucherCode ? "'".$conn->real_escape_string($voucherCode)."'" : "NULL";
        $uid = (int)$user['id'];
        
        $conn->begin_transaction();
        try {
            $conn->query("INSERT INTO orders (user_id,receiver_name,receiver_phone,receiver_address,payment_method,voucher_code,discount_amount,total)
                          VALUES ($uid,'$en','$ep','$ea','$payment',$vc,$discount,$total)");
            $order_id = $conn->insert_id;
            
            foreach ($cart as $item) {
                $pid=$item['id']; $qty=$item['qty']; $price=$item['price'];
                $conn->query("INSERT INTO order_items (order_id,product_id,quantity,price) VALUES ($order_id,$pid,$qty,$price)");
                $conn->query("UPDATE products SET stock=stock-$qty WHERE id=$pid AND stock>=$qty");
            }
            
            // Update voucher used count
            if ($voucherCode) {
                $conn->query("UPDATE vouchers SET used_count = used_count + 1 WHERE code = $vc");
                unset($_SESSION['voucher']);
            }
            
            $conn->commit();
            
            // Send order confirmation email
            sendOrderConfirmationEmail($order_id, $conn);
            
            unset($_SESSION['cart']);
            header("Location: order_success.php?id=$order_id"); exit;
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Đặt hàng thất bại, vui lòng thử lại.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        /* Khắc phục triệt để lỗi Sticky Sidebar */
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: var(--sp-7);
            align-items: stretch; /* Đảm bảo các cột cao bằng nhau */
        }
        .checkout-sidebar-column {
            position: relative;
            height: 100%;
        }
        .sticky-summary {
            position: -webkit-sticky;
            position: sticky;
            top: 100px; /* Khoảng cách so với Menu khi cuộn */
            z-index: 10;
            background: var(--clr-surface);
            padding: var(--sp-6);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
        }
        @media (max-width: 992px) {
            .checkout-grid { grid-template-columns: 1fr; }
            .sticky-summary { position: static; margin-top: var(--sp-6); }
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">
    <h1 style="font-size:var(--text-2xl);font-weight:800;margin-bottom:var(--sp-6);">📦 Đặt hàng</h1>

    <div class="checkout-grid">

        <!-- Form -->
        <div>
            <?php if (!empty($errors)): ?>
                <div class="alert-error" role="alert" style="margin-bottom:var(--sp-4);">
                    <?php foreach($errors as $e): ?>
                        <p>⚠ <?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($_SESSION['checkout_msg'])): ?>
                <div class="alert alert-<?= $_SESSION['checkout_msg_type'] ?>" style="margin-bottom:var(--sp-4);">
                    <?= htmlspecialchars($_SESSION['checkout_msg']) ?>
                </div>
                <?php unset($_SESSION['checkout_msg'], $_SESSION['checkout_msg_type']); ?>
            <?php endif; ?>

            <form method="POST" style="background:var(--clr-surface);padding:var(--sp-7);border-radius:var(--r-md);box-shadow:var(--shadow-card);">
                <h2 style="font-size:var(--text-lg);font-weight:700;margin-bottom:var(--sp-5);">Thông tin người nhận</h2>

                <div class="form-group">
                    <label for="receiver_name">Họ và tên *</label>
                    <input type="text" id="receiver_name" name="receiver_name"
                           value="<?= htmlspecialchars($prefill['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="receiver_phone">Số điện thoại *</label>
                    <input type="tel" id="receiver_phone" name="receiver_phone"
                           value="<?= htmlspecialchars($prefill['phone']) ?>"
                           placeholder="0901234567" required>
                </div>
                <div class="form-group">
                    <label for="receiver_address">Địa chỉ giao hàng *</label>
                    <textarea id="receiver_address" name="receiver_address" rows="3" required><?= htmlspecialchars($prefill['address']) ?></textarea>
                </div>

                <h2 style="font-size:var(--text-lg);font-weight:700;margin:var(--sp-6) 0 var(--sp-4);">Phương thức thanh toán</h2>
                <div class="payment-options">
                    <label>
                        <input type="radio" name="payment_method" value="COD"
                               <?= ($_POST['payment_method']??'COD')==='COD'?'checked':'' ?>>
                        💵 Thanh toán khi nhận hàng (COD)
                    </label>
                    <label>
                        <input type="radio" name="payment_method" value="online"
                               <?= ($_POST['payment_method']??'')==='online'?'checked':'' ?>>
                        💳 Thanh toán online (giả lập)
                    </label>
                </div>

                <button type="submit" class="btn-checkout" style="margin-top:var(--sp-6);height:52px;font-size:var(--text-md);">
                    ✅ Xác nhận đặt hàng
                </button>
            </form>
        </div>

        <!-- Order summary -->
        <div class="checkout-sidebar-column">
            <div class="sticky-summary">
            <h3 style="font-size:var(--text-lg);font-weight:700;border-bottom:1px solid var(--clr-border-muted);padding-bottom:var(--sp-4);margin-bottom:var(--sp-5);">
                Đơn hàng của bạn
            </h3>
            <div class="order-summary">
                <?php foreach($cart as $item): ?>
                    <div class="order-item">
                        <span><?= htmlspecialchars($item['name']) ?> × <?= $item['qty'] ?></span>
                        <span><?= formatPrice($item['price'] * $item['qty']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <hr style="border:none;border-top:1px dashed var(--clr-border-muted);margin:var(--sp-4) 0;">
            <div class="summary-row">
                <span>Tạm tính:</span>
                <span><?= formatPrice($subtotal) ?></span>
            </div>
            <?php if ($discount > 0): ?>
            <div class="summary-row" style="color:var(--clr-error);">
                <span>Giảm giá (<?= htmlspecialchars($voucherCode) ?>):</span>
                <span>-<?= formatPrice($discount) ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Phí vận chuyển:</span>
                <span style="color:var(--clr-success);font-weight:600;">Miễn phí ✓</span>
            </div>
            <div class="summary-row" style="margin-top:var(--sp-2);">
                <strong>Tổng cộng:</strong>
                <span class="summary-total"><?= formatPrice($total) ?></span>
            </div>
            
            <!-- Voucher Form -->
            <div style="margin-top:var(--sp-5); border-top:1px solid var(--clr-border-muted); padding-top:var(--sp-4);">
                <form action="apply_voucher.php" method="POST" style="display:flex; gap:10px;">
                    <input type="text" name="voucher_code" placeholder="Mã giảm giá..." 
                           value="<?= htmlspecialchars($voucherCode) ?>"
                           style="flex:1; padding:10px; border:1px solid var(--clr-border); border-radius:var(--r-sm); text-transform:uppercase;">
                    <button type="submit" class="btn btn-primary" style="padding:10px 16px;">Áp dụng</button>
                </form>
                <?php if ($voucherCode): ?>
                    <div style="margin-top:8px; font-size:13px; text-align:right;">
                        <a href="apply_voucher.php?remove=1" style="color:var(--clr-error); text-decoration:underline;">Bỏ chọn mã</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>