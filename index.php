<?php
session_start();
require './config/db.php';

$featured = $conn->query("SELECT * FROM products WHERE is_featured=1 LIMIT 4");
$bestseller = $conn->query("SELECT * FROM products WHERE is_bestseller=1 LIMIT 4");
$suggested = $conn->query("SELECT * FROM products WHERE is_suggested=1 LIMIT 4");
$banners = $conn->query("SELECT * FROM banners WHERE is_active=1");

// Load active vouchers for price display
$activeVouchers = [];
$vRes = $conn->query("SELECT * FROM vouchers WHERE is_active=1 AND (end_date IS NULL OR end_date > NOW()) AND (usage_limit = 0 OR used_count < usage_limit)");
if ($vRes) while ($v = $vRes->fetch_assoc()) $activeVouchers[] = $v;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore — Điện Thoại, Laptop &amp; Phụ Kiện</title>
    <meta name="description"
        content="TechStore — Mua sắm điện thoại, laptop và phụ kiện chính hãng, giá tốt nhất, giao hàng toàn quốc.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
</head>

<body>
    <?php include 'header.php'; ?>

    <!-- PROMO BANNER -->
    <div class="promo-banner" role="marquee" aria-label="Khuyến mãi">
        <div class="promo-marquee">
            <span class="promo-item">🚚 <strong>Miễn phí vận chuyển</strong> cho đơn từ 500K</span>
            <span class="promo-dot"></span>
            <span class="promo-item">⚡ <strong>Flash Sale</strong> mỗi ngày — Giảm đến 40%</span>
            <span class="promo-dot"></span>
            <span class="promo-item">🎁 Tặng <strong>bảo hành 12 tháng</strong> mọi sản phẩm</span>
            <span class="promo-dot"></span>
            <span class="promo-item">💳 Trả góp <strong>0% lãi suất</strong> qua Visa/Mastercard</span>
            <span class="promo-dot"></span>
            <span class="promo-item">🚚 <strong>Miễn phí vận chuyển</strong> cho đơn từ 500K</span>
            <span class="promo-dot"></span>
            <span class="promo-item">⚡ <strong>Flash Sale</strong> mỗi ngày — Giảm đến 40%</span>
            <span class="promo-dot"></span>
            <span class="promo-item">🎁 Tặng <strong>bảo hành 12 tháng</strong> mọi sản phẩm</span>
            <span class="promo-dot"></span>
            <span class="promo-item">💳 Trả góp <strong>0% lãi suất</strong> qua Visa/Mastercard</span>
        </div>
    </div>

    <main>
        <!-- HERO BANNER -->
        <?php $bannerList = [];
        while ($b = $banners->fetch_assoc())
            $bannerList[] = $b; ?>
        <?php if (!empty($bannerList)): ?>
            <section class="hero" aria-label="Banner quảng cáo">
                <div class="container">
                    <div class="banner-slider">
                        <?php foreach ($bannerList as $b): ?>
                            <a href="<?= htmlspecialchars($b['link']) ?>">
                                <img src="assets/images/<?= htmlspecialchars($b['image']) ?>"
                                    alt="<?= htmlspecialchars($b['title']) ?>"
                                    style="width:100%;border-radius:var(--r-lg);max-height:420px;object-fit:cover;">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <div class="container">
            <!-- FEATURES BAR -->
            <div class="features-bar" role="list" aria-label="Cam kết dịch vụ">
                <div class="feature-item" role="listitem">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="3" width="15" height="13" />
                            <path d="M16 8h4l3 5v3h-7V8z" />
                            <circle cx="5.5" cy="18.5" r="2.5" />
                            <circle cx="18.5" cy="18.5" r="2.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="feature-title">Giao hàng toàn quốc</p>
                        <p class="feature-sub">Miễn phí đơn trên 500K</p>
                    </div>
                </div>
                <div class="feature-item" role="listitem">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        </svg>
                    </div>
                    <div>
                        <p class="feature-title">Bảo hành chính hãng</p>
                        <p class="feature-sub">12 tháng tại trung tâm</p>
                    </div>
                </div>
                <div class="feature-item" role="listitem">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                    </div>
                    <div>
                        <p class="feature-title">Đổi trả 30 ngày</p>
                        <p class="feature-sub">Không cần lý do</p>
                    </div>
                </div>
                <div class="feature-item" role="listitem">
                    <div class="feature-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                    </div>
                    <div>
                        <p class="feature-title">Trả góp 0%</p>
                        <p class="feature-sub">Visa, Mastercard, MOMO</p>
                    </div>
                </div>
            </div>

            <!-- SẢN PHẨM NỔI BẬT -->
            <section class="section" aria-labelledby="featured-title">
                <div class="section-header">
                    <h2 class="section-title" id="featured-title">⭐ Sản phẩm <span>nổi bật</span></h2>
                    <a href="products.php" class="view-all" aria-label="Xem tất cả sản phẩm nổi bật">Xem tất cả →</a>
                </div>
                <div class="product-grid" role="list" aria-label="Sản phẩm nổi bật">
                    <?php while ($p = $featured->fetch_assoc()): ?>
                        <?php include 'product_card.php'; ?>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- BÁN CHẠY -->
            <section class="section" aria-labelledby="bestseller-title">
                <div class="section-header">
                    <h2 class="section-title" id="bestseller-title">🔥 Bán chạy <span>nhất</span></h2>
                    <a href="products.php" class="view-all">Xem tất cả →</a>
                </div>
                <div class="product-grid" role="list" aria-label="Sản phẩm bán chạy">
                    <?php while ($p = $bestseller->fetch_assoc()): ?>
                        <?php include 'product_card.php'; ?>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- GỢI Ý -->
            <section class="section" aria-labelledby="suggested-title">
                <div class="section-header">
                    <h2 class="section-title" id="suggested-title">💡 Có thể <span>bạn thích</span></h2>
                    <a href="products.php" class="view-all">Xem tất cả →</a>
                </div>
                <div class="product-grid" role="list" aria-label="Sản phẩm gợi ý">
                    <?php while ($p = $suggested->fetch_assoc()): ?>
                        <?php include 'product_card.php'; ?>
                    <?php endwhile; ?>
                </div>
            </section>

            <!-- BRANDS -->
            <section class="brands-section" aria-label="Thương hiệu nổi tiếng">
                <p class="brands-title">Thương hiệu chính hãng</p>
                <div class="brands-row" role="list">
                    <div class="brand-item" role="listitem" tabindex="0">Apple</div>
                    <div class="brand-item" role="listitem" tabindex="0">Samsung</div>
                    <div class="brand-item" role="listitem" tabindex="0">Dell</div>
                    <div class="brand-item" role="listitem" tabindex="0">Sony</div>
                    <div class="brand-item" role="listitem" tabindex="0">Xiaomi</div>
                    <div class="brand-item" role="listitem" tabindex="0">ASUS</div>
                </div>
            </section>
        </div>
    </main>

    <!-- TOAST -->
    <div class="toast-container" id="toastContainer" role="region" aria-label="Thông báo" aria-live="polite"></div>

    <?php include 'footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>

</html>