<?php
ob_start();
require_once 'database.php';
require_once 'pusher-broadcast.php';
require_once __DIR__ . '/csrf.php';
session_start();
csrf_verify(true);
ob_clean(); // discard any output from includes before we send JSON
header('Content-Type: application/json');

if(!isset($_SESSION['userID'])){ echo json_encode(['success'=>false,'message'=>'Not logged in']); exit(); }

if(isset($_POST['processSale'])){
    $cart           = json_decode($_POST['cart'], true);
    $total_amount   = floatval($_POST['total_amount']);
    $discount_amount= floatval($_POST['discount_amount']);
    $tax_amount     = floatval($_POST['tax_amount']);
    $payment        = floatval($_POST['payment']);
    $change_amount  = floatval($_POST['change_amount']);
    $payment_method = sanitize($_POST['payment_method']);
    $customerID     = intval($_POST['customerID']) ?: null;
    $userID         = $_SESSION['userID'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("CALL ProcessSale(?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iiidddds",
            $userID, $customerID,
            $total_amount, $discount_amount, $tax_amount,
            $payment, $change_amount,
            $payment_method
        );
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $salesID = intval($result['salesID']);
        $stmt->close();
        while($conn->more_results()) $conn->next_result();

        // Insert each item
        foreach($cart as $item){
            $productID = intval($item['id']);
            $qty       = intval($item['qty']);
            $price     = floatval($item['price']);
            $subtotal  = $price * $qty;
            $stmtDetail = $conn->prepare("CALL AddSaleDetail(?,?,?,?,?)");
            $stmtDetail->bind_param("iiidd", $salesID, $productID, $qty, $price, $subtotal);
            $stmtDetail->execute();
            $stmtDetail->get_result();
            $stmtDetail->close();
            while($conn->more_results()) $conn->next_result();
        }

        // If Credit (Utang), auto-record the credit balance for the customer
        if($payment_method === 'Credit' && $customerID){
            $notes = "Utang from Sale #$salesID";
            $stmtCredit = $conn->prepare("CALL AddCredit(?,?,?,?)");
            $stmtCredit->bind_param("idsi", $customerID, $total_amount, $notes, $userID);
            $stmtCredit->execute();
            $stmtCredit->get_result();
            $stmtCredit->close();
            while($conn->more_results()) $conn->next_result();
        }

        $conn->commit();

        // ── Pusher: broadcast to all open tabs/browsers ──────────────────────
        // Fetch updated stock for each sold item so POS cards refresh in real-time
        $updatedStock = [];
        foreach ($cart as $item) {
            $pid = intval($item['id']);
            $stockRow = $conn->query("SELECT stock_quantity FROM product WHERE productID = $pid");
            if ($stockRow && $r = $stockRow->fetch_assoc()) {
                $updatedStock[] = ['productID' => $pid, 'stock_quantity' => (int)$r['stock_quantity']];
            }
        }
        pusherBroadcast('sale-completed', [
            'salesID'      => $salesID,
            'total'        => $total_amount,
            'payment'      => $payment_method,
            'cashier'      => $_SESSION['userName'] ?? 'Unknown',
            'updatedStock' => $updatedStock,  // real-time stock update sa POS
        ]);
        // ────────────────────────────────────────────────────────────────────

        echo json_encode(['success'=>true,'salesID'=>$salesID]);
    } catch(Exception $e){
        $conn->rollback();
        echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
    }
    exit();
}
?>