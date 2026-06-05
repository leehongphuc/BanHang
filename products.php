<?php
session_start();
require './config/db.php';

$category_id = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$brand_id = isset($_GET['brand']) ? (int) $_GET['brand'] : 0;
$min_price = isset($_GET['min_price']) ? (int) $_GET['min_price'] : 0;
$max_price = !empty($_GET['max_price']) ? (int) $_GET['max_price'] : 999999999;
$max_price_url = ($max_price === 999999999) ? '' : $max_price;
$sort = $_GET['sort'] ?? 'id';
$allowed_sorts = ['price_asc' => 'price ASC', 'price_desc' => 'price DESC', 'views' => 'views DESC', 'id' => 'id DESC'];
$order_by = $allowed_sorts[$sort] ?? 'id DESC';

$per_page = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

$where = "WHERE price BETWEEN $min_price AND $max_price";
if ($category_id)
    $where .= " AND category_id = $category_id";
if ($brand_id)
    $where .= " AND brand_id = $brand_id";

$total_row = $conn->query("SELECT COUNT(*) as c FROM products $where")->fetch_assoc()['c'];
$total_pages = ceil($total_row / $per_page);
$products = $conn->query("SELECT * FROM products $where ORDER BY $order_by LIMIT $per_page OFFSET $offset");
$categories = $conn->query("SELECT * FROM categories");
$brands = $conn->query("SELECT * FROM brands");

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
    <title>Danh sách sản phẩm — TechStore</title>
    <meta name="description"
        content="Khám phá hàng ngàn sản phẩm điện thoại, laptop, phụ kiện chính hãng tại TechStore.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css') ?>">
    <style>
        /* Dual Range Slider Custom CSS */
        .price-slider-container input[type="range"] {
            -webkit-appearance: none;
            appearance: none;
            width: 100%;
            position: absolute;
            background: transparent;
            pointer-events: none;
            margin: 0;
            top: 16px;
        }
        .price-slider-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            height: 20px;
            width: 20px;
            border-radius: 50%;
            background: var(--clr-brand);
            cursor: pointer;
            pointer-events: auto;
            border: 3px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.4);
            margin-top: -8px; /* Offset to center on track */
        }
        .price-slider-container input[type="range"]::-moz-range-thumb {
            height: 20px;
            width: 20px;
            border-radius: 50%;
            background: var(--clr-brand);
            cursor: pointer;
            pointer-events: auto;
            border: 3px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.4);
            margin-top: -8px;
        }
        .price-slider-container input[type="range"]::-webkit-slider-runnable-track {
            -webkit-appearance: none;
            appearance: none;
            height: 4px;
            background: transparent;
        }
        .price-slider-container input[type="range"]::-moz-range-track {
            appearance: none;
            height: 4px;
            background: transparent;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container" style="margin-top:var(--sp-6);margin-bottom:var(--sp-10);">

        <!-- Page Title -->
        <div class="section-header" style="margin-bottom:var(--sp-5);">
            <h1 class="section-title">Tất cả <span>sản phẩm</span></h1>
            <span style="font-size:var(--text-sm);color:var(--clr-text-secondary);">
                Tìm thấy <strong><?= $total_row ?></strong> sản phẩm
            </span>
        </div>


        <!-- ===== NEW FILTER BAR ===== -->
        <style>
            .top-filter-bar { display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap; position: relative; z-index: 10; }
            .btn-loc { display:flex; align-items:center; gap:6px; border:1px solid var(--clr-border); background:white; padding:8px 16px; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; color:var(--clr-text-primary); transition:0.2s; }
            .btn-loc:hover { border-color:var(--clr-brand); color:var(--clr-brand); }
            
            .quick-brand { display:inline-flex; align-items:center; padding:6px 14px; background:var(--clr-surface); border:1px solid var(--clr-border); border-radius:20px; font-size:13px; font-weight:600; cursor:pointer; color:var(--clr-text-secondary); }
            .quick-brand:hover, .quick-brand.active { background:var(--clr-brand-light); color:var(--clr-brand); border-color:var(--clr-brand); }
            
            /* Modal / Dropdown */
            .filter-dropdown { 
                display: none; position: absolute; top: 100%; left: 0; margin-top:10px; width:100%; max-width:700px;
                background:white; border:1px solid var(--clr-border); border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.1); 
                padding:24px; z-index:100;
            }
            .filter-dropdown.show { display: block; }
            
            .filter-section { margin-bottom: 24px; }
            .filter-section-title { font-size:15px; font-weight:700; color:var(--clr-text-primary); margin-bottom:12px; }
            
            .filter-grid { display:flex; gap:10px; flex-wrap:wrap; }
            .f-btn { padding:8px 16px; border:1px solid var(--clr-border); background:white; border-radius:8px; font-size:14px; cursor:pointer; color:var(--clr-text-secondary); text-align:center; transition:0.2s; }
            .f-btn:hover { border-color:var(--clr-brand); color:var(--clr-brand); }
            .f-btn.active { border-color:var(--clr-brand); background:var(--clr-brand-light); color:var(--clr-brand); font-weight:600; }
            
            .filter-footer { display:flex; justify-content:space-between; align-items:center; padding-top:16px; border-top:1px solid var(--clr-border); }
            .btn-clear { color:var(--clr-error); background:white; border:1px solid var(--clr-error); padding:10px 24px; border-radius:8px; font-weight:600; cursor:pointer; transition:0.2s; }
            .btn-clear:hover { background:var(--clr-error); color:white; }
            .btn-submit { background:var(--clr-brand); color:white; border:none; padding:10px 24px; border-radius:8px; font-weight:600; cursor:pointer; transition:0.2s; }
            .btn-submit:hover { opacity:0.9; }

            /* Mũi tên cho dropdown */
            .filter-dropdown::before {
                content: ''; position:absolute; top:-8px; left:30px; border-left:8px solid transparent; border-right:8px solid transparent; border-bottom:8px solid white;
            }
            .filter-dropdown::after {
                content: ''; position:absolute; top:-9px; left:30px; border-left:8px solid transparent; border-right:8px solid transparent; border-bottom:8px solid var(--clr-border); z-index:-1;
            }
        </style>

        <form method="GET" id="filter-form" style="position:relative; margin-bottom:var(--sp-5);">
            <input type="hidden" name="category" id="hid-category" value="<?= $category_id ?>">
            <input type="hidden" name="brand" id="hid-brand" value="<?= $brand_id ?>">
            <input type="hidden" name="page" value="1">

            <!-- TOP BAR -->
            <div class="top-filter-bar">
                <button type="button" class="btn-loc" id="btn-toggle-filter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                    Lọc
                </button>
                
                <?php 
                $quickBrands = [];
                if($brands) {
                    $brands->data_seek(0);
                    $count = 0;
                    while($b = $brands->fetch_assoc()) {
                        if($count < 7) { $quickBrands[] = $b; $count++; }
                    }
                }
                foreach($quickBrands as $qb): ?>
                    <button type="button" class="quick-brand <?= $brand_id == $qb['id'] ? 'active' : '' ?>" data-quickbrand="<?= $qb['id'] ?>">
                        <?= htmlspecialchars($qb['name']) ?>
                    </button>
                <?php endforeach; ?>
                
                <!-- Sort options floated right via auto margin -->
                <div style="margin-left:auto; display:flex; align-items:center; gap:8px;">
                    <span style="font-size:14px; font-weight:600; color:var(--clr-text-secondary); display:none;">Sắp xếp:</span>
                    <select name="sort" onchange="this.form.submit()" style="padding:8px 12px; border-radius:8px; border:1px solid var(--clr-border); outline:none; font-size:14px; color:var(--clr-text-primary); cursor:pointer;">
                        <option value="id"         <?= $sort=='id'         ? 'selected':'' ?>>Mới nhất</option>
                        <option value="price_asc"  <?= $sort=='price_asc'  ? 'selected':'' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort=='price_desc' ? 'selected':'' ?>>Giá giảm dần</option>
                        <option value="views"      <?= $sort=='views'      ? 'selected':'' ?>>Phổ biến nhất</option>
                    </select>
                </div>

                <!-- OFF-CANVAS / DROPDOWN PANEL -->
                <div class="filter-dropdown" id="filter-dropdown">
                    <div style="text-align:center; font-weight:700; font-size:16px; margin-bottom:20px; color:var(--clr-text-primary);">Tất cả bộ lọc</div>
                    
                    <!-- Hãng -->
                    <div class="filter-section">
                        <div class="filter-section-title">Hãng</div>
                        <div class="filter-grid">
                            <button type="button" class="f-btn <?= !$brand_id ? 'active' : '' ?>" data-setbrand="0">Tất cả</button>
                            <?php if($brands): $brands->data_seek(0); while($b = $brands->fetch_assoc()): ?>
                                <button type="button" class="f-btn <?= $brand_id == $b['id'] ? 'active' : '' ?>" data-setbrand="<?= $b['id'] ?>">
                                    <?= htmlspecialchars($b['name']) ?>
                                </button>
                            <?php endwhile; endif; ?>
                        </div>
                    </div>

                    <!-- Danh mục -->
                    <div class="filter-section">
                        <div class="filter-section-title">Danh mục</div>
                        <div class="filter-grid">
                            <button type="button" class="f-btn <?= !$category_id ? 'active' : '' ?>" data-setcat="0">Tất cả</button>
                            <?php $categories->data_seek(0); while($cat = $categories->fetch_assoc()): ?>
                                <button type="button" class="f-btn <?= $category_id == $cat['id'] ? 'active' : '' ?>" data-setcat="<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </button>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Giá -->
                    <div class="filter-section">
                        <div class="filter-section-title">Giá</div>
                        <div class="filter-grid" style="margin-bottom:20px;">
                            <button type="button" class="f-btn" data-setprice="0,2000000">Dưới 2 triệu</button>
                            <button type="button" class="f-btn" data-setprice="2000000,4000000">Từ 2 - 4 triệu</button>
                            <button type="button" class="f-btn" data-setprice="4000000,7000000">Từ 4 - 7 triệu</button>
                            <button type="button" class="f-btn" data-setprice="7000000,13000000">Từ 7 - 13 triệu</button>
                            <button type="button" class="f-btn" data-setprice="13000000,20000000">Từ 13 - 20 triệu</button>
                            <button type="button" class="f-btn" data-setprice="20000000,50000000">Trên 20 triệu</button>
                        </div>
                        
                        <div style="font-size:14px; color:var(--clr-brand); margin-bottom:15px; display:flex; align-items:center; gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                            Hoặc chọn mức giá phù hợp với bạn
                        </div>
                        
                        <div class="price-slider-container" style="position:relative; width: 100%; max-width:500px; height: 36px; display:flex; align-items:center; margin:0 auto 15px;">
                            <div style="position:absolute; width:100%; height:4px; background:var(--clr-border); border-radius:2px; top:16px;"></div>
                            <div id="slider-track" style="position:absolute; height:4px; background:var(--clr-brand); border-radius:2px; top:16px; left:0%; right:0%;"></div>
                            <input type="range" name="min_price" id="min_price_slider" min="0" max="50000000" step="500000" value="<?= $min_price ?: 0 ?>">
                            <input type="range" name="max_price" id="max_price_slider" min="0" max="50000000" step="500000" value="<?= $max_price_url !== '' ? $max_price_url : 50000000 ?>">
                        </div>
                        
                        <div style="display:flex; justify-content:center; gap:20px; align-items:center;">
                            <div style="padding:8px 16px; border:1px solid var(--clr-border); border-radius:8px; font-weight:700; color:var(--clr-text-primary); font-size:14px; min-width:140px; text-align:center;">
                                <span id="min_price_display">0đ</span>
                            </div>
                            <span style="color:var(--clr-text-tertiary);">-</span>
                            <div style="padding:8px 16px; border:1px solid var(--clr-border); border-radius:8px; font-weight:700; color:var(--clr-text-primary); font-size:14px; min-width:140px; text-align:center;">
                                <span id="max_price_display">50.000.000đ</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="filter-footer">
                        <button type="button" class="btn-clear" onclick="window.location='products.php'">Bỏ chọn</button>
                        <button type="submit" class="btn-submit">Xem kết quả</button>
                    </div>
                </div>
            </div>
        </form>

        <script>
        // Toggle dropdown
        const btnToggle = document.getElementById('btn-toggle-filter');
        const dropdown = document.getElementById('filter-dropdown');
        btnToggle.addEventListener('click', (e) => {
            dropdown.classList.toggle('show');
            e.stopPropagation();
        });
        document.addEventListener('click', (e) => {
            if(!dropdown.contains(e.target) && e.target !== btnToggle && !btnToggle.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        // Prevent click inside dropdown from closing it
        dropdown.addEventListener('click', (e) => {
            e.stopPropagation();
        });

        // Quick brands
        document.querySelectorAll('.quick-brand').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('hid-brand').value = this.dataset.quickbrand;
                document.getElementById('filter-form').submit();
            });
        });

        // Modal buttons logic
        document.querySelectorAll('.f-btn[data-setbrand]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.f-btn[data-setbrand]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('hid-brand').value = this.dataset.setbrand;
            });
        });
        document.querySelectorAll('.f-btn[data-setcat]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.f-btn[data-setcat]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('hid-category').value = this.dataset.setcat;
            });
        });
        
        // Modal Preset Price Buttons
        document.querySelectorAll('.f-btn[data-setprice]').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.f-btn[data-setprice]').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const parts = this.dataset.setprice.split(',');
                document.getElementById('min_price_slider').value = parts[0];
                document.getElementById('max_price_slider').value = parts[1];
                updateSlider();
            });
        });

        // Dual Range Slider Logic
        const minSlider = document.getElementById('min_price_slider');
        const maxSlider = document.getElementById('max_price_slider');
        const track = document.getElementById('slider-track');
        const minDisplay = document.getElementById('min_price_display');
        const maxDisplay = document.getElementById('max_price_display');
        const maxVal = parseInt(minSlider.max);

        function updateSlider() {
            let min = parseInt(minSlider.value);
            let max = parseInt(maxSlider.value);
            
            if (min > max) {
                let tmp = min;
                min = max;
                max = tmp;
                if (this === minSlider) minSlider.value = min;
                else maxSlider.value = max;
            }
            
            minDisplay.textContent = new Intl.NumberFormat('vi-VN').format(min) + 'đ';
            maxDisplay.textContent = new Intl.NumberFormat('vi-VN').format(max) + 'đ';
            
            let percent1 = (min / maxVal) * 100;
            let percent2 = (max / maxVal) * 100;
            track.style.left = percent1 + '%';
            track.style.right = (100 - percent2) + '%';
        }

        minSlider.addEventListener('input', () => {
            document.querySelectorAll('.f-btn[data-setprice]').forEach(b => b.classList.remove('active'));
            updateSlider();
        });
        maxSlider.addEventListener('input', () => {
            document.querySelectorAll('.f-btn[data-setprice]').forEach(b => b.classList.remove('active'));
            updateSlider();
        });
        updateSlider(); // Initial call
        </script>


        <!-- Active filters badge row -->
        <?php $hasFilter = $category_id || $brand_id || $min_price > 0 || $max_price_url !== '' || $sort !== 'id'; ?>
        <?php if ($hasFilter): ?>
            <div style="display:flex;gap:var(--sp-2);flex-wrap:wrap;align-items:center;margin-bottom:var(--sp-4);">
                <span style="font-size:var(--text-xs);color:var(--clr-text-tertiary);">Đang lọc:</span>
                <?php if ($category_id): ?>
                    <span
                        style="background:var(--clr-brand-light);color:var(--clr-brand);padding:3px 12px;border-radius:var(--r-full);font-size:var(--text-xs);font-weight:600;">
                        Danh mục
                    </span>
                <?php endif; ?>
                <?php if ($brand_id): ?>
                    <span
                        style="background:var(--clr-brand-light);color:var(--clr-brand);padding:3px 12px;border-radius:var(--r-full);font-size:var(--text-xs);font-weight:600;">
                        Thương hiệu
                    </span>
                <?php endif; ?>
                <?php if ($min_price > 0 || $max_price_url !== ''): ?>
                    <span
                        style="background:var(--clr-brand-light);color:var(--clr-brand);padding:3px 12px;border-radius:var(--r-full);font-size:var(--text-xs);font-weight:600;">
                        <?= $min_price > 0 ? formatPrice($min_price) : '0đ' ?> —
                        <?= $max_price_url ? formatPrice((int) $max_price_url) : 'tối đa' ?>
                    </span>
                <?php endif; ?>
                <?php if ($sort !== 'id'): ?>
                    <span
                        style="background:var(--clr-brand-light);color:var(--clr-brand);padding:3px 12px;border-radius:var(--r-full);font-size:var(--text-xs);font-weight:600;">
                        <?= ['price_asc' => 'Giá tăng', 'price_desc' => 'Giá giảm', 'views' => 'Phổ biến'][$sort] ?? '' ?>
                    </span>
                <?php endif; ?>
                <a href="products.php" style="font-size:var(--text-xs);color:var(--clr-error);margin-left:var(--sp-1);">✕
                    Xóa tất cả</a>
            </div>
        <?php endif; ?>


        <!-- Product Grid -->
        <div class="product-grid" role="list" aria-label="Danh sách sản phẩm">
            <?php while ($p = $products->fetch_assoc()): ?>
                <?php include 'product_card.php'; ?>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <?php
            $range = 2; // số trang hiển thị mỗi bên trang hiện tại
            $pBase = "?category={$category_id}&sort={$sort}&min_price={$min_price}&max_price={$max_price_url}";
            ?>
            <nav class="pagination" style="margin-top:var(--sp-8);" aria-label="Phân trang">

                <?php if ($page > 1): ?>
                    <a href="<?= $pBase ?>&page=<?= $page - 1 ?>" aria-label="Trang trước">&lsaquo; Trước</a>
                <?php else: ?>
                    <span style="opacity:.35;padding:8px 14px;">&lsaquo; Trước</span>
                <?php endif; ?>

                <?php if ($page > $range + 1): ?>
                    <a href="<?= $pBase ?>&page=1" aria-label="Trang 1">1</a>
                    <?php if ($page > $range + 2): ?>
                        <span style="padding:8px 6px;color:var(--clr-text-tertiary);">…</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = max(1, $page - $range); $i <= min($total_pages, $page + $range); $i++): ?>
                    <a href="<?= $pBase ?>&page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"
                        aria-label="Trang <?= $i ?>" <?= $i == $page ? 'aria-current="page"' : '' ?>>
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages - $range): ?>
                    <?php if ($page < $total_pages - $range - 1): ?>
                        <span style="padding:8px 6px;color:var(--clr-text-tertiary);">…</span>
                    <?php endif; ?>
                    <a href="<?= $pBase ?>&page=<?= $total_pages ?>"
                        aria-label="Trang <?= $total_pages ?>"><?= $total_pages ?></a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="<?= $pBase ?>&page=<?= $page + 1 ?>" aria-label="Trang sau">Sau &rsaquo;</a>
                <?php else: ?>
                    <span style="opacity:.35;padding:8px 14px;">Sau &rsaquo;</span>
                <?php endif; ?>

            </nav>
        <?php endif; ?>
    </div>

    <div class="toast-container" id="toastContainer" role="region" aria-live="polite"></div>
    <?php include 'footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>

</html>