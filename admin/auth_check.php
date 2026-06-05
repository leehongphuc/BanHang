<?php
/**
 * Admin Auth Guard — TechStore
 * PHẢI include TRƯỚC session_start() trong mọi trang admin.
 * Sử dụng session riêng biệt 'ts_admin' để không xung đột với session user.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_name('ts_admin');   // Session riêng cho admin
    session_start();
}

function isAdmin(): bool {
    return !empty($_SESSION['admin_id']) && ($_SESSION['admin_role'] ?? '') === 'admin';
}

function requireAdmin(): void {
    if (isAdmin()) return;
    $current = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: /BanHang/admin/login.php?redirect=' . urlencode($current));
    exit;
}
