<?php
session_start();
require './config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$res = $conn->query("SELECT * FROM orders WHERE id = $id");
if (!$res || $res->num_rows === 0) {
    header('Location: index.php'); exit;
}
$order = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .success-box {
            text-align: center;
            padding: var(--sp-8) var(--sp-5);
            background: var(--clr-surface);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-card);
            max-width: 600px;
            margin: var(--sp-10) auto;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: var(--clr-success-light);
            color: var(--clr-success);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: var(--sp-5);
        }
        .success-icon svg { width: 40px; height: 40px; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container">
    <div class="success-box">
        <div class="success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1 style="font-size:var(--text-2xl);font-weight:800;color:var(--clr-text-primary);margin-bottom:var(--sp-2);">Đặt hàng thành công!</h1>
        <p style="color:var(--clr-text-secondary);margin-bottom:var(--sp-6);">Cảm ơn bạn đã mua sắm tại TechStore. Mã đơn hàng của bạn là <strong>#<?= $order['id'] ?></strong>.</p>
        
        <div style="background:var(--clr-surface-raised);padding:var(--sp-5);border-radius:var(--r-md);text-align:left;margin-bottom:var(--sp-6);line-height:1.8;">
            <h3 style="font-size:var(--text-md);font-weight:700;margin-bottom:var(--sp-3);border-bottom:1px solid var(--clr-border-muted);padding-bottom:var(--sp-2);">Thông tin giao hàng</h3>
            <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['receiver_name']) ?></p>
            <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['receiver_phone']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= nl2br(htmlspecialchars($order['receiver_address'])) ?></p>
            <p><strong>Phương thức TT:</strong> <?= $order['payment_method'] === 'COD' ? 'Thanh toán khi nhận hàng' : 'Thanh toán online' ?></p>
            <p style="margin-top:var(--sp-3);padding-top:var(--sp-3);border-top:1px dashed var(--clr-border);">
                <strong>Tổng tiền:</strong> 
                <span style="color:var(--clr-brand);font-weight:700;font-size:var(--text-lg);float:right;"><?= formatPrice($order['total']) ?></span>
            </p>
        </div>

        <a href="index.php" class="btn-checkout" style="display:inline-flex;width:auto;padding:0 var(--sp-6);height:48px;">
            Tiếp tục mua sắm
        </a>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
