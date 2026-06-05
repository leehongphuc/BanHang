<?php
session_start();
require './config/db.php';
require './config/auth.php';
requireLogin();

$user = currentUser();
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Chỉ xem đơn hàng của chính mình
$uid = (int)$user['id'];
$res = $conn->query("SELECT * FROM orders WHERE id = $id AND user_id = $uid");
if (!$res || $res->num_rows === 0) {
    header('Location: account.php?tab=orders');
    exit;
}
$order = $res->fetch_assoc();

// Xử lý Hủy đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if (in_array($order['status'], ['pending', 'confirmed'])) {
        $conn->query("UPDATE orders SET status = 'cancelled' WHERE id = $id AND user_id = $uid");
        header("Location: order_detail.php?id=$id");
        exit;
    }
}

// Load items
$items = [];
$resItems = $conn->query(
    "SELECT oi.*, p.name, p.image
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = $id"
);
if ($resItems) {
    while ($row = $resItems->fetch_assoc()) $items[] = $row;
}

$statusMap = [
    'pending'   => ['Chờ xác nhận', 'badge-pending'],
    'confirmed' => ['Đã xác nhận',  'badge-confirmed'],
    'shipping'  => ['Đang giao',    'badge-shipping'],
    'delivered' => ['Đã giao',      'badge-delivered'],
    'cancelled' => ['Đã hủy',       'badge-cancelled'],
];
$s = $statusMap[$order['status']] ?? ['Không rõ', 'badge-pending'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng #<?= $order['id'] ?> — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);max-width:800px;">

    <a href="account.php?tab=orders" style="display:inline-flex;align-items:center;gap:var(--sp-1);color:var(--clr-brand);font-weight:600;font-size:var(--text-sm);margin-bottom:var(--sp-4);">
        ← Quay lại đơn hàng
    </a>

    <div class="account-form-card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:var(--sp-3);margin-bottom:var(--sp-5);">
            <h1 style="font-size:var(--text-xl);font-weight:800;">Đơn hàng #<?= $order['id'] ?></h1>
            <span class="order-badge <?= $s[1] ?>"><?= $s[0] ?></span>
        </div>

        <!-- Thông tin giao hàng -->
        <div style="background:var(--clr-surface-raised);padding:var(--sp-5);border-radius:var(--r-md);margin-bottom:var(--sp-5);line-height:1.8;">
            <h3 style="font-size:var(--text-md);font-weight:700;margin-bottom:var(--sp-2);">Thông tin giao hàng</h3>
            <p><strong>Người nhận:</strong> <?= htmlspecialchars($order['receiver_name']) ?></p>
            <p><strong>SĐT:</strong> <?= htmlspecialchars($order['receiver_phone']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= nl2br(htmlspecialchars($order['receiver_address'])) ?></p>
            <p><strong>Thanh toán:</strong> <?= $order['payment_method'] === 'COD' ? 'COD — Thanh toán khi nhận hàng' : 'Thanh toán online' ?></p>
            <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>

        <!-- Sản phẩm -->
        <h3 style="font-size:var(--text-md);font-weight:700;margin-bottom:var(--sp-3);">Sản phẩm đã đặt</h3>
        <div class="order-items-list">
            <?php foreach ($items as $item): ?>
            <div class="order-item-row">
                <img src="assets/images/<?= htmlspecialchars($item['image'] ?? 'placeholder.jpg') ?>"
                     alt="" class="order-item-img" loading="lazy">
                <div class="order-item-info">
                    <p class="order-item-name"><?= htmlspecialchars($item['name'] ?? 'Sản phẩm') ?></p>
                    <p class="order-item-qty">SL: <?= $item['quantity'] ?></p>
                </div>
                <span class="order-item-price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <hr style="border:none;border-top:1px dashed var(--clr-border);margin:var(--sp-5) 0;">

        <div style="display:flex;justify-content:space-between;align-items:center;">
            <strong style="font-size:var(--text-lg);">Tổng cộng:</strong>
            <span style="font-size:var(--text-xl);font-weight:800;color:var(--clr-brand);"><?= formatPrice($order['total']) ?></span>
        </div>

        <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
            <div style="margin-top:var(--sp-5); padding-top:var(--sp-5); border-top:1px solid var(--clr-border); text-align:right;">
                <form method="POST" onsubmit="return confirm('Bạn có thực sự muốn hủy đơn hàng này không? Hành động này không thể hoàn tác.');">
                    <button type="submit" name="cancel_order" value="1" style="background:var(--clr-error, #dc2626); color:white; border:none; padding:12px 24px; border-radius:var(--r-md); font-weight:600; font-family:inherit; cursor:pointer;">
                        ✕ Hủy đơn hàng
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>
