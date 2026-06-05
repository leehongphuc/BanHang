<?php
/**
 * Admin Layout — TechStore
 * Dùng chung cho tất cả trang admin
 * Gọi: adminLayout('Page Title', 'nav-key', function() { ... });
 */
function adminLayout(string $pageTitle, string $activeNav, callable $content): void {
    $adminName    = $_SESSION['user_name'] ?? 'Admin';
    $adminInitial = mb_strtoupper(mb_substr($adminName, 0, 1));
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — TechStore Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/BanHang/admin/assets/css/admin.css">
</head>
<body>

<!-- ======= SIDEBAR ======= -->
<aside class="sidebar" id="sidebar" role="navigation" aria-label="Admin navigation">
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <svg viewBox="0 0 24 24" fill="white"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        </div>
        <div>
            <div class="sidebar-logo-text">TechStore</div>
            <div class="sidebar-logo-sub">Admin Panel</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Tổng quan</div>
        <a href="/BanHang/admin/index.php" class="sidebar-item <?= $activeNav === 'dashboard' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Dashboard
        </a>

        <div class="sidebar-section-label">Catalogue</div>
        <a href="/BanHang/admin/products.php" class="sidebar-item <?= $activeNav === 'products' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
            Sản phẩm
        </a>
        <a href="/BanHang/admin/categories.php" class="sidebar-item <?= $activeNav === 'categories' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            Danh mục
        </a>

        <div class="sidebar-section-label">Vận hành</div>
        <a href="/BanHang/admin/orders.php" class="sidebar-item <?= $activeNav === 'orders' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            Đơn hàng
        </a>
        <a href="/BanHang/admin/users.php" class="sidebar-item <?= $activeNav === 'users' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Người dùng
        </a>
        <div class="sidebar-section-label">Marketing</div>
        <a href="/BanHang/admin/vouchers.php" class="sidebar-item <?= $activeNav === 'vouchers' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
            Mã giảm giá
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-admin-info">
            <div class="admin-avatar-sm"><?= $adminInitial ?></div>
            <div>
                <div class="admin-name"><?= htmlspecialchars($adminName) ?></div>
                <div class="admin-role">Administrator</div>
            </div>
        </div>
        <a href="/BanHang/admin/logout.php" class="sidebar-logout">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Đăng xuất
        </a>
    </div>
</aside>

<!-- ======= MAIN ======= -->
<div class="admin-content">
    <header class="admin-topbar">
        <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="topbar-actions">
            <a href="/BanHang/index.php" target="_blank" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                Xem website
            </a>
        </div>
    </header>

    <main class="admin-body">
        <?php $content(); ?>
    </main>
</div>

<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebar-toggle');
    const sidebar   = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    }
});
</script>
</body>
</html>
    <?php
}
