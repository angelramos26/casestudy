<?php
// purchaseCreateModal.php
// The "Create Purchase Order" modal rendered once outside the data loop.
// Role check is handled by the caller (purchase.php wraps include in if-Admin block).
/** @var mysqli_result $suppliers  Injected by purchase.php */
/** @var mysqli_result $products   Injected by purchase.php */
?>

<!-- Create PO Modal -->
<div class="modal fade" id="createPOModal" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="../backend/purchaseAuth.php" id="poForm">
        <?php csrf_field(); ?>
      <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
        <h5 class="modal-title"><i class="bi bi-bag-check me-1"></i>Create Purchase Order</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-2 mb-3">
          <div class="col-md-8">
            <label class="form-label fw-semibold small">Supplier <span class="text-danger">*</span></label>
            <select name="supplierID" class="form-select" required>
              <option value="">— Select Supplier —</option>
              <?php $suppliers->data_seek(0); while($s=$suppliers->fetch_assoc()): ?>
              <option value="<?php echo $s['supplierID']; ?>"><?php echo htmlspecialchars($s['companyName']); ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold small">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
          </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-2">
          <strong class="small">Order Items</strong>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addPORow()"><i class="bi bi-plus"></i> Add Row</button>
        </div>
        <div id="poItems">
          <div class="row g-2 mb-2 po-row">
            <div class="col-6">
              <select name="productID[]" class="form-select form-select-sm" required onchange="fillCost(this)">
                <option value="">— Select Product —</option>
                <?php $products->data_seek(0); while($p=$products->fetch_assoc()): ?>
                <option value="<?php echo $p['productID']; ?>" data-cost="<?php echo $p['cost']; ?>"><?php echo htmlspecialchars($p['productName']); ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1" required></div>
            <div class="col-3"><input type="number" name="unit_cost[]" class="form-control form-control-sm" placeholder="Unit Cost" step="0.01" min="0" required></div>
            <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.po-row').remove()"><i class="bi bi-trash"></i></button></div>
          </div>
        </div>
        <div class="text-muted small mt-1">Unit cost auto-fills from product cost. You may override.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="createPO" class="btn btn-ev">Create PO</button>
      </div>
    </form>
  </div></div>
</div>

<script>
const productOptions = `<?php
    $products->data_seek(0);
    $opts = '';
    while($p=$products->fetch_assoc()){
        $opts .= "<option value='{$p['productID']}' data-cost='{$p['cost']}'>" . addslashes(htmlspecialchars($p['productName'])) . "</option>";
    }
    echo addslashes($opts);
?>`;

function addPORow(){
    const row = `<div class="row g-2 mb-2 po-row">
      <div class="col-6"><select name="productID[]" class="form-select form-select-sm" required onchange="fillCost(this)"><option value="">— Select Product —</option>${productOptions}</select></div>
      <div class="col-2"><input type="number" name="qty[]" class="form-control form-control-sm" placeholder="Qty" min="1" required></div>
      <div class="col-3"><input type="number" name="unit_cost[]" class="form-control form-control-sm" placeholder="Unit Cost" step="0.01" min="0" required></div>
      <div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.po-row').remove()"><i class="bi bi-trash"></i></button></div>
    </div>`;
    document.getElementById('poItems').insertAdjacentHTML('beforeend', row);
}

function fillCost(sel){
    const opt = sel.options[sel.selectedIndex];
    const cost = opt.dataset.cost;
    const row = sel.closest('.po-row');
    if(cost) row.querySelector('input[name="unit_cost[]"]').value = cost;
}

// Auto-fill cost on first row product change
document.querySelector('#poItems select').addEventListener('change', function(){ fillCost(this); });
</script>