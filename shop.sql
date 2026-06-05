-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 05, 2026 at 01:52 PM
-- Server version: 11.4.9-MariaDB
-- PHP Version: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

DROP TABLE IF EXISTS `banners`;
CREATE TABLE IF NOT EXISTS `banners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `image` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image`, `title`, `link`, `is_active`) VALUES
(1, 'banner1.jpg', 'Khuyến mãi tháng 5', '/products.php', 1);

-- --------------------------------------------------------

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
CREATE TABLE IF NOT EXISTS `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `brands`
--

INSERT INTO `brands` (`id`, `name`) VALUES
(1, 'Apple'),
(2, 'Samsung'),
(3, 'Xiaomi'),
(4, 'Oppo'),
(5, 'Sony'),
(6, 'Dell'),
(7, 'HP'),
(8, 'Asus'),
(9, 'Lenovo'),
(10, 'JBL'),
(11, 'Logitech'),
(12, 'Khác');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `created_at`) VALUES
(1, 'Điện thoại', '2026-05-08 14:41:34'),
(2, 'Laptop', '2026-05-08 14:41:34'),
(3, 'Phụ kiện', '2026-05-08 14:41:34'),
(7, 'Máy Tính Bảng', '2026-05-16 03:17:46'),
(6, 'Khác', '2026-05-16 03:17:14');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `receiver_address` text NOT NULL,
  `payment_method` enum('COD','online') DEFAULT 'COD',
  `voucher_code` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(15,0) DEFAULT 0,
  `total` decimal(15,0) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `receiver_name`, `receiver_phone`, `receiver_address`, `payment_method`, `voucher_code`, `discount_amount`, `total`, `status`, `created_at`) VALUES
(11, 1, 'sadas11', '0398393978', '12321323', 'COD', NULL, 0, 57980000, 'pending', '2026-05-16 03:33:42'),
(8, 1, 'sadas', '0398393978', 'dsasadsa', 'COD', NULL, 0, 17990000, 'pending', '2026-05-15 14:44:23'),
(9, 2, 'Test User Updated', '0123456789', '123 Main St, Hanoi', 'COD', NULL, 0, 24990000, 'pending', '2026-05-15 14:48:49'),
(10, 3, 'Administrator', '0345234564', 'ádadas', 'COD', NULL, 0, 22990000, 'pending', '2026-05-16 00:42:27');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(15,0) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 7, 1, 17990000),
(2, 1, 5, 1, 6490000),
(3, 2, 1, 50, 34990000),
(4, 3, 4, 10, 24990000),
(5, 4, 2, 3, 22990000),
(6, 4, 6, 7, 290000),
(7, 5, 10, 1, 24990000),
(8, 6, 2, 5, 22990000),
(9, 7, 4, 1, 24990000),
(10, 8, 7, 1, 17990000),
(11, 9, 10, 1, 24990000),
(12, 10, 2, 1, 22990000),
(13, 11, 1, 1, 34990000),
(14, 11, 2, 1, 22990000);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,0) NOT NULL,
  `old_price` decimal(15,0) DEFAULT NULL,
  `discount_end` datetime DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_bestseller` tinyint(1) DEFAULT 0,
  `is_suggested` tinyint(1) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `brand_id` (`brand_id`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `name`, `description`, `price`, `old_price`, `discount_end`, `stock`, `image`, `is_featured`, `is_bestseller`, `is_suggested`, `views`, `created_at`) VALUES
(1, 1, NULL, 'iPhone 15 Pro Max', 'Điện thoại Apple mới nhất với chip A17 Pro', 34990000, NULL, NULL, 8, 'iphone15.jpg', 1, 1, 0, 1227, '2026-05-08 14:41:34'),
(2, 1, NULL, 'Samsung Galaxy S24', 'Flagship Android với AI tích hợp', 22990000, NULL, NULL, 20, 'samsung_s24.jpg', 1, 0, 1, 936, '2026-05-08 14:41:34'),
(3, 2, NULL, 'MacBook Air M3', 'Laptop siêu mỏng hiệu năng cao', 29990000, NULL, NULL, 20, 'macbook.jpg', 0, 1, 1, 808, '2026-05-08 14:41:34'),
(4, 2, NULL, 'Dell XPS 13', 'Laptop văn phòng cao cấp', 24990000, NULL, NULL, 4, 'dell_xps.jpg', 0, 0, 1, 608, '2026-05-08 14:41:34'),
(5, 3, NULL, 'AirPods Pro 2', 'Tai nghe không dây chống ồn', 6490000, NULL, NULL, 99, 'airpods.jpg', 1, 1, 0, 2031, '2026-05-08 14:41:34'),
(6, 3, NULL, 'Ốp lưng iPhone 15', 'Ốp chống sốc cao cấp', 290000, NULL, NULL, 193, 'case.jpg', 0, 0, 1, 407, '2026-05-08 14:41:34'),
(7, 7, NULL, 'iPad Air M2', 'Máy tính bảng đa năng', 17990000, NULL, NULL, 23, 'ipad.jpg', 1, 0, 1, 706, '2026-05-08 14:41:34'),
(8, 7, NULL, 'iPad Air M1', 'Máy tính bảng đa năng', 17990000, NULL, NULL, 24, 'ipad.jpg', 1, 0, 1, 708, '2026-05-08 14:41:34'),
(9, 2, NULL, 'Dell XPS 15', 'Laptop văn phòng cao cấp', 24990000, NULL, NULL, 15, 'dell_xps.jpg', 0, 0, 1, 605, '2026-05-08 14:41:34'),
(10, 2, NULL, 'Dell XPS 18', 'Laptop văn phòng cao cấp', 24990000, NULL, NULL, 13, 'dell_xps.jpg', 0, 0, 1, 606, '2026-05-08 14:41:34'),
(11, 1, 3, 'Xiaomi Redmi Note 12', 'Smartphone tầm trung với màn hình AMOLED 120Hz', 34000000, NULL, NULL, 50, 'xiaomi_note12.jpg', 0, 1, 1, 306, '2026-05-16 00:00:00'),
(12, 3, NULL, 'Bàn phím cơ Keychron K2', 'Bàn phím cơ không dây layout 75%', 1890000, NULL, NULL, 30, 'keychron_k2.jpg', 0, 1, 1, 150, '2026-05-16 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` enum('version','color') NOT NULL COMMENT 'version = phiên bản, color = màu sắc',
  `name` varchar(150) NOT NULL COMMENT 'VD: 128GB, Đen Titan, Xanh dương nhạt',
  `price` decimal(15,0) NOT NULL DEFAULT 0 COMMENT '0 = dùng giá gốc sản phẩm',
  `color_hex` varchar(7) DEFAULT NULL COMMENT 'Mã màu HEX, VD: #1a5276 (chỉ dùng cho type=color)',
  `image` varchar(255) DEFAULT NULL COMMENT 'Ảnh riêng cho màu này (optional)',
  `sort_order` smallint(6) DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_type` (`product_id`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `type`, `name`, `price`, `color_hex`, `image`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 1, 'version', '256GB', 34990000, NULL, NULL, 1, 1, '2026-05-16 00:59:13'),
(2, 1, 'version', '512GB', 39990000, NULL, NULL, 2, 1, '2026-05-16 00:59:13'),
(3, 1, 'version', '1TB', 44990000, NULL, NULL, 3, 1, '2026-05-16 00:59:13'),
(4, 1, 'color', 'Titan Đen', 34990000, '#2c2c2c', NULL, 1, 1, '2026-05-16 00:59:13'),
(5, 1, 'color', 'Titan Trắng', 34990000, '#f5f5f0', NULL, 2, 1, '2026-05-16 00:59:13'),
(6, 1, 'color', 'Titan Xanh', 34990000, '#4a7c8e', NULL, 3, 1, '2026-05-16 00:59:13'),
(7, 1, 'color', 'Titan Tự Nhiên', 34990000, '#c5b99a', NULL, 4, 1, '2026-05-16 00:59:13'),
(8, 2, 'version', '8GB/128GB', 22990000, NULL, NULL, 1, 1, '2026-05-16 00:59:14'),
(9, 2, 'version', '12GB/256GB', 26990000, NULL, NULL, 2, 1, '2026-05-16 00:59:14'),
(10, 2, 'color', 'Đen Phantom', 22990000, '#1a1a1a', NULL, 1, 1, '2026-05-16 00:59:14'),
(11, 2, 'color', 'Tím Cobalt', 22990000, '#6b5b95', NULL, 2, 1, '2026-05-16 00:59:14'),
(12, 2, 'color', 'Xám Marble', 22990000, '#9e9e9e', NULL, 3, 1, '2026-05-16 00:59:14'),
(13, 3, 'version', '8GB/256GB', 29990000, NULL, NULL, 1, 1, '2026-05-16 01:00:00'),
(14, 3, 'version', '16GB/512GB', 34990000, NULL, NULL, 2, 1, '2026-05-16 01:00:00'),
(15, 3, 'color', 'Midnight', 29990000, '#000b18', NULL, 1, 1, '2026-05-16 01:00:00'),
(16, 3, 'color', 'Starlight', 29990000, '#fdf6e3', NULL, 2, 1, '2026-05-16 01:00:00'),
(17, 3, 'color', 'Space Gray', 29990000, '#535150', NULL, 3, 1, '2026-05-16 01:00:00'),
(18, 3, 'color', 'Silver', 29990000, '#e3e4e5', NULL, 4, 1, '2026-05-16 01:00:00'),
(19, 4, 'version', 'Core i5/8GB/256GB', 24990000, NULL, NULL, 1, 1, '2026-05-16 01:00:00'),
(20, 4, 'version', 'Core i7/16GB/512GB', 29990000, NULL, NULL, 2, 1, '2026-05-16 01:00:00'),
(21, 4, 'color', 'Platinum', 24990000, '#e5e4e2', NULL, 1, 1, '2026-05-16 01:00:00'),
(22, 4, 'color', 'Graphite', 24990000, '#4b4b4b', NULL, 2, 1, '2026-05-16 01:00:00'),
(28, 12, 'version', 'Switch Red', 1890000, NULL, NULL, 1, 1, '2026-05-16 01:00:00'),
(29, 12, 'version', 'Switch Brown', 1890000, NULL, NULL, 2, 1, '2026-05-16 01:00:00'),
(30, 12, 'version', 'Switch Blue', 1890000, NULL, NULL, 3, 1, '2026-05-16 01:00:00'),
(31, 12, 'color', 'Nhôm Đen', 1890000, '#1c1c1c', NULL, 1, 1, '2026-05-16 01:00:00'),
(52, 11, 'version', '4GB/128GB', 4590000, NULL, NULL, 1, 1, '2026-05-16 03:39:41'),
(53, 11, 'version', '8GB/128GB', 5590000, NULL, NULL, 2, 1, '2026-05-16 03:39:41'),
(54, 11, 'color', 'Xanh dương nhạt', 4590000, '#8ab4f8', NULL, 1, 1, '2026-05-16 03:39:41'),
(55, 11, 'color', 'Xám', 4590000, '#5f6368', NULL, 2, 1, '2026-05-16 03:39:41'),
(56, 11, 'color', 'Xanh lá', 4590000, '#81c995', NULL, 3, 1, '2026-05-16 03:39:41');

-- --------------------------------------------------------

--
-- Table structure for table `product_specifications`
--

DROP TABLE IF EXISTS `product_specifications`;
CREATE TABLE IF NOT EXISTS `product_specifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `spec_group` varchar(100) DEFAULT 'Thông số chung' COMMENT 'Nhóm thông số: Màn hình, Hiệu năng, Camera...',
  `spec_name` varchar(150) NOT NULL COMMENT 'Tên thông số: CPU, RAM...',
  `spec_value` varchar(500) NOT NULL COMMENT 'Giá trị: Apple A17 Pro, 8GB...',
  `sort_order` smallint(6) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_specifications`
--

INSERT INTO `product_specifications` (`product_id`, `spec_group`, `spec_name`, `spec_value`, `sort_order`) VALUES
-- iPhone 15 Pro Max (id=1)
(1, 'Màn hình', 'Công nghệ màn hình', 'Super Retina XDR OLED', 1),
(1, 'Màn hình', 'Kích thước', '6.7 inch', 2),
(1, 'Màn hình', 'Độ phân giải', '2796 x 1290 pixels', 3),
(1, 'Màn hình', 'Tần số quét', '120Hz ProMotion', 4),
(1, 'Màn hình', 'Độ sáng tối đa', '2000 nits (ngoài trời)', 5),
(1, 'Hiệu năng', 'Chipset', 'Apple A17 Pro (3nm)', 1),
(1, 'Hiệu năng', 'CPU', '6 nhân (2 hiệu năng + 4 tiết kiệm)', 2),
(1, 'Hiệu năng', 'GPU', 'Apple GPU 6 nhân', 3),
(1, 'Hiệu năng', 'RAM', '8GB', 4),
(1, 'Camera', 'Camera sau', '48MP (chính) + 12MP (góc siêu rộng) + 12MP (tele 5x)', 1),
(1, 'Camera', 'Camera trước', '12MP, TrueDepth', 2),
(1, 'Camera', 'Quay video', '4K@60fps, Dolby Vision HDR, ProRes', 3),
(1, 'Pin & Sạc', 'Dung lượng pin', '4422 mAh', 1),
(1, 'Pin & Sạc', 'Sạc nhanh', '20W có dây, 15W MagSafe', 2),
(1, 'Kết nối', 'SIM', 'Nano SIM + eSIM', 1),
(1, 'Kết nối', 'Cổng sạc', 'USB Type-C (USB 3)', 2),
(1, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E (802.11ax)', 3),
(1, 'Kết nối', 'Bluetooth', '5.3', 4),
(1, 'Thiết kế', 'Kích thước', '159.9 x 76.7 x 8.25 mm', 1),
(1, 'Thiết kế', 'Trọng lượng', '221g', 2),
(1, 'Thiết kế', 'Chất liệu', 'Khung Titanium, mặt kính Ceramic Shield', 3),
(1, 'Thiết kế', 'Chống nước', 'IP68 (6m/30 phút)', 4),
(1, 'Hệ điều hành', 'OS', 'iOS 17', 1),

-- Samsung Galaxy S24 (id=2)
(2, 'Màn hình', 'Công nghệ màn hình', 'Dynamic AMOLED 2X', 1),
(2, 'Màn hình', 'Kích thước', '6.2 inch', 2),
(2, 'Màn hình', 'Độ phân giải', '2340 x 1080 pixels (FHD+)', 3),
(2, 'Màn hình', 'Tần số quét', '120Hz', 4),
(2, 'Màn hình', 'Độ sáng tối đa', '2600 nits', 5),
(2, 'Hiệu năng', 'Chipset', 'Exynos 2400 (4nm)', 1),
(2, 'Hiệu năng', 'CPU', '10 nhân (1x3.2GHz + 2x2.9GHz + 3x2.6GHz + 4x1.9GHz)', 2),
(2, 'Hiệu năng', 'GPU', 'Xclipse 940', 3),
(2, 'Hiệu năng', 'RAM', '8GB / 12GB', 4),
(2, 'Camera', 'Camera sau', '50MP (chính) + 12MP (góc rộng) + 10MP (tele 3x)', 1),
(2, 'Camera', 'Camera trước', '12MP', 2),
(2, 'Camera', 'Quay video', '8K@30fps, 4K@60fps', 3),
(2, 'Pin & Sạc', 'Dung lượng pin', '4000 mAh', 1),
(2, 'Pin & Sạc', 'Sạc nhanh', '25W có dây, 15W không dây', 2),
(2, 'Kết nối', 'SIM', 'Nano SIM + eSIM', 1),
(2, 'Kết nối', 'Cổng sạc', 'USB Type-C (USB 3.2)', 2),
(2, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E', 3),
(2, 'Thiết kế', 'Kích thước', '147 x 70.6 x 7.6 mm', 1),
(2, 'Thiết kế', 'Trọng lượng', '167g', 2),
(2, 'Thiết kế', 'Chống nước', 'IP68', 3),
(2, 'Hệ điều hành', 'OS', 'Android 14, One UI 6.1 (Galaxy AI)', 1),

-- MacBook Air M3 (id=3)
(3, 'Màn hình', 'Công nghệ màn hình', 'Liquid Retina IPS', 1),
(3, 'Màn hình', 'Kích thước', '13.6 inch', 2),
(3, 'Màn hình', 'Độ phân giải', '2560 x 1664 pixels', 3),
(3, 'Màn hình', 'Độ sáng tối đa', '500 nits', 4),
(3, 'Màn hình', 'Dải màu', 'P3 Wide Color, True Tone', 5),
(3, 'Hiệu năng', 'Chipset', 'Apple M3', 1),
(3, 'Hiệu năng', 'CPU', '8 nhân (4 hiệu năng + 4 tiết kiệm)', 2),
(3, 'Hiệu năng', 'GPU', '8 nhân / 10 nhân', 3),
(3, 'Hiệu năng', 'RAM', '8GB / 16GB Unified Memory', 4),
(3, 'Lưu trữ', 'Ổ cứng', 'SSD 256GB / 512GB', 1),
(3, 'Pin & Sạc', 'Dung lượng pin', '52.6 Wh', 1),
(3, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 18 giờ', 2),
(3, 'Pin & Sạc', 'Sạc', '67W MagSafe', 3),
(3, 'Kết nối', 'Cổng', '2x Thunderbolt/USB-4, MagSafe 3, Jack 3.5mm', 1),
(3, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E (802.11ax)', 2),
(3, 'Kết nối', 'Bluetooth', '5.3', 3),
(3, 'Thiết kế', 'Kích thước', '304.1 x 215 x 11.3 mm', 1),
(3, 'Thiết kế', 'Trọng lượng', '1.24 kg', 2),
(3, 'Thiết kế', 'Chất liệu', 'Nhôm nguyên khối tái chế 100%', 3),
(3, 'Hệ điều hành', 'OS', 'macOS Sonoma', 1),

-- Dell XPS 13 (id=4)
(4, 'Màn hình', 'Công nghệ màn hình', 'IPS Anti-Glare', 1),
(4, 'Màn hình', 'Kích thước', '13.4 inch', 2),
(4, 'Màn hình', 'Độ phân giải', '1920 x 1200 pixels (FHD+)', 3),
(4, 'Màn hình', 'Tần số quét', '60Hz', 4),
(4, 'Hiệu năng', 'Chipset', 'Intel Core Ultra 7 155H', 1),
(4, 'Hiệu năng', 'CPU', '16 nhân (6P + 8E + 2LP)', 2),
(4, 'Hiệu năng', 'GPU', 'Intel Arc Graphics', 3),
(4, 'Hiệu năng', 'RAM', '16GB LPDDR5x', 4),
(4, 'Lưu trữ', 'Ổ cứng', 'SSD 512GB NVMe PCIe 4.0', 1),
(4, 'Pin & Sạc', 'Dung lượng pin', '55 Wh', 1),
(4, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 13 giờ', 2),
(4, 'Kết nối', 'Cổng', '2x Thunderbolt 4, MicroSD, Jack 3.5mm', 1),
(4, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E', 2),
(4, 'Kết nối', 'Bluetooth', '5.3', 3),
(4, 'Thiết kế', 'Kích thước', '295.4 x 199.4 x 14.8 mm', 1),
(4, 'Thiết kế', 'Trọng lượng', '1.17 kg', 2),
(4, 'Hệ điều hành', 'OS', 'Windows 11 Home', 1),

-- AirPods Pro 2 (id=5)
(5, 'Âm thanh', 'Driver', 'Apple H2 chip, driver tùy chỉnh', 1),
(5, 'Âm thanh', 'Chống ồn', 'ANC chủ động thích ứng', 2),
(5, 'Âm thanh', 'Chế độ nghe', 'Xuyên âm (Transparency), Adaptive Audio', 3),
(5, 'Âm thanh', 'Âm thanh không gian', 'Spatial Audio + Head Tracking', 4),
(5, 'Pin & Sạc', 'Tai nghe', 'Lên đến 6 giờ (ANC bật)', 1),
(5, 'Pin & Sạc', 'Hộp sạc', 'Tổng 30 giờ với hộp sạc', 2),
(5, 'Pin & Sạc', 'Sạc', 'MagSafe, Qi, Lightning, USB-C', 3),
(5, 'Kết nối', 'Bluetooth', '5.3', 1),
(5, 'Kết nối', 'Chip', 'Apple H2', 2),
(5, 'Thiết kế', 'Chống nước', 'IP54 (tai nghe + hộp sạc)', 1),
(5, 'Thiết kế', 'Trọng lượng', '5.3g / bên, hộp sạc 50.8g', 2),
(5, 'Thiết kế', 'Điều khiển', 'Cảm ứng lực, vuốt điều chỉnh âm lượng', 3),

-- Ốp lưng iPhone 15 (id=6)
(6, 'Thông số chung', 'Tương thích', 'iPhone 15 / iPhone 15 Pro', 1),
(6, 'Thông số chung', 'Chất liệu', 'Polycarbonate + TPU viền mềm', 2),
(6, 'Thông số chung', 'Tiêu chuẩn chống sốc', 'MIL-STD-810G (rơi từ 1.2m)', 3),
(6, 'Thông số chung', 'Tương thích MagSafe', 'Có', 4),
(6, 'Thông số chung', 'Trọng lượng', '35g', 5),
(6, 'Thông số chung', 'Độ dày', '1.2 mm', 6),

-- iPad Air M2 (id=7)
(7, 'Màn hình', 'Công nghệ màn hình', 'Liquid Retina IPS', 1),
(7, 'Màn hình', 'Kích thước', '10.9 inch', 2),
(7, 'Màn hình', 'Độ phân giải', '2360 x 1640 pixels', 3),
(7, 'Màn hình', 'Tần số quét', '60Hz', 4),
(7, 'Màn hình', 'Độ sáng tối đa', '500 nits', 5),
(7, 'Hiệu năng', 'Chipset', 'Apple M2', 1),
(7, 'Hiệu năng', 'CPU', '8 nhân', 2),
(7, 'Hiệu năng', 'GPU', '10 nhân', 3),
(7, 'Hiệu năng', 'RAM', '8GB Unified Memory', 4),
(7, 'Camera', 'Camera sau', '12MP, f/1.8', 1),
(7, 'Camera', 'Camera trước', '12MP Ultra Wide, Center Stage', 2),
(7, 'Pin & Sạc', 'Dung lượng pin', '28.6 Wh', 1),
(7, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 10 giờ', 2),
(7, 'Kết nối', 'Cổng', 'USB Type-C (USB 3.1 Gen 2)', 1),
(7, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E', 2),
(7, 'Kết nối', 'Bluetooth', '5.3', 3),
(7, 'Thiết kế', 'Kích thước', '247.6 x 178.5 x 6.1 mm', 1),
(7, 'Thiết kế', 'Trọng lượng', '462g', 2),
(7, 'Hệ điều hành', 'OS', 'iPadOS 17', 1),

-- iPad Air M1 (id=8)
(8, 'Màn hình', 'Công nghệ màn hình', 'Liquid Retina IPS', 1),
(8, 'Màn hình', 'Kích thước', '10.9 inch', 2),
(8, 'Màn hình', 'Độ phân giải', '2360 x 1640 pixels', 3),
(8, 'Màn hình', 'Tần số quét', '60Hz', 4),
(8, 'Hiệu năng', 'Chipset', 'Apple M1', 1),
(8, 'Hiệu năng', 'CPU', '8 nhân', 2),
(8, 'Hiệu năng', 'GPU', '8 nhân', 3),
(8, 'Hiệu năng', 'RAM', '8GB Unified Memory', 4),
(8, 'Camera', 'Camera sau', '12MP, f/1.8', 1),
(8, 'Camera', 'Camera trước', '12MP Ultra Wide, Center Stage', 2),
(8, 'Pin & Sạc', 'Dung lượng pin', '28.6 Wh', 1),
(8, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 10 giờ', 2),
(8, 'Kết nối', 'Cổng', 'USB Type-C', 1),
(8, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6 (802.11ax)', 2),
(8, 'Kết nối', 'Bluetooth', '5.0', 3),
(8, 'Thiết kế', 'Kích thước', '247.6 x 178.5 x 6.1 mm', 1),
(8, 'Thiết kế', 'Trọng lượng', '461g', 2),
(8, 'Hệ điều hành', 'OS', 'iPadOS 16', 1),

-- Dell XPS 15 (id=9)
(9, 'Màn hình', 'Công nghệ màn hình', 'OLED InfinityEdge', 1),
(9, 'Màn hình', 'Kích thước', '15.6 inch', 2),
(9, 'Màn hình', 'Độ phân giải', '3456 x 2160 pixels (3.5K)', 3),
(9, 'Màn hình', 'Tần số quét', '60Hz', 4),
(9, 'Màn hình', 'Độ sáng tối đa', '400 nits', 5),
(9, 'Hiệu năng', 'Chipset', 'Intel Core i7-13700H', 1),
(9, 'Hiệu năng', 'CPU', '14 nhân (6P + 8E)', 2),
(9, 'Hiệu năng', 'GPU', 'NVIDIA GeForce RTX 4050 6GB', 3),
(9, 'Hiệu năng', 'RAM', '16GB DDR5', 4),
(9, 'Lưu trữ', 'Ổ cứng', 'SSD 512GB NVMe PCIe 4.0', 1),
(9, 'Pin & Sạc', 'Dung lượng pin', '86 Wh', 1),
(9, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 13 giờ', 2),
(9, 'Kết nối', 'Cổng', '2x Thunderbolt 4, USB-C 3.2, SD Card, Jack 3.5mm', 1),
(9, 'Kết nối', 'Wi-Fi', 'Wi-Fi 6E', 2),
(9, 'Thiết kế', 'Kích thước', '344.4 x 230.1 x 18 mm', 1),
(9, 'Thiết kế', 'Trọng lượng', '1.86 kg', 2),
(9, 'Hệ điều hành', 'OS', 'Windows 11 Pro', 1),

-- Dell XPS 18 (id=10)
(10, 'Màn hình', 'Công nghệ màn hình', 'IPS Anti-Glare', 1),
(10, 'Màn hình', 'Kích thước', '18 inch', 2),
(10, 'Màn hình', 'Độ phân giải', '2560 x 1600 pixels (WQXGA)', 3),
(10, 'Màn hình', 'Tần số quét', '120Hz', 4),
(10, 'Hiệu năng', 'Chipset', 'Intel Core Ultra 9 185H', 1),
(10, 'Hiệu năng', 'CPU', '16 nhân (6P + 8E + 2LP)', 2),
(10, 'Hiệu năng', 'GPU', 'NVIDIA GeForce RTX 4070 8GB', 3),
(10, 'Hiệu năng', 'RAM', '32GB DDR5', 4),
(10, 'Lưu trữ', 'Ổ cứng', 'SSD 1TB NVMe PCIe 4.0', 1),
(10, 'Pin & Sạc', 'Dung lượng pin', '99.5 Wh', 1),
(10, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 12 giờ', 2),
(10, 'Kết nối', 'Cổng', '2x Thunderbolt 4, USB-C 3.2, HDMI 2.1, SD Card', 1),
(10, 'Kết nối', 'Wi-Fi', 'Wi-Fi 7 (802.11be)', 2),
(10, 'Thiết kế', 'Kích thước', '403.5 x 274.9 x 19.5 mm', 1),
(10, 'Thiết kế', 'Trọng lượng', '2.73 kg', 2),
(10, 'Hệ điều hành', 'OS', 'Windows 11 Pro', 1),

-- Xiaomi Redmi Note 12 (id=11)
(11, 'Màn hình', 'Công nghệ màn hình', 'AMOLED', 1),
(11, 'Màn hình', 'Kích thước', '6.67 inch', 2),
(11, 'Màn hình', 'Độ phân giải', '2400 x 1080 pixels (FHD+)', 3),
(11, 'Màn hình', 'Tần số quét', '120Hz', 4),
(11, 'Màn hình', 'Độ sáng tối đa', '1200 nits', 5),
(11, 'Hiệu năng', 'Chipset', 'Qualcomm Snapdragon 685 (6nm)', 1),
(11, 'Hiệu năng', 'CPU', '8 nhân Kryo 265 (2.8GHz)', 2),
(11, 'Hiệu năng', 'GPU', 'Adreno 610', 3),
(11, 'Hiệu năng', 'RAM', '4GB / 8GB LPDDR4X', 4),
(11, 'Camera', 'Camera sau', '50MP (chính) + 8MP (góc rộng) + 2MP (macro)', 1),
(11, 'Camera', 'Camera trước', '13MP', 2),
(11, 'Camera', 'Quay video', '1080p@30fps', 3),
(11, 'Pin & Sạc', 'Dung lượng pin', '5000 mAh', 1),
(11, 'Pin & Sạc', 'Sạc nhanh', '33W', 2),
(11, 'Kết nối', 'SIM', 'Dual Nano SIM', 1),
(11, 'Kết nối', 'Cổng sạc', 'USB Type-C', 2),
(11, 'Kết nối', 'Wi-Fi', 'Wi-Fi 5 (802.11ac)', 3),
(11, 'Kết nối', 'Bluetooth', '5.1', 4),
(11, 'Thiết kế', 'Kích thước', '165.9 x 76 x 7.9 mm', 1),
(11, 'Thiết kế', 'Trọng lượng', '183.5g', 2),
(11, 'Thiết kế', 'Chống nước', 'IP53', 3),
(11, 'Hệ điều hành', 'OS', 'Android 13, MIUI 14', 1),

-- Bàn phím cơ Keychron K2 (id=12)
(12, 'Thông số chung', 'Layout', '75% (84 phím)', 1),
(12, 'Thông số chung', 'Switch', 'Gateron G Pro (Red/Brown/Blue)', 2),
(12, 'Thông số chung', 'Keycap', 'PBT Double-shot, OEM Profile', 3),
(12, 'Thông số chung', 'Đèn nền', 'RGB 18 chế độ', 4),
(12, 'Kết nối', 'Không dây', 'Bluetooth 5.1 (3 thiết bị)', 1),
(12, 'Kết nối', 'Có dây', 'USB Type-C', 2),
(12, 'Kết nối', 'Tương thích', 'Windows / macOS / Linux / iOS / Android', 3),
(12, 'Pin & Sạc', 'Dung lượng pin', '4000 mAh', 1),
(12, 'Pin & Sạc', 'Thời lượng pin', 'Lên đến 240 giờ (tắt đèn)', 2),
(12, 'Pin & Sạc', 'Sạc', 'USB Type-C', 3),
(12, 'Thiết kế', 'Khung', 'Nhôm CNC + nhựa ABS', 1),
(12, 'Thiết kế', 'Kích thước', '317 x 129 x 40 mm', 2),
(12, 'Thiết kế', 'Trọng lượng', '663g', 3),
(12, 'Thiết kế', 'Hot-swap', 'Có (phiên bản V2)', 4);

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--


DROP TABLE IF EXISTS `reviews`;
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `reviewer_name` varchar(100) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `reviewer_name`, `rating`, `comment`, `created_at`) VALUES
(1, 1, 'Nguyễn Văn A', 5, 'Sản phẩm rất tốt, giao hàng nhanh!', '2026-05-08 14:41:34'),
(2, 1, 'Trần Thị B', 4, 'Hài lòng, pin trâu hơn mong đợi', '2026-05-08 14:41:34'),
(3, 2, 'Lê Văn C', 5, 'Giá hợp lý, hiệu năng ổn định', '2026-05-08 14:41:34'),
(4, 2, 'fds', 5, 'dfs', '2026-05-08 15:17:48'),
(5, 5, 'dfsf', 3, 'dfdsf', '2026-05-08 15:32:48'),
(6, 1, 'dsad', 5, 'rất là tôn lun đó', '2026-05-08 16:11:27'),
(7, 1, 'dsfd', 1, 'nhân viên cực kỳ tệ', '2026-05-08 16:11:43'),
(8, 1, 'Người đẹp Trai', 1, 'rát tệ', '2026-05-09 03:08:18'),
(9, 1, 'dsfd', 3, 'Tạm được', '2026-05-16 03:10:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT '',
  `address` text DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `address`, `role`, `is_active`, `password`, `created_at`, `updated_at`) VALUES
(1, 'sadas11', 'gg@gmail.com', '0398393978', '12321323', 'user', 1, '$2y$10$AX0oHPIeSeji4cWKBzHda.eS1d4JCyttJrlb6HIMUVu1Vxk7GRvYe', '2026-05-15 14:44:08', '2026-05-16 03:36:10'),
(2, 'Test User Updated', 'testuser@example.com', '0123456789', '123 Main St, Hanoi', 'user', 1, '$2y$10$ifXcuI7zTnUDsJwsyR3hWu4jARvmZ8OSVVe4u7b/H4F7jCnN7Ysgi', '2026-05-15 14:47:12', '2026-05-15 14:48:10'),
(3, 'Administrator', 'admin@techstore.vn', '', NULL, 'admin', 1, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-05-16 00:38:14', '2026-05-16 00:38:14'),
(4, '324324', '42432@gmail.com', '03983939763424', NULL, 'user', 1, '$2y$10$3dcVqCAjUAmnIte/83nwmOYuEzibiyi.MRUlbPquhLxwI2MIvbJeO', '2026-05-16 03:32:24', '2026-05-16 03:32:24');

-- --------------------------------------------------------

--
-- Table structure for table `vouchers`
--

DROP TABLE IF EXISTS `vouchers`;
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `discount_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` decimal(15,0) NOT NULL,
  `min_order_value` decimal(15,0) DEFAULT 0,
  `usage_limit` int(11) DEFAULT 0,
  `used_count` int(11) DEFAULT 0,
  `end_date` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vouchers`
--

INSERT INTO `vouchers` (`id`, `code`, `category_id`, `discount_type`, `discount_value`, `min_order_value`, `usage_limit`, `used_count`, `end_date`, `is_active`, `created_at`) VALUES
(7, 'SADSA', 1, 'percent', 12, 2000000, 12, 0, '2026-06-05 09:03:00', 1, '2026-05-16 02:58:08');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_token` (`token`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

DROP TABLE IF EXISTS `wishlist`;
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_product` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
