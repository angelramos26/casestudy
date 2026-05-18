<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Cashier'])){
    header("Location: dashboard.php"); exit();
}

$pageTitle = "POS – Take Order";

// Menu items
$itemsResult = $conn->query("
    SELECT mi.itemID, mi.itemName, mi.price, mi.description, mi.item_image, mi.is_available,
           c.categoryName
    FROM menu_item mi
    JOIN category c ON mi.categoryID = c.categoryID
    WHERE mi.status = 'Active' AND mi.is_available = 1
    ORDER BY c.categoryName, mi.itemName ASC
");
$allItems = [];
while($row = $itemsResult->fetch_assoc()) $allItems[] = $row;

// Categories
$catResult = $conn->query("
    SELECT DISTINCT c.categoryName
    FROM category c
    JOIN menu_item mi ON c.categoryID = mi.categoryID
    WHERE mi.status = 'Active' AND mi.is_available = 1
    ORDER BY c.categoryName
");
$categories = [];
while($row = $catResult->fetch_assoc()) $categories[] = $row['categoryName'];

// Available tables (for Dine-In)
$tableResult = $conn->query("
    SELECT tableID, tableNo, capacity, section, status
    FROM dining_table
    ORDER BY section, tableNo
");
$tables = [];
while($row = $tableResult->fetch_assoc()) $tables[] = $row;

// (customers removed — all orders are walk-in)

// Open/Active orders for quick resume
$openOrdersResult = $conn->query("
    SELECT o.orderID, o.orderType, o.pax, o.status, o.dateCreated,
           dt.tableNo, dt.section,
           COUNT(oi.orderItemID) AS item_count
    FROM orders o
    LEFT JOIN dining_table dt ON o.tableID = dt.tableID
    LEFT JOIN order_items oi ON o.orderID = oi.orderID AND oi.item_status != 'Void'
    WHERE o.status IN ('Open','Active')
    GROUP BY o.orderID, o.orderType, o.pax, o.status, o.dateCreated, dt.tableNo, dt.section
    ORDER BY o.dateCreated DESC
    LIMIT 20
");
$openOrders = [];
while($row = $openOrdersResult->fetch_assoc()) $openOrders[] = $row;
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ══════════════════════════════════════════════════
   RESTAURANT POS — FULL LAYOUT
   ══════════════════════════════════════════════════ */
.rpos-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - var(--topbar-h));
    background: var(--s-bg);
    overflow: hidden;
}

/* ── Top action bar ── */
.rpos-actionbar {
    background: var(--c-white);
    border-bottom: 1.5px solid var(--s-border);
    padding: 10px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    box-shadow: var(--sh-sm);
    flex-shrink: 0;
}
.rpos-actionbar .order-type-tabs {
    display: flex;
    gap: 4px;
    background: var(--s-bg);
    padding: 4px;
    border-radius: 10px;
}
.order-type-tab {
    padding: 6px 16px;
    border-radius: 7px;
    border: none;
    background: transparent;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--t-mid);
    cursor: pointer;
    transition: all .18s;
    display: flex;
    align-items: center;
    gap: 5px;
}
.order-type-tab.active {
    background: var(--charcoal);
    color: var(--orange);
    box-shadow: 0 2px 8px rgba(26,26,26,.15);
}
.order-type-tab:hover:not(.active) { background: rgba(26,26,26,.07); color: var(--charcoal); }

.rpos-open-orders {
    display: flex;
    gap: 6px;
    overflow-x: auto;
    flex: 1;
    align-items: center;
    scrollbar-width: none;
}
.rpos-open-orders::-webkit-scrollbar { display: none; }
.open-order-chip {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--s-border);
    background: var(--c-white);
    cursor: pointer;
    font-size: 12px;
    font-weight: 700;
    color: var(--t-mid);
    transition: all .18s;
}
.open-order-chip:hover { border-color: var(--charcoal); color: var(--charcoal); }
.open-order-chip.active-chip { border-color: var(--orange); background: var(--orange-soft); color: var(--orange); }
.open-order-chip .chip-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--c-success); flex-shrink: 0; }
.open-order-chip .chip-dot.pending { background: var(--c-warning); }

/* ── POS Body ── */
.rpos-body {
    flex: 1;
    display: flex;
    overflow: hidden;
    gap: 0;
}

/* ── Menu Panel ── */
.menu-panel {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--s-bg);
}

.menu-search-area {
    background: var(--c-white);
    padding: 14px 20px 0;
    border-bottom: 1px solid var(--s-border);
    box-shadow: var(--sh-sm);
}

.menu-search-row {
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
    left: 14px; top: 50%;
    transform: translateY(-50%);
    color: var(--t-muted);
    font-size: 1rem;
    pointer-events: none;
}
.search-box input {
    width: 100%;
    padding: 10px 16px 10px 42px;
    border: 2px solid var(--s-border);
    border-radius: var(--r-md);
    font-size: 13.5px;
    color: var(--t-main);
    background: var(--s-bg);
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    font-family: var(--font-ui);
}
.search-box input:focus {
    border-color: var(--charcoal);
    box-shadow: 0 0 0 3px rgba(26,26,26,0.08);
    background: var(--c-white);
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
    border: 1.5px solid var(--s-border);
    background: var(--c-white);
    font-size: 12px;
    font-weight: 700;
    color: var(--t-mid);
    cursor: pointer;
    transition: all .18s;
    flex-shrink: 0;
}
.cat-pill:hover { border-color: var(--charcoal-light); color: var(--charcoal); }
.cat-pill.active { background: var(--charcoal); color: var(--orange); border-color: var(--charcoal); box-shadow: 0 3px 10px rgba(26,26,26,0.2); }

/* ── Menu Grid ── */
.menu-grid {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
    gap: 12px;
    align-content: start;
    scrollbar-width: thin;
    scrollbar-color: var(--s-border) transparent;
}

.menu-card {
    background: var(--c-white);
    border-radius: var(--r-md);
    padding: 14px 10px 12px;
    text-align: center;
    cursor: pointer;
    border: 2px solid transparent;
    box-shadow: var(--sh-sm);
    transition: all .2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    position: relative;
    user-select: none;
}
.menu-card:hover { border-color: var(--orange); transform: translateY(-3px); box-shadow: 0 8px 24px rgba(26,26,26,0.13); }
.menu-card:active { transform: scale(0.97); }
.menu-card.unavailable { opacity: .4; cursor: not-allowed; }
.menu-card.unavailable:hover { transform: none; border-color: transparent; box-shadow: var(--sh-sm); }

.menu-card .item-img {
    width: 72px; height: 72px;
    object-fit: cover;
    border-radius: var(--r-sm);
    border: 2px solid var(--s-border);
}
.menu-card .item-icon {
    width: 72px; height: 72px;
    border-radius: var(--r-sm);
    background: var(--s-bg);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem;
    color: var(--t-muted);
}
.menu-card .item-name {
    font-size: 12px;
    font-weight: 700;
    color: var(--t-main);
    line-height: 1.35;
}
.menu-card .item-price {
    font-size: 13.5px;
    font-weight: 800;
    color: var(--charcoal);
    font-family: var(--font-mono);
}
.menu-card .item-cat {
    font-size: 10px;
    color: var(--t-muted);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.menu-card .sold-out-badge {
    position: absolute; top: 6px; right: 6px;
    background: var(--c-danger); color: #fff;
    font-size: 9px; font-weight: 800;
    padding: 2px 6px; border-radius: 4px;
    letter-spacing: .5px; text-transform: uppercase;
}

/* ── Order Panel ── */
.order-panel {
    width: 340px;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    background: var(--c-white);
    border-left: 1.5px solid var(--s-border);
    overflow: hidden;
}

.order-header {
    padding: 14px 18px 12px;
    border-bottom: 1.5px solid var(--s-border);
    background: var(--charcoal);
    flex-shrink: 0;
}
.order-header-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}
.order-header-top .order-title {
    font-size: 13px; font-weight: 800; color: #fff;
    display: flex; align-items: center; gap: 6px;
}
.order-header-top .btn-clear-order {
    background: rgba(232,67,90,.25); color: #fff;
    border: 1px solid rgba(232,67,90,.4);
    border-radius: 6px; padding: 4px 10px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    transition: all .15s;
}
.order-header-top .btn-clear-order:hover { background: var(--c-danger); }

.order-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
.order-meta-item { display: flex; flex-direction: column; gap: 2px; }
.order-meta-label { font-size: 9.5px; color: rgba(255,255,255,.45); font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.order-meta-value { font-size: 12px; font-weight: 700; color: #fff; }
.order-meta select {
    background: rgba(255,255,255,.1);
    color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 6px;
    padding: 4px 8px;
    font-size: 12px;
    font-weight: 600;
    outline: none;
    width: 100%;
    font-family: var(--font-ui);
}
.order-meta select option { background: var(--charcoal); color: #fff; }

/* ── Order Items ── */
.order-items {
    flex: 1;
    overflow-y: auto;
    padding: 0;
    scrollbar-width: thin;
    scrollbar-color: var(--s-border) transparent;
}
.order-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    padding: 40px 20px;
    color: var(--t-muted);
    gap: 8px;
    text-align: center;
}
.order-empty i { font-size: 2.5rem; opacity: .3; }
.order-empty span { font-size: 13px; font-weight: 600; }
.order-empty small { font-size: 11.5px; }

.order-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--s-border);
    transition: background .15s;
}
.order-item:hover { background: var(--s-bg); }
.order-item .oi-name {
    flex: 1;
    min-width: 0;
    font-size: 13px;
    font-weight: 700;
    color: var(--t-main);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.order-item .oi-note {
    font-size: 10.5px;
    color: var(--t-muted);
    font-weight: 500;
    margin-top: 1px;
}
.qty-ctrl {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.qty-btn {
    width: 24px; height: 24px;
    border-radius: 6px;
    border: 1.5px solid var(--s-border);
    background: var(--c-white);
    color: var(--t-main);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
    line-height: 1;
}
.qty-btn:hover { background: var(--charcoal); color: var(--orange); border-color: var(--charcoal); }
.qty-val {
    width: 26px;
    text-align: center;
    font-size: 13px;
    font-weight: 800;
    font-family: var(--font-mono);
    color: var(--t-main);
}
.order-item .oi-total {
    font-size: 13px;
    font-weight: 800;
    color: var(--charcoal);
    font-family: var(--font-mono);
    flex-shrink: 0;
    min-width: 64px;
    text-align: right;
}
.btn-void-item {
    background: none; border: none; color: var(--t-muted);
    cursor: pointer; padding: 3px; font-size: 14px;
    transition: color .15s;
    flex-shrink: 0;
}
.btn-void-item:hover { color: var(--c-danger); }

/* ── Order Summary ── */
.order-summary {
    border-top: 1.5px solid var(--s-border);
    padding: 14px 18px;
    flex-shrink: 0;
}
.sum-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
    font-size: 13px;
}
.sum-row label { color: var(--t-muted); font-weight: 600; }
.sum-row select, .sum-row input[type="number"] {
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-sm);
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 700;
    color: var(--t-main);
    background: var(--s-bg);
    outline: none;
    font-family: var(--font-ui);
    max-width: 160px;
}
.sum-divider { border: none; border-top: 1.5px dashed var(--s-border); margin: 10px 0; }
.sum-total {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 6px;
}
.sum-total .label { font-size: 13px; font-weight: 800; color: var(--t-main); }
.sum-total .amount { font-size: 22px; font-weight: 800; color: var(--charcoal); font-family: var(--font-mono); }

.tendered-wrap {
    display: flex;
    align-items: center;
    border: 2px solid var(--s-border);
    border-radius: var(--r-sm);
    overflow: hidden;
    margin-top: 4px;
    margin-bottom: 8px;
    transition: border-color .2s;
}
.tendered-wrap:focus-within { border-color: var(--charcoal); }
.tendered-wrap .currency {
    padding: 8px 12px;
    background: var(--s-bg);
    font-weight: 800;
    color: var(--t-muted);
    font-size: 14px;
    border-right: 1.5px solid var(--s-border);
}
.tendered-wrap input {
    flex: 1;
    border: none;
    outline: none;
    padding: 8px 12px;
    font-size: 15px;
    font-weight: 800;
    color: var(--t-main);
    font-family: var(--font-mono);
    background: transparent;
    width: 100%;
}
.change-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--s-bg);
    border-radius: var(--r-sm);
    padding: 8px 12px;
    margin-bottom: 10px;
}
.change-row .label { font-size: 12px; font-weight: 700; color: var(--t-muted); }
.change-row .value { font-size: 16px; font-weight: 800; color: var(--c-success); font-family: var(--font-mono); }

/* ── Order Actions ── */
.order-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    padding: 0 18px 18px;
    flex-shrink: 0;
}
.btn-send-kitchen {
    background: var(--orange); color: #fff;
    border: none; border-radius: var(--r-sm);
    padding: 11px 10px; font-size: 12.5px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%;
}
.btn-send-kitchen:hover { filter: brightness(1.1); }
.btn-send-kitchen:disabled { opacity: .4; cursor: not-allowed; }

.btn-settle {
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: var(--r-sm);
    padding: 11px 10px; font-size: 12.5px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%;
}
.btn-settle:hover { background: var(--charcoal-mid); }
.btn-settle:disabled { opacity: .4; cursor: not-allowed; }

/* ── New Order Modal ── */
.new-order-form .form-label-sm { font-size: 12px; font-weight: 700; color: var(--t-mid); margin-bottom: 4px; display: block; }
.new-order-form .form-control, .new-order-form .form-select {
    border: 1.5px solid var(--s-border);
    border-radius: var(--r-sm);
    font-size: 13px; color: var(--t-main);
    background: var(--s-bg); padding: 8px 12px; outline: none;
    width: 100%; margin-bottom: 12px;
    font-family: var(--font-ui);
    transition: border-color .2s;
}
.new-order-form .form-control:focus, .new-order-form .form-select:focus {
    border-color: var(--charcoal); background: var(--c-white); box-shadow: none;
}

/* ── Item Note Modal ── */
.item-note-badge {
    display: inline-block;
    background: var(--orange-soft); color: var(--charcoal);
    font-size: 10px; font-weight: 700;
    padding: 1px 6px; border-radius: 4px;
    margin-top: 2px;
    max-width: 130px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* ── Receipt ── */
.receipt-box { font-family: 'Courier New', monospace; font-size: 12px; padding: 8px 0; }
.receipt-title { text-align: center; font-weight: 900; font-size: 1rem; letter-spacing: 2px; color: var(--charcoal); margin-bottom: 2px; }
.receipt-sub { text-align: center; color: #888; font-size: 10.5px; margin-bottom: 2px; }
.receipt-type-badge { text-align: center; margin: 6px 0; }
.receipt-type-badge span { background: var(--charcoal); color: var(--orange); padding: 2px 12px; border-radius: 10px; font-size: 10px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; }
.receipt-divider { border-top: 1px dashed #ccc; margin: 6px 0; }
.receipt-row { display: flex; justify-content: space-between; padding: 1px 0; }
.receipt-item { display: flex; justify-content: space-between; padding: 2px 0; font-size: 11.5px; color: #333; }
.receipt-grand { font-weight: 900; font-size: 1.05rem; border-top: 2px solid #333; padding-top: 4px; color: var(--charcoal); }
.receipt-logo-wrap { text-align: center; margin-bottom: 8px; }
.receipt-logo-wrap img { max-height: 52px; max-width: 140px; object-fit: contain; }

/* ── Order type indicator ── */
.type-indicator {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 800;
}
.type-dine { background: #e0f2fe; color: #0369a1; }
.type-take { background: #f0fdf4; color: #166534; }
.type-del  { background: var(--orange-soft); color: var(--orange); }

/* ── Service charge row ── */
.svc-row {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 6px; font-size: 13px;
}
.svc-row label { color: var(--t-muted); font-weight: 600; flex: 1; }
.svc-check { width: 16px; height: 16px; cursor: pointer; }

/* ── Scrollbar ── */
.order-items::-webkit-scrollbar { width: 4px; }
.order-items::-webkit-scrollbar-thumb { background: var(--s-border); border-radius: 4px; }

@media print {
    .no-print { display: none !important; }
    body { margin: 0; }
}

/* ── Modal Action Buttons ── */
.btn-rpos-primary {
    display: inline-flex; align-items: center; gap: 6px;
    background: var(--g-orange); color: #fff;
    border: none; border-radius: 8px;
    padding: 9px 20px; font-size: 13.5px; font-weight: 700;
    cursor: pointer; transition: opacity .15s, transform .15s;
    letter-spacing: .2px;
}
.btn-rpos-primary:hover { opacity: .88; transform: translateY(-1px); }
.btn-rpos-primary:active { opacity: 1; transform: none; }

.btn-rpos-ghost {
    display: inline-flex; align-items: center; gap: 6px;
    background: transparent; color: var(--t-mid);
    border: 1.5px solid var(--s-border); border-radius: 8px;
    padding: 8px 18px; font-size: 13.5px; font-weight: 600;
    cursor: pointer; transition: background .15s, border-color .15s;
}
.btn-rpos-ghost:hover { background: var(--orange-soft); border-color: var(--orange); color: var(--orange-dark); }
</style>

<?php
$topbarTitle = 'Point of Sale';
$topbarIcon  = 'bi-layout-text-window-reverse';
$topbarSub   = 'Take Order / Settle Bill';
include 'topbar.php';
?>

<div class="rpos-wrapper">

    <!-- ── Action Bar ── -->
    <div class="rpos-actionbar">
        <!-- Order Type Selector (for new order) -->
        <div class="order-type-tabs">
            <button class="order-type-tab active" data-type="Dine-In" onclick="setOrderType(this)">
                <i class="bi bi-people"></i> Dine-In
            </button>
            <button class="order-type-tab" data-type="Takeout" onclick="setOrderType(this)">
                <i class="bi bi-bag"></i> Takeout
            </button>
            <button class="order-type-tab" data-type="Delivery" onclick="setOrderType(this)">
                <i class="bi bi-bicycle"></i> Delivery
            </button>
        </div>

        <button class="btn-rpos-primary" style="font-size:12.5px;padding:7px 16px;" onclick="openNewOrderModal()">
            <i class="bi bi-plus-circle"></i> New Order
        </button>

        <!-- Open Orders Chips -->
        <div class="rpos-open-orders" id="openOrderChips">
            <?php foreach($openOrders as $oo): ?>
            <div class="open-order-chip" data-order-id="<?= $oo['orderID'] ?>" onclick="loadOrder(<?= $oo['orderID'] ?>)">
                <span class="chip-dot <?= $oo['status'] === 'Open' ? '' : 'pending' ?>"></span>
                <?php if($oo['tableNo']): ?>
                <i class="bi bi-grid-3x3-gap" style="font-size:11px;"></i>
                T<?= htmlspecialchars($oo['tableNo']) ?>
                <?php else: ?>
                <i class="bi bi-bag" style="font-size:11px;"></i>
                #<?= $oo['orderID'] ?>
                <?php endif; ?>
                <span style="font-size:10px;color:var(--t-muted);font-weight:600;"><?= $oo['item_count'] ?> itm</span>
            </div>
            <?php endforeach; ?>
            <?php if(empty($openOrders)): ?>
            <span style="font-size:12px;color:var(--t-muted);font-style:italic;">No open orders</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── POS Body ── -->
    <div class="rpos-body">

        <!-- ── Menu Panel ── -->
        <div class="menu-panel">
            <div class="menu-search-area">
                <div class="menu-search-row">
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="menuSearch" placeholder="Search menu items…" autocomplete="off" oninput="filterMenu()">
                    </div>
                </div>
                <div class="cat-strip">
                    <div class="cat-pill active" data-cat="All" onclick="selectCat(this)">All</div>
                    <?php foreach($categories as $cat): ?>
                    <div class="cat-pill" data-cat="<?= htmlspecialchars($cat) ?>" onclick="selectCat(this)">
                        <?= htmlspecialchars($cat) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="menu-grid" id="menuGrid">
                <?php foreach($allItems as $item): ?>
                <?php $unavail = !$item['is_available']; ?>
                <div class="menu-card <?= $unavail ? 'unavailable' : '' ?>"
                     data-id="<?= $item['itemID'] ?>"
                     data-name="<?= htmlspecialchars($item['itemName']) ?>"
                     data-price="<?= $item['price'] ?>"
                     data-cat="<?= htmlspecialchars($item['categoryName']) ?>"
                     onclick="<?= $unavail ? '' : "addToOrder({id:'{$item['itemID']}',name:'" . addslashes(htmlspecialchars($item['itemName'])) . "',price:{$item['price']}})" ?>">
                    <?php if($unavail): ?>
                    <div class="sold-out-badge">86'd</div>
                    <?php endif; ?>
                    <?php if($item['item_image'] && file_exists('../' . $item['item_image'])): ?>
                    <img class="item-img" src="../<?= htmlspecialchars($item['item_image']) ?>" alt="<?= htmlspecialchars($item['itemName']) ?>" loading="lazy">
                    <?php else: ?>
                    <div class="item-icon">🍽️</div>
                    <?php endif; ?>
                    <div class="item-name"><?= htmlspecialchars($item['itemName']) ?></div>
                    <div class="item-price">₱<?= number_format($item['price'], 2) ?></div>
                    <div class="item-cat"><?= htmlspecialchars($item['categoryName']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ── Order Panel ── -->
        <div class="order-panel">

            <div class="order-header">
                <div class="order-header-top">
                    <div class="order-title">
                        <i class="bi bi-receipt"></i>
                        <span id="orderTitle">Current Order</span>
                    </div>
                    <button class="btn-clear-order" onclick="clearOrder()">
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                </div>
                <div class="order-meta">
                    <div class="order-meta-item">
                        <span class="order-meta-label">Type</span>
                        <span class="order-meta-value" id="orderTypeDisplay">—</span>
                    </div>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Table / Ref</span>
                        <span class="order-meta-value" id="orderTableDisplay">—</span>
                    </div>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Pax</span>
                        <span class="order-meta-value" id="orderPaxDisplay">—</span>
                    </div>
                    <div class="order-meta-item">
                        <span class="order-meta-label">Status</span>
                        <span class="order-meta-value" id="orderStatusDisplay">—</span>
                    </div>
                </div>
            </div>

            <!-- Order Items List -->
            <div class="order-items" id="orderItems">
                <div class="order-empty" id="orderEmpty">
                    <i class="bi bi-clipboard-plus"></i>
                    <span>No order open</span>
                    <small>Start a New Order or tap an open order above</small>
                </div>
            </div>

            <!-- Summary -->
            <div class="order-summary" id="orderSummarySection" style="display:none;">
                <div class="sum-row">
                    <label>Subtotal</label>
                    <span id="subtotalDisplay" style="font-weight:800;font-family:var(--font-mono);">₱0.00</span>
                </div>
                <div class="svc-row">
                    <label>Service Charge (10%)</label>
                    <input type="checkbox" class="svc-check" id="svcChargeCheck" onchange="recalc()">
                    <span id="svcChargeDisplay" style="font-weight:700;font-family:var(--font-mono);">₱0.00</span>
                </div>
                <div class="sum-row">
                    <label>Discount</label>
                    <select id="discountSelect" onchange="recalc()">
                        <option value="0">None</option>
                        <option value="5">5%</option>
                        <option value="10">10%</option>
                        <option value="20">Promo 20%</option>
                        <option value="pwd">PWD / Senior 20%</option>
                    </select>
                    <span id="discountDisplay" style="color:var(--c-danger);font-weight:800;font-family:var(--font-mono);">₱0.00</span>
                </div>
                <hr class="sum-divider">
                <div class="sum-total">
                    <span class="label">TOTAL DUE</span>
                    <span class="amount" id="totalDisplay">₱0.00</span>
                </div>

                <div style="margin-top:12px;">
                    <div class="sum-row" style="margin-bottom:4px;">
                        <label>Payment Method</label>
                        <select id="paymentMethod" onchange="toggleCreditWarn()">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Card">Card</option>
                        </select>
                    </div>

                    <div style="font-size:11.5px;font-weight:700;color:var(--t-muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px;">Amount Tendered</div>
                    <div class="tendered-wrap">
                        <span class="currency">₱</span>
                        <input type="number" id="tenderedInput" placeholder="0.00" min="0" step="0.01" oninput="recalc()">
                    </div>
                    <div class="change-row">
                        <span class="label">Change</span>
                        <span class="value" id="changeDisplay">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="order-actions" id="orderActionsSection" style="display:none;">
                <button class="btn-send-kitchen" id="btnSendKitchen" onclick="sendToKitchen()" disabled>
                    <i class="bi bi-fire"></i> Send to Kitchen
                </button>
                <button class="btn-settle" id="btnSettle" onclick="settleOrder()" disabled>
                    <i class="bi bi-cash-coin"></i> Settle Bill
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ══ New Order Modal ══ -->
<div class="modal fade" id="newOrderModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;">
            <div class="modal-header" style="background:var(--charcoal);border:none;padding:16px 22px;">
                <h5 class="modal-title" style="font-weight:800;color:#fff;font-size:1rem;">
                    <i class="bi bi-plus-circle me-2" style="color:var(--orange);"></i>Open New Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:22px;">
                <div class="new-order-form">
                    <label class="form-label-sm">Order Type</label>
                    <div class="order-type-tabs" style="background:var(--s-bg);padding:4px;border-radius:10px;display:flex;gap:4px;margin-bottom:14px;">
                        <button class="order-type-tab active" data-type="Dine-In" onclick="modalSetType(this)" style="flex:1;justify-content:center;">
                            <i class="bi bi-people"></i> Dine-In
                        </button>
                        <button class="order-type-tab" data-type="Takeout" onclick="modalSetType(this)" style="flex:1;justify-content:center;">
                            <i class="bi bi-bag"></i> Takeout
                        </button>
                        <button class="order-type-tab" data-type="Delivery" onclick="modalSetType(this)" style="flex:1;justify-content:center;">
                            <i class="bi bi-bicycle"></i> Delivery
                        </button>
                    </div>

                    <div id="tablePickerSection">
                        <label class="form-label-sm">Table</label>
                        <select id="modalTableSelect" class="form-select">
                            <option value="">-- Select Table --</option>
                            <?php
                            $sections = [];
                            foreach($tables as $t) {
                                $sections[$t['section']][] = $t;
                            }
                            foreach($sections as $sec => $tbls): ?>
                            <optgroup label="<?= htmlspecialchars($sec) ?>">
                                <?php foreach($tbls as $t): ?>
                                <option value="<?= $t['tableID'] ?>"
                                    <?= $t['status'] !== 'Available' ? 'disabled' : '' ?>>
                                    Table <?= htmlspecialchars($t['tableNo']) ?>
                                    (<?= $t['capacity'] ?> pax)
                                    <?= $t['status'] !== 'Available' ? '— ' . $t['status'] : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <label class="form-label-sm" style="margin-top:12px;">Number of Guests (Pax)</label>
                    <input type="number" id="modalPax" class="form-control" value="1" min="1" max="99">

                    <label class="form-label-sm" style="margin-top:12px;">Order Notes (optional)</label>
                    <input type="text" id="modalNotes" class="form-control" placeholder="e.g. No pork, allergy info…">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:14px 22px;">
                <button type="button" class="btn-rpos-ghost" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-rpos-primary" onclick="createOrder()">
                    <i class="bi bi-check2-circle"></i> Open Order
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Item Note Modal ══ -->
<div class="modal fade" id="itemNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;">
            <div class="modal-header" style="background:var(--charcoal);border:none;padding:14px 20px;">
                <h6 class="modal-title" style="font-weight:800;color:#fff;">
                    <i class="bi bi-pencil-square me-2" style="color:var(--orange);"></i>Item Note
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px;">
                <p style="font-size:13px;color:var(--t-muted);margin-bottom:10px;">Add a special note for <strong id="noteItemName"></strong>:</p>
                <input type="hidden" id="noteItemId">
                <textarea id="noteTextarea" class="form-control" rows="3" placeholder="e.g. No onions, extra spicy, well done…" style="border:1.5px solid var(--s-border);border-radius:8px;font-size:13px;resize:none;font-family:var(--font-ui);outline:none;"></textarea>
            </div>
            <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:12px 20px;">
                <button class="btn-rpos-ghost" data-bs-dismiss="modal">Cancel</button>
                <button class="btn-rpos-primary" onclick="saveItemNote()">Save Note</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ Receipt Modal ══ -->
<div class="modal fade" id="receiptModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border:none;border-radius:18px;overflow:hidden;">
            <div class="modal-header" style="background:var(--c-success);color:#fff;border:none;padding:16px 22px;">
                <h5 class="modal-title" style="font-weight:800;">
                    <i class="bi bi-check-circle-fill me-2"></i>Order Settled!
                </h5>
            </div>
            <div class="modal-body" id="receiptBody" style="padding:20px;"></div>
            <div class="modal-footer" style="border-top:1.5px solid var(--s-border);padding:14px 22px;">
                <button class="btn-rpos-ghost" onclick="printReceipt()">
                    <i class="bi bi-printer me-1"></i>Print
                </button>
                <button class="btn-rpos-primary" onclick="afterSettle()">
                    <i class="bi bi-plus-circle me-1"></i>New Order
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ════════════════════════════════════════════════════
//  RESTAURANT POS — STATE & CORE LOGIC
// ════════════════════════════════════════════════════
let currentOrder = null;   // { orderID, orderType, tableID, tableNo, pax, status }
let orderItems   = [];     // [{ tempId, id, name, price, qty, note }]
let pendingOrderType = 'Dine-In';
let isProcessing = false;

// ── Helpers ──
const $ = id => document.getElementById(id);
const fmt = n => '₱' + parseFloat(n||0).toFixed(2);
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function post(url, data) {
    data.csrf_token = csrf();
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k, v));
    return fetch(url, { method: 'POST', body: fd }).then(r => r.json());
}

// ── Order Type Selection (action bar) ──
function setOrderType(btn) {
    document.querySelectorAll('.rpos-actionbar .order-type-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    pendingOrderType = btn.dataset.type;
}

// ── Order Type Selection (modal) ──
function modalSetType(btn) {
    document.querySelectorAll('#newOrderModal .order-type-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const type = btn.dataset.type;
    $('tablePickerSection').style.display = type === 'Dine-In' ? '' : 'none';
}

function openNewOrderModal() {
    const modal = new bootstrap.Modal($('newOrderModal'));
    // Sync selected type
    document.querySelectorAll('#newOrderModal .order-type-tab').forEach(b => {
        b.classList.toggle('active', b.dataset.type === pendingOrderType);
    });
    $('tablePickerSection').style.display = pendingOrderType === 'Dine-In' ? '' : 'none';
    $('modalPax').value = '1';
    $('modalNotes').value = '';
    modal.show();
}

// ── Create Order ──
function createOrder() {
    if(isProcessing) return;
    const type = document.querySelector('#newOrderModal .order-type-tab.active').dataset.type;
    const tableID = type === 'Dine-In' ? $('modalTableSelect').value : '';
    const pax   = parseInt($('modalPax').value) || 1;
    const notes = $('modalNotes').value.trim();

    if(type === 'Dine-In' && !tableID) {
        Swal.fire({ icon: 'warning', title: 'Select a table', text: 'Dine-In orders require a table.', timer: 2000, showConfirmButton: false });
        return;
    }

    isProcessing = true;
    post('../backend/orderAuth.php', { openOrder: 1, orderType: type, tableID, pax, customerID: 0, notes })
    .then(data => {
        isProcessing = false;
        if(data.success) {
            bootstrap.Modal.getInstance($('newOrderModal')).hide();
            const tableSel = $('modalTableSelect');
            const tableNo = tableID ? tableSel.options[tableSel.selectedIndex].text.replace(/Table\s+/, '').split(' ')[0] : '';
            currentOrder = { orderID: data.orderID, orderType: type, tableID, tableNo, pax, status: 'Open' };
            orderItems = [];
            renderOrder();
            addOrderChip(data.orderID, type, tableNo);
            Swal.fire({ icon: 'success', title: 'Order #' + data.orderID + ' Opened!', timer: 1200, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(() => { isProcessing = false; Swal.fire({ icon: 'error', title: 'Network error' }); });
}

// ── Load existing order ──
function loadOrder(orderID) {
    fetch('../backend/getOrderDetails.php?orderID=' + orderID + '&format=json')
    .then(r => r.json())
    .then(data => {
        if(!data.success) { Swal.fire({ icon: 'error', title: 'Could not load order', text: data.message }); return; }
        currentOrder = {
            orderID: data.order.orderID,
            orderType: data.order.orderType,
            tableID: data.order.tableID,
            tableNo: data.order.tableNo,
            pax: data.order.pax,
            status: data.order.status
        };
        orderItems = (data.items || []).map((it, i) => ({
            tempId: 'srv_' + it.orderItemID,
            orderItemID: it.orderItemID,
            id: it.itemID,
            name: it.itemName,
            price: parseFloat(it.price),
            qty: parseInt(it.quantity),
            note: it.notes || '',
            status: it.status
        }));
        // Highlight active chip
        document.querySelectorAll('.open-order-chip').forEach(c => {
            c.classList.toggle('active-chip', parseInt(c.dataset.orderId) === parseInt(orderID));
        });
        renderOrder();
    });
}

// ── Add item to local order ──
function addToOrder(item) {
    if(!currentOrder) {
        Swal.fire({ icon: 'info', title: 'No Open Order', text: 'Open a new order first by clicking "New Order".', confirmButtonColor: 'var(--charcoal)' });
        return;
    }
    if(currentOrder.status === 'Paid') {
        Swal.fire({ icon: 'warning', title: 'Order is closed', text: 'This order is already paid.' }); return;
    }

    const existing = orderItems.find(i => i.id === item.id && !i.orderItemID);
    if(existing) { existing.qty++; }
    else {
        orderItems.push({
            tempId: 'tmp_' + Date.now() + '_' + Math.random(),
            id: item.id, name: item.name, price: item.price,
            qty: 1, note: '', status: 'Pending'
        });
    }
    renderOrderItems();
    recalc();
}

function changeQty(tempId, delta) {
    const item = orderItems.find(i => i.tempId === tempId);
    if(!item) return;
    item.qty = Math.max(0, item.qty + delta);
    if(item.qty === 0) {
        // If it's a server-synced item, ask to void
        if(item.orderItemID) { promptVoidItem(tempId); return; }
        orderItems = orderItems.filter(i => i.tempId !== tempId);
    }
    renderOrderItems();
    recalc();
}

function promptVoidItem(tempId) {
    const item = orderItems.find(i => i.tempId === tempId);
    if(!item) return;
    Swal.fire({
        title: 'Void Item?',
        input: 'text',
        inputPlaceholder: 'Reason for void (required)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--c-danger)',
        confirmButtonText: 'Void Item',
        preConfirm: (reason) => {
            if(!reason.trim()) { Swal.showValidationMessage('Please enter a reason'); return false; }
            return reason;
        }
    }).then(r => {
        if(r.isConfirmed) {
            post('../backend/orderAuth.php', { voidItem: 1, orderItemID: item.orderItemID, reason: r.value })
            .then(d => {
                if(d.success) {
                    orderItems = orderItems.filter(i => i.tempId !== tempId);
                    renderOrderItems(); recalc();
                    Swal.fire({ icon: 'success', title: 'Item voided', timer: 1000, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: d.message });
                }
            });
        } else {
            // Restore qty
            if(item) item.qty = 1;
            renderOrderItems(); recalc();
        }
    });
}

function openItemNote(tempId) {
    const item = orderItems.find(i => i.tempId === tempId);
    if(!item) return;
    $('noteItemId').value = tempId;
    $('noteItemName').textContent = item.name;
    $('noteTextarea').value = item.note || '';
    new bootstrap.Modal($('itemNoteModal')).show();
}

function saveItemNote() {
    const tempId = $('noteItemId').value;
    const note   = $('noteTextarea').value.trim();
    const item   = orderItems.find(i => i.tempId === tempId);
    if(item) item.note = note;
    bootstrap.Modal.getOrCreateInstance($('itemNoteModal')).hide();
    renderOrderItems();
}

// ── Render ──
function renderOrder() {
    if(!currentOrder) {
        $('orderTitle').textContent = 'Current Order';
        $('orderTypeDisplay').textContent = '—';
        $('orderTableDisplay').textContent = '—';
        $('orderPaxDisplay').textContent = '—';
        $('orderStatusDisplay').textContent = '—';
        $('orderSummarySection').style.display = 'none';
        $('orderActionsSection').style.display = 'none';
        $('orderItems').innerHTML = `<div class="order-empty" id="orderEmpty">
            <i class="bi bi-clipboard-plus"></i>
            <span>No order open</span>
            <small>Start a New Order or tap an open order above</small>
        </div>`;
        return;
    }

    const o = currentOrder;
    $('orderTitle').textContent = 'Order #' + o.orderID;
    $('orderTypeDisplay').textContent = o.orderType;
    $('orderTableDisplay').textContent = o.tableNo ? 'Table ' + o.tableNo : '#' + o.orderID;
    $('orderPaxDisplay').textContent = o.pax + ' pax';
    $('orderStatusDisplay').textContent = o.status;
    $('orderSummarySection').style.display = '';
    $('orderActionsSection').style.display = '';
    renderOrderItems();
    recalc();
}

function renderOrderItems() {
    const container = $('orderItems');
    if(!orderItems.length) {
        container.innerHTML = `<div class="order-empty" style="padding:30px 20px;">
            <i class="bi bi-bag-plus" style="font-size:2rem;opacity:.3;"></i>
            <span style="font-size:12.5px;color:var(--t-muted);">Tap menu items to add them</span>
        </div>`;
        updateActionBtns();
        return;
    }

    let html = '';
    orderItems.forEach(item => {
        const isVoided = item.status === 'Void';
        const isSent   = item.orderItemID && item.status !== 'Void';
        html += `<div class="order-item" ${isVoided ? 'style="opacity:.4;"' : ''}>
            <div style="flex:1;min-width:0;">
                <div class="oi-name">${escHtml(item.name)}
                    ${isSent ? '<i class="bi bi-fire" style="color:var(--orange);font-size:10px;margin-left:4px;" title="Sent to kitchen"></i>' : ''}
                    ${isVoided ? '<span style="color:var(--c-danger);font-size:10px;font-weight:800;"> VOID</span>' : ''}
                </div>
                ${item.note ? `<div class="item-note-badge" title="${escHtml(item.note)}">${escHtml(item.note)}</div>` : ''}
            </div>
            <div class="qty-ctrl" ${isVoided ? 'style="pointer-events:none;"' : ''}>
                <button class="qty-btn" onclick="changeQty('${item.tempId}',-1)">−</button>
                <span class="qty-val">${item.qty}</span>
                <button class="qty-btn" onclick="changeQty('${item.tempId}',1)">+</button>
            </div>
            <div class="oi-total">₱${(item.price * item.qty).toFixed(2)}</div>
            <button class="btn-void-item" onclick="${isVoided ? '' : "openItemNote('" + item.tempId + "')"}" title="Add note" ${isVoided ? 'disabled' : ''}>
                <i class="bi bi-chat-left-dots"></i>
            </button>
        </div>`;
    });
    container.innerHTML = html;
    updateActionBtns();
}

function updateActionBtns() {
    if(!currentOrder) return;
    const hasItems = orderItems.some(i => i.status !== 'Void');
    const unsent   = orderItems.some(i => !i.orderItemID && i.qty > 0);
    $('btnSendKitchen').disabled = !unsent || currentOrder.status === 'Paid';
    $('btnSettle').disabled = !hasItems || currentOrder.status === 'Paid';
}

function recalc() {
    const activeItems = orderItems.filter(i => i.status !== 'Void');
    const subtotal  = activeItems.reduce((s, i) => s + i.price * i.qty, 0);
    const svcPct    = $('svcChargeCheck') && $('svcChargeCheck').checked ? 0.10 : 0;
    const svcAmt    = subtotal * svcPct;
    const discVal   = $('discountSelect') ? $('discountSelect').value : '0';
    const discPct   = discVal === 'pwd' ? 20 : parseFloat(discVal);
    const discAmt   = subtotal * discPct / 100;
    const total     = subtotal + svcAmt - discAmt;
    const tendered  = parseFloat($('tenderedInput') && $('tenderedInput').value) || 0;
    const change    = Math.max(0, tendered - total);

    if($('subtotalDisplay')) $('subtotalDisplay').textContent = fmt(subtotal);
    if($('svcChargeDisplay')) $('svcChargeDisplay').textContent = fmt(svcAmt);
    if($('discountDisplay')) $('discountDisplay').textContent = '−' + fmt(discAmt);
    if($('totalDisplay')) $('totalDisplay').textContent = fmt(total);
    if($('changeDisplay')) $('changeDisplay').textContent = fmt(change);
    updateActionBtns();
}

function toggleCreditWarn() {
    // Credit payment removed - walk-in only
}

// ── Menu Filter ──
function filterMenu() {
    const q   = $('menuSearch').value.toLowerCase().trim();
    const cat = document.querySelector('.cat-pill.active').dataset.cat;
    document.querySelectorAll('.menu-card').forEach(card => {
        const matchCat  = cat === 'All' || card.dataset.cat === cat;
        const matchText = !q || card.dataset.name.toLowerCase().includes(q);
        card.style.display = (matchCat && matchText) ? '' : 'none';
    });
}
function selectCat(pill) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
    pill.classList.add('active');
    filterMenu();
}

// ── Send to Kitchen ──
function sendToKitchen() {
    if(!currentOrder || isProcessing) return;
    const unsent = orderItems.filter(i => !i.orderItemID && i.qty > 0);
    if(!unsent.length) return;

    isProcessing = true;
    $('btnSendKitchen').disabled = true;
    $('btnSendKitchen').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending…';

    const items = unsent.map(i => ({ itemID: i.id, quantity: i.qty, notes: i.note }));

    post('../backend/orderAuth.php', {
        sendToKitchen: 1,
        orderID: currentOrder.orderID,
        items: JSON.stringify(items)
    })
    .then(data => {
        isProcessing = false;
        $('btnSendKitchen').innerHTML = '<i class="bi bi-fire"></i> Send to Kitchen';
        if(data.success) {
            // Assign server IDs
            if(data.itemIDs) {
                data.itemIDs.forEach((sid, idx) => {
                    if(unsent[idx]) {
                        unsent[idx].orderItemID = sid;
                        unsent[idx].status = 'Sent';
                    }
                });
            } else {
                unsent.forEach(i => { i.orderItemID = 'synced'; i.status = 'Sent'; });
            }
            currentOrder.status = 'Active';
            renderOrderItems(); recalc();
            Swal.fire({ icon: 'success', title: '🔥 Sent to Kitchen!', timer: 1200, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
        updateActionBtns();
    })
    .catch(() => {
        isProcessing = false;
        $('btnSendKitchen').innerHTML = '<i class="bi bi-fire"></i> Send to Kitchen';
        Swal.fire({ icon: 'error', title: 'Network error' });
    });
}

// ── Settle Order ──
function settleOrder() {
    if(!currentOrder || isProcessing) return;

    const method    = $('paymentMethod').value;
    const discVal   = $('discountSelect').value;
    const discPct   = discVal === 'pwd' ? 20 : parseFloat(discVal);
    const activeItems = orderItems.filter(i => i.status !== 'Void');
    const subtotal  = activeItems.reduce((s, i) => s + i.price * i.qty, 0);
    const svcPct    = $('svcChargeCheck') && $('svcChargeCheck').checked ? 0.10 : 0;
    const svcAmt    = subtotal * svcPct;
    const discAmt   = subtotal * discPct / 100;
    const total     = subtotal + svcAmt - discAmt;
    const tendered  = parseFloat($('tenderedInput').value) || 0;
    const change    = Math.max(0, tendered - total);

    if(tendered < total) {
        Swal.fire({ icon: 'warning', title: 'Insufficient payment', text: `Tendered (${fmt(tendered)}) is less than total (${fmt(total)}).` }); return;
    }
    if(!activeItems.length) {
        Swal.fire({ icon: 'warning', title: 'Empty order', text: 'Please add items before settling.' }); return;
    }

    // If there are unsent items, auto-send them first
    const unsent = orderItems.filter(i => !i.orderItemID && i.qty > 0);
    if(unsent.length) {
        Swal.fire({
            title: 'Send items to kitchen first?',
            text: `${unsent.length} item(s) haven't been sent to the kitchen yet.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Send & Settle',
            cancelButtonText: 'Settle Without Sending',
            confirmButtonColor: 'var(--orange)'
        }).then(r => {
            if(r.isConfirmed) {
                sendToKitchen();
                setTimeout(() => doSettle(method, 0, discVal, discAmt, svcAmt, total, tendered, change), 800);
            } else {
                doSettle(method, 0, discVal, discAmt, svcAmt, total, tendered, change);
            }
        });
        return;
    }
    doSettle(method, 0, discVal, discAmt, svcAmt, total, tendered, change);
}

function doSettle(method, custID, discVal, discAmt, svcAmt, total, tendered, change) {
    isProcessing = true;
    $('btnSettle').disabled = true;
    $('btnSettle').innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Settling…';

    const subAmt = orderItems.reduce((s,i) => s + (i.qty * i.price), 0);
    post('../backend/orderAuth.php', {
        settleOrder: 1,
        orderID: currentOrder.orderID,
        payment_method: method,
        customerID: custID || '0',
        discount_type: $('discountSelect').value,
        discount_amount: discAmt.toFixed(2),
        service_charge: svcAmt.toFixed(2),
        subtotal: subAmt.toFixed(2),
        total_amount: total.toFixed(2),
        amount_tendered: method === 'Credit' ? '0' : tendered.toFixed(2),
        payment: method === 'Credit' ? '0' : tendered.toFixed(2),
        change_amount: change.toFixed(2)
    })
    .then(data => {
        isProcessing = false;
        $('btnSettle').innerHTML = '<i class="bi bi-cash-coin"></i> Settle Bill';
        if(data.success) {
            showReceipt(data.orderID || currentOrder.orderID, total, discAmt, svcAmt, tendered, change, method);
            removeOrderChip(currentOrder.orderID);
        } else {
            $('btnSettle').disabled = false;
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    })
    .catch(() => {
        isProcessing = false;
        $('btnSettle').innerHTML = '<i class="bi bi-cash-coin"></i> Settle Bill';
        $('btnSettle').disabled = false;
        Swal.fire({ icon: 'error', title: 'Network error' });
    });
}

// ── Receipt ──
function showReceipt(orderID, total, discAmt, svcAmt, tendered, change, method) {
    const storeName    = localStorage.getItem('ev_store_name') || 'Restaurant POS';
    const storeTagline = localStorage.getItem('ev_store_tagline') || '';
    const footer       = localStorage.getItem('ev_receipt_footer') || 'Thank you! Please come again!';
    const storedLogo   = localStorage.getItem('ev_store_logo');
    const logoHtml     = storedLogo ? `<div class="receipt-logo-wrap"><img src="${storedLogo}"></div>` : '';
    const tagHtml      = storeTagline ? `<div class="receipt-sub">${storeTagline}</div>` : '';

    const orderType = currentOrder ? currentOrder.orderType : '';
    const tableRef  = currentOrder ? (currentOrder.tableNo ? 'Table ' + currentOrder.tableNo : '#' + currentOrder.orderID) : '';
    const activeItems = orderItems.filter(i => i.status !== 'Void');
    const subtotal = activeItems.reduce((s, i) => s + i.price * i.qty, 0);

    let itemsHtml = '';
    activeItems.forEach(item => {
        itemsHtml += `<div class="receipt-item">
            <span>${escHtml(item.name)} ×${item.qty}</span>
            <span>₱${(item.price * item.qty).toFixed(2)}</span>
        </div>`;
    });

    const custName = 'Walk-in';

    $('receiptBody').innerHTML = `
    <div class="receipt-box">
        ${logoHtml}
        <div class="receipt-title">${escHtml(storeName)}</div>
        ${tagHtml}
        <div class="receipt-sub">Order #${orderID} &nbsp;|&nbsp; ${new Date().toLocaleString('en-PH',{dateStyle:'medium',timeStyle:'short'})}</div>
        <div class="receipt-type-badge"><span>${orderType}${tableRef ? ' — ' + tableRef : ''}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Guest:</span><span>${escHtml(custName)}</span></div>
        <div class="receipt-row"><span>Pax:</span><span>${currentOrder ? currentOrder.pax : '—'}</span></div>
        <div class="receipt-divider"></div>
        ${itemsHtml}
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Subtotal:</span><span>₱${subtotal.toFixed(2)}</span></div>
        ${svcAmt > 0 ? `<div class="receipt-row"><span>Service Charge:</span><span>₱${svcAmt.toFixed(2)}</span></div>` : ''}
        ${discAmt > 0 ? `<div class="receipt-row"><span>Discount:</span><span style="color:#e8435a;">−₱${discAmt.toFixed(2)}</span></div>` : ''}
        <div class="receipt-row receipt-grand"><span>TOTAL:</span><span>₱${total.toFixed(2)}</span></div>
        <div class="receipt-divider"></div>
        <div class="receipt-row"><span>Payment (${method}):</span><span>₱${tendered.toFixed(2)}</span></div>
        <div class="receipt-row"><span>Change:</span><span>₱${change.toFixed(2)}</span></div>
        <div class="receipt-divider"></div>
        <div style="text-align:center;font-size:10px;color:#aaa;margin-top:4px;">${escHtml(footer)}</div>
    </div>`;

    new bootstrap.Modal($('receiptModal')).show();
}

function printReceipt() {
    const content = $('receiptBody').innerHTML;
    const w = window.open('', '_blank', 'width=360,height=650');
    w.document.write(`<html><head><title>Receipt</title><style>
        body{font-family:'Courier New',monospace;font-size:12px;padding:16px;max-width:300px;margin:0 auto;}
        .receipt-row,.receipt-item{display:flex;justify-content:space-between;padding:2px 0;}
        .receipt-title{text-align:center;font-weight:900;font-size:.95rem;letter-spacing:2px;}
        .receipt-sub{text-align:center;color:#888;font-size:10px;}
        .receipt-type-badge{text-align:center;margin:4px 0;font-weight:700;}
        .receipt-divider{border-top:1px dashed #ccc;margin:5px 0;}
        .receipt-grand{font-weight:900;font-size:1rem;border-top:2px solid #333;padding-top:3px;}
        .receipt-logo-wrap{text-align:center;margin-bottom:6px;}
        .receipt-logo-wrap img{max-height:48px;max-width:120px;object-fit:contain;}
    </style></head><body>${content}
</body></html>`);
    w.document.close(); w.print();
}

function afterSettle() {
    bootstrap.Modal.getOrCreateInstance($('receiptModal')).hide();
    currentOrder = null;
    orderItems   = [];
    if($('discountSelect')) $('discountSelect').value = '0';
    if($('paymentMethod'))  $('paymentMethod').value  = 'Cash';
    if($('tenderedInput'))  $('tenderedInput').value  = '';
    if($('svcChargeCheck')) $('svcChargeCheck').checked = false;
    renderOrder();
}

// ── Order Chips ──
function addOrderChip(orderID, type, tableNo) {
    const chips = $('openOrderChips');
    const noMsg = chips.querySelector('span');
    if(noMsg) noMsg.remove();

    const chip = document.createElement('div');
    chip.className = 'open-order-chip active-chip';
    chip.dataset.orderId = orderID;
    chip.innerHTML = `<span class="chip-dot"></span>
        <i class="bi bi-${type === 'Dine-In' ? 'grid-3x3-gap' : (type === 'Takeout' ? 'bag' : 'bicycle')}" style="font-size:11px;"></i>
        ${tableNo ? 'T' + tableNo : '#' + orderID}
        <span style="font-size:10px;color:var(--t-muted);font-weight:600;">0 itm</span>`;
    chip.onclick = () => loadOrder(orderID);

    document.querySelectorAll('.open-order-chip').forEach(c => c.classList.remove('active-chip'));
    chips.prepend(chip);
}

function removeOrderChip(orderID) {
    const chip = $('openOrderChips').querySelector(`[data-order-id="${orderID}"]`);
    if(chip) chip.remove();
}

// ── Utils ──
function clearOrder() {
    if(!currentOrder) return;
    Swal.fire({
        title: 'Clear current view?',
        text: 'This will only clear your view. The order remains open.',
        icon: 'question', showCancelButton: true,
        confirmButtonText: 'Yes, clear view'
    }).then(r => {
        if(r.isConfirmed) {
            currentOrder = null; orderItems = [];
            document.querySelectorAll('.open-order-chip').forEach(c => c.classList.remove('active-chip'));
            renderOrder();
        }
    });
}

function escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

// Keyboard shortcut: Enter key in tendered input → settle
document.addEventListener('DOMContentLoaded', () => {
    const tin = $('tenderedInput');
    if(tin) tin.addEventListener('keydown', e => { if(e.key === 'Enter') settleOrder(); });

    // Auto-load order if ?loadOrder=X is in URL (e.g. coming from Table Map)
    const urlParams = new URLSearchParams(window.location.search);
    const autoLoad  = urlParams.get('loadOrder');
    if(autoLoad) loadOrder(parseInt(autoLoad));
});
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>

<?php include 'footer.php'; ?>
