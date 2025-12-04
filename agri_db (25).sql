-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 11:26 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `agri_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_deletion_requests`
--

CREATE TABLE `account_deletion_requests` (
  `requestID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_deletion_requests`
--

INSERT INTO `account_deletion_requests` (`requestID`, `userID`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `requested_at`) VALUES
(1, 13, 'I want to delete my account permanently.', 'pending', NULL, NULL, '2025-12-04 09:59:27'),
(2, 14, 'Privacy concerns.', 'approved', 1, '2025-12-04 09:59:27', '2025-12-04 09:59:27'),
(3, 18, 'Duplicate account.', 'rejected', 1, '2025-12-04 09:59:27', '2025-12-04 09:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cartID` int(11) NOT NULL,
  `buyerID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cart`
--

INSERT INTO `cart` (`cartID`, `buyerID`, `productID`, `quantity`, `added_at`, `updated_at`) VALUES
(1, 3, 7, 2, '2025-11-16 00:30:00', '2025-11-25 03:53:48'),
(2, 3, 11, 1, '2025-11-16 01:15:00', '2025-11-25 03:53:48'),
(3, 4, 12, 3, '2025-11-16 02:00:00', '2025-11-25 03:53:48'),
(10, 8, 3, 4, '2025-11-23 02:16:48', '2025-11-27 17:57:27'),
(11, 8, 9, 3, '2025-11-23 02:16:50', '2025-11-28 01:11:18'),
(22, 2, 1, 1, '2025-11-29 03:46:32', '2025-11-29 03:46:32'),
(23, 2, 2, 1, '2025-11-29 03:46:32', '2025-11-29 03:46:32'),
(25, 8, 1, 1, '2025-11-29 12:20:03', '2025-11-29 12:20:03'),
(26, 16, 4, 1, '2025-11-30 01:24:50', '2025-11-30 01:24:50');

--
-- Triggers `cart`
--
DELIMITER $$
CREATE TRIGGER `before_cart_insert` BEFORE INSERT ON `cart` FOR EACH ROW BEGIN
    DECLARE unique_items INT;
    DECLARE total_quantity INT;
    DECLARE max_unique INT DEFAULT 80;
    DECLARE max_total INT DEFAULT 200;
    DECLARE max_per_item INT DEFAULT 20;

    SELECT CAST(setting_value AS UNSIGNED) INTO max_unique
    FROM system_settings WHERE setting_key = 'cart_max_unique_items' LIMIT 1;

    SELECT CAST(setting_value AS UNSIGNED) INTO max_total
    FROM system_settings WHERE setting_key = 'cart_max_total_quantity' LIMIT 1;

    SELECT CAST(setting_value AS UNSIGNED) INTO max_per_item
    FROM system_settings WHERE setting_key = 'cart_item_max_quantity' LIMIT 1;

    SELECT COUNT(*), COALESCE(SUM(quantity), 0)
    INTO unique_items, total_quantity
    FROM cart
    WHERE buyerID = NEW.buyerID;

    IF unique_items >= max_unique THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cart limit reached: Maximum 80 different items allowed';
    END IF;

    IF (total_quantity + NEW.quantity) > max_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cart quantity limit: Maximum 200 total items allowed';
    END IF;

    IF NEW.quantity > max_per_item THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item quantity limit: Maximum 20 per product';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_cart_update` BEFORE UPDATE ON `cart` FOR EACH ROW BEGIN
    DECLARE total_quantity INT;
    DECLARE max_total INT DEFAULT 200;
    DECLARE max_per_item INT DEFAULT 20;

    SELECT CAST(setting_value AS UNSIGNED) INTO max_total
    FROM system_settings WHERE setting_key = 'cart_max_total_quantity' LIMIT 1;

    SELECT CAST(setting_value AS UNSIGNED) INTO max_per_item
    FROM system_settings WHERE setting_key = 'cart_item_max_quantity' LIMIT 1;

    SELECT COALESCE(SUM(quantity), 0)
    INTO total_quantity
    FROM cart
    WHERE buyerID = NEW.buyerID AND cartID != NEW.cartID;

    IF (total_quantity + NEW.quantity) > max_total THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cart quantity limit: Maximum 200 total items allowed';
    END IF;

    IF NEW.quantity > max_per_item THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Item quantity limit: Maximum 20 per product';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `categoryID` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `image_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`categoryID`, `category`, `image_url`) VALUES
(1, 'Vegetables', '/images/vegetables.jpg'),
(2, 'Fruits', '/images/fruits.jpg'),
(3, 'Seeds', '/images/seeds.jpg'),
(4, 'Saplings', '/images/saplings.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_riders`
--

CREATE TABLE `delivery_riders` (
  `riderID` int(11) NOT NULL,
  `rider_name` varchar(200) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `vehicle_type` varchar(50) DEFAULT NULL,
  `vehicle_plate` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `total_deliveries` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_riders`
--

INSERT INTO `delivery_riders` (`riderID`, `rider_name`, `contact_number`, `vehicle_type`, `vehicle_plate`, `is_active`, `total_deliveries`, `created_at`, `updated_at`) VALUES
(1, 'Juan Dela Cruz', '09171234567', 'Motorcycle', NULL, 1, 0, '2025-11-25 03:53:43', '2025-11-25 03:53:43'),
(2, 'Lebron James', '09181234567', 'Motorcycle', 'ABC 123', 1, 0, '2025-11-25 03:53:43', '2025-11-29 12:22:37'),
(3, 'Pedro Reyes', '09191234567', 'Truck', NULL, 1, 0, '2025-11-25 03:53:43', '2025-11-25 03:53:43'),
(4, 'Michael Jordan', '09121493081', 'Tricycle', NULL, 0, 0, '2025-11-28 09:15:00', '2025-11-28 21:03:19'),
(5, 'Kapitan Tiago', '09118264090', 'Truck', NULL, 0, 0, '2025-11-28 09:15:51', '2025-11-28 21:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_logs`
--

CREATE TABLE `inventory_logs` (
  `logID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `change_type` enum('restock','sale','adjustment','return','damage','reserved','unreserved') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_logs`
--

INSERT INTO `inventory_logs` (`logID`, `productID`, `change_type`, `quantity_change`, `quantity_after`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 1, 'restock', 20, 70, NULL, 'Weekly restock', 1, '2025-12-04 09:59:28'),
(2, 3, 'sale', -5, 95, 1, 'Sold items from order #1', 2, '2025-12-04 09:59:28'),
(3, 9, 'adjustment', -3, 77, NULL, 'Damaged during transport', 1, '2025-12-04 09:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `buyerID` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `order_status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `delivery_address` text NOT NULL,
  `delivery_municipality` varchar(100) NOT NULL,
  `delivery_province` varchar(100) NOT NULL,
  `delivery_postal_code` varchar(10) NOT NULL,
  `payment_method` enum('cash_on_delivery','gcash','bank_transfer') DEFAULT 'cash_on_delivery',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_method_new` enum('cod','gcash','paymaya') NOT NULL DEFAULT 'cod',
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `lgu_delivery_status` enum('pending_pickup','picked_up','in_transit','delivered','failed') DEFAULT 'pending_pickup',
  `assigned_rider_id` int(11) DEFAULT NULL,
  `rider_assigned_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `payment_received_by_lgu_at` timestamp NULL DEFAULT NULL,
  `credited_to_seller_at` timestamp NULL DEFAULT NULL,
  `lgu_notes` text DEFAULT NULL,
  `receipt_generated` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`orderID`, `buyerID`, `order_date`, `total_amount`, `order_status`, `delivery_address`, `delivery_municipality`, `delivery_province`, `delivery_postal_code`, `payment_method`, `payment_status`, `payment_method_new`, `shipping_fee`, `lgu_delivery_status`, `assigned_rider_id`, `rider_assigned_at`, `picked_up_at`, `delivered_at`, `payment_received_by_lgu_at`, `credited_to_seller_at`, `lgu_notes`, `receipt_generated`, `notes`, `updated_at`) VALUES
(1, 2, '2025-11-10 02:30:00', 295.00, 'delivered', '123 Rizal Avenue, Brgy. II-A', 'San Pablo City', 'Laguna', '4000', 'gcash', 'paid', 'gcash', 0.00, 'pending_pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Please deliver before 3pm', '2025-11-25 03:53:48'),
(2, 3, '2025-11-12 06:20:00', 540.00, 'processing', '456 Luna Street, Brgy. III-B', 'San Pablo City', 'Laguna', '4000', 'cash_on_delivery', 'pending', 'cod', 0.00, 'pending_pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, '2025-11-18 13:15:08'),
(3, 4, '2025-11-15 01:15:00', 220.00, 'shipped', '789 Bonifacio Road, Brgy. VI-C', 'San Pablo City', 'Laguna', '4000', 'gcash', 'paid', 'gcash', 0.00, 'pending_pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'Call upon arrival', '2025-11-25 03:53:48');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_payment_received` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    DECLARE shop_id INT;
    DECLARE current_balance DECIMAL(10,2);
    
    IF OLD.payment_received_by_lgu_at IS NULL AND NEW.payment_received_by_lgu_at IS NOT NULL THEN
        SELECT DISTINCT p.shopID INTO shop_id
        FROM order_items oi
        JOIN products p ON oi.productID = p.productID
        WHERE oi.orderID = NEW.orderID
        LIMIT 1;
        
        IF shop_id IS NOT NULL THEN
            SELECT balance INTO current_balance FROM shops WHERE shopID = shop_id;
            
            UPDATE shops 
            SET balance = balance + NEW.total_amount, total_earned = total_earned + NEW.total_amount
            WHERE shopID = shop_id;
            
            INSERT INTO seller_transactions 
            (shopID, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, notes)
            VALUES 
            (shop_id, 'order_payment', NEW.total_amount, current_balance, current_balance + NEW.total_amount, 'order', NEW.orderID, CONCAT('Payment from Order #', NEW.orderID));
            
            UPDATE orders SET credited_to_seller_at = CURRENT_TIMESTAMP WHERE orderID = NEW.orderID;
        END IF;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_rider_delivery_completed` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
    IF OLD.lgu_delivery_status != 'delivered' AND NEW.lgu_delivery_status = 'delivered' AND NEW.assigned_rider_id IS NOT NULL THEN
        UPDATE delivery_riders 
        SET total_deliveries = total_deliveries + 1 
        WHERE riderID = NEW.assigned_rider_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `orderItemID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`orderItemID`, `orderID`, `productID`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 2, 35.00, 70.00),
(2, 1, 5, 1, 80.00, 80.00),
(3, 1, 6, 1, 150.00, 150.00),
(4, 2, 9, 3, 180.00, 540.00),
(5, 3, 10, 5, 40.00, 200.00),
(6, 3, 4, 1, 120.00, 120.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `productID` int(11) NOT NULL,
  `shopID` int(11) DEFAULT NULL,
  `sellerID` int(11) NOT NULL,
  `categoryID` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 5,
  `unit` varchar(50) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`productID`, `shopID`, `sellerID`, `categoryID`, `product_name`, `description`, `price`, `stock_quantity`, `reserved_quantity`, `low_stock_threshold`, `unit`, `is_available`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 'Fresh Pechay', 'Freshly harvested pechay, crisp and green. Perfect for sinigang and stir-fry.', 35.00, 50, 0, 5, 'bundle', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:34'),
(2, 1, 1, 1, 'Sitaw (String Beans)', 'Long and tender sitaw, freshly picked. Great for adobong sitaw.', 45.00, 30, 0, 5, 'bundle', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(3, 1, 1, 1, 'Kamote (Sweet Potato)', 'Yellow sweet potato, naturally sweet and nutritious.', 60.00, 100, 0, 5, 'kg', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(4, 1, 1, 3, 'Tomato Seeds', 'High-quality hybrid tomato seeds. Package of 50 seeds.', 120.00, 20, 0, 5, 'pack', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(5, 2, 2, 1, 'Organic Lettuce', 'Certified organic lettuce grown without pesticides. Fresh and crispy.', 80.00, 25, 0, 5, 'head', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(6, 2, 2, 1, 'Organic Cherry Tomatoes', 'Sweet and juicy organic cherry tomatoes. Safe for kids.', 150.00, 15, 0, 5, 'kg', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(7, 2, 2, 1, 'Organic Eggplant', 'Long purple eggplant grown organically. Perfect for tortang talong.', 70.00, 40, 0, 5, 'kg', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(8, 2, 2, 3, 'Organic Vegetable Seeds', 'Mixed organic vegetable seeds. Perfect for home gardens.', 200.00, 30, 0, 5, 'pack', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(9, 3, 3, 2, 'Carabao Mango', 'Premium Carabao mangoes from Laguna. Sweet and fleshy, export quality.', 180.00, 80, 0, 5, 'kg', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(10, 3, 3, 2, 'Fresh Coconuts', 'Young coconuts with refreshing juice. Perfect for summer.', 40.00, 150, 0, 5, 'pcs', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(11, 3, 3, 2, 'Ripe Papaya', 'Sweet ripe papaya with red-orange flesh. Rich in vitamins.', 50.00, 60, 0, 5, 'kg', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35'),
(12, 3, 3, 4, 'Mango Saplings', 'Grafted carabao mango saplings, 1 year old. Ready for transplanting.', 250.00, 35, 0, 5, 'pcs', 1, '2025-11-18 13:15:08', '2025-11-28 00:49:35');

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `imageID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_main` tinyint(1) DEFAULT 0,
  `image_order` tinyint(1) DEFAULT 1,
  `file_size_kb` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`imageID`, `productID`, `image_path`, `is_main`, `image_order`, `file_size_kb`, `uploaded_at`) VALUES
(1, 1, '/uploads/products/pechay_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(2, 1, '/uploads/products/pechay_bunch.jpg', 0, 2, NULL, '2025-11-18 13:15:08'),
(3, 2, '/uploads/products/sitaw_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(4, 2, '/uploads/products/sitaw_fresh.jpg', 0, 4, NULL, '2025-11-18 13:15:08'),
(5, 2, '/uploads/products/sitaw_harvest.jpg', 0, 5, NULL, '2025-11-18 13:15:08'),
(6, 3, '/uploads/products/kamote_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(7, 3, '/uploads/products/kamote_yellow.jpg', 0, 7, NULL, '2025-11-18 13:15:08'),
(8, 4, '/uploads/products/tomato_seeds_pack.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(9, 4, '/uploads/products/tomato_seeds_close.jpg', 0, 9, NULL, '2025-11-18 13:15:08'),
(10, 5, '/uploads/products/lettuce_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(11, 5, '/uploads/products/lettuce_fresh.jpg', 0, 11, NULL, '2025-11-18 13:15:08'),
(12, 5, '/uploads/products/lettuce_organic.jpg', 0, 12, NULL, '2025-11-18 13:15:08'),
(13, 6, '/uploads/products/cherry_tomato_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(14, 6, '/uploads/products/cherry_tomato_bowl.jpg', 0, 14, NULL, '2025-11-18 13:15:08'),
(15, 7, '/uploads/products/eggplant_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(16, 7, '/uploads/products/eggplant_purple.jpg', 0, 16, NULL, '2025-11-18 13:15:08'),
(17, 7, '/uploads/products/eggplant_bunch.jpg', 0, 17, NULL, '2025-11-18 13:15:08'),
(18, 8, '/uploads/products/veg_seeds_pack.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(19, 8, '/uploads/products/veg_seeds_variety.jpg', 0, 19, NULL, '2025-11-18 13:15:08'),
(20, 9, '/uploads/products/mango_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(21, 9, '/uploads/products/mango_ripe.jpg', 0, 21, NULL, '2025-11-18 13:15:08'),
(22, 9, '/uploads/products/mango_export.jpg', 0, 22, NULL, '2025-11-18 13:15:08'),
(23, 10, '/uploads/products/coconut_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(24, 10, '/uploads/products/coconut_juice.jpg', 0, 24, NULL, '2025-11-18 13:15:08'),
(25, 11, '/uploads/products/papaya_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(26, 11, '/uploads/products/papaya_ripe.jpg', 0, 26, NULL, '2025-11-18 13:15:08'),
(27, 11, '/uploads/products/papaya_sliced.jpg', 0, 27, NULL, '2025-11-18 13:15:08'),
(28, 12, '/uploads/products/sapling_main.jpg', 1, 0, NULL, '2025-11-18 13:15:08'),
(29, 12, '/uploads/products/sapling_nursery.jpg', 0, 29, NULL, '2025-11-18 13:15:08'),
(30, 1, '/uploads/products/pechay_field.jpg', 0, 3, NULL, '2025-11-29 04:09:49'),
(31, 3, '/uploads/products/kamote_harvest.jpg', 0, 8, NULL, '2025-11-29 04:09:49'),
(32, 4, '/uploads/products/tomato_seeds_growing.jpg', 0, 10, NULL, '2025-11-29 04:09:49'),
(33, 6, '/uploads/products/cherry_tomato_vine.jpg', 0, 15, NULL, '2025-11-29 04:09:50'),
(34, 8, '/uploads/products/veg_seeds_planting.jpg', 0, 20, NULL, '2025-11-29 04:09:50'),
(35, 10, '/uploads/products/coconut_tree.jpg', 0, 25, NULL, '2025-11-29 04:09:50'),
(36, 12, '/uploads/products/sapling_planted.jpg', 0, 30, NULL, '2025-11-29 04:09:50');

--
-- Triggers `product_images`
--
DELIMITER $$
CREATE TRIGGER `before_product_image_insert` BEFORE INSERT ON `product_images` FOR EACH ROW BEGIN
    DECLARE image_count INT;
    DECLARE main_image_exists INT;
    DECLARE max_images INT DEFAULT 5;
    
    -- Get max images setting
    SELECT CAST(setting_value AS UNSIGNED) INTO max_images
    FROM system_settings WHERE setting_key = 'max_product_images' LIMIT 1;
    
    -- If inserting as main image, check if one already exists
    IF NEW.is_main = 1 THEN
        SELECT COUNT(*) INTO main_image_exists
        FROM product_images
        WHERE productID = NEW.productID AND is_main = 1;
        
        IF main_image_exists > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Product already has a main image. Set is_main = 0 or update existing main image.';
        END IF;
    END IF;
    
    -- Count total images for this product
    SELECT COUNT(*) INTO image_count
    FROM product_images
    WHERE productID = NEW.productID;
    
    -- Check max images limit
    IF image_count >= max_images THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Maximum 5 images allowed per product (1 main + 4 gallery)';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_product_image_update` BEFORE UPDATE ON `product_images` FOR EACH ROW BEGIN
    DECLARE main_image_exists INT;
    
    -- If changing to main image, check if another main exists
    IF NEW.is_main = 1 AND OLD.is_main = 0 THEN
        SELECT COUNT(*) INTO main_image_exists
        FROM product_images
        WHERE productID = NEW.productID 
          AND is_main = 1 
          AND imageID != NEW.imageID;
        
        IF main_image_exists > 0 THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Product already has a main image. Unset the existing main image first.';
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `product_images_old`
--

CREATE TABLE `product_images_old` (
  `imageID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `imageURL` varchar(500) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images_old`
--

INSERT INTO `product_images_old` (`imageID`, `productID`, `imageURL`, `is_primary`, `created_at`) VALUES
(1, 1, '/uploads/products/pechay_main.jpg', 1, '2025-11-18 13:15:08'),
(2, 1, '/uploads/products/pechay_bunch.jpg', 0, '2025-11-18 13:15:08'),
(3, 2, '/uploads/products/sitaw_main.jpg', 1, '2025-11-18 13:15:08'),
(4, 2, '/uploads/products/sitaw_fresh.jpg', 0, '2025-11-18 13:15:08'),
(5, 2, '/uploads/products/sitaw_harvest.jpg', 0, '2025-11-18 13:15:08'),
(6, 3, '/uploads/products/kamote_main.jpg', 1, '2025-11-18 13:15:08'),
(7, 3, '/uploads/products/kamote_yellow.jpg', 0, '2025-11-18 13:15:08'),
(8, 4, '/uploads/products/tomato_seeds_pack.jpg', 1, '2025-11-18 13:15:08'),
(9, 4, '/uploads/products/tomato_seeds_close.jpg', 0, '2025-11-18 13:15:08'),
(10, 5, '/uploads/products/lettuce_main.jpg', 1, '2025-11-18 13:15:08'),
(11, 5, '/uploads/products/lettuce_fresh.jpg', 0, '2025-11-18 13:15:08'),
(12, 5, '/uploads/products/lettuce_organic.jpg', 0, '2025-11-18 13:15:08'),
(13, 6, '/uploads/products/cherry_tomato_main.jpg', 1, '2025-11-18 13:15:08'),
(14, 6, '/uploads/products/cherry_tomato_bowl.jpg', 0, '2025-11-18 13:15:08'),
(15, 7, '/uploads/products/eggplant_main.jpg', 1, '2025-11-18 13:15:08'),
(16, 7, '/uploads/products/eggplant_purple.jpg', 0, '2025-11-18 13:15:08'),
(17, 7, '/uploads/products/eggplant_bunch.jpg', 0, '2025-11-18 13:15:08'),
(18, 8, '/uploads/products/veg_seeds_pack.jpg', 1, '2025-11-18 13:15:08'),
(19, 8, '/uploads/products/veg_seeds_variety.jpg', 0, '2025-11-18 13:15:08'),
(20, 9, '/uploads/products/mango_main.jpg', 1, '2025-11-18 13:15:08'),
(21, 9, '/uploads/products/mango_ripe.jpg', 0, '2025-11-18 13:15:08'),
(22, 9, '/uploads/products/mango_export.jpg', 0, '2025-11-18 13:15:08'),
(23, 10, '/uploads/products/coconut_main.jpg', 1, '2025-11-18 13:15:08'),
(24, 10, '/uploads/products/coconut_juice.jpg', 0, '2025-11-18 13:15:08'),
(25, 11, '/uploads/products/papaya_main.jpg', 1, '2025-11-18 13:15:08'),
(26, 11, '/uploads/products/papaya_ripe.jpg', 0, '2025-11-18 13:15:08'),
(27, 11, '/uploads/products/papaya_sliced.jpg', 0, '2025-11-18 13:15:08'),
(28, 12, '/uploads/products/sapling_main.jpg', 1, '2025-11-18 13:15:08'),
(29, 12, '/uploads/products/sapling_nursery.jpg', 0, '2025-11-18 13:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `reviewID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `buyerID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `review_text` text DEFAULT NULL,
  `review_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_verified_purchase` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`reviewID`, `productID`, `buyerID`, `orderID`, `rating`, `review_text`, `review_date`, `is_verified_purchase`) VALUES
(1, 1, 2, 1, 5, 'Very fresh pechay! Perfect for my sinigang. Will order again!', '2025-11-12 08:00:00', 1),
(2, 5, 2, 1, 5, 'The organic lettuce is crisp and clean. Great quality!', '2025-11-12 08:05:00', 1),
(3, 6, 2, 1, 4, 'Sweet cherry tomatoes, my kids love them. A bit pricey but worth it.', '2025-11-12 08:10:00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `seller_applications`
--

CREATE TABLE `seller_applications` (
  `applicationID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_address` text NOT NULL,
  `business_permit` varchar(255) DEFAULT NULL,
  `application_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_applications`
--

INSERT INTO `seller_applications` (`applicationID`, `userID`, `business_name`, `business_address`, `business_permit`, `application_status`, `applied_at`, `reviewed_at`, `reviewed_by`) VALUES
(1, 8, 'Dela Cruz Fresh Vegetables', 'Sitio Maligaya, Brgy. San Buenaventura, San Pablo City', 'BP-2024-001', 'pending', '2024-01-09 17:00:00', '2025-12-02 02:48:19', 1),
(2, 6, 'Rosa\'s Organic Farm', 'Purok 3, Brgy. San Isidro, San Pablo City', 'BP-2024-002', 'approved', '2024-01-11 19:00:00', '2024-01-19 22:00:00', 1),
(3, 7, 'Reyes Fruit Garden', 'Km 8, Brgy. Sto. Angel Sur, San Pablo City', 'BP-2024-003', 'approved', '2024-01-24 21:30:00', '2024-01-31 17:45:00', 1),
(6, 2, 'M and C Seed Farm', 'Poblacion, Padre Garcia, Batangas', '/uploads/business_permits/permit_user_2_1764841301.pdf', 'pending', '2025-12-04 09:41:41', NULL, NULL),
(7, 4, 'Tan Bilihan ng Palay', 'Lagalag, Tiaong, Quezon', '/uploads/business_permits/permit_user_4_1764841418.pdf', 'pending', '2025-12-04 09:43:38', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `seller_profiles`
--

CREATE TABLE `seller_profiles` (
  `sellerID` int(11) NOT NULL,
  `shopID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_description` text DEFAULT NULL,
  `farm_location` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_sales` int(11) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_profiles`
--

INSERT INTO `seller_profiles` (`sellerID`, `shopID`, `userID`, `business_name`, `business_description`, `farm_location`, `rating`, `total_sales`, `is_verified`, `created_at`) VALUES
(1, 1, 5, 'Dela Cruz Fresh Vegetables', 'Family-owned farm specializing in fresh leafy vegetables and root crops. Serving San Pablo City for over 10 years.', 'Brgy. San Buenaventura, 2 hectares', 4.8, 150, 1, '2025-11-18 13:15:08'),
(2, 2, 6, 'Rosa\'s Organic Farm', 'Certified organic farm growing chemical-free vegetables using traditional farming methods.', 'Brgy. San Isidro, 1.5 hectares', 4.9, 98, 1, '2025-11-18 13:15:08'),
(3, 3, 7, 'Reyes Fruit Garden', 'Tropical fruit orchard specializing in mangoes, coconuts, and seasonal fruits. We also sell fruit seedlings.', 'Brgy. Sto. Angel Sur, 3 hectares', 4.7, 125, 1, '2025-11-18 13:15:08'),
(10, 4, 8, 'Dela Cruz Fresh Vegetables', NULL, 'Sitio Maligaya, Brgy. San Buenaventura, San Pablo City', 0.0, 0, 1, '2025-12-02 02:48:19'),
(11, 5, 2, 'M and C Seed Farm', NULL, 'K. Morales Street, Poblacion, Padre Garcia, Batangas', 0.0, 0, 1, '2025-12-04 03:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `seller_transactions`
--

CREATE TABLE `seller_transactions` (
  `transactionID` int(11) NOT NULL,
  `shopID` int(11) NOT NULL,
  `transaction_type` enum('order_payment','withdrawal_cash','withdrawal_atm','adjustment','refund') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance_before` decimal(10,2) NOT NULL,
  `balance_after` decimal(10,2) NOT NULL,
  `reference_type` enum('order','withdrawal','manual') NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_transactions`
--

INSERT INTO `seller_transactions` (`transactionID`, `shopID`, `transaction_type`, `amount`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `processed_by`, `notes`, `created_at`) VALUES
(1, 1, 'order_payment', 350.00, 0.00, 350.00, 'order', 5, 1, 'Payment credited', '2025-12-04 09:59:28'),
(2, 2, 'withdrawal_cash', -200.00, 500.00, 300.00, 'withdrawal', 2, 1, 'Cash withdrawal processed', '2025-12-04 09:59:28'),
(3, 3, 'adjustment', 50.00, 300.00, 350.00, 'manual', NULL, 1, 'Manual balance correction', '2025-12-04 09:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `shops`
--

CREATE TABLE `shops` (
  `shopID` int(11) NOT NULL,
  `sellerID` int(11) NOT NULL,
  `shop_name` varchar(255) NOT NULL,
  `shop_slug` varchar(255) NOT NULL,
  `shop_description` text DEFAULT NULL,
  `shop_logo` varchar(500) DEFAULT NULL,
  `shop_banner` varchar(500) DEFAULT NULL,
  `farm_location` text DEFAULT NULL,
  `business_hours` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `total_products` int(11) DEFAULT 0,
  `total_orders` int(11) DEFAULT 0,
  `total_reviews` int(11) DEFAULT 0,
  `balance` decimal(10,2) DEFAULT 0.00,
  `total_earned` decimal(10,2) DEFAULT 0.00,
  `total_withdrawn` decimal(10,2) DEFAULT 0.00,
  `atm_card_number` varchar(50) DEFAULT NULL,
  `atm_card_issued_at` timestamp NULL DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shops`
--

INSERT INTO `shops` (`shopID`, `sellerID`, `shop_name`, `shop_slug`, `shop_description`, `shop_logo`, `shop_banner`, `farm_location`, `business_hours`, `contact_number`, `rating`, `total_products`, `total_orders`, `total_reviews`, `balance`, `total_earned`, `total_withdrawn`, `atm_card_number`, `atm_card_issued_at`, `is_verified`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dela Cruz Fresh Vegetables', 'dela-cruz-fresh-vegetables', 'Family-owned farm specializing in fresh leafy vegetables and root crops. Serving San Pablo City for over 10 years.', NULL, NULL, 'Brgy. San Buenaventura, 2 hectares', NULL, NULL, 4.80, 0, 0, 0, 23856.00, 5689.00, 0.00, '21-11827', '2025-11-27 18:51:10', 1, 1, '2025-11-25 03:53:43', '2025-12-04 10:04:42'),
(2, 2, 'Rosa\'s Organic Farm', 'rosas-organic-farm', 'Certified organic farm growing chemical-free vegetables using traditional farming methods.', NULL, NULL, 'Brgy. San Isidro, 1.5 hectares', NULL, NULL, 4.90, 0, 0, 0, 0.00, 0.00, 0.00, NULL, NULL, 1, 1, '2025-11-25 03:53:43', '2025-11-27 14:10:16'),
(3, 3, 'Reyes Fruit Garden', 'reyes-fruit-garden', 'Tropical fruit orchard specializing in mangoes, coconuts, and seasonal fruits. We also sell fruit seedlings.', NULL, NULL, 'Brgy. Sto. Angel Sur, 3 hectares', NULL, NULL, 4.70, 0, 0, 0, 0.00, 0.00, 0.00, '21-11823', '2025-11-29 12:20:55', 1, 1, '2025-11-25 03:53:43', '2025-11-29 12:20:55'),
(4, 10, 'Dela Cruz Fresh Vegetables', 'dela-cruz-fresh-vegetables-1', 'Welcome to Dela Cruz Fresh Vegetables!', NULL, NULL, 'Sitio Maligaya, Brgy. San Buenaventura, San Pablo City', NULL, '09121493081', 0.00, 0, 0, 0, 0.00, 0.00, 0.00, NULL, NULL, 1, 1, '2025-12-02 02:48:19', '2025-12-02 02:48:19'),
(5, 11, 'M and C Seed Farm', 'm-and-c-seed-farm', 'Welcome to M and C Seed Farm!', NULL, NULL, 'K. Morales Street, Poblacion, Padre Garcia, Batangas', NULL, '09181234567', 0.00, 0, 0, 0, 0.00, 0.00, 0.00, NULL, NULL, 1, 1, '2025-12-04 03:24:22', '2025-12-04 03:24:22');

-- --------------------------------------------------------

--
-- Table structure for table `shop_followers`
--

CREATE TABLE `shop_followers` (
  `followerID` int(11) NOT NULL,
  `shopID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `followed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_followers`
--

INSERT INTO `shop_followers` (`followerID`, `shopID`, `userID`, `followed_at`) VALUES
(1, 1, 2, '2025-12-04 09:59:28'),
(2, 2, 3, '2025-12-04 09:59:28'),
(3, 3, 8, '2025-12-04 09:59:28');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `settingID` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`settingID`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'cart_max_unique_items', '80', 'number', 'Maximum number of different products a user can add to cart', '2025-11-25 03:53:42'),
(2, 'cart_max_total_quantity', '200', 'number', 'Maximum total quantity of all items in cart', '2025-11-25 03:53:42'),
(3, 'cart_item_max_quantity', '20', 'number', 'Maximum quantity per individual product', '2025-11-25 03:53:42'),
(4, 'platform_name', 'AgriMarket', 'text', 'Platform name', '2025-11-25 03:53:42'),
(5, 'lgu_delivery_enabled', 'true', 'boolean', 'LGU handles all deliveries - zero shipping fees', '2025-11-25 03:53:42'),
(6, 'payment_methods', '[\"cod\",\"gcash\",\"paymaya\"]', 'json', 'Available payment methods', '2025-11-25 03:53:42'),
(7, 'min_withdrawal_amount', '100', 'number', 'Minimum amount sellers can withdraw in PHP', '2025-11-25 03:53:42'),
(8, 'withdrawal_processing_days', '1', 'number', 'Days to process cash withdrawal requests', '2025-11-25 03:53:42'),
(9, 'max_product_images', '5', 'number', 'Maximum images per product (1 main + 4 gallery)', '2025-11-25 03:53:42'),
(10, 'max_image_size_mb', '20', 'number', 'Maximum image file size in MB', '2025-11-25 03:53:42'),
(11, 'allowed_image_types', '[\"jpg\",\"jpeg\",\"png\",\"webp\"]', 'json', 'Allowed image file extensions', '2025-11-25 03:53:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT '/images/avatars/avt1.jpg',
  `role` enum('buyer','seller','admin') DEFAULT 'buyer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `email`, `profile_image`, `password_hash`, `full_name`, `phone`, `avatar`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'admin@sanpablo-lgu.gov.ph', '/images/avatars/avt1.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '09171234567', '/images/avatars/avt1.jpg', 'admin', 1, '2025-11-18 13:15:07', '2025-11-27 16:44:04'),
(2, 'anna.garcia@gmail.com', '/images/avatars/avt4.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Anna Garcia', '09181234567', '/images/avatars/avt4.jpg', 'buyer', 1, '2025-11-18 13:15:07', '2025-12-04 03:30:06'),
(3, 'michael.cruz@yahoo.com', '/images/avatars/avt3.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Michael Cruz', '09191234567', '/images/avatars/avt3.jpg', 'buyer', 1, '2025-11-18 13:15:07', '2025-11-25 03:53:48'),
(4, 'lisa.tan@gmail.com', '/images/avatars/avt4.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lisa Tan', '09201234567', '/images/avatars/avt4.jpg', 'buyer', 1, '2025-11-18 13:15:07', '2025-11-25 03:53:48'),
(5, 'juan.delacruz@gmail.com', '/images/avatars/avt5.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Dela Cruz', '09211234567', '/images/avatars/avt5.jpg', 'seller', 1, '2025-11-18 13:15:07', '2025-12-02 02:46:20'),
(6, 'rosa.mendoza@yahoo.com', '/images/avatars/avt6.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Rosa Mendoza', '09221234567', '/images/avatars/avt6.jpg', 'seller', 1, '2025-11-18 13:15:07', '2025-11-25 03:53:48'),
(7, 'pedro.reyes@gmail.com', '/images/avatars/avt7.jpg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro Reyes', '09231234567', '/images/avatars/avt7.jpg', 'seller', 1, '2025-11-18 13:15:07', '2025-11-25 03:53:48'),
(8, 'dennisontenorio11@gmail.com', '/images/avatars/avt13.jpg', '$2y$10$snLkU5e6aQTTYsw1H.Cq6.N2IMK.V68bPd2ukOjSqkotpmzoMMLem', 'Dennison Tenorio', '09121493081', '/images/avatars/avt13.jpg', 'buyer', 1, '2025-11-19 04:20:47', '2025-12-02 02:50:10'),
(12, 'test@example.com', NULL, '$2y$10$9AVeVnfOo6dqbmLZXwu0Qux0O1y8FdYEz9hzdcj8Lkbx.R4fi/hbG', 'Test User', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 00:49:53', '2025-11-30 00:49:53'),
(13, 'dennis1@gmail.com', NULL, '$2y$10$iHtit/MP1XPCekjBm5EQv.xn9z79lMF/78tBFIGULIyCrsNdteFqa', 'Dennis Tenorio', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 01:06:21', '2025-11-30 01:06:21'),
(14, 'lebron23@gmail.com', NULL, '$2y$10$f9x/ZAzCCmOzDhylpJBV2O1nWziUYYqG57QLBnFDzfLaDHtK72b0i', 'Lebron James', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 01:09:45', '2025-11-30 01:09:45'),
(15, 'test2@example.com', NULL, '$2y$10$XIHKDIhd4phvb3BYeNMbte.1PYHcc8OwKjswNvRqAm9aSBeGWtk5O', 'test account 2', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 01:13:14', '2025-11-30 01:13:14'),
(16, 'kyrie11@gmail.com', NULL, '$2y$10$YYY8XKRoNFIySCaubNND7u8oc2DUxb2aIMJ6ERVm/mbkzOeuAauD6', 'Kyrie Irving', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 01:18:33', '2025-11-30 01:18:33'),
(17, 'fernandz@gmail.com', NULL, '$2y$10$9kfMHxw4D8lGklf632K.GOGIliE6/D8TqF8/UvS5HsAMIjra2iGa6', 'Fernando Cruz', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-11-30 01:28:19', '2025-11-30 01:28:19'),
(18, 'kwlenard2@gmail.com', NULL, '$2y$10$f2iqfTr/eMCTHox5hdi8AO8qvb9xghr5lRhxSx/3JePlvqrYJFRSO', 'Kawhi Leonard', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-12-01 04:40:59', '2025-12-01 04:40:59'),
(19, 'kuabhouu33@gmail.com', NULL, '$2y$10$9dRJJP.sf./cvYrycU5ooexTcvPnJkvISgRhc2vPn2ezP0xaYQT6O', 'Kuya Bhouu', NULL, '/images/avatars/avt1.jpg', 'buyer', 1, '2025-12-02 10:56:34', '2025-12-02 10:56:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_addresses`
--

CREATE TABLE `user_addresses` (
  `addressID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `address` text NOT NULL,
  `municipality` varchar(100) NOT NULL,
  `province` varchar(100) NOT NULL,
  `postal_code` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_addresses`
--

INSERT INTO `user_addresses` (`addressID`, `userID`, `address`, `municipality`, `province`, `postal_code`, `created_at`) VALUES
(1, 1, 'San Pablo City Hall', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(2, 2, '123 Rizal Avenue, Brgy. II-A', 'Nagcarlan', 'Laguna', '4000', '2025-11-28 21:13:38'),
(3, 3, '456 Luna Street, Brgy. III-B', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(4, 4, '789 Bonifacio Road, Brgy. VI-C', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(5, 5, 'Sitio Maligaya, Brgy. San Buenaventura', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(6, 6, 'Purok 3, Brgy. San Isidro', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(7, 7, 'Km 8, Brgy. Sto. Angel Sur', 'San Pablo City', 'Laguna', '4000', '2025-11-28 21:13:38'),
(8, 8, 'Matipunso', 'San Antonio', 'laguna', '4324', '2025-11-28 21:13:38'),
(16, 2, 'F. Mendoza Street', 'Tiaong', 'Quezon', '4324', '2025-11-28 23:44:45'),
(17, 8, 'F. Mendoza St. Matipunso', 'San Antonio', 'Quezon', '4324', '2025-12-02 01:08:38');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlistID` int(11) NOT NULL,
  `buyerID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlist`
--

INSERT INTO `wishlist` (`wishlistID`, `buyerID`, `productID`, `added_at`) VALUES
(1, 2, 9, '2025-11-14 04:00:00'),
(2, 2, 12, '2025-11-15 06:30:00'),
(3, 3, 8, '2025-11-16 03:00:00'),
(4, 4, 4, '2025-11-16 07:30:00'),
(8, 2, 5, '2025-11-28 00:36:38'),
(9, 2, 6, '2025-11-28 00:36:41'),
(11, 8, 4, '2025-11-28 06:42:20'),
(12, 8, 1, '2025-11-28 06:42:26'),
(19, 13, 11, '2025-11-30 01:06:31'),
(20, 13, 1, '2025-11-30 01:06:44'),
(22, 16, 1, '2025-11-30 01:24:15'),
(24, 2, 10, '2025-11-30 15:16:37'),
(25, 18, 7, '2025-12-01 04:41:09'),
(27, 18, 4, '2025-12-01 04:42:39'),
(28, 2, 1, '2025-12-01 05:34:35'),
(29, 2, 4, '2025-12-01 05:35:20'),
(30, 8, 8, '2025-12-02 02:56:05');

-- --------------------------------------------------------

--
-- Table structure for table `withdrawal_requests`
--

CREATE TABLE `withdrawal_requests` (
  `withdrawalID` int(11) NOT NULL,
  `shopID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `withdrawal_method` enum('cash','atm') NOT NULL DEFAULT 'cash',
  `atm_card_presented` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `withdrawal_requests`
--

INSERT INTO `withdrawal_requests` (`withdrawalID`, `shopID`, `amount`, `withdrawal_method`, `atm_card_presented`, `status`, `requested_at`, `processed_by`, `processed_at`, `rejection_reason`, `notes`) VALUES
(1, 1, 500.00, 'cash', NULL, 'pending', '2025-12-04 09:59:28', NULL, NULL, NULL, 'First withdrawal request'),
(2, 2, 300.00, 'atm', '21-11827', 'approved', '2025-12-04 09:59:28', 1, '2025-12-04 09:59:28', NULL, 'ATM withdrawal approved'),
(3, 3, 700.00, 'cash', NULL, 'rejected', '2025-12-04 09:59:28', 1, '2025-12-04 09:59:28', 'Insufficient balance', 'Rejected due to low funds');

--
-- Triggers `withdrawal_requests`
--
DELIMITER $$
CREATE TRIGGER `after_withdrawal_completed` AFTER UPDATE ON `withdrawal_requests` FOR EACH ROW BEGIN
    DECLARE current_balance DECIMAL(10,2);
    
    IF OLD.status != 'completed' AND NEW.status = 'completed' THEN
        SELECT balance INTO current_balance FROM shops WHERE shopID = NEW.shopID;
        
        UPDATE shops 
        SET balance = balance - NEW.amount, total_withdrawn = total_withdrawn + NEW.amount
        WHERE shopID = NEW.shopID;
        
        INSERT INTO seller_transactions 
        (shopID, transaction_type, amount, balance_before, balance_after, reference_type, reference_id, processed_by, notes)
        VALUES 
        (NEW.shopID, CONCAT('withdrawal_', NEW.withdrawal_method), -NEW.amount, current_balance, current_balance - NEW.amount, 'withdrawal', NEW.withdrawalID, NEW.processed_by, CONCAT('Withdrawal via ', UPPER(NEW.withdrawal_method)));
    END IF;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_deletion_requests`
--
ALTER TABLE `account_deletion_requests`
  ADD PRIMARY KEY (`requestID`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cartID`),
  ADD KEY `idx_cartID` (`cartID`),
  ADD KEY `idx_buyerID` (`buyerID`),
  ADD KEY `idx_productID` (`productID`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`categoryID`),
  ADD UNIQUE KEY `category` (`category`),
  ADD KEY `idx_categoryID` (`categoryID`);

--
-- Indexes for table `delivery_riders`
--
ALTER TABLE `delivery_riders`
  ADD PRIMARY KEY (`riderID`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD PRIMARY KEY (`logID`),
  ADD KEY `idx_productID` (`productID`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_change_type` (`change_type`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `idx_orderID` (`orderID`),
  ADD KEY `idx_buyerID` (`buyerID`),
  ADD KEY `idx_status` (`order_status`),
  ADD KEY `idx_date` (`order_date`),
  ADD KEY `idx_rider` (`assigned_rider_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`orderItemID`),
  ADD KEY `idx_orderItemID` (`orderItemID`),
  ADD KEY `idx_orderID` (`orderID`),
  ADD KEY `idx_productID` (`productID`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `idx_productID` (`productID`),
  ADD KEY `idx_sellerID` (`sellerID`),
  ADD KEY `idx_categoryID` (`categoryID`),
  ADD KEY `idx_available` (`is_available`),
  ADD KEY `idx_shopID` (`shopID`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`imageID`),
  ADD KEY `idx_productID` (`productID`),
  ADD KEY `idx_order` (`image_order`),
  ADD KEY `idx_is_main` (`is_main`);

--
-- Indexes for table `product_images_old`
--
ALTER TABLE `product_images_old`
  ADD PRIMARY KEY (`imageID`),
  ADD KEY `idx_imageID` (`imageID`),
  ADD KEY `idx_productID` (`productID`),
  ADD KEY `idx_primary` (`is_primary`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`reviewID`),
  ADD KEY `idx_reviewID` (`reviewID`),
  ADD KEY `idx_productID` (`productID`),
  ADD KEY `idx_buyerID` (`buyerID`),
  ADD KEY `idx_orderID` (`orderID`),
  ADD KEY `idx_rating` (`rating`);

--
-- Indexes for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD PRIMARY KEY (`applicationID`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_reviewed_by` (`reviewed_by`),
  ADD KEY `idx_status` (`application_status`);

--
-- Indexes for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD PRIMARY KEY (`sellerID`),
  ADD UNIQUE KEY `userID` (`userID`),
  ADD KEY `idx_sellerID` (`sellerID`),
  ADD KEY `idx_userID` (`userID`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_shopID` (`shopID`);

--
-- Indexes for table `seller_transactions`
--
ALTER TABLE `seller_transactions`
  ADD PRIMARY KEY (`transactionID`),
  ADD KEY `idx_shopID` (`shopID`),
  ADD KEY `idx_type` (`transaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `shops`
--
ALTER TABLE `shops`
  ADD PRIMARY KEY (`shopID`),
  ADD UNIQUE KEY `shop_slug` (`shop_slug`),
  ADD UNIQUE KEY `atm_card_number` (`atm_card_number`),
  ADD KEY `idx_sellerID` (`sellerID`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_balance` (`balance`);

--
-- Indexes for table `shop_followers`
--
ALTER TABLE `shop_followers`
  ADD PRIMARY KEY (`followerID`),
  ADD UNIQUE KEY `unique_follow` (`shopID`,`userID`),
  ADD KEY `idx_shopID` (`shopID`),
  ADD KEY `idx_userID` (`userID`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`settingID`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userID`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD PRIMARY KEY (`addressID`),
  ADD KEY `idx_userID` (`userID`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlistID`),
  ADD UNIQUE KEY `unique_wishlist` (`buyerID`,`productID`),
  ADD KEY `idx_wishlistID` (`wishlistID`),
  ADD KEY `idx_buyerID` (`buyerID`),
  ADD KEY `idx_productID` (`productID`);

--
-- Indexes for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD PRIMARY KEY (`withdrawalID`),
  ADD KEY `idx_shopID` (`shopID`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_requested_at` (`requested_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_deletion_requests`
--
ALTER TABLE `account_deletion_requests`
  MODIFY `requestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cartID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `delivery_riders`
--
ALTER TABLE `delivery_riders`
  MODIFY `riderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  MODIFY `logID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `orderItemID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `imageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `product_images_old`
--
ALTER TABLE `product_images_old`
  MODIFY `imageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `reviewID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `seller_applications`
--
ALTER TABLE `seller_applications`
  MODIFY `applicationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  MODIFY `sellerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `seller_transactions`
--
ALTER TABLE `seller_transactions`
  MODIFY `transactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `shops`
--
ALTER TABLE `shops`
  MODIFY `shopID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shop_followers`
--
ALTER TABLE `shop_followers`
  MODIFY `followerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `user_addresses`
--
ALTER TABLE `user_addresses`
  MODIFY `addressID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlistID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  MODIFY `withdrawalID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_deletion_requests`
--
ALTER TABLE `account_deletion_requests`
  ADD CONSTRAINT `account_deletion_requests_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`buyerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_logs`
--
ALTER TABLE `inventory_logs`
  ADD CONSTRAINT `inventory_logs_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`buyerID`) REFERENCES `users` (`userID`),
  ADD CONSTRAINT `orders_ibfk_rider` FOREIGN KEY (`assigned_rider_id`) REFERENCES `delivery_riders` (`riderID`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`sellerID`) REFERENCES `seller_profiles` (`sellerID`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`categoryID`) REFERENCES `categories` (`categoryID`),
  ADD CONSTRAINT `products_ibfk_3` FOREIGN KEY (`shopID`) REFERENCES `shops` (`shopID`) ON DELETE CASCADE;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `product_images_old`
--
ALTER TABLE `product_images_old`
  ADD CONSTRAINT `product_images_old_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`buyerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`) ON DELETE CASCADE;

--
-- Constraints for table `seller_applications`
--
ALTER TABLE `seller_applications`
  ADD CONSTRAINT `seller_applications_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`userID`) ON DELETE SET NULL;

--
-- Constraints for table `seller_profiles`
--
ALTER TABLE `seller_profiles`
  ADD CONSTRAINT `seller_profiles_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_profiles_ibfk_2` FOREIGN KEY (`shopID`) REFERENCES `shops` (`shopID`) ON DELETE SET NULL;

--
-- Constraints for table `seller_transactions`
--
ALTER TABLE `seller_transactions`
  ADD CONSTRAINT `seller_transactions_ibfk_1` FOREIGN KEY (`shopID`) REFERENCES `shops` (`shopID`) ON DELETE CASCADE;

--
-- Constraints for table `shop_followers`
--
ALTER TABLE `shop_followers`
  ADD CONSTRAINT `shop_followers_ibfk_1` FOREIGN KEY (`shopID`) REFERENCES `shops` (`shopID`) ON DELETE CASCADE,
  ADD CONSTRAINT `shop_followers_ibfk_2` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `user_addresses`
--
ALTER TABLE `user_addresses`
  ADD CONSTRAINT `user_addresses_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`buyerID`) REFERENCES `users` (`userID`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`productID`) REFERENCES `products` (`productID`) ON DELETE CASCADE;

--
-- Constraints for table `withdrawal_requests`
--
ALTER TABLE `withdrawal_requests`
  ADD CONSTRAINT `withdrawal_requests_ibfk_1` FOREIGN KEY (`shopID`) REFERENCES `shops` (`shopID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
