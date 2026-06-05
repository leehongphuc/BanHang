<?php
/* ========== search.php ========== */
session_start();
require './config/db.php';

// STT 9 - Tìm kiếm theo từ khóa có phân trang
$q = trim($_GET['q'] ?? '');
$per_page = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$products = [];
$total_pages = 0;
$total_row = 0;

if ($q !== '') {
    $esc = $conn->real_escape_string($q);
    $total_row = $conn->query("SELECT COUNT(*) as c FROM products
        WHERE name LIKE '%$esc%' OR description LIKE '%$esc%'")->fetch_assoc()['c'];
    $total_pages = ceil($total_row / $per_page);
    $res = $conn->query("SELECT * FROM products WHERE name LIKE '%$esc%' OR description LIKE '%$esc%'
                          ORDER BY views DESC LIMIT $per_page OFFSET $offset");
    while($r = $res->fetch_assoc()) $products[] = $r;
}

// Load active vouchers for price display
$activeVouchers = [];
$vRes = $conn->query("SELECT * FROM vouchers WHERE is_active=1 AND (end_date IS NULL OR end_date > NOW()) AND (usage_limit = 0 OR used_count < usage_limit)");
if ($vRes) while ($v = $vRes->fetch_assoc()) $activeVouchers[] = $v;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tìm kiếm: <?= htmlspecialchars($q) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-top:24px;">
    <h2>Kết quả tìm kiếm: "<?= htmlspecialchars($q) ?>"</h2>
    <p>Tìm thấy <?= $total_row ?> sản phẩm</p>

    <?php if (empty($products)): ?>
        <p>Không tìm thấy sản phẩm phù hợp.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach($products as $p): ?>
                <?php include 'product_card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$total_pages;$i++): ?>
                <a href="?q=<?=urlencode($q)?>&page=<?=$i?>"
                   class="<?=$i==$page?'active':''?>"><?=$i?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
</body>
</html>


<?php
