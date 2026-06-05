<?php
session_start();
require './config/db.php';
require './config/auth.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Bắt buộc đăng nhập khi thêm vào giỏ
if ($action === 'add' && !isLoggedIn()) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

if ($action === 'add' && isset($_POST['product_id'])) {
    $pid = (int)$_POST['product_id'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (!isset($_SESSION['cart'][$pid])) {
        $p = $conn->query("SELECT id,name,price,image,stock,category_id FROM products WHERE id=$pid")->fetch_assoc();
        if ($p) {
            $_SESSION['cart'][$pid] = ['id'=>$pid,'name'=>$p['name'],'price'=>$p['price'],
                                       'image'=>$p['image'],'stock'=>$p['stock'],'category_id'=>$p['category_id'],'qty'=>0];
        }
    }
    if (isset($_SESSION['cart'][$pid])) {
        $new_qty = $_SESSION['cart'][$pid]['qty'] + $qty;
        $_SESSION['cart'][$pid]['qty'] = min($new_qty, $_SESSION['cart'][$pid]['stock']);
    }
    $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    header("Location: $referer"); exit;
}

if ($action === 'remove' && isset($_GET['id'])) {
    unset($_SESSION['cart'][(int)$_GET['id']]);
    header('Location: cart.php'); exit;
}

if ($action === 'update' && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $pid => $qty) {
        $pid = (int)$pid; $qty = max(0, (int)$qty);
        if ($qty === 0) unset($_SESSION['cart'][$pid]);
        elseif (isset($_SESSION['cart'][$pid])) {
            $_SESSION['cart'][$pid]['qty'] = min($qty, $_SESSION['cart'][$pid]['stock']);
        }
    }
    header('Location: cart.php'); exit;
}

$cart  = getCart();
$total = 0;
foreach ($cart as $item) $total += $item['price'] * $item['qty'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">
    <h1 style="font-size:var(--text-2xl);font-weight:800;margin-bottom:var(--sp-5);color:var(--clr-text-primary);">
        🛒 Giỏ hàng của bạn
    </h1>

    <?php if (empty($cart)): ?>
        <div class="empty-cart">
            <img src="https://cdn-icons-png.flaticon.com/512/11329/11329060.png"
                 alt="Giỏ hàng trống" style="width:120px;opacity:.5;margin-bottom:var(--sp-5);">
            <h2>Giỏ hàng của bạn đang trống!</h2>
            <a href="products.php" class="btn-continue" style="margin-top:var(--sp-4);">← Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="cart-wrapper">

            <!-- Danh sách sản phẩm -->
            <div class="cart-main">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style="text-align:center">Đơn giá</th>
                                <th style="text-align:center">Số lượng</th>
                                <th style="text-align:right">Thành tiền</th>
                                <th style="text-align:center">Xóa</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($cart as $item): ?>
                        <tr>
                            <td data-label="Sản phẩm">
                                <div class="product-col">
                                    <img src="assets/images/<?= htmlspecialchars($item['image'] ?: 'placeholder.jpg') ?>"
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                    <a href="product_detail.php?id=<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </a>
                                </div>
                            </td>
                            <td data-label="Đơn giá" style="text-align:center;color:var(--clr-text-secondary);">
                                <?= formatPrice($item['price']) ?>
                            </td>
                            <td data-label="Số lượng" style="text-align:center;">
                                <input type="number" name="quantities[<?= $item['id'] ?>]"
                                       class="qty-input"
                                       value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>">
                            </td>
                            <td data-label="Thành tiền" style="text-align:right;font-weight:700;color:var(--clr-brand);">
                                <?= formatPrice($item['price'] * $item['qty']) ?>
                            </td>
                            <td data-label="Xóa" style="text-align:center;">
                                <a href="cart.php?action=remove&id=<?= $item['id'] ?>"
                                   class="btn-remove"
                                   aria-label="Xóa <?= htmlspecialchars($item['name']) ?>"
                                   onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">✕</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <button type="submit" class="btn-update">↻ Cập nhật số lượng</button>
                    <div style="clear:both;"></div>
                </form>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="cart-sidebar">
                <h3 style="border-bottom:1px solid var(--clr-border-muted);padding-bottom:var(--sp-4);margin-bottom:var(--sp-5);font-size:var(--text-lg);font-weight:700;">
                    Tóm tắt đơn hàng
                </h3>
                <div class="summary-row">
                    <span>Tạm tính:</span>
                    <strong><?= formatPrice($total) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Phí giao hàng:</span>
                    <span style="color:var(--clr-success);font-weight:600;">Miễn phí ✓</span>
                </div>
                <hr style="border:none;border-top:1px dashed var(--clr-border-muted);margin:var(--sp-4) 0;">
                <div class="summary-row">
                    <strong>Tổng cộng:</strong>
                    <span class="summary-total"><?= formatPrice($total) ?></span>
                </div>
                <p style="font-size:var(--text-xs);color:var(--clr-text-tertiary);text-align:right;margin-top:calc(var(--sp-2)*-1);">(Đã bao gồm VAT nếu có)</p>
                <a href="checkout.php" class="btn-checkout" style="margin-top:var(--sp-5);">Tiến hành đặt hàng →</a>
                <a href="products.php" style="display:block;text-align:center;margin-top:var(--sp-3);color:var(--clr-brand);font-size:var(--text-sm);">
                    ← Tiếp tục mua sắm
                </a>
            </div>

        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>