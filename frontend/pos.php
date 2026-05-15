<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "Point of Sale – 7Evelyn POS";

// Fetch all active products with category
$productsResult = $conn->query("
    SELECT p.productID, p.productName, p.barcode, p.price, p.stock_quantity, p.expiry_date,
           p.product_image, c.categoryName
    FROM product p
    JOIN category c ON p.categoryID = c.categoryID
    WHERE p.status = 'Active'
    ORDER BY p.productName ASC
");
$allProducts = [];
while($row = $productsResult->fetch_assoc()) $allProducts[] = $row;

// Fetch categories
$categoriesResult = $conn->query("SELECT DISTINCT c.categoryName FROM category c JOIN product p ON c.categoryID=p.categoryID WHERE p.status='Active' ORDER BY c.categoryName");
$categories = [];
while($row = $categoriesResult->fetch_assoc()) $categories[] = $row['categoryName'];

// Fetch customers
$customersResult = $conn->query("SELECT customerID, customerName, credit_balance FROM customer WHERE dateDeleted IS NULL ORDER BY customerName");
$customers = [];
while($row = $customersResult->fetch_assoc()) $customers[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* POS-specific overrides */
.pos-topbar {
    background: white;
    border-bottom: 1px solid #d6ede6;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}
.pos-body {
    display: flex;
    height: calc(100vh - 53px);
    overflow: hidden;
}
/* Product Panel */
.product-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--ev-bg);
}
.search-bar {
    padding: 14px 16px 8px;
    background: white;
    border-bottom: 1px solid #d6ede6;
}
.search-wrap {
    position: relative;
    max-width: 100%;
}
.search-wrap input {
    width: 100%;
    padding: 10px 16px 10px 44px;
    border-radius: 12px;
    border: 2px solid #b3ddd2;
    font-size: 14px;
    outline: none;
    transition: border-color .2s;
}
.search-wrap input:focus { border-color: var(--ev-purple); }
.search-wrap i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--ev-purple);
    font-size: 1.1rem;
}
.cat-pills {
    display: flex;
    gap: 6px;
    padding: 8px 16px 10px;
    overflow-x: auto;
    background: white;
    border-bottom: 1px solid #d6ede6;
    flex-shrink: 0;
}
.cat-pills::-webkit-scrollbar { height: 3px; }
.cat-pills::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
.cat-pill {
    white-space: nowrap;
    padding: 5px 14px;
    border-radius: 20px;
    border: 1.5px solid #b3ddd2;
    background: white;
    font-size: 12.5px;
    font-weight: 600;
    color: #555;
    cursor: pointer;
    transition: all .2s;
    flex-shrink: 0;
}
.cat-pill.active, .cat-pill:hover {
    background: var(--ev-gradient);
    color: white;
    border-color: transparent;
}
.product-grid {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 10px;
    align-content: start;
}
.product-grid::-webkit-scrollbar { width: 5px; }
.product-grid::-webkit-scrollbar-thumb { background: #ccc; border-radius: 5px; }
.prod-card {
    background: white;
    border-radius: 12px;
    padding: 14px 10px 12px;
    text-align: center;
    cursor: pointer;
    border: 2px solid transparent;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    position: relative;
}
.prod-card:hover { border-color: var(--ev-purple); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,129,97,0.12); }
.prod-card.out-of-stock { opacity: 0.5; cursor: not-allowed; }
.prod-card .prod-icon { font-size: 1.8rem; color: #ccc; }
.prod-card .prod-img {
    width: 72px; height: 72px;
    object-fit: cover;
    border-radius: 10px;
    border: 1.5px solid #c8e8de;
    flex-shrink: 0;
}
.prod-card .prod-name { font-size: 12.5px; font-weight: 600; color: #333; line-height: 1.3; }
.prod-card .prod-price { font-size: 14px; font-weight: 800; color: var(--ev-purple); }
.prod-card .prod-stock { font-size: 11px; color: #999; }
.prod-card .stock-badge {
    position: absolute;
    top: 6px;
    right: 6px;
    background: #fde8ea;
    color: #f01b2d;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 8px;
}

/* Cart Panel */
.cart-panel {
    width: 340px;
    flex-shrink: 0;
    background: white;
    border-left: 1px solid #d6ede6;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.cart-header {
    background: var(--ev-gradient);
    color: white;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.cart-header h6 { margin: 0; font-weight: 700; font-size: 1rem; }
.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 8px 12px;
}
.cart-items::-webkit-scrollbar { width: 4px; }
.cart-items::-webkit-scrollbar-thumb { background: #ddd; border-radius: 4px; }
.cart-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.cart-item-name { flex: 1; font-size: 12.5px; font-weight: 600; color: #333; }
.cart-item-price { font-size: 12px; color: #888; }
.qty-ctrl { display: flex; align-items: center; gap: 4px; }
.qty-btn {
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 1.5px solid var(--ev-purple);
    background: white;
    color: var(--ev-purple);
    font-size: 14px;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: all .15s;
}
.qty-btn:hover { background: var(--ev-gradient); color: white; border-color: transparent; }
.qty-val { font-size: 13px; font-weight: 700; min-width: 20px; text-align: center; }
.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #bbb;
    gap: 8px;
    padding: 20px;
}
.cart-empty i { font-size: 3rem; }

/* Cart Summary */
.cart-summary {
    border-top: 1px solid #d6ede6;
    padding: 12px 14px;
    flex-shrink: 0;
    background: #f0f7f5;
}
.summary-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    margin-bottom: 4px;
    color: #555;
}
.summary-row.total {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--ev-purple);
    margin-top: 6px;
    padding-top: 6px;
    border-top: 2px solid #d6ede6;
}
.summary-row.total span:last-child { font-size: 1.25rem; }
.cart-actions { padding: 10px 14px 14px; flex-shrink: 0; }
.btn-process {
    width: 100%;
    background: var(--ev-gradient);
    color: white;
    border: none;
    padding: 13px;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-process:hover { opacity: .88; transform: translateY(-1px); }
.btn-process:disabled { opacity: .4; cursor: not-allowed; transform: none; }
.form-select-sm, .form-control-sm { font-size: 13px; }

/* Receipt modal */
.receipt-box {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 14px 16px;
    max-width: 320px;
    margin: 0 auto;
}
.receipt-logo-wrap {
    text-align: center;
    margin-bottom: 6px;
}
.receipt-logo-wrap img {
    max-height: 48px;
    max-width: 120px;
    object-fit: contain;
}
.receipt-title {
    text-align: center;
    font-weight: 800;
    font-size: 0.95rem;
    letter-spacing: 2px;
    margin-bottom: 2px;
    color: #008161;
}
.receipt-sub { text-align: center; color: #888; font-size: 10px; margin-bottom: 8px; }
.receipt-divider { border-top: 1px dashed #ccc; margin: 6px 0; }
.receipt-total-row { display: flex; justify-content: space-between; padding: 1px 0; font-size: 12px; }
.receipt-grand { font-weight: 800; font-size: 0.9rem; border-top: 1px solid #333; margin-top: 2px; padding-top: 3px; }
.receipt-item-row { display: flex; justify-content: space-between; font-size: 11.5px; color: #444; padding: 1px 0; }
</style>

<!-- Topbar -->
<div class="pos-topbar no-print">
    <i class="bi bi-cart3" style="color:var(--ev-purple);font-size:1.3rem;"></i>
    <h5 style="margin:0;font-weight:700;color:#004a38;">Point of Sale</h5>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="text-muted small"><?php echo htmlspecialchars($_SESSION['userName']); ?></span>
        <span class="role-pill"><?php echo htmlspecialchars($_SESSION['roleName']); ?></span>
        <span class="badge bg-secondary"><?php echo date('M d, Y h:i A'); ?></span>
    </div>
</div>

<div class="pos-body">

    <!-- PRODUCT PANEL -->
    <div class="product-panel">
        <div class="search-bar">
            <div class="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="searchInput" placeholder="Search product by name or barcode..." autocomplete="off">
            </div>
        </div>
        <div class="cat-pills">
            <span class="cat-pill active" data-cat="All">All</span>
            <?php foreach($categories as $cat): ?>
            <span class="cat-pill" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></span>
            <?php endforeach; ?>
        </div>
        <div class="product-grid" id="productGrid">
            <?php foreach($allProducts as $p): ?>
            <div class="prod-card <?php echo $p['stock_quantity'] <= 0 ? 'out-of-stock' : ''; ?>"
                 data-id="<?php echo $p['productID']; ?>"
                 data-name="<?php echo htmlspecialchars($p['productName'], ENT_QUOTES); ?>"
                 data-price="<?php echo $p['price']; ?>"
                 data-stock="<?php echo $p['stock_quantity']; ?>"
                 data-cat="<?php echo htmlspecialchars($p['categoryName'], ENT_QUOTES); ?>"
                 data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? '', ENT_QUOTES); ?>"
                 onclick="addToCart(this)">
                <?php if($p['stock_quantity'] <= 0): ?>
                <span class="stock-badge">Out</span>
                <?php elseif($p['stock_quantity'] <= 10): ?>
                <span class="stock-badge" style="background:#fdecd8;color:#a8510a;">Low</span>
                <?php endif; ?>
                <?php if(!empty($p['product_image'])): ?>
                <img src="../<?php echo htmlspecialchars($p['product_image']); ?>" alt="<?php echo htmlspecialchars($p['productName'], ENT_QUOTES); ?>" class="prod-img">
                <?php else: ?>
                <i class="bi bi-cart prod-icon"></i>
                <?php endif; ?>
                <div class="prod-name"><?php echo htmlspecialchars($p['productName']); ?></div>
                <div class="prod-price">₱<?php echo number_format($p['price'], 2); ?></div>
                <div class="prod-stock">Stock: <?php echo $p['stock_quantity']; ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CART PANEL -->
    <div class="cart-panel">
        <div class="cart-header">
            <h6><i class="bi bi-receipt me-1"></i>Cart</h6>
            <button class="btn btn-sm btn-outline-light" onclick="clearCart()" title="Clear cart">
                <i class="bi bi-trash"></i>
            </button>
        </div>

        <!-- Customer -->
        <div style="padding:10px 12px 0;border-bottom:1px solid #eee;padding-bottom:10px;flex-shrink:0;">
            <select id="customerSelect" class="form-select form-select-sm">
                <option value="">— Walk-in Customer —</option>
                <?php foreach($customers as $c): ?>
                <option value="<?php echo $c['customerID']; ?>"><?php echo htmlspecialchars($c['customerName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Cart Items -->
        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <i class="bi bi-cart-x"></i>
                <span>Cart is empty</span>
                <small>Click a product to add</small>
            </div>
        </div>

        <!-- Summary -->
        <div class="cart-summary">
            <div class="summary-row"><span>Subtotal:</span><span id="subtotalDisplay">₱0.00</span></div>
            <div class="summary-row">
                <span>Discount:</span>
                <select id="discountSelect" class="form-select form-select-sm" style="width:120px;" onchange="recalc()">
                    <option value="0">None</option>
                    <option value="5">5%</option>
                    <option value="10">10%</option>
                    <option value="20">20%</option>
                    <option value="pwd">PWD/Senior (20%)</option>
                </select>
            </div>
            <div class="summary-row"><span>Discount Amt:</span><span id="discountDisplay" style="color:#f01b2d;">₱0.00</span></div>
            <div class="summary-row total"><span>TOTAL:</span><span id="totalDisplay">₱0.00</span></div>
            <div class="summary-row mt-2">
                <span>Payment:</span>
                <select id="paymentMethod" class="form-select form-select-sm" style="width:120px;" onchange="toggleCreditWarning()">
                    <option value="Cash">Cash</option>
                    <option value="GCash">GCash</option>
                    <option value="Card">Card</option>
                    <option value="Credit">Credit (Utang)</option>
                </select>
            </div>
            <div id="creditWarning" class="alert alert-warning py-1 px-2 mt-1 mb-0 small" style="display:none;">
                <i class="bi bi-exclamation-triangle me-1"></i>Requires a selected customer.
            </div>
            <div class="summary-row mt-1">
                <span>Amount Tendered (₱)</span>
            </div>
            <input type="number" id="tenderedInput" class="form-control form-control-sm mb-1" placeholder="0.00" min="0" step="0.01" oninput="recalc()">
            <div class="summary-row"><span>Change:</span><span id="changeDisplay" style="color:var(--ev-purple);font-weight:700;">₱0.00</span></div>
        </div>

        <div class="cart-actions">
            <button class="btn-process" id="processBtn" onclick="processSale()" disabled>
                <i class="bi bi-check2-circle"></i> Process Sale
            </button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--ev-gradient);color:#fff;">
                <h5 class="modal-title"><i class="bi bi-receipt me-1"></i>Sale Complete!</h5>
            </div>
            <div class="modal-body" id="receiptBody">
                <!-- Filled by JS -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-secondary" onclick="printReceipt()"><i class="bi bi-printer me-1"></i>Print</button>
                <button class="btn btn-ev" onclick="newSale()"><i class="bi bi-plus-circle me-1"></i>New Sale</button>
            </div>
        </div>
    </div>
</div>

<script>
// ── Data from PHP ──
const allProducts = <?php echo json_encode($allProducts); ?>;

let cart = []; // [{id, name, price, qty, stock}]

// ── Product Filter ──
document.getElementById('searchInput').addEventListener('input', filterProducts);
document.querySelectorAll('.cat-pill').forEach(p => {
    p.addEventListener('click', () => {
        document.querySelectorAll('.cat-pill').forEach(x => x.classList.remove('active'));
        p.classList.add('active');
        filterProducts();
    });
});

function filterProducts(){
    const q   = document.getElementById('searchInput').value.toLowerCase().trim();
    const cat = document.querySelector('.cat-pill.active').dataset.cat;
    document.querySelectorAll('.prod-card').forEach(card => {
        const matchCat  = cat === 'All' || card.dataset.cat === cat;
        const matchText = !q || card.dataset.name.toLowerCase().includes(q) || card.dataset.barcode.toLowerCase().includes(q);
        card.style.display = (matchCat && matchText) ? '' : 'none';
    });
}

// ── Cart ──
function addToCart(card){
    if(card.classList.contains('out-of-stock')) return;
    const id    = card.dataset.id;
    const name  = card.dataset.name;
    const price = parseFloat(card.dataset.price);
    const stock = parseInt(card.dataset.stock);
    const existing = cart.find(i => i.id === id);
    if(existing){
        if(existing.qty >= stock){ Swal.fire({icon:'warning',title:'Stock limit',text:`Only ${stock} in stock.`,timer:1500,showConfirmButton:false}); return; }
        existing.qty++;
    } else {
        cart.push({id, name, price, qty:1, stock});
    }
    renderCart();
}

function changeQty(id, delta){
    const item = cart.find(i => i.id === id);
    if(!item) return;
    item.qty += delta;
    if(item.qty <= 0) cart = cart.filter(i => i.id !== id);
    renderCart();
}

function clearCart(){
    if(!cart.length) return;
    Swal.fire({
        title:'Clear cart?',icon:'question',showCancelButton:true,
        confirmButtonText:'Yes, clear',confirmButtonColor:'#f01b2d'
    }).then(r => { if(r.isConfirmed){ cart=[]; renderCart(); } });
}

function renderCart(){
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('cartEmpty');
    if(!cart.length){
        container.innerHTML = '';
        container.appendChild(empty);
        document.getElementById('processBtn').disabled = true;
        recalc();
        return;
    }
    let html = '';
    cart.forEach(item => {
        html += `<div class="cart-item">
            <div>
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-price">₱${item.price.toFixed(2)} each</div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="qty-ctrl">
                    <button class="qty-btn" onclick="changeQty('${item.id}',-1)">−</button>
                    <span class="qty-val">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty('${item.id}',1)">+</button>
                </div>
                <div style="font-size:13px;font-weight:700;min-width:60px;text-align:right;">₱${(item.price*item.qty).toFixed(2)}</div>
            </div>
        </div>`;
    });
    container.innerHTML = html;
    document.getElementById('processBtn').disabled = false;
    recalc();
}

function recalc(){
    const subtotal = cart.reduce((s,i) => s + i.price*i.qty, 0);
    const discVal  = document.getElementById('discountSelect').value;
    const discPct  = (discVal === 'pwd') ? 20 : parseFloat(discVal);
    const discAmt  = subtotal * discPct / 100;
    const total    = subtotal - discAmt;
    const tendered = parseFloat(document.getElementById('tenderedInput').value) || 0;
    const change   = Math.max(0, tendered - total);

    document.getElementById('subtotalDisplay').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('discountDisplay').textContent = '₱' + discAmt.toFixed(2);
    document.getElementById('totalDisplay').textContent    = '₱' + total.toFixed(2);
    document.getElementById('changeDisplay').textContent   = '₱' + change.toFixed(2);
}

function toggleCreditWarning(){
    const method = document.getElementById('paymentMethod').value;
    document.getElementById('creditWarning').style.display = method === 'Credit' ? '' : 'none';
}

// ── Process Sale ──
function processSale(){
    if(!cart.length) return;

    const method     = document.getElementById('paymentMethod').value;
    const customerID = document.getElementById('customerSelect').value;
    const discVal    = document.getElementById('discountSelect').value;
    const discPct    = (discVal === 'pwd') ? 20 : parseFloat(discVal);
    const subtotal   = cart.reduce((s,i) => s + i.price*i.qty, 0);
    const discAmt    = subtotal * discPct / 100;
    const total      = subtotal - discAmt;
    const tendered   = parseFloat(document.getElementById('tenderedInput').value) || 0;
    const change     = Math.max(0, tendered - total);

    // Validations
    if(method === 'Credit' && !customerID){
        Swal.fire({icon:'warning',title:'Select a customer','text':'Credit/Utang requires a customer.'});
        return;
    }
    if(method !== 'Credit' && tendered < total){
        Swal.fire({icon:'warning',title:'Insufficient payment',text:`Amount tendered (₱${tendered.toFixed(2)}) is less than total (₱${total.toFixed(2)}).`});
        return;
    }

    // Disable button to prevent double-submit
    document.getElementById('processBtn').disabled = true;

    const formData = new FormData();
    formData.append('processSale', '1');
    formData.append('cart',           JSON.stringify(cart.map(i => ({id:i.id, qty:i.qty, price:i.price}))));
    formData.append('total_amount',   total.toFixed(2));
    formData.append('discount_amount',discAmt.toFixed(2));
    formData.append('tax_amount',     '0');
    formData.append('payment',        (method === 'Credit' ? 0 : tendered).toFixed(2));
    formData.append('change_amount',  change.toFixed(2));
    formData.append('payment_method', method);
    formData.append('customerID',     customerID || '0');
    formData.append('csrf_token',     CSRF_TOKEN);

    fetch('../backend/salesAuth.php', { method:'POST', body: formData })
    .then(res => res.text())
    .then(raw => {
        try {
            const data = JSON.parse(raw);
            if(data.success){
                showReceipt(data.salesID, total, discAmt, tendered, change, method, customerID);
            } else {
                document.getElementById('processBtn').disabled = false;
                Swal.fire({icon:'error',title:'Sale Failed',text: data.message || 'An error occurred.'});
            }
        } catch(e) {
            document.getElementById('processBtn').disabled = false;
            Swal.fire({icon:'error',title:'Server Error',html:'<pre style="font-size:11px;text-align:left;max-height:200px;overflow:auto;">'+raw+'</pre>'});
        }
    })
    .catch(err => {
        document.getElementById('processBtn').disabled = false;
        Swal.fire({icon:'error',title:'Network error',text: err.message || 'Could not reach the server.'});
    });
}

function showReceipt(salesID, total, discAmt, tendered, change, method, customerID){
    const customerName = customerID
        ? document.getElementById('customerSelect').options[document.getElementById('customerSelect').selectedIndex].text
        : 'Walk-in Customer';

    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `
        <div class="receipt-item-row">
            <span>${item.name} <span style="color:#999;">x${item.qty}</span></span>
            <span>₱${(item.price*item.qty).toFixed(2)}</span>
        </div>`;
    });

    const subtotal = cart.reduce((s,i) => s + i.price*i.qty, 0);

    // Logo from localStorage
    const storedLogo    = localStorage.getItem('ev_store_logo');
    const storeName     = localStorage.getItem('ev_store_name') || '7EVELYN POS';
    const storeTagline  = localStorage.getItem('ev_store_tagline') || '';
    const receiptFooter = localStorage.getItem('ev_receipt_footer') || 'Thank you for shopping!';
    const showLogo      = localStorage.getItem('ev_show_logo_receipt') !== 'false';
    const showTagline   = localStorage.getItem('ev_show_tagline') !== 'false';
    const logoHtml      = (storedLogo && showLogo)
        ? `<div class="receipt-logo-wrap"><img src="${storedLogo}" alt="logo"></div>`
        : '';
    const taglineHtml   = (storeTagline && showTagline)
        ? `<div class="receipt-sub" style="font-size:10px;">${storeTagline}</div>`
        : '';

    document.getElementById('receiptBody').innerHTML = `
    <div class="receipt-box">
        ${logoHtml}
        <div class="receipt-title">${storeName}</div>
        ${taglineHtml}
        <div class="receipt-sub">Official Receipt &nbsp;|&nbsp; #${salesID}</div>
        <div class="receipt-divider"></div>
        <div class="receipt-total-row"><span>Customer:</span><span>${customerName}</span></div>
        <div class="receipt-total-row"><span>Date:</span><span>${new Date().toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'})}</span></div>
        <div class="receipt-divider"></div>
        ${itemsHtml}
        <div class="receipt-divider"></div>
        <div class="receipt-total-row"><span>Subtotal:</span><span>₱${subtotal.toFixed(2)}</span></div>
        ${discAmt > 0 ? `<div class="receipt-total-row"><span>Discount:</span><span style="color:#f01b2d;">-₱${discAmt.toFixed(2)}</span></div>` : ''}
        <div class="receipt-total-row receipt-grand"><span>TOTAL:</span><span>₱${total.toFixed(2)}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-total-row"><span>Payment (${method}):</span><span>₱${method === 'Credit' ? '0.00' : tendered.toFixed(2)}</span></div>
        ${method !== 'Credit' ? `<div class="receipt-total-row"><span>Change:</span><span>₱${change.toFixed(2)}</span></div>` : ''}
        <div class="receipt-divider"></div>
        <div style="text-align:center;font-size:10px;color:#aaa;margin-top:4px;">${receiptFooter}</div>
    </div>`;

    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

function printReceipt(){
    const content = document.getElementById('receiptBody').innerHTML;
    const w = window.open('','_blank','width=360,height=600');
    w.document.write(`<html><head><title>Receipt</title><style>
        body{font-family:'Courier New',monospace;font-size:12px;padding:16px;max-width:300px;margin:0 auto;}
        .receipt-total-row,.receipt-item-row{display:flex;justify-content:space-between;padding:1px 0;}
        .receipt-title{text-align:center;font-weight:800;font-size:0.95rem;letter-spacing:2px;color:#008161;}
        .receipt-sub{text-align:center;color:#888;font-size:10px;}
        .receipt-divider{border-top:1px dashed #ccc;margin:6px 0;}
        .receipt-grand{font-weight:800;border-top:1px solid #333;padding-top:3px;}
        .receipt-logo-wrap{text-align:center;margin-bottom:6px;}
        .receipt-logo-wrap img{max-height:48px;max-width:120px;object-fit:contain;}
        .receipt-item-row{font-size:11.5px;color:#444;}
    </style></head><body>${content}</body></html>`);
    w.document.close();
    w.print();
}

function newSale(){
    bootstrap.Modal.getInstance(document.getElementById('receiptModal')).hide();
    cart = [];
    document.getElementById('customerSelect').value = '';
    document.getElementById('discountSelect').value = '0';
    document.getElementById('paymentMethod').value = 'Cash';
    document.getElementById('tenderedInput').value = '';
    document.getElementById('creditWarning').style.display = 'none';
    // Stock counts are now updated in real-time via Pusher (see realtime.js)
    // location.reload() removed — no full-page reload needed anymore
}

// Quick tendered buttons helper
document.getElementById('tenderedInput').addEventListener('keydown', e => {
    if(e.key === 'Enter') processSale();
});
</script>

<!-- ── Pusher Real-time ──────────────────────────────────────────────────── -->
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>
<!-- ─────────────────────────────────────────────────────────────────────── -->

</div></div>
</body>
</html>