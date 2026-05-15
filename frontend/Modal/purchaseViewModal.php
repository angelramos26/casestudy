<?php
// purchaseViewModal.php
// Renders the per-row View PO modal for each $po (purchase order row).
// Must be included inside the while($po = $pos->fetch_assoc()) loop.
// Requires: $po (current PO row), $conn (DB connection for items query).
?>

<!-- View PO Modal -->
<div class="modal fade" id="viewPOModal<?php echo $po['poID']; ?>" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
      <h5 class="modal-title">PO #<?php echo $po['poID']; ?> – <?php echo htmlspecialchars($po['companyName']); ?></h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <table class="table table-sm table-bordered">
        <thead class="table-light"><tr><th>Product</th><th>Qty Ordered</th><th>Unit Cost</th><th>Total</th></tr></thead>
        <tbody>
        <?php
        $items = $conn->query("SELECT pod.*, p.productName
                               FROM purchase_order_details pod
                               JOIN product p ON pod.productID=p.productID
                               WHERE pod.poID={$po['poID']}");
        $poTotal = 0;
        while($item=$items->fetch_assoc()):
            $sub = $item['qty_ordered'] * $item['unit_cost'];
            $poTotal += $sub;
        ?>
        <tr>
          <td><?php echo htmlspecialchars($item['productName']); ?></td>
          <td class="text-center"><?php echo $item['qty_ordered']; ?></td>
          <td class="text-end">₱<?php echo number_format($item['unit_cost'],2); ?></td>
          <td class="text-end">₱<?php echo number_format($sub,2); ?></td>
        </tr>
        <?php endwhile; ?>
        <tr class="table-light fw-bold">
          <td colspan="3" class="text-end">Total:</td>
          <td class="text-end">₱<?php echo number_format($poTotal,2); ?></td>
        </tr>
        </tbody>
      </table>
    </div>
  </div></div>
</div>
