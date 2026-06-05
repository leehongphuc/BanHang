<?php
session_start();
require './config/db.php';
require './config/auth.php';

// Bảo vệ trang: Yêu cầu đăng nhập
$user = currentUser();
if ($user === null) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$userId = (int)$user['id'];

// Lấy danh sách sản phẩm yêu thích của user
$query = "SELECT p.* FROM products p
          INNER JOIN wishlist w ON p.id = w.product_id
          WHERE w.user_id = $userId
          ORDER BY w.created_at DESC";
$products = $conn->query($query);
$totalWishlist = $products ? $products->num_rows : 0;

// Load active vouchers for price display in cards
$activeVouchers = [];
$vRes = $conn->query("SELECT * FROM vouchers WHERE is_active=1 AND (end_date IS NULL OR end_date > NOW()) AND (usage_limit = 0 OR used_count < usage_limit)");
if ($vRes) {
    while ($v = $vRes->fetch_assoc()) {
        $activeVouchers[] = $v;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích — TechStore</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        .wishlist-empty {
            text-align: center;
            padding: var(--sp-10) var(--sp-6);
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            max-width: 600px;
            margin: var(--sp-6) auto;
        }
        .wishlist-empty-icon {
            font-size: 64px;
            color: var(--clr-text-tertiary);
            margin-bottom: var(--sp-4);
        }
        .wishlist-empty-title {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-2);
        }
        .wishlist-empty-desc {
            font-size: var(--text-md);
            color: var(--clr-text-secondary);
            margin-bottom: var(--sp-6);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">
        <!-- Page Title -->
        <div class="section-header" style="margin-bottom:var(--sp-6); display:flex; justify-content:space-between; align-items:center;">
            <h1 class="section-title">Sản phẩm <span>yêu thích</span></h1>
            <span style="font-size:var(--text-sm);color:var(--clr-text-secondary);">
                Đang lưu <strong><?= $totalWishlist ?></strong> sản phẩm
            </span>
        </div>

        <?php if ($totalWishlist === 0): ?>
            <!-- Empty state -->
            <div class="wishlist-empty">
                <div class="wishlist-empty-icon">❤️</div>
                <h2 class="wishlist-empty-title">Danh sách yêu thích trống</h2>
                <p class="wishlist-empty-desc">Hãy duyệt qua các sản phẩm của TechStore và bấm nút trái tim để lưu lại những sản phẩm bạn thích nhé!</p>
                <a href="products.php" class="btn-add-cart" style="max-width:220px; margin:0 auto; text-decoration:none;">
                    Khám phá sản phẩm
                </a>
            </div>
        <?php else: ?>
            <!-- Product Grid -->
            <div class="product-grid" role="list" aria-label="Danh sách sản phẩm yêu thích">
                <?php while ($p = $products->fetch_assoc()): ?>
                    <?php include 'product_card.php'; ?>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="toast-container" id="toastContainer" role="region" aria-live="polite"></div>
    <?php include 'footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>
