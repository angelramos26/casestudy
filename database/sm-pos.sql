-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: May 17, 2026 at 05:53 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sm-pos`
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
  -- trg_after_credit_insert_update_balance handles credit_balance on INSERT
  INSERT INTO customer_credit (customerID, amount, type, notes, userID)
  VALUES (p_customerID, p_amount, 'DEBIT', p_notes, p_userID);
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddCustomer` (IN `p_customerName` VARCHAR(100), IN `p_contactNo` VARCHAR(20), IN `p_email` VARCHAR(80), IN `p_address` VARCHAR(255))   BEGIN
  IF p_contactNo != '' AND EXISTS (
      SELECT 1 FROM customer
      WHERE contactNo = p_contactNo AND dateDeleted IS NULL
  ) THEN
    SELECT 'duplicate_contact' AS result;
  ELSEIF p_email != '' AND EXISTS (
      SELECT 1 FROM customer
      WHERE email = p_email AND dateDeleted IS NULL
  ) THEN
    SELECT 'duplicate_email' AS result;
  ELSE
    INSERT INTO customer (customerName, contactNo, email, address)
    VALUES (p_customerName, p_contactNo, p_email, p_address);
    SELECT LAST_INSERT_ID() AS customerID, 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddDiningTable` (IN `p_tableNo` VARCHAR(10), IN `p_capacity` INT, IN `p_section` VARCHAR(50))   BEGIN
  IF EXISTS (SELECT 1 FROM dining_table WHERE tableNo = p_tableNo) THEN
    SELECT 'duplicate_tableNo' AS result;
  ELSE
    INSERT INTO dining_table (tableNo, capacity, section, status)
    VALUES (p_tableNo, p_capacity, p_section, 'Available');
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddExpense` (IN `p_expenseCategoryID` INT, IN `p_amount` DECIMAL(10,2), IN `p_description` VARCHAR(255), IN `p_expense_date` DATE, IN `p_userID` INT)   BEGIN
  INSERT INTO expense (expenseCategoryID, amount, description, expense_date, userID)
  VALUES (p_expenseCategoryID, p_amount, p_description, p_expense_date, p_userID);
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddMenuItem` (IN `p_itemName` VARCHAR(100), IN `p_categoryID` INT, IN `p_price` DECIMAL(10,2), IN `p_cost` DECIMAL(10,2), IN `p_description` TEXT, IN `p_is_available` TINYINT, IN `p_status` VARCHAR(10))   BEGIN
  INSERT INTO menu_item (itemName, categoryID, price, cost, description, is_available, status)
  VALUES (p_itemName, p_categoryID, p_price, p_cost, p_description, p_is_available, p_status);
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `AddOrderItem` (IN `p_orderID` INT, IN `p_itemID` INT, IN `p_quantity` INT, IN `p_price` DECIMAL(10,2), IN `p_notes` VARCHAR(255))   BEGIN
  DECLARE v_existing INT;
  SELECT COUNT(*) INTO v_existing FROM order_items
  WHERE orderID=p_orderID AND itemID=p_itemID AND item_status='Pending';

  IF v_existing > 0 THEN
    -- Just bump qty
    UPDATE order_items SET quantity = quantity + p_quantity
    WHERE orderID=p_orderID AND itemID=p_itemID AND item_status='Pending';
  ELSE
    INSERT INTO order_items (orderID, itemID, quantity, price, notes, item_status)
    VALUES (p_orderID, p_itemID, p_quantity, p_price, p_notes, 'Pending');
  END IF;

  -- Update order total
  UPDATE orders SET total_amount = (
    SELECT COALESCE(SUM(price * quantity),0) FROM order_items WHERE orderID=p_orderID
  ) WHERE orderID=p_orderID;

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `CancelOrder` (IN `p_orderID` INT, IN `p_reason` VARCHAR(255), IN `p_userID` INT)   BEGIN
  DECLARE v_tableID INT;
  SELECT tableID INTO v_tableID FROM orders WHERE orderID=p_orderID;

  UPDATE orders SET status='Cancelled', cancel_reason=p_reason, cancelled_by=p_userID,
         dateCancelled=NOW() WHERE orderID=p_orderID;

  IF v_tableID IS NOT NULL THEN
    UPDATE dining_table SET status='Available' WHERE tableID=v_tableID;
  END IF;

  SELECT 'success' AS result;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteDiningTable` (IN `p_tableID` INT)   BEGIN
  DELETE FROM dining_table WHERE tableID = p_tableID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `DeleteMenuItem` (IN `p_itemID` INT)   BEGIN
  UPDATE menu_item SET status='Inactive' WHERE itemID = p_itemID;
  SELECT 'success' AS result;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `MarkItemReady` (IN `p_orderItemID` INT)   BEGIN
  UPDATE order_items SET item_status='Ready' WHERE orderItemID=p_orderItemID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `MarkItemServed` (IN `p_orderItemID` INT)   BEGIN
  UPDATE order_items SET item_status='Served' WHERE orderItemID=p_orderItemID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `OpenOrder` (IN `p_tableID` INT, IN `p_orderType` VARCHAR(20), IN `p_pax` INT, IN `p_customerID` INT, IN `p_userID` INT, IN `p_notes` VARCHAR(255))   BEGIN
  DECLARE v_orderID INT;

  -- Mark table as Occupied if dine-in
  IF p_tableID IS NOT NULL THEN
    UPDATE dining_table SET status='Occupied' WHERE tableID = p_tableID;
  END IF;

  INSERT INTO orders (tableID, orderType, pax, customerID, userID, notes, status)
  VALUES (p_tableID, p_orderType, p_pax, p_customerID, p_userID, p_notes, 'Open');

  SET v_orderID = LAST_INSERT_ID();
  SELECT v_orderID AS orderID, 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `PayCredit` (IN `p_customerID` INT, IN `p_amount` DECIMAL(10,2), IN `p_notes` VARCHAR(255), IN `p_userID` INT)   BEGIN
  DECLARE v_bal DECIMAL(10,2);
  SELECT credit_balance INTO v_bal FROM customer WHERE customerID = p_customerID;
  IF v_bal < p_amount THEN
    SELECT 'overpayment' AS result;
  ELSE
    -- trg_after_credit_insert_update_balance handles credit_balance on INSERT
    INSERT INTO customer_credit (customerID, amount, type, notes, userID)
    VALUES (p_customerID, p_amount, 'CREDIT', p_notes, p_userID);
    SELECT 'success' AS result;
  END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcessSale` (IN `p_userID` INT, IN `p_customerID` INT, IN `p_total_amount` DECIMAL(10,2), IN `p_discount_amount` DECIMAL(10,2), IN `p_tax_amount` DECIMAL(10,2), IN `p_payment` DECIMAL(10,2), IN `p_change_amount` DECIMAL(10,2), IN `p_payment_method` VARCHAR(20))   BEGIN
  INSERT INTO sales (userID, customerID, total_amount, discount_amount, tax_amount, payment, change_amount, payment_method)
  VALUES (p_userID, p_customerID, p_total_amount, p_discount_amount, p_tax_amount, p_payment, p_change_amount, p_payment_method);
  SELECT LAST_INSERT_ID() AS salesID, 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SendToKitchen` (IN `p_orderID` INT, IN `p_userID` INT)   BEGIN
  UPDATE order_items SET item_status='Sent'
  WHERE orderID=p_orderID AND item_status='Pending';

  UPDATE orders SET status='Active', dateSent=NOW()
  WHERE orderID=p_orderID AND status='Open';

  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `SettleOrder` (IN `p_orderID` INT, IN `p_userID` INT, IN `p_subtotal` DECIMAL(10,2), IN `p_discount_type` VARCHAR(20), IN `p_discount_amount` DECIMAL(10,2), IN `p_tax_amount` DECIMAL(10,2), IN `p_service_charge` DECIMAL(10,2), IN `p_total_amount` DECIMAL(10,2), IN `p_payment_method` VARCHAR(20), IN `p_payment` DECIMAL(10,2), IN `p_change_amount` DECIMAL(10,2), IN `p_customerID` INT)   BEGIN
  DECLARE v_tableID INT;

  SELECT tableID INTO v_tableID FROM orders WHERE orderID=p_orderID;

  -- Update order as paid
  UPDATE orders SET
    status='Paid',
    customerID=IF(p_customerID>0, p_customerID, customerID),
    subtotal=p_subtotal,
    discount_type=p_discount_type,
    discount_amount=p_discount_amount,
    tax_amount=p_tax_amount,
    service_charge=p_service_charge,
    total_amount=p_total_amount,
    payment_method=p_payment_method,
    payment=p_payment,
    change_amount=p_change_amount,
    paid_by=p_userID,
    datePaid=NOW()
  WHERE orderID=p_orderID;

  -- Free up the table
  IF v_tableID IS NOT NULL THEN
    UPDATE dining_table SET status='Available' WHERE tableID=v_tableID;
  END IF;

  -- Auto-add credit balance if Credit payment
  IF p_payment_method='Credit' AND p_customerID > 0 THEN
    INSERT INTO customer_credit (customerID, amount, type, notes, userID)
    VALUES (p_customerID, p_total_amount, 'DEBIT', CONCAT('Utang from Order #',p_orderID), p_userID);
    UPDATE customer SET credit_balance = credit_balance + p_total_amount WHERE customerID=p_customerID;
  END IF;

  SELECT p_orderID AS orderID, 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `TransferTable` (IN `p_orderID` INT, IN `p_newTableID` INT, IN `p_userID` INT)   BEGIN
  DECLARE v_oldTableID INT;
  SELECT tableID INTO v_oldTableID FROM orders WHERE orderID=p_orderID;

  -- Free old table
  IF v_oldTableID IS NOT NULL THEN
    UPDATE dining_table SET status='Available' WHERE tableID=v_oldTableID;
  END IF;

  -- Occupy new table
  UPDATE dining_table SET status='Occupied' WHERE tableID=p_newTableID;
  UPDATE orders SET tableID=p_newTableID WHERE orderID=p_orderID;

  SELECT 'success' AS result;
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

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateDiningTable` (IN `p_tableID` INT, IN `p_tableNo` VARCHAR(10), IN `p_capacity` INT, IN `p_section` VARCHAR(50))   BEGIN
  UPDATE dining_table SET tableNo=p_tableNo, capacity=p_capacity, section=p_section
  WHERE tableID = p_tableID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateMenuItem` (IN `p_itemID` INT, IN `p_itemName` VARCHAR(100), IN `p_categoryID` INT, IN `p_price` DECIMAL(10,2), IN `p_cost` DECIMAL(10,2), IN `p_description` TEXT, IN `p_is_available` TINYINT, IN `p_status` VARCHAR(10))   BEGIN
  UPDATE menu_item
  SET itemName=p_itemName, categoryID=p_categoryID, price=p_price, cost=p_cost,
      description=p_description, is_available=p_is_available, status=p_status
  WHERE itemID = p_itemID;
  SELECT 'success' AS result;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `UpdateOrderItem` (IN `p_orderItemID` INT, IN `p_quantity` INT, IN `p_notes` VARCHAR(255))   BEGIN
  DECLARE v_orderID INT;
  SELECT orderID INTO v_orderID FROM order_items WHERE orderItemID=p_orderItemID;

  IF p_quantity <= 0 THEN
    DELETE FROM order_items WHERE orderItemID=p_orderItemID;
  ELSE
    UPDATE order_items SET quantity=p_quantity, notes=p_notes WHERE orderItemID=p_orderItemID;
  END IF;

  -- Recalc total
  UPDATE orders SET total_amount=(
    SELECT COALESCE(SUM(price*quantity),0) FROM order_items WHERE orderID=v_orderID
  ) WHERE orderID=v_orderID;

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

CREATE DEFINER=`root`@`localhost` PROCEDURE `VoidOrderItem` (IN `p_orderItemID` INT, IN `p_reason` VARCHAR(255), IN `p_userID` INT)   BEGIN
  DECLARE v_orderID INT;
  DECLARE v_itemID  INT;
  DECLARE v_qty     INT;
  DECLARE v_price   DECIMAL(10,2);

  SELECT orderID, itemID, quantity, price
  INTO v_orderID, v_itemID, v_qty, v_price
  FROM order_items WHERE orderItemID=p_orderItemID;

  UPDATE order_items SET item_status='Void', void_reason=p_reason, void_by=p_userID,
         void_at=NOW() WHERE orderItemID=p_orderItemID;

  -- Log void
  INSERT INTO void_log (orderID, orderItemID, itemID, quantity, price, reason, userID)
  VALUES (v_orderID, p_orderItemID, v_itemID, v_qty, v_price, p_reason, p_userID);

  -- Recalc total (voids excluded)
  UPDATE orders SET total_amount=(
    SELECT COALESCE(SUM(price*quantity),0) FROM order_items
    WHERE orderID=v_orderID AND item_status != 'Void'
  ) WHERE orderID=v_orderID;

  SELECT 'success' AS result;
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
(2, 'Lugaw Meals'),
(3, 'Pares Meals'),
(4, 'Silog Meals');

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
(1, 'Angelyca Ramos', '', '', '', 118.40, '2026-05-15 09:17:31', NULL),
(2, 'John Marlou', '09000000000', '', '', 435.30, '2026-05-15 16:25:19', NULL),
(3, 'Angelyca Ramos', '', '', '', 70.00, '2026-05-15 16:56:58', NULL),
(4, 'Angelyca Ramos', '09123456789', '', '', 0.00, '2026-05-16 06:10:04', NULL);

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
(2, 1, 50.00, 'CREDIT', '', 3, '2026-05-15 09:18:00'),
(3, 1, 42.00, 'DEBIT', 'Utang from Sale #6', 1, '2026-05-15 16:23:50'),
(4, 2, 20.00, 'DEBIT', 'Utang from Sale #7', 1, '2026-05-15 16:33:42'),
(5, 1, 99.00, 'CREDIT', '', 1, '2026-05-16 05:52:04'),
(6, 2, 105.00, 'DEBIT', 'Utang from Sale #10', 1, '2026-05-16 05:52:43'),
(7, 1, 55.20, 'DEBIT', 'Utang from Sale #12', 1, '2026-05-16 06:09:19'),
(8, 1, 36.00, 'DEBIT', 'Utang from Sale #13', 1, '2026-05-16 06:09:30'),
(9, 2, 33.30, 'DEBIT', 'Utang from Sale #14', 1, '2026-05-16 06:52:03'),
(10, 1, 35.00, 'DEBIT', 'Utang from Sale #15', 1, '2026-05-16 06:55:40'),
(11, 3, 70.00, 'DEBIT', 'Utang from Sale #16', 1, '2026-05-16 06:57:48'),
(12, 2, 52.00, 'DEBIT', 'Utang from Sale #17', 1, '2026-05-16 06:59:41'),
(13, 2, 100.00, 'DEBIT', 'Utang from Sale #18', 1, '2026-05-16 07:00:15');

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
-- Table structure for table `dining_table`
--

CREATE TABLE `dining_table` (
  `tableID` int(11) NOT NULL,
  `tableNo` varchar(10) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `section` varchar(50) DEFAULT 'Main',
  `status` enum('Available','Occupied','Reserved','Maintenance') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `menu_item`
--

CREATE TABLE `menu_item` (
  `itemID` int(11) NOT NULL,
  `itemName` varchar(100) NOT NULL,
  `categoryID` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `item_image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `orderItemID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `item_status` enum('Pending','Sent','Ready','Served','Void') NOT NULL DEFAULT 'Pending',
  `void_reason` varchar(255) DEFAULT NULL,
  `void_by` int(11) DEFAULT NULL,
  `void_at` datetime DEFAULT NULL,
  `dateAdded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `orderID` int(11) NOT NULL,
  `orderNo` varchar(20) DEFAULT NULL,
  `tableID` int(11) DEFAULT NULL,
  `orderType` enum('Dine-In','Takeout','Delivery') NOT NULL DEFAULT 'Dine-In',
  `pax` int(11) NOT NULL DEFAULT 1,
  `customerID` int(11) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('Open','Active','Paid','Cancelled') NOT NULL DEFAULT 'Open',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) DEFAULT 'None',
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `service_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(20) DEFAULT NULL,
  `payment` decimal(10,2) DEFAULT NULL,
  `change_amount` decimal(10,2) DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `cancelled_by` int(11) DEFAULT NULL,
  `dateCancelled` datetime DEFAULT NULL,
  `paid_by` int(11) DEFAULT NULL,
  `dateSent` datetime DEFAULT NULL,
  `datePaid` datetime DEFAULT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'Chippy Original 110g', '8888002001', 2, 30.00, 22.00, 56, 15, '2026-12-31', 'Active', 'uploads/products/prod_6a06eeb3c5229.jpg'),
(4, 'Nova Country Cheddar 78g', '8888002002', 2, 25.00, 18.00, 42, 15, '2026-12-31', 'Active', 'uploads/products/prod_6a070879c07cd.jpg'),
(5, '555 Sardines 155g', '8888003001', 3, 20.00, 25.00, 1302, 10, '2028-06-01', 'Active', 'uploads/products/prod_6a074165eb996.jpg'),
(6, 'Argentina Corned Beef 150g', '8888003002', 3, 55.00, 42.00, 31, 10, '2028-01-01', 'Active', 'uploads/products/prod_6a06e653c0d64.jpg'),
(7, 'Pantene Shampoo 12ml', '8888004001', 4, 12.00, 8.00, 144, 25, NULL, 'Active', 'uploads/products/prod_6a07089a55a12.jpeg'),
(8, 'Safeguard Bar Soap 55g', '8888004002', 4, 18.00, 13.00, 99, 20, NULL, 'Active', 'uploads/products/prod_6a0708c91625d.jpg'),
(14, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(15, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(16, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(17, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(18, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(19, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(20, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(21, 'Powder', '', 4, 120.00, 150.00, 20, 10, '2026-05-26', 'Active', NULL),
(22, '555 Sardines 155g', '', 3, 34.00, 37.00, 2, 10, '2026-05-16', 'Active', 'uploads/products/prod_6a0824774b441.jpg');

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
-- Dumping data for table `purchase_order`
--

INSERT INTO `purchase_order` (`poID`, `supplierID`, `userID`, `status`, `notes`, `dateCreated`, `dateReceived`) VALUES
(1, 2, 1, 'Received', '', '2026-05-15 16:21:39', '2026-05-16 07:10:07'),
(2, 1, 1, 'Pending', '', '2026-05-16 07:48:43', NULL),
(3, 1, 1, 'Received', '', '2026-05-16 07:49:05', '2026-05-16 07:49:19');

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

--
-- Dumping data for table `purchase_order_details`
--

INSERT INTO `purchase_order_details` (`podID`, `poID`, `productID`, `qty_ordered`, `qty_received`, `unit_cost`) VALUES
(1, 1, 5, 1, 0, 25.00),
(2, 2, 5, 1234, 0, 25.00),
(3, 3, 5, 1234, 0, 25.00);

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
(3, 'Owner', 'Read-only access to all reports and monitoring', '2026-05-15 09:12:52', NULL),
(4, 'Security', '', '2026-05-15 16:42:49', '2026-05-15');

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
(4, 4, NULL, 90.00, 0.00, 0.00, 1000.00, 910.00, 'Cash', '2026-05-15 09:49:15'),
(5, 1, NULL, 35.00, 0.00, 0.00, 100.00, 65.00, 'Cash', '2026-05-15 16:22:40'),
(6, 1, 1, 42.00, 0.00, 0.00, 0.00, 58.00, 'Credit', '2026-05-15 16:23:50'),
(7, 1, 2, 20.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-15 16:33:42'),
(8, 1, NULL, 70.00, 0.00, 0.00, 70.00, 0.00, 'Cash', '2026-05-15 16:41:42'),
(9, 1, NULL, 35.00, 0.00, 0.00, 50.00, 15.00, 'Cash', '2026-05-15 19:07:56'),
(10, 1, 2, 105.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 05:52:43'),
(11, 1, NULL, 20.00, 0.00, 0.00, 50.00, 30.00, 'Cash', '2026-05-16 05:53:50'),
(12, 1, 1, 55.20, 13.80, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:09:19'),
(13, 1, 1, 36.00, 9.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:09:30'),
(14, 1, 2, 33.30, 3.70, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:52:03'),
(15, 1, 1, 35.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:55:40'),
(16, 1, 3, 70.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:57:48'),
(17, 1, 2, 52.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 06:59:41'),
(18, 1, 2, 100.00, 0.00, 0.00, 0.00, 0.00, 'Credit', '2026-05-16 07:00:15'),
(19, 1, NULL, 35.00, 0.00, 0.00, 50.00, 15.00, 'Cash', '2026-05-16 07:03:44'),
(20, 1, NULL, 80.00, 0.00, 0.00, 80.00, 0.00, 'Cash', '2026-05-16 07:04:32');

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
(4, 1, 2, 1, 20.00, 20.00),
(8, 3, 1, 1, 25.00, 25.00),
(10, 3, 2, 1, 20.00, 20.00),
(16, 6, 7, 1, 12.00, 12.00),
(17, 6, 3, 1, 30.00, 30.00),
(20, 8, 3, 1, 30.00, 30.00),
(28, 12, 22, 1, 34.00, 34.00),
(29, 12, 5, 1, 20.00, 20.00),
(31, 13, 4, 1, 25.00, 25.00),
(32, 14, 4, 1, 25.00, 25.00),
(33, 14, 7, 1, 12.00, 12.00),
(39, 17, 7, 1, 12.00, 12.00),
(40, 17, 4, 1, 25.00, 25.00),
(41, 18, 5, 5, 20.00, 100.00),
(44, 20, 4, 1, 25.00, 25.00);

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
-- Dumping data for table `stocks`
--

INSERT INTO `stocks` (`stockID`, `productID`, `qty`, `cost`, `type`, `supplierID`, `userID`, `notes`, `dateAdded`) VALUES
(1, 5, 1, 25.00, 'IN', 2, 1, 'PO #1 received', '2026-05-16 07:10:07'),
(2, 5, 1234, 25.00, 'IN', 1, 1, 'PO #3 received', '2026-05-16 07:49:19');

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
(1, 1, 'EMP-0001', 'admin@7evelyn.com', '$2y$10$iNxHxWMJQh5vtClVicL2sOc8De6QKi3mNDYmkJdxrHcSZvPXCdr/a', 'Admin', '', 'User', '', 'Male', '1990-01-01', 'Single', '09000000000', 'uploads/profiles/user_1_6a07483697121.jpg', '2026-05-15 09:12:52', NULL),
(2, 3, 'EMP-0002', 'owner@gmail.com', '$2y$10$T6Wk59125D9DFacwFMoSOOwzMHxis0pUAqqLY5Ql4PqznHsgkmaAy', 'Owner', '', 'User', '', 'Female', '1985-06-15', 'Single', '09111111111', NULL, '2026-05-15 09:12:52', NULL),
(3, 2, 'EMP-0003', 'cashier@gmail.com', '$2y$10$2j.NDo6kSUKHefN/CF4iPu4R9XF3PJvuFhYT5.q7Y7s/lwkrcYroO', 'Cashier', '', 'User', '', 'Female', '1995-03-20', 'Single', '09222222222', NULL, '2026-05-15 09:12:52', NULL),
(4, 1, 'EMP-001', 'admin@gmail.com', '$2y$10$VNlg65JuT2RWqIW3wS8taeWTCX4ftdzQVGWjMqZhXGAenfwK/LAlS', 'John Marlou', '', 'Castillo', '', 'Male', '2026-05-15', 'Single', '', 'uploads/profiles/user_4_6a07092f40006.png', '2026-05-15 09:15:43', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `void_log`
--

CREATE TABLE `void_log` (
  `voidID` int(11) NOT NULL,
  `orderID` int(11) NOT NULL,
  `orderItemID` int(11) NOT NULL,
  `itemID` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `userID` int(11) NOT NULL,
  `dateCreated` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Trigger to auto-generate orderNo for orders
--
DELIMITER $$
CREATE TRIGGER `trg_orders_after_insert_orderno` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    IF NEW.orderNo IS NULL THEN
        UPDATE orders SET orderNo = CONCAT('ORD-', LPAD(NEW.orderID, 5, '0')) WHERE orderID = NEW.orderID;
    END IF;
END$$
DELIMITER ;


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
-- Indexes for table `dining_table`
--
ALTER TABLE `dining_table`
  ADD PRIMARY KEY (`tableID`),
  ADD UNIQUE KEY `tableNo` (`tableNo`);

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
-- Indexes for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD PRIMARY KEY (`itemID`),
  ADD KEY `fk_item_category` (`categoryID`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`orderItemID`),
  ADD KEY `fk_oi_order` (`orderID`),
  ADD KEY `fk_oi_item` (`itemID`);


--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderID`),
  ADD KEY `fk_order_table` (`tableID`),
  ADD KEY `fk_order_customer` (`customerID`),
  ADD KEY `fk_order_user` (`userID`);

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
-- Indexes for table `void_log`
--
ALTER TABLE `void_log`
  ADD PRIMARY KEY (`voidID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `category`
--
ALTER TABLE `category`
  MODIFY `categoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `customerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_credit`
--
ALTER TABLE `customer_credit`
  MODIFY `creditID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `dining_table`
--
ALTER TABLE `dining_table`
  MODIFY `tableID` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `menu_item`
--
ALTER TABLE `menu_item`
  MODIFY `itemID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `orderItemID` int(11) NOT NULL AUTO_INCREMENT;


--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `orderID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product`
--
ALTER TABLE `product`
  MODIFY `productID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `purchase_order`
--
ALTER TABLE `purchase_order`
  MODIFY `poID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `podID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `role`
--
ALTER TABLE `role`
  MODIFY `roleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `salesID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sales_details`
--
ALTER TABLE `sales_details`
  MODIFY `salesDetailsID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `stockID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- AUTO_INCREMENT for table `void_log`
--
ALTER TABLE `void_log`
  MODIFY `voidID` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `menu_item`
--
ALTER TABLE `menu_item`
  ADD CONSTRAINT `fk_item_category` FOREIGN KEY (`categoryID`) REFERENCES `category` (`categoryID`);

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

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_order_table` FOREIGN KEY (`tableID`) REFERENCES `dining_table` (`tableID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_customer` FOREIGN KEY (`customerID`) REFERENCES `customer` (`customerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);


--
-- Constraints for table `void_log`
--
ALTER TABLE `void_log`
  ADD CONSTRAINT `fk_void_order` FOREIGN KEY (`orderID`) REFERENCES `orders` (`orderID`),
  ADD CONSTRAINT `fk_void_user` FOREIGN KEY (`userID`) REFERENCES `users` (`userID`);


COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
