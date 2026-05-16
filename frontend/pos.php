<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
session_start();
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }

$pageTitle = "Point of Sale – 7Evelyn POS";

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

$categoriesResult = $conn->query("SELECT DISTINCT c.categoryName FROM category c JOIN product p ON c.categoryID=p.categoryID WHERE p.status='Active' ORDER BY c.categoryName");
$categories = [];
while($row = $categoriesResult->fetch_assoc()) $categories[] = $row['categoryName'];

$customersResult = $conn->query("SELECT customerID, customerName, credit_balance FROM customer WHERE dateDeleted IS NULL ORDER BY customerName");
$customers = [];
while($row = $customersResult->fetch_assoc()) $customers[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ── Layout ── */
.pos-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 58px); /* 58px = ev-topbar height */
    background: var(--ice);
    overflow: hidden;
}









.pos-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    gap: 0;
}

/* ── Product Panel ── */
.product-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--ice);
}

.search-area {
    background: var(--white);
    padding: 14px 20px 0;
    border-bottom: 1px solid var(--ice-dark);
    box-shadow: var(--shadow-sm);
}

.search-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
}

.search-box {
    flex: 1;
    position: relative;
}
.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--navy-light);
    font-size: 1rem;
    pointer-events: none;
}
.search-box input {
    width: 100%;
    padding: 10px 16px 10px 42px;
    border: 2px solid var(--ice-dark);
    border-radius: var(--radius-md);
    font-size: 13.5px;
    color: var(--text-dark);
    background: var(--ice);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.search-box input::placeholder { color: var(--text-muted); }
.search-box input:focus {
    border-color: var(--navy);
    box-shadow: 0 0 0 3px rgba(38,35,65,0.08);
    background: var(--white);
}

.cat-strip {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    padding-bottom: 12px;
    scrollbar-width: none;
}
.cat-strip::-webkit-scrollbar { display: none; }
.cat-pill {
    white-space: nowrap;
    padding: 5px 16px;
    border-radius: 20px;
    border: 1.5px solid var(--ice-dark);
    background: var(--white);
    font-size: 12px;
    font-weight: 700;
    color: var(--text-mid);
    cursor: pointer;
    transition: all .18s;
    flex-shrink: 0;
    letter-spacing: .3px;
}
.cat-pill:hover { border-color: var(--navy-light); color: var(--navy); }
.cat-pill.active {
    background: var(--navy);
    color: var(--gold);
    border-color: var(--navy);
    box-shadow: 0 3px 10px rgba(38,35,65,0.2);
}

.product-grid {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
    gap: 12px;
    align-content: start;
    scrollbar-width: thin;
    scrollbar-color: var(--ice-dark) transparent;
}
.product-grid::-webkit-scrollbar { width: 5px; }
.product-grid::-webkit-scrollbar-thumb { background: var(--ice-dark); border-radius: 5px; }

.prod-card {
    background: var(--white);
    border-radius: var(--radius-md);
    padding: 14px 10px 12px;
    text-align: center;
    cursor: pointer;
    border: 2px solid transparent;
    box-shadow: var(--shadow-sm);
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    position: relative;
    user-select: none;
}
.prod-card:hover {
    border-color: var(--gold);
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(38,35,65,0.13);
}
.prod-card:active { transform: scale(0.97); }
.prod-card.out-of-stock { opacity: .45; cursor: not-allowed; }
.prod-card.out-of-stock:hover { transform: none; border-color: transparent; }

.prod-card .prod-img {
    width: 68px; height: 68px;
    object-fit: cover;
    border-radius: var(--radius-sm);
    border: 2px solid var(--ice-dark);
    flex-shrink: 0;
}
.prod-card .prod-icon {
    width: 68px; height: 68px;
    border-radius: var(--radius-sm);
    background: var(--ice);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem;
    color: var(--ice-dark);
}
.prod-card .prod-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--text-dark);
    line-height: 1.35;
    margin-top: 2px;
}
.prod-card .prod-price {
    font-size: 14.5px;
    font-weight: 800;
    color: var(--navy);
}
.prod-card .prod-stock {
    font-size: 10.5px;
    color: var(--text-muted);
}

.stock-badge {
    position: absolute;
    top: 7px;
    right: 7px;
    font-size: 9.5px;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 20px;
    letter-spacing: .3px;
}
.stock-badge.out  { background: #fde8ea; color: var(--danger); }
.stock-badge.low  { background: #fef3e2; color: var(--warn); }

/* ── Cart Panel ── */
.cart-panel {
    width: 320px;
    flex-shrink: 0;
    background: var(--white);
    border-left: 1px solid var(--ice-dark);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: -4px 0 20px rgba(38,35,65,0.07);
}

.cart-head {
    background: var(--navy);
    padding: 13px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.cart-head h6 {
    margin: 0;
    color: var(--white);
    font-weight: 800;
    font-size: .95rem;
    display: flex;
    align-items: center;
    gap: 7px;
}
.cart-head h6 i { color: var(--gold); }
.cart-head .cart-count {
    background: var(--gold);
    color: var(--navy);
    border-radius: 20px;
    font-size: 11px;
    font-weight: 800;
    padding: 1px 8px;
    margin-left: 4px;
}
.btn-clear {
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.18);
    color: rgba(255,255,255,0.7);
    border-radius: var(--radius-sm);
    padding: 4px 8px;
    font-size: 12px;
    cursor: pointer;
    transition: all .15s;
    display: flex; align-items: center; gap: 4px;
}
.btn-clear:hover { background: rgba(232,67,90,0.3); color: #ff8899; border-color: rgba(232,67,90,0.4); }

.cart-customer {
    padding: 10px 14px;
    border-bottom: 1px solid var(--ice);
    flex-shrink: 0;
    background: #fafbff;
}
.cart-customer select {
    width: 100%;
    padding: 7px 12px;
    border: 1.5px solid var(--ice-dark);
    border-radius: var(--radius-sm);
    font-size: 12.5px;
    color: var(--text-dark);
    background: var(--white);
    outline: none;
    transition: border-color .2s;
    cursor: pointer;
}
.cart-customer select:focus { border-color: var(--navy); }

.cart-items {
    flex: 1;
    overflow-y: auto;
    padding: 6px 12px;
    scrollbar-width: thin;
    scrollbar-color: var(--ice-dark) transparent;
}
.cart-items::-webkit-scrollbar { width: 4px; }
.cart-items::-webkit-scrollbar-thumb { background: var(--ice-dark); border-radius: 4px; }

.cart-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--text-muted);
    gap: 8px;
    padding: 30px 20px;
}
.cart-empty i { font-size: 2.8rem; opacity: .4; }
.cart-empty span { font-size: 13.5px; font-weight: 700; opacity: .6; }
.cart-empty small { font-size: 11.5px; opacity: .45; }

.cart-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 4px;
    border-bottom: 1px solid var(--ice);
    transition: background .15s;
    border-radius: var(--radius-sm);
}
.cart-item:last-child { border-bottom: none; }
.cart-item-info { flex: 1; min-width: 0; }
.cart-item-name {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.cart-item-unit { font-size: 11px; color: var(--text-muted); }
.cart-item-subtotal {
    font-size: 13px;
    font-weight: 800;
    color: var(--navy);
    min-width: 58px;
    text-align: right;
}

.qty-ctrl { display: flex; align-items: center; gap: 4px; }
.qty-btn {
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 1.5px solid var(--ice-dark);
    background: var(--ice);
    color: var(--navy);
    font-size: 14px;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    transition: all .15s;
}
.qty-btn:hover { background: var(--navy); color: var(--gold); border-color: var(--navy); }
.qty-val { font-size: 13px; font-weight: 800; min-width: 20px; text-align: center; color: var(--text-dark); }

/* ── Cart Summary ── */
.cart-summary {
    background: #f8f9fe;
    border-top: 2px solid var(--ice-dark);
    padding: 12px 14px 8px;
    flex-shrink: 0;
}

.sum-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12.5px;
    color: var(--text-mid);
    margin-bottom: 6px;
}
.sum-row label { font-weight: 600; margin: 0; }
.sum-row span { font-weight: 700; }

.sum-row select {
    padding: 4px 8px;
    border: 1.5px solid var(--ice-dark);
    border-radius: var(--radius-sm);
    font-size: 12px;
    background: var(--white);
    color: var(--text-dark);
    outline: none;
    cursor: pointer;
    min-width: 110px;
}
.sum-row select:focus { border-color: var(--navy); }

.sum-divider { border: none; border-top: 1px dashed var(--ice-dark); margin: 6px 0; }

.sum-total {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    background: var(--navy);
    border-radius: var(--radius-md);
    padding: 10px 14px;
    margin: 6px 0;
}
.sum-total .label { color: rgba(255,255,255,.75); font-size: 12px; font-weight: 700; letter-spacing: .5px; }
.sum-total .amount { color: var(--gold); font-size: 1.35rem; font-weight: 900; }

.tendered-wrap {
    position: relative;
}
.tendered-wrap .currency {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 13px;
    font-weight: 800;
    color: var(--text-mid);
    pointer-events: none;
}
.tendered-wrap input {
    width: 100%;
    padding: 8px 12px 8px 28px;
    border: 2px solid var(--ice-dark);
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 700;
    color: var(--text-dark);
    background: var(--white);
    outline: none;
    transition: border-color .2s;
}
.tendered-wrap input:focus { border-color: var(--navy); }

.change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
}
.change-row .label { font-size: 12px; color: var(--text-muted); font-weight: 600; }
.change-row .value { font-size: 15px; font-weight: 900; color: var(--success); }

.credit-warn {
    background: #fff8e6;
    border: 1px solid #f5c842;
    border-radius: var(--radius-sm);
    padding: 6px 10px;
    font-size: 11.5px;
    color: #7a5c00;
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
}

/* ── Process Button ── */
.cart-actions { padding: 8px 14px 14px; flex-shrink: 0; }
.btn-process {
    width: 100%;
    background: var(--gold);
    color: var(--navy);
    border: none;
    padding: 13px;
    border-radius: var(--radius-md);
    font-size: .95rem;
    font-weight: 900;
    cursor: pointer;
    transition: all .22s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    letter-spacing: .4px;
    box-shadow: 0 4px 16px rgba(249,217,74,0.35);
}
.btn-process:hover:not(:disabled) {
    background: var(--gold-dim);
    box-shadow: 0 6px 22px rgba(249,217,74,0.5);
    transform: translateY(-1px);
}
.btn-process:active:not(:disabled) { transform: scale(0.98); }
.btn-process:disabled {
    background: var(--ice-dark);
    color: var(--text-muted);
    cursor: not-allowed;
    box-shadow: none;
}

/* ── Receipt Modal ── */
.receipt-box {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 16px;
    max-width: 300px;
    margin: 0 auto;
}
.receipt-logo-wrap { text-align: center; margin-bottom: 6px; }
.receipt-logo-wrap img { max-height: 48px; max-width: 120px; object-fit: contain; }
.receipt-title { text-align: center; font-weight: 800; font-size: .95rem; letter-spacing: 2px; color: var(--navy); margin-bottom: 2px; }
.receipt-sub { text-align: center; color: #888; font-size: 10px; margin-bottom: 6px; }
.receipt-divider { border-top: 1px dashed #ccc; margin: 6px 0; }
.receipt-row { display: flex; justify-content: space-between; font-size: 12px; padding: 1px 0; }
.receipt-item { display: flex; justify-content: space-between; font-size: 11.5px; color: #555; padding: 1px 0; }
.receipt-grand { font-weight: 800; font-size: .9rem; border-top: 1px solid #333; margin-top: 3px; padding-top: 3px; }
</style>

    <!-- Topbar -->
    <?php
    $topbarTitle = 'Point of Sale';
    $topbarIcon  = 'bi-lightning-charge-fill';
    $topbarSub   = 'Process customer transactions';
    $topbarExtra = '<span class="ev-date-chip"><i class="bi bi-clock me-1"></i>' . date('M d, Y h:i A') . '</span>';
    include 'topbar.php';
    ?>

<div class="pos-wrapper">

    <div class="pos-body">

        <!-- PRODUCT PANEL -->
        <div class="product-panel">
            <div class="search-area">
                <div class="search-row">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" placeholder="Search by name or scan barcode…" autocomplete="off">
                    </div>
                </div>
                <div class="cat-strip">
                    <span class="cat-pill active" data-cat="All">All</span>
                    <?php foreach($categories as $cat): ?>
                    <span class="cat-pill" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></span>
                    <?php endforeach; ?>
                </div>
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
                        <span class="stock-badge out">Out</span>
                    <?php elseif($p['stock_quantity'] <= 10): ?>
                        <span class="stock-badge low">Low</span>
                    <?php endif; ?>
                    <?php if(!empty($p['product_image'])): ?>
                        <img src="../<?php echo htmlspecialchars($p['product_image']); ?>" class="prod-img" alt="">
                    <?php else: ?>
                        <div class="prod-icon"><i class="bi bi-box-seam"></i></div>
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
            <div class="cart-head">
                <h6>
                    <i class="bi bi-bag-fill"></i>
                    Order
                    <span class="cart-count" id="cartCount">0</span>
                </h6>
                <button class="btn-clear" onclick="clearCart()">
                    <i class="bi bi-trash3"></i> Clear
                </button>
            </div>

            <div class="cart-customer">
                <select id="customerSelect">
                    <option value="">👤 Walk-in Customer</option>
                    <?php foreach($customers as $c): ?>
                    <option value="<?php echo $c['customerID']; ?>"><?php echo htmlspecialchars($c['customerName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cart-items" id="cartItems">
                <div class="cart-empty" id="cartEmpty">
                    <i class="bi bi-bag-x"></i>
                    <span>Cart is empty</span>
                    <small>Tap a product to add it</small>
                </div>
            </div>

            <div class="cart-summary">
                <div class="sum-row">
                    <label>Subtotal</label>
                    <span id="subtotalDisplay">₱0.00</span>
                </div>
                <div class="sum-row">
                    <label>Discount</label>
                    <select id="discountSelect" onchange="recalc()">
                        <option value="0">None</option>
                        <option value="5">5%</option>
                        <option value="10">10%</option>
                        <option value="20">20%</option>
                        <option value="pwd">PWD / Senior 20%</option>
                    </select>
                </div>
                <div class="sum-row">
                    <label>Discount Amt</label>
                    <span id="discountDisplay" style="color:var(--danger);">₱0.00</span>
                </div>

                <hr class="sum-divider">

                <div class="sum-total">
                    <span class="label">TOTAL DUE</span>
                    <span class="amount" id="totalDisplay">₱0.00</span>
                </div>

                <div class="sum-row" style="margin-top:8px;">
                    <label>Payment</label>
                    <select id="paymentMethod" onchange="toggleCreditWarning()">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Card">Card</option>
                        <option value="Credit">Credit (Utang)</option>
                    </select>
                </div>

                <div id="creditWarning" class="credit-warn" style="display:none;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    Select a customer for credit.
                </div>

                <div style="margin-top:8px;margin-bottom:4px;font-size:11.5px;font-weight:600;color:var(--text-muted);">AMOUNT TENDERED</div>
                <div class="tendered-wrap">
                    <span class="currency">₱</span>
                    <input type="number" id="tenderedInput" placeholder="0.00" min="0" step="0.01" oninput="recalc()">
                </div>

                <div class="change-row">
                    <span class="label">Change</span>
                    <span class="value" id="changeDisplay">₱0.00</span>
                </div>
            </div>

            <div class="cart-actions">
                <button class="btn-process" id="processBtn" onclick="processSale()" disabled>
                    <i class="bi bi-check2-circle"></i> Process Sale
                </button>
            </div>
        </div>

    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:var(--navy);color:#fff;border:none;">
                <h5 class="modal-title" style="font-weight:800;">
                    <i class="bi bi-check-circle-fill me-2" style="color:var(--gold);"></i>Sale Complete!
                </h5>
            </div>
            <div class="modal-body" id="receiptBody"></div>
            <div class="modal-footer" style="border-top:1px solid var(--ice-dark);">
                <button class="btn btn-outline-secondary btn-sm" onclick="printReceipt()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <button class="btn btn-sm" style="background:var(--navy);color:var(--gold);font-weight:700;" onclick="newSale()">
                    <i class="bi bi-plus-circle me-1"></i>New Sale
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const allProducts = <?php echo json_encode($allProducts); ?>;
let cart = [];

// ── Filter ──
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
        const matchText = !q || card.dataset.name.toLowerCase().includes(q) || (card.dataset.barcode||'').toLowerCase().includes(q);
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
        if(existing.qty >= stock){
            Swal.fire({icon:'warning',title:'Stock limit',text:`Only ${stock} in stock.`,timer:1500,showConfirmButton:false});
            return;
        }
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
        title:'Clear cart?', icon:'question', showCancelButton:true,
        confirmButtonText:'Yes, clear', confirmButtonColor:'#e8435a'
    }).then(r => { if(r.isConfirmed){ cart=[]; renderCart(); } });
}

function renderCart(){
    const container = document.getElementById('cartItems');
    const emptyEl   = document.getElementById('cartEmpty');
    const count = cart.reduce((s,i) => s + i.qty, 0);
    document.getElementById('cartCount').textContent = count;

    if(!cart.length){
        // Use display toggle instead of appendChild to avoid null ref when
        // cartEmpty is already inside container and innerHTML='' destroys it
        container.querySelectorAll('.cart-item').forEach(el => el.remove());
        emptyEl.style.display = '';
        document.getElementById('processBtn').disabled = true;
        recalc();
        return;
    }

    let html = '';
    cart.forEach(item => {
        html += `<div class="cart-item">
            <div class="cart-item-info">
                <div class="cart-item-name">${item.name}</div>
                <div class="cart-item-unit">₱${item.price.toFixed(2)} each</div>
            </div>
            <div class="qty-ctrl">
                <button class="qty-btn" onclick="changeQty('${item.id}',-1)">−</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty('${item.id}',1)">+</button>
            </div>
            <div class="cart-item-subtotal">₱${(item.price*item.qty).toFixed(2)}</div>
        </div>`;
    });
    document.getElementById('cartEmpty').style.display = 'none';
    container.querySelectorAll('.cart-item').forEach(el => el.remove());
    container.insertAdjacentHTML('beforeend', html);
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
    document.getElementById('creditWarning').style.display = (method === 'Credit') ? '' : 'none';
}

// ── Process Sale ──
// BUG FIX: use a simple flag instead of disabling the button permanently on success
let isProcessing = false;

function processSale(){
    if(!cart.length || isProcessing) return;

    const method     = document.getElementById('paymentMethod').value;
    const customerID = document.getElementById('customerSelect').value;
    const discVal    = document.getElementById('discountSelect').value;
    const discPct    = (discVal === 'pwd') ? 20 : parseFloat(discVal);
    const subtotal   = cart.reduce((s,i) => s + i.price*i.qty, 0);
    const discAmt    = subtotal * discPct / 100;
    const total      = subtotal - discAmt;
    const tendered   = parseFloat(document.getElementById('tenderedInput').value) || 0;
    const change     = Math.max(0, tendered - total);

    if(method === 'Credit' && !customerID){
        Swal.fire({icon:'warning',title:'Select a customer',text:'Credit/Utang requires a customer.'});
        return;
    }
    if(method !== 'Credit' && tendered < total){
        Swal.fire({icon:'warning',title:'Insufficient payment',text:`Amount tendered (₱${tendered.toFixed(2)}) is less than total (₱${total.toFixed(2)}).`});
        return;
    }

    // Lock against double-submit
    isProcessing = true;
    const btn = document.getElementById('processBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing…';

    const formData = new FormData();
    formData.append('processSale',    '1');
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
        // Strip stray whitespace/BOM that PHP may emit before/after JSON
        const clean = raw.trim().replace(/^﻿/, '');
        let data;
        try {
            data = JSON.parse(clean);
        } catch(e) {
            resetProcessBtn();
            Swal.fire({icon:'error',title:'Server Error',html:'<pre style="font-size:11px;text-align:left;max-height:200px;overflow:auto;">'+raw+'</pre>'});
            return;
        }
        if(data.success){
            // Reset processing flag & button immediately so UI is unblocked
            isProcessing = false;
            resetProcessBtn();
            showReceipt(data.salesID, total, discAmt, tendered, change, method, customerID);
            // Cart and form are cleared in newSale() when the receipt modal closes
        } else {
            resetProcessBtn();
            Swal.fire({icon:'error',title:'Sale Failed',text: data.message || 'An error occurred.'});
        }
    })
    .catch(err => {
        resetProcessBtn();
        Swal.fire({icon:'error',title:'Network error',text: err.message || 'Could not reach the server.'});
    });
}

// BUG FIX: restore button state on error so user can retry
function resetProcessBtn(){
    isProcessing = false;
    const btn = document.getElementById('processBtn');
    btn.disabled = cart.length === 0;
    btn.innerHTML = '<i class="bi bi-check2-circle"></i> Process Sale';
}

function showReceipt(salesID, total, discAmt, tendered, change, method, customerID){
    const customerName = customerID
        ? document.getElementById('customerSelect').options[document.getElementById('customerSelect').selectedIndex].text
        : 'Walk-in Customer';

    const subtotal = cart.reduce((s,i) => s + i.price*i.qty, 0);
    let itemsHtml = '';
    cart.forEach(item => {
        itemsHtml += `<div class="receipt-item">
            <span>${item.name} <span style="color:#aaa;">×${item.qty}</span></span>
            <span>₱${(item.price*item.qty).toFixed(2)}</span>
        </div>`;
    });

    const storedLogo    = localStorage.getItem('ev_store_logo');
    const storeName     = localStorage.getItem('ev_store_name') || '7EVELYN POS';
    const storeTagline  = localStorage.getItem('ev_store_tagline') || '';
    const receiptFooter = localStorage.getItem('ev_receipt_footer') || 'Thank you for shopping!';
    const showLogo      = localStorage.getItem('ev_show_logo_receipt') !== 'false';
    const showTagline   = localStorage.getItem('ev_show_tagline') !== 'false';
    const logoHtml      = (storedLogo && showLogo) ? `<div class="receipt-logo-wrap"><img src="${storedLogo}" alt="logo"></div>` : '';
    const taglineHtml   = (storeTagline && showTagline) ? `<div class="receipt-sub">${storeTagline}</div>` : '';

    document.getElementById('receiptBody').innerHTML = `
    <div class="receipt-box">
        ${logoHtml}
        <div class="receipt-title">${storeName}</div>
        ${taglineHtml}
        <div class="receipt-sub">Official Receipt &nbsp;|&nbsp; #${salesID}</div>
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Customer:</span><span>${customerName}</span></div>
        <div class="receipt-row"><span>Date:</span><span>${new Date().toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'})}</span></div>
        <div class="receipt-divider"></div>
        ${itemsHtml}
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Subtotal:</span><span>₱${subtotal.toFixed(2)}</span></div>
        ${discAmt > 0 ? `<div class="receipt-row"><span>Discount:</span><span style="color:#e8435a;">−₱${discAmt.toFixed(2)}</span></div>` : ''}
        <div class="receipt-row receipt-grand"><span>TOTAL:</span><span>₱${total.toFixed(2)}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Payment (${method}):</span><span>₱${method === 'Credit' ? '0.00' : tendered.toFixed(2)}</span></div>
        ${method !== 'Credit' ? `<div class="receipt-row"><span>Change:</span><span>₱${change.toFixed(2)}</span></div>` : ''}
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
        .receipt-row,.receipt-item{display:flex;justify-content:space-between;padding:1px 0;}
        .receipt-title{text-align:center;font-weight:800;font-size:.95rem;letter-spacing:2px;color:#262341;}
        .receipt-sub{text-align:center;color:#888;font-size:10px;}
        .receipt-divider{border-top:1px dashed #ccc;margin:6px 0;}
        .receipt-grand{font-weight:800;border-top:1px solid #333;padding-top:3px;}
        .receipt-logo-wrap{text-align:center;margin-bottom:6px;}
        .receipt-logo-wrap img{max-height:48px;max-width:120px;object-fit:contain;}
        .receipt-item{font-size:11.5px;color:#444;}
    </style></head><body>${content}</body></html>`);
    w.document.close();
    w.print();
}

let _resetting = false; // guard against re-entrant newSale calls

function newSale(){
    if(_resetting) return;
    _resetting = true;

    // Hide modal only if it is still open (when called from the New Sale button)
    const modalEl = document.getElementById('receiptModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl);
    if(modalInstance) modalInstance.hide();

    cart = [];
    isProcessing = false;
    document.getElementById('customerSelect').value  = '';
    document.getElementById('discountSelect').value  = '0';
    document.getElementById('paymentMethod').value   = 'Cash';
    document.getElementById('tenderedInput').value   = '';
    document.getElementById('creditWarning').style.display = 'none';
    renderCart(); // resets button, count, totals

    _resetting = false;
}

document.getElementById('tenderedInput').addEventListener('keydown', e => {
    if(e.key === 'Enter') processSale();
});

// Reset POS state whenever receipt modal is closed (X button, backdrop, or New Sale button)
document.getElementById('receiptModal').addEventListener('hidden.bs.modal', function () {
    newSale();
});
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>