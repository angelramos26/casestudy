-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: May 15, 2026 at 04:09 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `evelyn_pos`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `AddCategory` (IN `p_categoryName` VARCHAR(80))   BEGIN
  IF EXISTS (SELECT 1 FROM category WHERE categoryName = p_categoryName) THEN
    SELECT 'duplicate_name' AS result;
  ELSE
    INSERT INTO category (categoryName) VALUES (p_categoryName);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddCredit` (IN `p_customerID` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` VARCHAR(255), IN `p_userID` INT)   BEGIN
  INSERT INTO customer_credit (customerID, amount, type, notes, userID) VALUES (p_customerID, p_amount, 'DEBIT', p_notes, p_userID);
  UPDATE customer SET credit_balance = credit_balance + p_amount WHERE customerID = p_customerID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddCustomer` (IN `p_customerName` VARCHAR(100), IN `p_contactNo` VARCHAR(20), IN `p_email` VARCHAR(80), IN `p_address` VARCHAR(255))   BEGIN
  IF p_email != '' AND EXISTS (SELECT 1 FROM customer WHERE email = p_email AND dateDeleted IS NULL) THEN
    SELECT 'duplicate_email' AS result;
  ELSE
    INSERT INTO customer (customerName, contactNo, email, address) VALUES (p_customerName, p_contactNo, p_email, p_address);
    SELECT LAST_INSERT_ID() AS customerID, 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddExpense` (IN `p_expenseCategoryID` INT, IN `p_amount` DECIMAL(10,2), IN `p_description` VARCHAR(255), IN `p_expense_date` DATE, IN `p_userID` INT)   BEGIN
  INSERT INTO expense (expenseCategoryID, amount, description, expense_date, userID)
  VALUES (p_expenseCategoryID, p_amount, p_description, p_expense_date, p_userID);
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddProduct` (IN `p_productName` VARCHAR(100), IN `p_barcode` VARCHAR(50), IN `p_categoryID` INT, IN `p_price` DECIMAL(10,2), IN `p_cost` DECIMAL(10,2), IN `p_stock_quantity` INT, IN `p_reorder_level` INT, IN `p_expiry_date` DATE, IN `p_status` VARCHAR(10))   BEGIN
  IF EXISTS (SELECT 1 FROM product WHERE barcode = p_barcode AND p_barcode IS NOT NULL AND p_barcode != '') THEN
    SELECT 'duplicate_barcode' AS result;
  ELSE
    INSERT INTO product (productName, barcode, categoryID, price, cost, stock_quantity, reorder_level, expiry_date, status)
    VALUES (p_productName, p_barcode, p_categoryID, p_price, p_cost, p_stock_quantity, p_reorder_level, p_expiry_date, p_status);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddRole` (IN `p_roleName` VARCHAR(50), IN `p_roleDesc` VARCHAR(255))   BEGIN
  IF EXISTS (SELECT 1 FROM role WHERE roleName = p_roleName AND dateDeleted IS NULL) THEN
    SELECT 'duplicate_name' AS result;
  ELSE
    INSERT INTO role (roleName, roleDesc) VALUES (p_roleName, p_roleDesc);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddSaleDetail` (IN `p_salesID` INT, IN `p_productID` INT, IN `p_sold_quantity` INT, IN `p_price` DECIMAL(10,2), IN `p_subtotal` DECIMAL(10,2))   BEGIN
  INSERT INTO sales_details (salesID, productID, sold_quantity, price, subtotal)
  VALUES (p_salesID, p_productID, p_sold_quantity, p_price, p_subtotal);
  UPDATE product SET stock_quantity = stock_quantity - p_sold_quantity WHERE productID = p_productID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddStock` (IN `p_productID` INT, IN `p_qty` INT, IN `p_cost` DECIMAL(10,2), IN `p_supplierID` INT, IN `p_userID` INT, IN `p_notes` VARCHAR(255))   BEGIN
  INSERT INTO stocks (productID, qty, cost, supplierID, userID, type, notes)
  VALUES (p_productID, p_qty, p_cost, p_supplierID, p_userID, 'IN', p_notes);
  UPDATE product SET stock_quantity = stock_quantity + p_qty, cost = p_cost WHERE productID = p_productID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddSupplier` (IN `p_email` VARCHAR(80), IN `p_companyName` VARCHAR(100), IN `p_supplierName` VARCHAR(100), IN `p_contactNo` VARCHAR(20), IN `p_address` VARCHAR(255))   BEGIN
  IF EXISTS (SELECT 1 FROM supplier WHERE email = p_email AND dateDeleted IS NULL) THEN
    SELECT 'duplicate_email' AS result;
  ELSE
    INSERT INTO supplier (email, companyName, supplierName, contactNo, address) VALUES (p_email, p_companyName, p_supplierName, p_contactNo, p_address);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddUser` (IN `p_roleID` INT, IN `p_userNo` VARCHAR(20), IN `p_email` VARCHAR(80), IN `p_password` VARCHAR(128), IN `p_givenName` VARCHAR(50), IN `p_midName` VARCHAR(50), IN `p_surName` VARCHAR(50), IN `p_extName` VARCHAR(10), IN `p_gender` VARCHAR(10), IN `p_birthdate` DATE, IN `p_civilStatus` VARCHAR(20), IN `p_contactNo` VARCHAR(20))   BEGIN
  IF EXISTS (SELECT 1 FROM users WHERE email = p_email AND dateDeleted IS NULL) THEN
    SELECT 'duplicate_email' AS result;
  ELSEIF EXISTS (SELECT 1 FROM users WHERE userNo = p_userNo AND dateDeleted IS NULL) THEN
    SELECT 'duplicate_userNo' AS result;
  ELSE
    INSERT INTO users (roleID, userNo, email, password, givenName, midName, surName, extName, gender, birthdate, civilStatus, contactNo)
    VALUES (p_roleID, p_userNo, p_email, p_password, p_givenName, p_midName, p_surName, p_extName, p_gender, p_birthdate, p_civilStatus, p_contactNo);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AdjustStock` (IN `p_productID` INT, IN `p_qty` INT, IN `p_type` VARCHAR(10), IN `p_userID` INT, IN `p_notes` VARCHAR(255))   BEGIN
  DECLARE v_current INT;
  SELECT stock_quantity INTO v_current FROM product WHERE productID = p_productID;
  IF p_type = 'OUT' AND v_current < p_qty THEN
    SELECT 'insufficient' AS result;
  ELSE
    INSERT INTO stocks (productID, qty, cost, userID, type, notes)
    VALUES (p_productID, p_qty, 0, p_userID, p_type, p_notes);
    IF p_type = 'OUT' THEN
      UPDATE product SET stock_quantity = stock_quantity - p_qty WHERE productID = p_productID;
    ELSE
      UPDATE product SET stock_quantity = p_qty WHERE productID = p_productID;
    END IF;
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `CreatePurchaseOrder` (IN `p_supplierID` INT, IN `p_userID` INT, IN `p_notes` VARCHAR(255))   BEGIN
  INSERT INTO purchase_order (supplierID, userID, notes, status)
  VALUES (p_supplierID, p_userID, p_notes, 'Pending');
  SELECT LAST_INSERT_ID() AS poID, 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteCategory` (IN `p_categoryID` INT)   BEGIN
  DELETE FROM category WHERE categoryID = p_categoryID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteCustomer` (IN `p_customerID` INT, IN `p_dateDeleted` DATE)   BEGIN
  UPDATE customer SET dateDeleted = p_dateDeleted WHERE customerID = p_customerID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteProduct` (IN `p_productID` INT)   BEGIN
  UPDATE product SET status = 'Inactive' WHERE productID = p_productID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteRole` (IN `p_roleID` INT, IN `p_dateDeleted` DATE)   BEGIN
  UPDATE role SET dateDeleted = p_dateDeleted WHERE roleID = p_roleID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteSupplier` (IN `p_supplierID` INT, IN `p_dateDeleted` DATE)   BEGIN
  UPDATE supplier SET dateDeleted = p_dateDeleted WHERE supplierID = p_supplierID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteUser` (IN `p_userID` INT, IN `p_dateDeleted` DATE)   BEGIN
  UPDATE users SET dateDeleted = p_dateDeleted WHERE userID = p_userID;
  SELECT ROW_COUNT() AS affected;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `PayCredit` (IN `p_customerID` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` VARCHAR(255), IN `p_userID` INT)   BEGIN
  DECLARE v_bal DECIMAL(10,2);
  SELECT credit_balance INTO v_bal FROM customer WHERE customerID = p_customerID;
  IF v_bal < p_amount THEN
    SELECT 'overpayment' AS result;
  ELSE
    INSERT INTO customer_credit (customerID, amount, type, notes, userID) VALUES (p_customerID, p_amount, 'CREDIT', p_notes, p_userID);
    UPDATE customer SET credit_balance = credit_balance - p_amount WHERE customerID = p_customerID;
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcessSale` (IN `p_userID` INT, IN `p_customerID` INT, IN `p_total_amount` DECIMAL(10,2), IN `p_discount_amount` DECIMAL(10,2), IN `p_tax_amount` DECIMAL(10,2), IN `p_payment` DECIMAL(10,2), IN `p_change_amount` DECIMAL(10,2), IN `p_payment_method` VARCHAR(20))   BEGIN
  INSERT INTO sales (userID, customerID, total_amount, discount_amount, tax_amount, payment, change_amount, payment_method)
  VALUES (p_userID, p_customerID, p_total_amount, p_discount_amount, p_tax_amount, p_payment, p_change_amount, p_payment_method);
  SELECT LAST_INSERT_ID() AS salesID, 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCategory` (IN `p_categoryID` INT, IN `p_categoryName` VARCHAR(80))   BEGIN
  DECLARE v_exists INT;
  SELECT COUNT(*) INTO v_exists FROM category WHERE categoryName = p_categoryName AND categoryID != p_categoryID;
  IF v_exists > 0 THEN SELECT 'name_duplicate' AS result;
  ELSE
    UPDATE category SET categoryName = p_categoryName WHERE categoryID = p_categoryID;
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateCustomer` (IN `p_customerID` INT, IN `p_customerName` VARCHAR(100), IN `p_contactNo` VARCHAR(20), IN `p_email` VARCHAR(80), IN `p_address` VARCHAR(255))   BEGIN
  UPDATE customer SET customerName=p_customerName, contactNo=p_contactNo, email=p_email, address=p_address
  WHERE customerID = p_customerID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateProduct` (IN `p_productID` INT, IN `p_productName` VARCHAR(100), IN `p_barcode` VARCHAR(50), IN `p_categoryID` INT, IN `p_price` DECIMAL(10,2), IN `p_cost` DECIMAL(10,2), IN `p_reorder_level` INT, IN `p_expiry_date` DATE, IN `p_status` VARCHAR(10))   BEGIN
  UPDATE product SET productName=p_productName, barcode=p_barcode, categoryID=p_categoryID,
    price=p_price, cost=p_cost, reorder_level=p_reorder_level, expiry_date=p_expiry_date, status=p_status
  WHERE productID = p_productID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateRole` (IN `p_roleID` INT, IN `p_roleName` VARCHAR(50), IN `p_roleDesc` VARCHAR(255))   BEGIN
  DECLARE v_exists INT;
  SELECT COUNT(*) INTO v_exists FROM role WHERE roleName = p_roleName AND roleID != p_roleID AND dateDeleted IS NULL;
  IF v_exists > 0 THEN SELECT 'name_duplicate' AS result;
  ELSE
    UPDATE role SET roleName = p_roleName, roleDesc = p_roleDesc WHERE roleID = p_roleID;
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateSupplier` (IN `p_supplierID` INT, IN `p_email` VARCHAR(80), IN `p_companyName` VARCHAR(100), IN `p_supplierName` VARCHAR(100), IN `p_contactNo` VARCHAR(20), IN `p_address` VARCHAR(255))   BEGIN
  DECLARE v_exists INT;
  SELECT COUNT(*) INTO v_exists FROM supplier WHERE email = p_email AND supplierID != p_supplierID AND dateDeleted IS NULL;
  IF v_exists > 0 THEN SELECT 'email_duplicate' AS result;
  ELSE
    UPDATE supplier SET email=p_email, companyName=p_companyName, supplierName=p_supplierName, contactNo=p_contactNo, address=p_address
    WHERE supplierID = p_supplierID;
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateUser` (IN `p_userID` INT, IN `p_roleID` INT, IN `p_userNo` VARCHAR(20), IN `p_email` VARCHAR(80), IN `p_givenName` VARCHAR(50), IN `p_midName` VARCHAR(50), IN `p_surName` VARCHAR(50), IN `p_extName` VARCHAR(10), IN `p_gender` VARCHAR(10), IN `p_birthdate` DATE, IN `p_civilStatus` VARCHAR(20), IN `p_contactNo` VARCHAR(20))   BEGIN
  DECLARE v_emailDup INT;
  SELECT COUNT(*) INTO v_emailDup FROM users WHERE email = p_email AND userID != p_userID AND dateDeleted IS NULL;
  IF v_emailDup > 0 THEN SELECT 'email_duplicate' AS result;
  ELSE
    UPDATE users SET roleID=p_roleID, userNo=p_userNo, email=p_email, givenName=p_givenName,
      midName=p_midName, surName=p_surName, extName=p_extName, gender=p_gender,
      birthdate=p_birthdate, civilStatus=p_civilStatus, contactNo=p_contactNo
    WHERE userID = p_userID;
    SELECT 'success' AS result;
  END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `category`
--

CREATE TABLE `category` (
  `categoryID` int(11) NOT NULL,
  `categoryName` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `category`
--

INSERT INTO `category` (`categoryID`, `categoryName`) VALUES
(1, 'Beverages'),
(2, 'Snacks'),
(3, 'Canned Goods'),
(4, 'Personal Care'),
(5, 'Household'),
(6, 'Dairy'),
(7, 'Bread & Pastry'),
(8, 'Condiments');

-- --------------------------------------------------------

--
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `customerID` int(11) NOT NULL,
  `customerName` varchar(100) NOT NULL,
  `contactNo` varchar(20) DEFAULT NULL,
  `email` varchar(80) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateDeleted` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`customerID`, `customerName`, `contactNo`, `email`, `address`, `credit_balance`, `dateCreated`, `dateDeleted`) VALUES
(1, 'Angelyca Ramos', '', '', '', 15.00, '2026-05-15 09:17:31', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_credit`
--

CREATE TABLE `customer_credit` (
  `creditID` int(11) NOT NULL,
  `customerID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('DEBIT','CREDIT') NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_credit`
--

INSERT INTO `customer_credit` (`creditID`, `customerID`, `amount`, `type`, `notes`, `userID`, `dateCreated`) VALUES
(1, 1, 65.00, 'DEBIT', 'Utang from Sale #3', 3, '2026-05-15 09:17:46'),
(2, 1, 50.00, 'CREDIT', '', 3, '2026-05-15 09:18:00');

--
-- Triggers `customer_credit`
--
DELIMITER $$
CREATE TRIGGER `trg_after_credit_insert_update_balance` AFTER INSERT ON `customer_credit` FOR EACH ROW BEGIN
    IF NEW.type = 'DEBIT' THEN
        UPDATE `customer`
        SET `credit_balance` = `credit_balance` + NEW.amount
        WHERE `customerID` = NEW.customerID;
    ELSEIF NEW.type = 'CREDIT' THEN
        UPDATE `customer`
        SET `credit_balance` = `credit_balance` - NEW.amount
        WHERE `customerID` = NEW.customerID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `expense`
--

CREATE TABLE `expense` (
  `expenseID` int(11) NOT NULL,
  `expenseCategoryID` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `expense_date` date NOT NULL,
  `userID` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_category`
--

CREATE TABLE `expense_category` (
  `expenseCategoryID` int(11) NOT NULL,
  `categoryName` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expense_category`
--

INSERT INTO `expense_category` (`expenseCategoryID`, `categoryName`) VALUES
(1, 'Utilities'),
(2, 'Rent'),
(3, 'Salaries'),
(4, 'Supplies'),
(5, 'Repairs & Maintenance'),
(6, 'Transportation'),
(7, 'Miscellaneous');

-- --------------------------------------------------------

--
-- Table structure for table `product`
--

CREATE TABLE `product` (
  `productID` int(11) NOT NULL,
  `productName` varchar(100) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `categoryID` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 10,
  `expiry_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `product_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product`
--

INSERT INTO `product` (`productID`, `productName`, `barcode`, `categoryID`, `price`, `cost`, `stock_quantity`, `reorder_level`, `expiry_date`, `status`, `product_image`) VALUES
(1, 'Coca-Cola 350ml', '8888001001', 1, 25.00, 18.00, 99, 20, '2027-01-01', 'Active', 'uploads/products/prod_6a0708533b4df.jpg'),
(2, 'Royal 350ml', '8888001002', 1, 20.00, 14.00, 78, 20, '2027-01-01', 'Active', 'uploads/products/prod_6a0708a7b18bd.jpeg'),
(3, 'Chippy Original 110g', '8888002001', 2, 30.00, 22.00, 60, 15, '2026-12-31', 'Active', 'uploads/products/prod_6a06eeb3c5229.jpg'),
(4, 'Nova Country Cheddar 78g', '8888002002', 2, 25.00, 18.00, 50, 15, '2026-12-31', 'Active', 'uploads/products/prod_6a070879c07cd.jpg'),
(5, '555 Sardines 155g', '8888003001', 3, 20.00, 14.00, 79, 10, '2028-06-01', 'Active', 'uploads/products/prod_6a06e624b6d15.jpg'),
(6, 'Argentina Corned Beef 150g', '8888003002', 3, 55.00, 42.00, 37, 10, '2028-01-01', 'Active', 'uploads/products/prod_6a06e653c0d64.jpg'),
(7, 'Pantene Shampoo 12ml', '8888004001', 4, 12.00, 8.00, 150, 25, NULL, 'Active', 'uploads/products/prod_6a07089a55a12.jpeg'),
(8, 'Safeguard Bar Soap 55g', '8888004002', 4, 18.00, 13.00, 99, 20, NULL, 'Active', 'uploads/products/prod_6a0708c91625d.jpg'),
(9, 'Tide Powder 55g', '8888005001', 5, 10.00, 7.00, 200, 30, NULL, 'Active', 'uploads/products/prod_6a0709145f331.jpeg'),
(10, 'Ariel Liquid 22ml', '8888005002', 5, 15.00, 11.00, 118, 25, NULL, 'Active', 'uploads/products/prod_6a06e667ae729.jpg'),
(11, 'Bear Brand 33g', '8888006001', 6, 20.00, 15.00, 87, 20, '2026-11-30', 'Active', 'uploads/products/prod_6a06eea9291f2.jpg'),
(12, 'Gardenia Bread 400g', '8888007001', 7, 65.00, 52.00, 5, 10, '2026-05-20', 'Active', 'uploads/products/prod_6a0708695b0c0.jpg'),
(13, 'Silver Swan Soy Sauce 1L', '8888008001', 8, 45.00, 35.00, 30, 10, '2027-06-01', 'Active', 'uploads/products/prod_6a0708d963075.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order`
--

CREATE TABLE `purchase_order` (
  `poID` int(11) NOT NULL,
  `supplierID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `status` enum('Pending','Received','Cancelled') NOT NULL DEFAULT 'Pending',
  `notes` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateReceived` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `purchase_order`
--
DELIMITER $$
CREATE TRIGGER `trg_before_purchase_order_cancel` BEFORE UPDATE ON `purchase_order` FOR EACH ROW BEGIN
    IF OLD.status = 'Received' AND NEW.status = 'Cancelled' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot cancel a purchase order that has already been received.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

CREATE TABLE `purchase_order_details` (
  `podID` int(11) NOT NULL,
  `poID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `qty_ordered` int(11) NOT NULL,
  `qty_received` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `role`
--

CREATE TABLE `role` (
  `roleID` int(11) NOT NULL,
  `roleName` varchar(50) NOT NULL,
  `roleDesc` varchar(255) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateDeleted` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`roleID`, `roleName`, `roleDesc`, `dateCreated`, `dateDeleted`) VALUES
(1, 'Admin', 'Full system access including settings and reports', '2026-05-15 09:12:52', NULL),
(2, 'Cashier', 'POS, sales, and receipt generation only', '2026-05-15 09:12:52', NULL),
(3, 'Owner', 'Read-only access to all reports and monitoring', '2026-05-15 09:12:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `salesID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `customerID` int(11) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment` decimal(10,2) NOT NULL,
  `change_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Credit','GCash','Card') NOT NULL DEFAULT 'Cash',
  `saleDate` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`salesID`, `userID`, `customerID`, `total_amount`, `discount_amount`, `tax_amount`, `payment`, `change_amount`, `payment_method`, `saleDate`) VALUES
(1, 3, NULL, 113.00, 0.00, 0.00, 1000.00, 887.00, 'Cash', '2026-05-15 09:16:56'),
(2, 3, NULL, 90.00, 0.00, 0.00, 100.00, 10.00, 'Cash', '2026-05-15 09:17:14'),
(3, 3, 1, 65.00, 0.00, 0.00, 0.00, 935.00, 'Credit', '2026-05-15 09:17:46'),
(4, 4, NULL, 90.00, 0.00, 0.00, 1000.00, 910.00, 'Cash', '2026-05-15 09:49:15');

-- --------------------------------------------------------

--
-- Table structure for table `sales_details`
--

CREATE TABLE `sales_details` (
  `salesDetailsID` int(11) NOT NULL,
  `salesID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `sold_quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_details`
--

INSERT INTO `sales_details` (`salesDetailsID`, `salesID`, `productID`, `sold_quantity`, `price`, `subtotal`) VALUES
(1, 1, 5, 1, 20.00, 20.00),
(2, 1, 6, 1, 55.00, 55.00),
(3, 1, 8, 1, 18.00, 18.00),
(4, 1, 2, 1, 20.00, 20.00),
(5, 2, 6, 1, 55.00, 55.00),
(6, 2, 10, 1, 15.00, 15.00),
(7, 2, 11, 1, 20.00, 20.00),
(8, 3, 1, 1, 25.00, 25.00),
(9, 3, 11, 1, 20.00, 20.00),
(10, 3, 2, 1, 20.00, 20.00),
(11, 4, 11, 1, 20.00, 20.00),
(12, 4, 10, 1, 15.00, 15.00),
(13, 4, 6, 1, 55.00, 55.00);

--
-- Triggers `sales_details`
--
DELIMITER $$
CREATE TRIGGER `trg_after_sale_detail_insert` AFTER INSERT ON `sales_details` FOR EACH ROW BEGIN
    UPDATE `product`
    SET `stock_quantity` = `stock_quantity` - NEW.sold_quantity
    WHERE `productID` = NEW.productID;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_after_sale_detail_insert_lowstock` AFTER INSERT ON `sales_details` FOR EACH ROW BEGIN
    DECLARE v_qty INT;

    SELECT `stock_quantity` INTO v_qty
    FROM `product`
    WHERE `productID` = NEW.productID;

    IF v_qty <= 0 THEN
        UPDATE `product`
        SET `status` = 'Inactive'
        WHERE `productID` = NEW.productID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

CREATE TABLE `stocks` (
  `stockID` int(11) NOT NULL,
  `productID` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `type` enum('IN','OUT','ADJUST') NOT NULL DEFAULT 'IN',
  `supplierID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `dateAdded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `stocks`
--
DELIMITER $$
CREATE TRIGGER `trg_after_stock_in_update_product` AFTER INSERT ON `stocks` FOR EACH ROW BEGIN
    IF NEW.type = 'IN' AND NEW.cost > 0 THEN
        UPDATE `product`
        SET `cost` = NEW.cost
        WHERE `productID` = NEW.productID;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `supplier`
--

CREATE TABLE `supplier` (
  `supplierID` int(11) NOT NULL,
  `email` varchar(80) NOT NULL,
  `companyName` varchar(100) NOT NULL,
  `supplierName` varchar(100) NOT NULL,
  `contactNo` varchar(20) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateDeleted` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supplier`
--

INSERT INTO `supplier` (`supplierID`, `email`, `companyName`, `supplierName`, `contactNo`, `address`, `dateCreated`, `dateDeleted`) VALUES
(1, 'info@jti.com', 'Japan Tobacco International', 'Juan dela Cruz', '09111111111', 'Makati City, Metro Manila', '2026-05-15 09:12:52', NULL),
(2, 'sales@coca-cola.com', 'Coca-Cola Beverages Phils.', 'Maria Santos', '09222222222', 'Naga City, Camarines Sur', '2026-05-15 09:12:52', NULL),
(3, 'orders@nestleph.com', 'Nestle Philippines Inc.', 'Pedro Reyes', '09333333333', 'Meycauayan, Bulacan', '2026-05-15 09:12:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `settingID` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`settingID`, `setting_key`, `setting_value`) VALUES
(1, 'store_name', '7Evelyn Store'),
(2, 'store_address', '123 Rizal Street, Brgy. Evelyn, Philippines'),
(3, 'store_contact', '09XX-XXX-XXXX'),
(4, 'store_tin', '000-000-000-000'),
(5, 'tax_rate', '12'),
(6, 'tax_enabled', '0'),
(7, 'discount_senior', '20'),
(8, 'discount_pwd', '20'),
(9, 'receipt_footer', 'Thank you for shopping at 7Evelyn!'),
(10, 'currency_symbol', '₱'),
(11, 'low_stock_threshold', '10'),
(12, 'expiry_alert_days', '30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userID` int(11) NOT NULL,
  `roleID` int(11) NOT NULL,
  `userNo` varchar(20) NOT NULL,
  `email` varchar(80) NOT NULL,
  `password` varchar(128) NOT NULL,
  `givenName` varchar(50) NOT NULL,
  `midName` varchar(50) DEFAULT NULL,
  `surName` varchar(50) NOT NULL,
  `extName` varchar(10) DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `birthdate` date NOT NULL,
  `civilStatus` enum('Single','Married','Widowed','Separated') NOT NULL,
  `contactNo` varchar(20) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `dateDeleted` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userID`, `roleID`, `userNo`, `email`, `password`, `givenName`, `midName`, `surName`, `extName`, `gender`, `birthdate`, `civilStatus`, `contactNo`, `profile_image`, `dateCreated`, `dateDeleted`) VALUES
(1, 1, 'EMP-0001', 'admin@7evelyn.com', '$2y$10$iNxHxWMJQh5vtClVicL2sOc8De6QKi3mNDYmkJdxrHcSZvPXCdr/a', 'Admin', NULL, 'User', NULL, 'Male', '1990-01-01', 'Single', '09000000000', NULL, '2026-05-15 09:12:52', NULL),
(2, 3, 'EMP-0002', 'owner@gmail.com', '$2y$10$T6Wk59125D9DFacwFMoSOOwzMHxis0pUAqqLY5Ql4PqznHsgkmaAy', 'Owner', '', 'User', '', 'Female', '1985-06-15', 'Single', '09111111111', NULL, '2026-05-15 09:12:52', NULL),
(3, 2, 'EMP-0003', 'cashier@gmail.com', '$2y$10$2j.NDo6kSUKHefN/CF4iPu4R9XF3PJvuFhYT5.q7Y7s/lwkrcYroO', 'Cashier', '', 'User', '', 'Female', '1995-03-20', 'Single', '09222222222', NULL, '2026-05-15 09:12:52', NULL),
(4, 1, 'EMP-001', 'admin@gmail.com', '$2y$10$VNlg65JuT2RWqIW3wS8taeWTCX4ftdzQVGWjMqZhXGAenfwK/LAlS', 'John Marlou', '', 'Castillo', '', 'Male', '2026-05-15', 'Single', '', 'uploads/profiles/user_4_6a07092f40006.png', '2026-05-15 09:15:43', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`categoryID`);

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customerID`);

--
-- Indexes for table `customer_credit`
--
ALTER TABLE `customer_credit`
  ADD PRIMARY KEY (`creditID`),
  ADD KEY `customerID` (`customerID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `expense`
--
ALTER TABLE `expense`
  ADD PRIMARY KEY (`expenseID`),
  ADD KEY `expenseCategoryID` (`expenseCategoryID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `expense_category`
--
ALTER TABLE `expense_category`
  ADD PRIMARY KEY (`expenseCategoryID`);

--
-- Indexes for table `product`
--
ALTER TABLE `product`
  ADD PRIMARY KEY (`productID`),
  ADD KEY `categoryID` (`categoryID`);

--
-- Indexes for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD PRIMARY KEY (`poID`),
  ADD KEY `supplierID` (`supplierID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`podID`),
  ADD KEY `poID` (`poID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `role`
--
ALTER TABLE `role`
  ADD PRIMARY KEY (`roleID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`salesID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `customerID` (`customerID`);

--
-- Indexes for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD PRIMARY KEY (`salesDetailsID`),
  ADD KEY `salesID` (`salesID`),
  ADD KEY `productID` (`productID`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`stockID`),
  ADD KEY `productID` (`productID`),
  ADD KEY `supplierID` (`supplierID`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `supplier`
--
ALTER TABLE `supplier`
  ADD PRIMARY KEY (`supplierID`);

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
  ADD KEY `roleID` (`roleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_credit`
--
ALTER TABLE `customer_credit`
  MODIFY `creditID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `expense`
--
ALTER TABLE `expense`
  MODIFY `expenseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_category`
--
ALTER TABLE `expense_category`
  MODIFY `expenseCategoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `purchase_order`
--
ALTER TABLE `purchase_order`
  MODIFY `poID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `podID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `roleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `salesID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_details`
--
ALTER TABLE `sales_details`
  MODIFY `salesDetailsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stockID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier`
--
ALTER TABLE `supplier`
  MODIFY `supplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `settingID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `customer_credit`
--
ALTER TABLE `customer_credit`
  ADD CONSTRAINT `cc_customer_fk` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`),
  ADD CONSTRAINT `cc_user_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `expense`
--
ALTER TABLE `expense`
  ADD CONSTRAINT `exp_cat_fk` FOREIGN KEY (`expenseCategoryID`) REFERENCES `expense_category` (`expenseCategoryID`),
  ADD CONSTRAINT `exp_user_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `product`
--
ALTER TABLE `product`
  ADD CONSTRAINT `product_category_fk` FOREIGN KEY (`categoryID`) REFERENCES `category` (`categoryID`);

--
-- Constraints for table `purchase_order`
--
ALTER TABLE `purchase_order`
  ADD CONSTRAINT `po_supplier_fk` FOREIGN KEY (`supplierID`) REFERENCES `supplier` (`supplierID`),
  ADD CONSTRAINT `po_user_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD CONSTRAINT `pod_po_fk` FOREIGN KEY (`poID`) REFERENCES `purchase_order` (`poID`),
  ADD CONSTRAINT `pod_product_fk` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_user_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `sales_details`
--
ALTER TABLE `sales_details`
  ADD CONSTRAINT `sd_product_fk` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`),
  ADD CONSTRAINT `sd_sales_fk` FOREIGN KEY (`salesID`) REFERENCES `sales` (`salesID`);

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_product_fk` FOREIGN KEY (`productID`) REFERENCES `product` (`productID`),
  ADD CONSTRAINT `stocks_user_fk` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_fk` FOREIGN KEY (`roleID`) REFERENCES `role` (`roleID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
