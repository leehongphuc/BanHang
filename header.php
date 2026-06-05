<?php
// Thêm Google Fonts vào <head> nếu chưa có — gọi hàm này trong mỗi trang
function renderFonts(): void {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">';
}

// Load auth nếu chưa
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/config/auth.php';

    // Auto-revert expired sale prices
    $conn->query("UPDATE products SET price = IFNULL(old_price, price), old_price = NULL, discount_end = NULL WHERE discount_end IS NOT NULL AND discount_end <= NOW()");
}

$cartCount = cartCount() ?? 0;
$user      = currentUser(); 
$loggedIn  = ($user !== null);
$userName  = $_SESSION['user_name'] ?? '';
$userInitial = $loggedIn ? mb_strtoupper(mb_substr($userName, 0, 1)) : '';

$wishlistCount = 0;
$wishlistProductIds = [];
if ($loggedIn) {
    $wRes = $conn->query("SELECT product_id FROM wishlist WHERE user_id = " . (int)$user['id']);
    if ($wRes) {
        while ($row = $wRes->fetch_row()) {
            $wishlistProductIds[] = (int)$row[0];
        }
    }
    $wishlistCount = count($wishlistProductIds);
}
?>
<header class="header" id="header" role="banner">
    <div class="container">
        <div class="header-inner">

            <!-- Logo -->
            <a href="index.php" class="logo" aria-label="TechStore - Trang chủ">
                <div class="logo-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="white"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                TechStore
            </a>

            <!-- Category Nav -->
            <nav class="nav-cats" aria-label="Danh mục sản phẩm">
                <a href="products.php" class="nav-cat-btn">📱 Điện thoại</a>
                <a href="products.php?category=2" class="nav-cat-btn">💻 Laptop</a>
                <a href="products.php?category=3" class="nav-cat-btn">🎧 Phụ kiện</a>
            </nav>

            <!-- Search -->
            <div class="search-wrap" role="search">
                <span class="search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </span>
                <form action="search.php" method="GET" style="width:100%">
                    <input
                        type="search"
                        name="q"
                        id="search-input"
                        class="search-input"
                        placeholder="Tìm điện thoại, laptop, phụ kiện..."
                        aria-label="Tìm kiếm sản phẩm"
                        autocomplete="off"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    >
                    <div id="autocomplete-list" role="listbox" aria-label="Gợi ý tìm kiếm"></div>
                </form>
            </div>

            <!-- Actions -->
            <div class="header-actions">
                <!-- Cart -->
                <a href="cart.php" class="icon-btn" id="header-cart-btn" aria-label="Giỏ hàng">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span class="badge-count" id="header-cart-badge" style="display:<?= $cartCount > 0 ? 'flex' : 'none' ?>" aria-hidden="true"><?= $cartCount ?></span>
                </a>

                <!-- Wishlist -->
                <a href="wishlist.php" class="icon-btn" id="header-wishlist-btn" aria-label="Sản phẩm yêu thích">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    <span class="badge-count" id="header-wishlist-badge" style="display:<?= $wishlistCount > 0 ? 'flex' : 'none' ?>" aria-hidden="true"><?= $wishlistCount ?></span>
                </a>

                <?php if ($loggedIn): ?>
                <!-- User dropdown -->
                <div class="user-dropdown" id="user-dropdown">
                    <button class="user-dropdown-trigger" id="user-trigger"
                            aria-expanded="false" aria-haspopup="true">
                        <span class="user-avatar-sm"><?= $userInitial ?></span>
                        <span class="user-trigger-name"><?= htmlspecialchars($userName) ?></span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" class="user-chevron"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="user-dropdown-menu" id="user-menu">
                        <a href="account.php" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Tài khoản
                        </a>
                        <a href="account.php?tab=orders" class="user-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                            Đơn hàng
                        </a>
                        <hr class="user-dropdown-divider">
                        <a href="logout.php" class="user-dropdown-item user-dropdown-item--danger">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Đăng xuất
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <!-- Login button -->
                <a href="login.php" class="btn-login">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Đăng nhập
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</header>