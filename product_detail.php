<?php
session_start();
require './config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: products.php'); exit; }

$product = $conn->query("SELECT p.*, c.name as cat_name, b.name as brand_name FROM products p
    LEFT JOIN categories c ON p.category_id=c.id
    LEFT JOIN brands b ON p.brand_id=b.id
    WHERE p.id=$id")->fetch_assoc();
if (!$product) { header('Location: products.php'); exit; }

$conn->query("UPDATE products SET views=views+1 WHERE id=$id");

// Submit review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $name   = $conn->real_escape_string(trim($_POST['reviewer_name'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $cmt    = $conn->real_escape_string(trim($_POST['comment'] ?? ''));
    if ($name && $rating >= 1 && $rating <= 5 && $cmt) {
        $conn->query("INSERT INTO reviews (product_id,reviewer_name,rating,comment)
                      VALUES ($id,'$name',$rating,'$cmt')");
        header("Location: product_detail.php?id=$id#reviews"); exit;
    }
}

// Related products
$related = $conn->query("SELECT * FROM products WHERE category_id={$product['category_id']} AND id != $id LIMIT 4");

// Reviews
$reviews    = $conn->query("SELECT * FROM reviews WHERE product_id=$id ORDER BY created_at DESC");
$avg_rating = (float)($conn->query("SELECT AVG(rating) as avg FROM reviews WHERE product_id=$id")->fetch_assoc()['avg'] ?? 0);
$inStock    = $product['stock'] > 0;

// Load variants
$variantsResult = $conn->query("SELECT * FROM product_variants WHERE product_id=$id AND is_active=1 ORDER BY sort_order,id");
$versions = [];
$colors   = [];
while ($v = $variantsResult->fetch_assoc()) {
    if ($v['type'] === 'version') $versions[] = $v;
    else                          $colors[]   = $v;
}
$basePrice = (float)$product['price'];

// Load active vouchers for price display
$activeVouchers = [];
$vRes = $conn->query("SELECT * FROM vouchers WHERE is_active=1 AND (end_date IS NULL OR end_date > NOW()) AND (usage_limit = 0 OR used_count < usage_limit)");
if ($vRes) while ($v = $vRes->fetch_assoc()) $activeVouchers[] = $v;

// Load product specifications
$specsResult = $conn->query("SELECT * FROM product_specifications WHERE product_id=$id ORDER BY spec_group, sort_order, id");
$specGroups = [];
if ($specsResult) {
    while ($s = $specsResult->fetch_assoc()) {
        $specGroups[$s['spec_group']][] = $s;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — TechStore</title>
    <meta name="description" content="<?= htmlspecialchars(mb_substr($product['description'] ?? '', 0, 155)) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        /* ---- Detail-specific overrides ---- */
        .detail-wrap {
            display: grid;
            grid-template-columns: 420px 1fr;
            gap: var(--sp-8);
            align-items: stretch;
            margin-top: var(--sp-6);
        }
        @media(max-width:768px){ .detail-wrap{ grid-template-columns:1fr; } }

        /* Image pane */
        .detail-image-pane {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            padding: var(--sp-6);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .detail-main-img {
            width: 100%;
            aspect-ratio: 1/1;
            object-fit: contain;
            border-radius: var(--r-sm);
            background: var(--clr-surface-raised);
            padding: var(--sp-4);
        }

        /* Info pane */
        .detail-info {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            padding: var(--sp-7);
        }
        .detail-breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--sp-2);
            font-size: var(--text-sm);
            color: var(--clr-text-secondary);
            margin-bottom: var(--sp-3);
        }
        .detail-breadcrumb a { color: var(--clr-text-secondary); transition: color var(--dur-fast); }
        .detail-breadcrumb a:hover { color: var(--clr-brand); }
        .detail-breadcrumb .sep { color: var(--clr-text-tertiary); }

        .detail-title {
            font-size: var(--text-2xl);
            font-weight: 800;
            line-height: var(--lh-heading, 1.35);
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-3);
        }

        .detail-rating {
            display: flex;
            align-items: center;
            gap: var(--sp-2);
            margin-bottom: var(--sp-4);
        }
        .detail-rating .stars { display: flex; gap: 2px; }
        .detail-rating .star-count {
            font-size: var(--text-sm);
            color: var(--clr-text-secondary);
        }

        .detail-price-block {
            display: inline-block;
            margin-bottom: var(--sp-5);
            padding: 16px 24px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 5px solid var(--clr-brand);
        }
        .detail-price-current {
            font-size: 32px;
            font-weight: 800;
            color: var(--clr-brand);
            font-family: inherit;
            line-height: 1;
            letter-spacing: -0.5px;
        }

        .detail-stock {
            display: inline-flex;
            align-items: center;
            gap: var(--sp-2);
            font-size: var(--text-sm);
            font-weight: 600;
            padding: var(--sp-1) var(--sp-3);
            border-radius: var(--r-full);
            margin-bottom: var(--sp-5);
        }
        .detail-stock.in { background: var(--clr-success-light, #E3FCEF); color: var(--clr-success); }
        .detail-stock.out { background: var(--clr-error-light, #FFEBE6); color: var(--clr-error); }

        .detail-qty-row {
            display: flex;
            align-items: center;
            gap: var(--sp-4);
            margin-bottom: var(--sp-5);
        }
        .detail-qty-label {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--clr-text-secondary);
            white-space: nowrap;
        }
        .detail-qty-control {
            display: flex;
            align-items: center;
            border: 1.5px solid var(--clr-border);
            border-radius: var(--r-full);
            overflow: hidden;
        }
        .detail-qty-btn {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            color: var(--clr-text-secondary);
            transition: all var(--dur-fast);
            cursor: pointer;
            background: none;
            border: none;
        }
        .detail-qty-btn:hover { background: var(--clr-surface-overlay); color: var(--clr-brand); }
        .detail-qty-input {
            width: 52px; height: 40px;
            text-align: center;
            border: none;
            font-family: var(--font-mono);
            font-size: var(--text-base);
            font-weight: 700;
            color: var(--clr-text-primary);
            background: none;
        }
        .detail-qty-input:focus { outline: none; }

        .detail-btn-cart {
            display: flex; align-items: center; justify-content: center; gap: var(--sp-2);
            flex: 1; height: 52px;
            background: var(--clr-brand);
            color: white;
            border-radius: var(--r-full);
            font-size: var(--text-md);
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all var(--dur-base) var(--ease);
            margin-bottom: var(--sp-3);
        }
        .detail-btn-cart:hover { background: var(--clr-brand-hover); transform: translateY(-2px); box-shadow: var(--shadow-btn); }
        .detail-btn-cart:disabled { background: var(--clr-border); color: var(--clr-text-tertiary); cursor: not-allowed; transform: none; box-shadow: none; }

        /* Description & Reviews */
        .detail-section {
            background: var(--clr-surface);
            border-radius: var(--r-md);
            box-shadow: var(--shadow-card);
            padding: var(--sp-7);
            margin-top: var(--sp-5);
        }
        .detail-section-title {
            font-size: var(--text-xl);
            font-weight: 700;
            margin-bottom: var(--sp-5);
            padding-bottom: var(--sp-3);
            border-bottom: 2px solid var(--clr-border-muted);
            color: var(--clr-text-primary);
        }
        .detail-section-title span { color: var(--clr-brand); }

        /* Review items */
        .review-item {
            padding: var(--sp-4) 0;
            border-bottom: 1px solid var(--clr-border-muted);
        }
        .review-item:last-child { border-bottom: none; }
        .review-header { display: flex; align-items: center; gap: var(--sp-3); margin-bottom: var(--sp-2); flex-wrap: wrap; }
        .review-avatar {
            width: 36px; height: 36px;
            border-radius: var(--r-full);
            background: var(--clr-brand-light);
            display: flex; align-items: center; justify-content: center;
            font-size: var(--text-base);
            font-weight: 700;
            color: var(--clr-brand);
            flex-shrink: 0;
        }
        .review-author { font-weight: 700; font-size: var(--text-base); }
        .review-date { font-size: var(--text-xs); color: var(--clr-text-tertiary); margin-left: auto; }
        .review-body { font-size: var(--text-base); color: var(--clr-text-secondary); line-height: 1.7; margin-top: var(--sp-2); }

        /* Review form */
        .review-form-wrap {
            background: var(--clr-surface-raised);
            border-radius: var(--r-md);
            padding: var(--sp-6);
            margin-bottom: var(--sp-6);
        }
        .review-form-title { font-size: var(--text-lg); font-weight: 700; margin-bottom: var(--sp-4); }
        .review-form-wrap .form-group { margin-bottom: var(--sp-4); }
        .review-form-wrap .form-group label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 600;
            margin-bottom: var(--sp-2);
            color: var(--clr-text-secondary);
        }
        .review-form-wrap input[type=text],
        .review-form-wrap textarea {
            width: 100%;
            padding: var(--sp-3) var(--sp-4);
            border: 1.5px solid var(--clr-border);
            border-radius: var(--r-sm);
            font-family: inherit;
            font-size: var(--text-base);
            transition: border-color var(--dur-base);
        }
        .review-form-wrap input:focus,
        .review-form-wrap textarea:focus {
            outline: none;
            border-color: var(--clr-brand);
            box-shadow: 0 0 0 3px var(--clr-brand-light);
        }
        .star-select { display: flex; flex-direction: row-reverse; gap: var(--sp-1); justify-content: flex-end; }
        .star-select input { display: none; }
        .star-select label { font-size: 28px; color: var(--clr-border); cursor: pointer; transition: color var(--dur-fast); }
        .star-select input:checked ~ label,
        .star-select label:hover,
        .star-select label:hover ~ label { color: var(--clr-star); }

        .btn-submit-review {
            background: var(--clr-brand);
            color: white;
            padding: var(--sp-3) var(--sp-7);
            border-radius: var(--r-full);
            border: none;
            font-family: inherit;
            font-size: var(--text-base);
            font-weight: 700;
            cursor: pointer;
            transition: all var(--dur-base) var(--ease);
        }
        .btn-submit-review:hover { background: var(--clr-brand-hover); transform: translateY(-1px); box-shadow: var(--shadow-btn); }

        /* Related */
        .related-section { margin-top: var(--sp-5); }

        /* ---- Specifications Section ---- */
        .specs-section-inner {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .specs-group {
            border-radius: var(--r-md);
            overflow: hidden;
            border: 1.5px solid var(--clr-border);
            transition: border-color .2s, box-shadow .2s;
        }
        .specs-group:hover {
            border-color: var(--clr-brand);
            box-shadow: 0 2px 12px rgba(0,82,204,.08);
        }
        .specs-group-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 22px;
            background: var(--clr-surface);
            border-bottom: 1.5px solid var(--clr-border-muted);
            cursor: pointer;
            user-select: none;
            transition: background .15s;
        }
        .specs-group-header:hover { background: var(--clr-surface-raised); }
        .specs-group-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            background: var(--clr-brand-light);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            color: var(--clr-brand);
            transition: transform .2s var(--ease);
        }
        .specs-group:hover .specs-group-icon { transform: scale(1.08); }
        .specs-group-icon svg { width: 18px; height: 18px; }
        .specs-group-title {
            font-size: var(--text-md);
            font-weight: 700;
            color: var(--clr-text-primary);
            flex: 1;
        }
        .specs-group-count {
            font-size: var(--text-xs);
            color: var(--clr-text-tertiary);
            font-weight: 500;
            background: var(--clr-surface-overlay);
            padding: 3px 10px;
            border-radius: var(--r-full);
        }

        .specs-row {
            display: grid;
            grid-template-columns: 210px 1fr;
            min-height: 48px;
            transition: background .12s;
        }
        .specs-row:nth-child(odd) { background: var(--clr-surface); }
        .specs-row:nth-child(even) { background: var(--clr-surface-raised); }
        .specs-row:hover { background: var(--clr-brand-light); }
        .specs-row:last-child .specs-name,
        .specs-row:last-child .specs-value { border-bottom: none; }

        .specs-name {
            padding: 14px 22px;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--clr-text-secondary);
            border-right: 1px solid var(--clr-border-muted);
            border-bottom: 1px solid var(--clr-border-muted);
            display: flex;
            align-items: center;
            line-height: 1.5;
        }
        .specs-value {
            padding: 14px 22px;
            font-size: var(--text-sm);
            color: var(--clr-text-primary);
            font-weight: 500;
            border-bottom: 1px solid var(--clr-border-muted);
            display: flex;
            align-items: center;
            line-height: 1.5;
        }

        @media(max-width:600px) {
            .specs-row { grid-template-columns: 130px 1fr; }
            .specs-name, .specs-value { padding: 11px 14px; font-size: 12.5px; }
            .specs-group-header { padding: 14px 16px; gap: 10px; }
            .specs-group-icon { width: 32px; height: 32px; border-radius: 8px; }
            .specs-group-icon svg { width: 16px; height: 16px; }
            .specs-group-title { font-size: var(--text-base); }
        }

        /* ---- Variants (phiên bản, màu sắc) ---- */
        .variant-section {
            margin-bottom: var(--sp-5);
        }
        .variant-label {
            font-size: var(--text-sm);
            font-weight: 700;
            color: var(--clr-text-primary);
            margin-bottom: var(--sp-3);
        }
        .variant-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        /* Version buttons */
        .version-btn {
            padding: 10px 18px;
            border: 1.5px solid var(--clr-border);
            border-radius: var(--r-sm);
            background: var(--clr-surface);
            font-family: inherit;
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--clr-text-primary);
            cursor: pointer;
            transition: all .15s;
            position: relative;
        }
        .version-btn:hover {
            border-color: var(--clr-brand);
            color: var(--clr-brand);
        }
        .version-btn.selected {
            border-color: var(--clr-brand);
            color: var(--clr-brand);
            background: var(--clr-brand-light, #eff6ff);
        }
        .version-btn.selected::after {
            content: '✓';
            position: absolute;
            top: -8px; right: -8px;
            width: 18px; height: 18px;
            background: var(--clr-brand);
            color: white;
            border-radius: 50%;
            font-size: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        /* Color buttons */
        .color-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            border: 1.5px solid var(--clr-border);
            border-radius: var(--r-sm);
            background: var(--clr-surface);
            font-family: inherit;
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--clr-text-primary);
            cursor: pointer;
            transition: all .15s;
            position: relative;
        }
        .color-btn:hover { border-color: var(--clr-brand); }
        .color-btn.selected {
            border-color: var(--clr-brand);
            background: var(--clr-brand-light, #eff6ff);
        }
        .color-btn.selected::after {
            content: '✓';
            position: absolute;
            top: -8px; right: -8px;
            width: 18px; height: 18px;
            background: var(--clr-brand);
            color: white;
            border-radius: 50%;
            font-size: 10px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .color-swatch {
            width: 20px; height: 20px;
            border-radius: 50%;
            border: 1.5px solid rgba(0,0,0,.12);
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container" style="margin-bottom: var(--sp-10);">

    <!-- 2-column detail layout -->
    <div class="detail-wrap">

        <!-- LEFT: Image -->
        <div class="detail-image-pane">
            <img src="assets/images/<?= htmlspecialchars($product['image'] ?: 'placeholder.jpg') ?>"
                 alt="<?= htmlspecialchars($product['name']) ?> - ảnh sản phẩm"
                 class="detail-main-img"
                 id="mainImg">
        </div>

        <!-- RIGHT: Info -->
        <div class="detail-info">

            <!-- Breadcrumb -->
            <nav class="detail-breadcrumb" aria-label="Breadcrumb">
                <a href="products.php">Sản phẩm</a>
                <span class="sep">›</span>
                <a href="products.php?category=<?= $product['category_id'] ?>">
                    <?= htmlspecialchars($product['cat_name'] ?? 'Danh mục') ?>
                </a>
                <span class="sep">›</span>
                <span aria-current="page"><?= htmlspecialchars($product['name']) ?></span>
            </nav>

            <!-- Title -->
            <h1 class="detail-title" itemprop="name"><?= htmlspecialchars($product['name']) ?></h1>
            
            <?php if (!empty($product['brand_name'])): ?>
            <div style="font-size:13px; color:var(--clr-text-secondary); margin-bottom:var(--sp-4);">
                Thương hiệu: <strong style="color:var(--clr-brand);"><?= htmlspecialchars($product['brand_name']) ?></strong>
            </div>
            <?php endif; ?>

            <!-- Rating -->
            <div class="detail-rating"
                 aria-label="Đánh giá: <?= number_format($avg_rating, 1) ?> trên 5 sao, <?= $reviews->num_rows ?> đánh giá">
                <div class="stars" aria-hidden="true">
                    <?php for($i=1;$i<=5;$i++): ?>
                        <svg class="star <?= $i <= round($avg_rating) ? '' : 'empty' ?>"
                             viewBox="0 0 24 24" style="width:18px;height:18px;">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    <?php endfor; ?>
                </div>
                <span class="star-count">
                    <?= number_format($avg_rating, 1) ?> (<?= $reviews->num_rows ?> đánh giá)
                </span>
            </div>

            <!-- Price -->
            <?php 
            $pCat = (int)$product['category_id'];
            $oldPrice = $product['old_price'] ?? 0;
            $currentPrice = $product['price'];

            // Nếu chưa có old_price thủ công, tính từ voucher
            $voucherDiscountValue = 0;
            $voucherDiscountType = '';
            if (!($oldPrice > $currentPrice)) {
                $bestDiscountAmount = 0;
                foreach ($activeVouchers as $v) {
                    if (!$v['category_id'] || $v['category_id'] == $pCat) {
                        if ($v['discount_type'] === 'percent') {
                            $disc = ($currentPrice * $v['discount_value']) / 100;
                        } else {
                            $disc = min((float)$v['discount_value'], $currentPrice);
                        }
                        if ($disc > $bestDiscountAmount) {
                            $bestDiscountAmount = $disc;
                            $voucherDiscountValue = $v['discount_value'];
                            $voucherDiscountType = $v['discount_type'];
                        }
                    }
                }
                if ($bestDiscountAmount > 0) {
                    $oldPrice = $currentPrice;
                    $currentPrice = round($currentPrice - $bestDiscountAmount);
                }
            }

            $hasDiscount = $oldPrice > $currentPrice;
            $discountPercent = $hasDiscount ? round((($oldPrice - $currentPrice) / $oldPrice) * 100) : 0;
            ?>
            <div class="detail-price-block" style="display:flex; align-items:center; gap:12px; margin-bottom:var(--sp-4);">
                <div class="detail-price-current" id="detail-price-display"
                     aria-label="Giá: <?= formatPrice($currentPrice) ?>"
                     style="margin-bottom:0;"
                     data-base-price="<?= (int)$product['price'] ?>"
                     data-voucher-val="<?= (float)$voucherDiscountValue ?>"
                     data-voucher-type="<?= $voucherDiscountType ?>"
                     data-has-manual-discount="<?= ($product['old_price'] > $product['price']) ? 'true' : 'false' ?>"
                     data-manual-old-price="<?= (int)$product['old_price'] ?>">
                    <?= formatPrice($currentPrice) ?>
                </div>
                <div id="detail-price-old-wrap" style="display: <?= $hasDiscount ? 'flex' : 'none' ?>; align-items:center; gap:12px;">
                    <div id="detail-price-old" style="font-size:18px; color:var(--clr-text-tertiary); text-decoration:line-through; font-weight:600;">
                        <?= formatPrice($oldPrice) ?>
                    </div>
                    <div id="detail-discount-badge" style="background:var(--clr-error-light); color:var(--clr-error); padding:4px 8px; border-radius:6px; font-size:14px; font-weight:700;">
                        -<?= $discountPercent ?>%
                    </div>
                </div>
            </div>

            <!-- Versions -->
            <?php if (!empty($versions)): ?>
            <div class="variant-section">
                <div class="variant-label">Phiên bản</div>
                <div class="variant-grid">
                    <?php foreach ($versions as $i => $v): ?>
                    <button type="button"
                            class="variant-btn version-btn <?= $i === 0 ? 'selected' : '' ?>"
                            data-price="<?= (int)$v['price'] ?: (int)$basePrice ?>"
                            onclick="selectVariant(this, 'version')">
                        <?= htmlspecialchars($v['name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Colors -->
            <?php if (!empty($colors)): ?>
            <div class="variant-section">
                <div class="variant-label">Màu sắc</div>
                <div class="variant-grid">
                    <?php foreach ($colors as $i => $c): ?>
                    <button type="button"
                            class="variant-btn color-btn <?= $i === 0 ? 'selected' : '' ?>"
                            data-price="<?= (int)$c['price'] ?: (int)$basePrice ?>"
                            onclick="selectVariant(this, 'color')">
                        <span class="color-swatch" style="background:<?= htmlspecialchars($c['color_hex'] ?? '#888') ?>;"></span>
                        <span class="color-name"><?= htmlspecialchars($c['name']) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stock -->
            <span class="detail-stock <?= $inStock ? 'in' : 'out' ?>">
                <?php if ($inStock): ?>
                    ✓ Còn <?= $product['stock'] ?> sản phẩm
                <?php else: ?>
                    ✕ Hết hàng
                <?php endif; ?>
            </span>

            <!-- Add to Cart Form -->
            <form method="POST" action="cart.php" class="ajax-cart-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <div style="display:flex; gap:var(--sp-4); align-items:flex-end; margin-bottom:var(--sp-3); flex-wrap:wrap;">
                    <?php if ($inStock): ?>
                    <div class="detail-qty-row" style="margin-bottom:0;">
                        <span class="detail-qty-label" style="display:block; margin-bottom:8px;">Số lượng:</span>
                        <div class="detail-qty-control" aria-label="Chọn số lượng">
                            <button type="button" class="detail-qty-btn" aria-label="Giảm" onclick="changeDetailQty(-1)">−</button>
                            <input type="number" name="qty" id="detail-qty" class="detail-qty-input" value="1" min="1" max="<?= $product['stock'] ?>" aria-label="Số lượng">
                            <button type="button" class="detail-qty-btn" aria-label="Tăng" onclick="changeDetailQty(1)">+</button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="detail-btn-cart" style="margin-bottom:0;" <?= !$inStock ? 'disabled aria-disabled="true"' : '' ?>>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                        </svg>
                        <?= $inStock ? 'Thêm vào giỏ hàng' : 'Hết hàng' ?>
                    </button>

                    <?php $isWishlisted = in_array((int)$product['id'], $wishlistProductIds ?? []); ?>
                    <button type="button"
                            class="btn-wishlist detail-btn-wishlist <?= $isWishlisted ? 'active' : '' ?>"
                            aria-label="Thêm sản phẩm vào yêu thích"
                            aria-pressed="<?= $isWishlisted ? 'true' : 'false' ?>"
                            onclick="toggleWishlist(this, <?= (int)$product['id'] ?>)">
                        <svg viewBox="0 0 24 24" fill="<?= $isWishlisted ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Quick meta -->
            <div style="margin-top:var(--sp-5);padding-top:var(--sp-5);border-top:1px solid var(--clr-border-muted);display:flex;flex-direction:column;gap:var(--sp-2);">
                <div style="display:flex;gap:var(--sp-3);font-size:var(--text-sm);color:var(--clr-text-secondary);">
                    <span>🚚</span><span>Miễn phí vận chuyển cho đơn trên 500K</span>
                </div>
                <div style="display:flex;gap:var(--sp-3);font-size:var(--text-sm);color:var(--clr-text-secondary);">
                    <span>🔄</span><span>Đổi trả trong 30 ngày</span>
                </div>
                <div style="display:flex;gap:var(--sp-3);font-size:var(--text-sm);color:var(--clr-text-secondary);">
                    <span>🛡️</span><span>Bảo hành chính hãng 12 tháng</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Description -->
    <div class="detail-section">
        <h2 class="detail-section-title">Mô tả <span>sản phẩm</span></h2>
        <div style="font-size:var(--text-md);color:var(--clr-text-secondary);line-height:1.8;">
            <?= nl2br(htmlspecialchars($product['description'] ?? 'Chưa có mô tả.')) ?>
        </div>
    </div>

    <!-- Specifications -->
    <?php if (!empty($specGroups)): ?>
    <div class="detail-section" id="specifications">
        <h2 class="detail-section-title">Thông số <span>kỹ thuật</span></h2>
        <div class="specs-section-inner">
            <?php
            // SVG icon map for spec groups
            $groupIcons = [
                'Màn hình'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
                'Hiệu năng'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                'Camera'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>',
                'Pin & Sạc'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="6" width="18" height="12" rx="2"/><line x1="23" y1="13" x2="23" y2="11"/><line x1="7" y1="10" x2="7" y2="14"/><line x1="11" y1="10" x2="11" y2="14"/></svg>',
                'Kết nối'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.55a11 11 0 0114.08 0"/><path d="M1.42 9a16 16 0 0121.16 0"/><path d="M8.53 16.11a6 6 0 016.95 0"/><circle cx="12" cy="20" r="1"/></svg>',
                'Thiết kế'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>',
                'Hệ điều hành'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/></svg>',
                'Lưu trữ'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
                'Âm thanh'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0118 0v6"/><path d="M21 19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h3zM3 19a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H3z"/></svg>',
                'Thông số chung' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
            ];
            foreach ($specGroups as $groupName => $specs):
                $icon = $groupIcons[$groupName] ?? $groupIcons['Thông số chung'];
                $count = count($specs);
            ?>
            <div class="specs-group">
                <div class="specs-group-header">
                    <span class="specs-group-icon"><?= $icon ?></span>
                    <span class="specs-group-title"><?= htmlspecialchars($groupName) ?></span>
                    <span class="specs-group-count"><?= $count ?> thông số</span>
                </div>
                <div class="specs-group-body">
                    <?php foreach ($specs as $spec): ?>
                    <div class="specs-row">
                        <div class="specs-name"><?= htmlspecialchars($spec['spec_name']) ?></div>
                        <div class="specs-value"><?= htmlspecialchars($spec['spec_value']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Reviews -->
    <div id="reviews" class="detail-section">
        <h2 class="detail-section-title">Đánh giá <span>sản phẩm</span></h2>

        <!-- Review form -->
        <div class="review-form-wrap">
            <p class="review-form-title">✍ Viết đánh giá của bạn</p>
            <form method="POST">
                <div class="form-group">
                    <label for="reviewer_name">Họ và tên *</label>
                    <input type="text" id="reviewer_name" name="reviewer_name"
                           placeholder="Nguyễn Văn A" required>
                </div>
                <div class="form-group">
                    <label>Đánh giá *</label>
                    <div class="star-select" role="group" aria-label="Chọn số sao">
                        <?php for($i=5;$i>=1;$i--): ?>
                            <input type="radio" name="rating" id="star<?=$i?>" value="<?=$i?>" required>
                            <label for="star<?=$i?>" aria-label="<?=$i?> sao">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="review_comment">Nhận xét *</label>
                    <textarea id="review_comment" name="comment" rows="4"
                              placeholder="Chia sẻ trải nghiệm của bạn..." required></textarea>
                </div>
                <button type="submit" name="submit_review" class="btn-submit-review">
                    Gửi đánh giá →
                </button>
            </form>
        </div>

        <!-- Review list -->
        <?php if ($reviews->num_rows === 0): ?>
            <p style="color:var(--clr-text-tertiary);font-size:var(--text-sm);text-align:center;padding:var(--sp-6) 0;">
                Chưa có đánh giá nào. Hãy là người đầu tiên!
            </p>
        <?php else: ?>
            <?php $reviews->data_seek(0); while($r = $reviews->fetch_assoc()): ?>
            <div class="review-item">
                <div class="review-header">
                    <div class="review-avatar"><?= mb_substr($r['reviewer_name'], 0, 1) ?></div>
                    <span class="review-author"><?= htmlspecialchars($r['reviewer_name']) ?></span>
                    <div class="stars" aria-hidden="true" style="display:flex;gap:1px;">
                        <?php for($i=1;$i<=5;$i++): ?>
                            <svg class="star <?= $i <= $r['rating'] ? '' : 'empty' ?>"
                                 viewBox="0 0 24 24" style="width:14px;height:14px;">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="review-date"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></span>
                </div>
                <p class="review-body"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <!-- Related products -->
    <?php if ($related->num_rows > 0): ?>
    <div class="related-section">
        <div class="section-header" style="margin-bottom:var(--sp-5);">
            <h2 class="section-title">Sản phẩm <span>liên quan</span></h2>
            <a href="products.php?category=<?= $product['category_id'] ?>" class="view-all">Xem thêm →</a>
        </div>
        <div class="product-grid" role="list">
            <?php while($p = $related->fetch_assoc()): ?>
                <?php include 'product_card.php'; ?>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Toast -->
<div class="toast-container" id="toastContainer" role="region" aria-live="polite"></div>

<?php include 'footer.php'; ?>
<script src="assets/js/main.js"></script>
<script>
function changeDetailQty(delta) {
    const input = document.getElementById('detail-qty');
    if (!input) return;
    const max = parseInt(input.max) || 999;
    const val = Math.min(max, Math.max(1, parseInt(input.value) + delta));
    input.value = val;
}

// Variant selection & price update
const basePrice = <?= (int)$basePrice ?>;
let selectedVersionPrice = <?= !empty($versions) ? (int)($versions[0]['price'] ?: $basePrice) : 0 ?>;
let selectedColorPrice   = <?= !empty($colors)   ? (int)($colors[0]['price']   ?: $basePrice) : 0 ?>;
const hasVersions = <?= !empty($versions) ? 'true' : 'false' ?>;
const hasColors   = <?= !empty($colors)   ? 'true' : 'false' ?>;

function selectVariant(btn, type) {
    // Deselect siblings of same type
    btn.closest('.variant-grid').querySelectorAll('.variant-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');

    const price = parseInt(btn.dataset.price) || basePrice;
    if (type === 'version') selectedVersionPrice = price;
    else                    selectedColorPrice   = price;

    updateDisplayPrice();
}

function updateDisplayPrice() {
    const el = document.getElementById('detail-price-display');
    const oldWrap = document.getElementById('detail-price-old-wrap');
    const oldEl = document.getElementById('detail-price-old');
    const badgeEl = document.getElementById('detail-discount-badge');
    if (!el) return;

    let price = basePrice;
    if (hasVersions) {
        price = selectedVersionPrice;
        if (hasColors && selectedColorPrice > 0 && selectedColorPrice !== basePrice) {
            price += (selectedColorPrice - basePrice);
        }
    } else if (hasColors) {
        price = selectedColorPrice;
    }

    let displayPrice = price;
    let oldPrice = 0;
    
    const hasManual = el.dataset.hasManualDiscount === 'true';
    const manualOld = parseInt(el.dataset.manualOldPrice) || 0;
    const vVal = parseFloat(el.dataset.voucherVal) || 0;
    const vType = el.dataset.voucherType;

    if (hasManual && manualOld > price) {
        oldPrice = manualOld;
        displayPrice = price;
    } else if (vVal > 0) {
        let disc = 0;
        if (vType === 'percent') {
            disc = (price * vVal) / 100;
        } else {
            disc = Math.min(vVal, price);
        }
        if (disc > 0) {
            oldPrice = price;
            displayPrice = Math.round(price - disc);
        }
    }

    el.textContent = new Intl.NumberFormat('vi-VN').format(displayPrice) + '₫';
    
    if (oldPrice > displayPrice) {
        oldWrap.style.display = 'flex';
        oldEl.textContent = new Intl.NumberFormat('vi-VN').format(oldPrice) + '₫';
        const pct = Math.round(((oldPrice - displayPrice) / oldPrice) * 100);
        badgeEl.textContent = '-' + pct + '%';
    } else {
        oldWrap.style.display = 'none';
    }
}

// Init price on load
document.addEventListener('DOMContentLoaded', updateDisplayPrice);
</script>
</body>
</html>