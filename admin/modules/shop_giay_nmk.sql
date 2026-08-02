-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th8 01, 2026 lúc 02:52 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `shop_giay_nmk`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `brands`
--

CREATE TABLE `brands` (
  `id` int(11) NOT NULL,
  `brand_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `brands`
--

INSERT INTO `brands` (`id`, `brand_name`, `description`) VALUES
(1, 'Nike', 'Thương hiệu thể thao hàng đầu thế giới'),
(2, 'Adidas', 'Thương hiệu ba sọc đến từ Đức'),
(3, 'Biti\'s', 'Thương hiệu quốc dân Việt Nam'),
(6, 'fffffffffffffffffffffffffffaaaaaa', 'ffffffffffffff'),
(7, 'Lulu Bot', 'ssssssss');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`) VALUES
(1, 'Giày Thể Thao', 'Các dòng giày phục vụ chạy bộ, tập gym'),
(2, 'Giày Sneaker/ Thời Trang', 'Giày đi chơi, phong cách năng động'),
(3, 'Giày Tây / Công Sở', ''),
(10, 'DanDanJoJo', '');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(20) NOT NULL,
  `discount_value` decimal(12,2) NOT NULL,
  `discount_type` enum('fixed','percent') DEFAULT 'fixed',
  `min_order_amount` decimal(12,2) DEFAULT 0.00,
  `max_discount` decimal(12,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expiry_date` date NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `max_discount_amount` decimal(12,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `discount_value`, `discount_type`, `min_order_amount`, `max_discount`, `usage_limit`, `used_count`, `expiry_date`, `status`, `start_date`, `end_date`, `max_discount_amount`) VALUES
(1, 'NMK100K', 100000.00, 'fixed', 0.00, NULL, NULL, 1, '2026-12-31', 1, NULL, NULL, 0.00),
(2, 'GIAM20', 20.00, 'percent', 0.00, NULL, NULL, 0, '2026-06-30', 1, NULL, NULL, 0.00),
(3, 'WELCOME2026', 20.00, 'percent', 45.00, NULL, 177, 0, '0000-00-00', 1, '2026-07-24 21:20:00', '2026-08-30 21:20:00', 20000.00),
(4, 'WELCOME2026E', 20000.00, 'fixed', 21111111.00, NULL, 12, 0, '0000-00-00', 1, '2026-07-24 21:23:00', '2026-08-23 21:23:00', 10000.00),
(5, 'WELCOME20260801', 20.00, 'percent', 100000.00, NULL, 100000, 0, '0000-00-00', 1, '2026-07-27 01:02:00', '2026-08-26 01:02:00', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_orders`
--

CREATE TABLE `import_orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `import_code` varchar(50) NOT NULL,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `import_orders`
--

INSERT INTO `import_orders` (`id`, `user_id`, `import_code`, `total_cost`, `note`, `created_at`) VALUES
(1, 1, 'NMK_IMP_20260724_211150', 1222220.00, '', '2026-07-24 19:11:50'),
(2, 1, 'NMK_IMP_20260727_005945', 204000.00, '', '2026-07-26 22:59:45'),
(3, 1, 'NMK_IMP_20260727_010008', 1500000.00, '', '2026-07-26 23:00:08');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `import_order_details`
--

CREATE TABLE `import_order_details` (
  `id` int(11) NOT NULL,
  `import_id` int(11) NOT NULL,
  `variant_id` int(11) NOT NULL,
  `import_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `import_order_details`
--

INSERT INTO `import_order_details` (`id`, `import_id`, `variant_id`, `import_price`, `quantity`) VALUES
(1, 1, 6, 122222.00, 10),
(2, 2, 11, 12000.00, 17),
(3, 3, 11, 150000.00, 10);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `address` text NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `coupon_code` varchar(20) DEFAULT NULL,
  `payment_method` enum('COD','Online') DEFAULT 'COD',
  `status` enum('Chờ xác nhận','Đang xử lý','Đang giao','Đã giao','Đã hủy') DEFAULT 'Chờ xác nhận',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `fullname`, `email`, `phone`, `address`, `total_price`, `coupon_code`, `payment_method`, `status`, `created_at`) VALUES
(1, 3, 'Nguyễn Thụ Hưởng', 'khue2002gl@gmail.com', '0984981098', 'sssssssssssss', 4200000.00, NULL, 'COD', 'Đã hủy', '2026-05-30 08:26:59'),
(2, 5, 'nguyen khue', 'khffffffffffffff@gmail.com', '123456', 'ssssssssssssssss', 2500000.00, NULL, 'COD', 'Đã hủy', '2026-05-31 08:27:54'),
(3, 5, 'nguyen khue', 'khffffffffffffff@gmail.com', '123456', 'ssssssssssssssss', 2500000.00, NULL, 'COD', 'Đã giao', '2026-05-31 09:23:04'),
(4, 4, 'Nguyễn Thụ Hưởng', 'khue0804gl@gmail.com', '0984981098', 'fffffffff', 9999999999.99, NULL, 'COD', 'Đã hủy', '2026-07-24 18:02:14'),
(5, 1, 'Nguyễn Minh Khuê', 'admin@nmkshoes.com', '0901234567', 'Định Quán, Đồng Nai', 550000.00, NULL, 'Online', 'Chờ xác nhận', '2026-07-24 21:02:56'),
(9, 1, 'Nguyễn Minh Khuê', 'admin@nmkshoes.com', '0901234567', 'Định Quán, Đồng Nai', 15540000.00, 'NMK100K', 'Online', 'Đã hủy', '2026-07-26 22:25:16'),
(10, 4, 'Nguyễn Thụ Hưởng', 'khue0804gl@gmail.com', '0984981098', 'ddddđ', 1600000.00, NULL, 'Online', 'Đang xử lý', '2026-07-26 22:57:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `variant_id`, `quantity`, `price`) VALUES
(1, 1, NULL, 1, 4200000.00),
(2, 2, NULL, 1, 2500000.00),
(3, 3, NULL, 1, 2500000.00),
(4, 4, 6, 1, 9999999999.99),
(5, 5, 9, 5, 110000.00),
(9, 9, 12, 3, 400000.00),
(10, 9, 11, 1, 14440000.00),
(11, 10, 12, 4, 400000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `old_price` decimal(12,2) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `category_id`, `brand_id`, `product_name`, `price`, `old_price`, `image_url`, `description`, `status`, `created_at`) VALUES
(3, 1, 2, 'ddddđ', 121323000.00, 12323000.00, NULL, '333333333333', 1, '2026-05-30 16:49:51'),
(5, 3, 2, 'Nike 0004444', 9900000000.00, 1000000.00, 'nmk_1784925511_6a63cd479817d.jpg', '22222222222222', 1, '2026-06-27 21:11:01'),
(7, NULL, 3, 'DanDanJoJo3333', 110000.00, 222220000.00, 'nmk_1784925482_6a63cd2acddb7.jpg', '222222', 1, '2026-07-24 18:50:30'),
(8, 2, 2, 'Liễu Như Yên', 140000.00, NULL, 'nmk_1784959862_6a645376427b9.jpg', 't546yyyyyyyyyyyyyy', 1, '2026-07-25 06:11:02'),
(9, 2, NULL, 'DanDanJoJo', 14440000.00, NULL, 'nmk_1784960058_6a64543a965cd.jpg', '3333333333', 1, '2026-07-25 06:14:18'),
(10, 10, 3, 'khue1703gl', 400000.00, 600000.00, 'nmk_1785103447_6a6684571b322.jpg', '666666', 1, '2026-07-26 22:04:07'),
(11, 10, 2, 'khue1703gl', 8000000.00, 3000.00, 'nmk_1785106852_6a6691a4bcccc.png', 'eeeeeeeeeeee', 1, '2026-07-26 23:00:52');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `color` varchar(50) NOT NULL,
  `size` int(11) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_variants`
--

INSERT INTO `product_variants` (`id`, `product_id`, `color`, `size`, `stock`, `low_stock_threshold`, `image`) VALUES
(6, 5, 'đen', 32, 22, 3, NULL),
(9, 7, 'đen', 44, 5, 5, NULL),
(10, 8, 'đen', 44, 10, 5, NULL),
(11, 9, 'trắng', 44, 37, 5, NULL),
(12, 10, 'trắng', 44, 37, 5, NULL),
(13, 11, 'vàng', 40, 20, 5, NULL),
(14, 10, 'đen', 38, 100, 5, NULL),
(15, 3, 'đen', 41, 11, 5, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `rating`, `comment`, `status`, `created_at`) VALUES
(1, 5, 4, 4, 'tttttttttt', 1, '2026-07-24 20:22:53');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','staff','customer') DEFAULT 'customer',
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin_nmk', '$2y$10$N9TVeAkZTvO1pEfOikE6q.BlYtwoq0e1gdq6zEJYXvhKU31txM93K', 'Nguyễn Minh Khuê', 'admin@nmkshoes.com', '0901234567', 'Định Quán, Đồng Nai', 'admin', 1, '2026-05-30 06:52:20', '2026-07-24 14:42:53'),
(2, 'khachhang1', '$2y$10$N9TVeAkZTvO1pEfOikE6q.BlYtwoq0e1gdq6zEJYXvhKU31txM93K', 'Trần Văn A', 'khach1@gmail.com', '0987654321', 'Quận 1, TP.HCM', 'customer', 1, '2026-05-30 06:52:20', '2026-05-30 15:13:48'),
(3, 'root111', '$2y$10$AIlEcJXumq37gv1BtxjXY.RMjpIcWEOK8gsDMJjnGRN8Byb2Pmnua', 'Nguyễn Thụ Hưởng', 'khue2002gl@gmail.com', '0984981098', 'sssssssssssss', 'customer', 1, '2026-05-30 08:20:13', '2026-05-30 08:20:13'),
(4, 'root123', '$2y$10$N9TVeAkZTvO1pEfOikE6q.BlYtwoq0e1gdq6zEJYXvhKU31txM93K', 'Nguyễn Thụ Hưởng', 'khue0804gl@gmail.com', '0984981098', 'ddddđ', 'customer', 1, '2026-05-30 14:37:20', '2026-07-24 20:00:13'),
(5, 'root2', '$2y$10$Ho6aNsr5Yp1vgGP6MRB8YOYi2znK0MxDwHPr2g86aPWTjNOduuE3K', 'nguyen khue', 'khffffffffffffff@gmail.com', '123456', 'ssssssssssssssss', 'customer', 1, '2026-05-31 08:20:00', '2026-05-31 08:20:00'),
(6, '', '$2y$10$e88yR22j3c/.tC2s06Jz/eA5O5s4.L650A.1X7H/32i0C1wQ471uO', 'Quản trị viên NMK', 'admin@nmk.com', NULL, NULL, 'admin', 1, '2026-07-24 12:29:04', '2026-07-24 12:29:04'),
(7, 'root4', '$2y$10$tdSWyYIpCLFViPQJ6VelH./JfdE0n4c5AOLPqaXUO2IsfMRCPOuZC', 'Nguyễn Thụ ffffffffff', 'fffffffffffff@gmail.com', '3333333333', '3333333', 'customer', 0, '2026-07-24 18:03:06', '2026-07-26 23:03:13'),
(8, 'khue111', '$2y$10$u5s5USXmLXyN2lWW6eJSIeaEwKe.DKUFFmwQ8g2x.xzjWGi.5wOAa', 'Nguyễn Minh Khuê', 'khu2e002@gmail.com', '022289913481', '1111111111111', 'customer', 1, '2026-07-26 22:58:47', '2026-07-26 22:58:47');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand_name` (`brand_name`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `import_id` (`import_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `variant_id` (`variant_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_size_color` (`size`,`color`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `brands`
--
ALTER TABLE `brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `import_orders`
--
ALTER TABLE `import_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `import_order_details`
--
ALTER TABLE `import_order_details`
  ADD CONSTRAINT `import_order_details_ibfk_1` FOREIGN KEY (`import_id`) REFERENCES `import_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `import_order_details_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`variant_id`) REFERENCES `product_variants` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
