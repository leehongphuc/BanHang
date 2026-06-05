<?php
/**
 * Application Configuration
 * 
 * Cấu hình chung cho toàn bộ ứng dụng
 * File này giúp dễ dàng chuyển đổi giữa development và production
 */

// Environment: 'development' hoặc 'production'
define('APP_ENV', 'development'); // Đổi thành 'production' khi deploy lên server

// Base URL (KHÔNG có dấu / ở cuối)
if (APP_ENV === 'production') {
    define('APP_URL', 'https://yourdomain.com'); // Thay bằng domain thật
} else {
    define('APP_URL', 'http://localhost/BanHang');
}

// Application Name
define('APP_NAME', 'TechStore');

// Debug Mode (chỉ bật khi development)
define('APP_DEBUG', APP_ENV === 'development');

// Error Reporting
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../error_log');
}

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Helper Functions
function app_url($path = '') {
    return APP_URL . ($path ? '/' . ltrim($path, '/') : '');
}
