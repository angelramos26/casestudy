<?php
// stocksModalAuth.php
// Contains Stock In, Stock Out, and Stock Adjustment modals.
// Rendered once outside the data loop, wrapped in Admin role check.
// Requires: $conn (DB connection for product queries), $suppliers (MySQLi result).
?>

<?php if($_SESSION['roleName']==='Admin'): ?>

<!-- Stock In Modal -->
<div class="modal fade" id="stockInModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i>Stock In</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' ORDER BY productName"); while($p=$ps->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Quantity *</label><input type="number" name="qty" class="form-control" min="1" required></div>
        <div class="mb-3"><label class="form-label">Cost per Unit (₱)</label><input type="number" step="0.01" name="cost" class="form-control" min="0" value="0"></div>
        <div class="mb-3"><label class="form-label">Supplier</label>
          <select name="supplierID" class="form-select">
            <option value="">— No Supplier —</option>
            <?php $suppliers->data_seek(0); while($s=$suppliers->fetch_assoc()): ?>
            <option value="<?php echo $s['supplierID']; ?>"><?php echo htmlspecialchars($s['companyName']); ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockIn" class="btn btn-ev">Add Stock</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Stock Out Modal -->
<div class="modal fade" id="stockOutModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="bi bi-dash-circle me-1"></i>Stock Out</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps2=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' AND stock_quantity > 0 ORDER BY productName"); while($p=$ps2->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">Quantity *</label><input type="number" name="qty" class="form-control" min="1" required></div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Reason for stock out..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockOut" class="btn btn-danger">Remove Stock</button>
      </div>
    </form>
  </div></div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="adjustModal" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="../backend/stocksAuth.php">
        <?php csrf_field(); ?>
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="bi bi-sliders me-1"></i>Stock Adjustment</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Product *</label>
          <select name="productID" class="form-select" required>
            <option value="">— Select Product —</option>
            <?php $ps3=$conn->query("SELECT productID,productName,stock_quantity FROM product WHERE status='Active' ORDER BY productName"); while($p=$ps3->fetch_assoc()): ?>
            <option value="<?php echo $p['productID']; ?>"><?php echo htmlspecialchars($p['productName']); ?> (Stock: <?php echo $p['stock_quantity']; ?>)</option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="mb-3"><label class="form-label">New Stock Quantity *</label><input type="number" name="qty" class="form-control" min="0" required></div>
        <div class="mb-3"><label class="form-label">Reason *</label><textarea name="notes" class="form-control" rows="2" required placeholder="Reason for adjustment..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="stockAdjust" class="btn btn-ev">Apply Adjustment</button>
      </div>
    </form>
  </div></div>
</div>

<?php endif; ?>
