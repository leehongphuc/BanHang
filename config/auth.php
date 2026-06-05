<?php
/**
 * Auth Helper — TechStore
 * Include sau session_start() và db.php
 */

/**
 * Kiểm tra đã đăng nhập chưa
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Lấy thông tin user hiện tại (cache trong request)
 */
function currentUser(): ?array {
    static $user = null;
    if (!isLoggedIn()) return null;
    if ($user !== null) return $user;

    global $conn;
    $uid = (int)$_SESSION['user_id'];
    $res = $conn->query("SELECT id, fullname, email, phone, address, created_at, is_active FROM users WHERE id = $uid");
    $user = $res ? $res->fetch_assoc() : null;

    // Session bị stale (user bị xóa khỏi DB hoặc bị khóa)
    if (!$user || (isset($user['is_active']) && !$user['is_active'])) {
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
        $user = null;
    }
    return $user;
}

/**
 * Bắt buộc đăng nhập — redirect nếu chưa login
 */
function requireLogin(string $redirectAfter = ''): void {
    if (isLoggedIn()) return;

    $target = 'login.php';
    if ($redirectAfter) {
        $target .= '?redirect=' . urlencode($redirectAfter);
    } else {
        // Auto-detect current URL
        $current = $_SERVER['REQUEST_URI'] ?? '';
        if ($current && $current !== '/BanHang/login.php' && $current !== '/BanHang/register.php') {
            $target .= '?redirect=' . urlencode($current);
        }
    }
    header("Location: $target");
    exit;
}
