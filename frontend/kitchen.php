<?php
require_once '../backend/database.php';
require_once '../backend/pusher.php';
if(!isset($_SESSION['userID'])){ header("Location: ../index.php"); exit(); }
if(!in_array($_SESSION['roleName'], ['Admin','Cashier','Kitchen'])){
    header("Location: dashboard.php"); exit();
}
$pageTitle = "Kitchen Display – Restaurant POS";

// Orders with items sent to kitchen (not yet served)
$kitchenResult = $conn->query("
    SELECT o.orderID, o.orderType, o.pax, o.notes AS orderNotes, o.dateCreated AS created_at,
           dt.tableNo, dt.section,
           oi.orderItemID, oi.itemID, oi.quantity, oi.notes AS itemNotes, oi.item_status AS itemStatus,
           oi.dateAdded AS sent_at, NULL AS ready_at,
           mi.itemName
    FROM orders o
    LEFT JOIN dining_table dt ON o.tableID = dt.tableID
    JOIN order_items oi ON o.orderID = oi.orderID
    JOIN menu_item mi ON oi.itemID = mi.itemID
    WHERE o.status IN ('Open','Active')
      AND oi.item_status IN ('Sent','Ready')
    ORDER BY oi.dateAdded ASC
");

$tickets = [];
while($row = $kitchenResult->fetch_assoc()) {
    $oid = $row['orderID'];
    if(!isset($tickets[$oid])) {
        $tickets[$oid] = [
            'orderID'    => $row['orderID'],
            'orderType'  => $row['orderType'],
            'pax'        => $row['pax'],
            'orderNotes' => $row['orderNotes'],
            'created_at' => $row['created_at'],
            'tableNo'    => $row['tableNo'],
            'section'    => $row['section'],
            'items'      => []
        ];
    }
    $tickets[$oid]['items'][] = [
        'orderItemID' => $row['orderItemID'],
        'itemName'    => $row['itemName'],
        'quantity'    => $row['quantity'],
        'itemNotes'   => $row['itemNotes'],
        'status'      => $row['itemStatus'],
        'sent_at'     => $row['sent_at'],
        'ready_at'    => $row['ready_at']
    ];
}
?>
<?php include 'header.php'; ?>
<?php include 'nav.php'; ?>

<style>
/* ══ Kitchen Display ══ */
.kds-body {
    padding: 20px 24px;
    background: var(--s-bg);
    min-height: calc(100vh - var(--topbar-h));
}

.kds-status-tabs {
    display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;
}
.kds-tab {
    padding: 7px 20px; border-radius: 20px;
    border: 2px solid var(--s-border);
    background: var(--c-white); font-size: 12.5px; font-weight: 700;
    color: var(--t-mid); cursor: pointer; transition: all .18s;
}
.kds-tab.active { background: var(--charcoal); color: var(--orange); border-color: var(--charcoal); }
.kds-tab:hover:not(.active) { border-color: var(--charcoal); color: var(--charcoal); }

.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 16px;
    align-items: start;
}

.ticket {
    background: var(--c-white);
    border-radius: var(--r-lg);
    border: 2px solid var(--s-border);
    box-shadow: var(--sh-sm);
    overflow: hidden;
    transition: border-color .2s;
}
.ticket.has-ready { border-color: var(--c-success); }
.ticket.all-ready { border-color: var(--c-success); background: var(--c-success-bg); }

.ticket-header {
    padding: 12px 16px;
    background: var(--charcoal);
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px;
}
.ticket-table {
    font-size: 1.1rem; font-weight: 900; color: #fff;
    display: flex; align-items: center; gap: 6px;
}
.ticket-meta { font-size: 10.5px; color: rgba(255,255,255,.55); }
.ticket-timer {
    font-size: 12px; font-weight: 800; color: var(--orange);
    font-family: var(--font-mono);
    background: rgba(0,0,0,.25); padding: 3px 8px; border-radius: 6px;
}
.ticket-timer.overdue { color: #ff8080; }

.ticket-type-pill {
    font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 10px;
    letter-spacing: .5px; text-transform: uppercase;
}
.type-dine { background: rgba(59,130,246,.25); color: #93c5fd; }
.type-take { background: rgba(34,200,122,.25); color: #6ee7b7; }
.type-del  { background: rgba(239,130,13,.25); color: #fcd34d; }

.ticket-order-note {
    margin: 0 12px;
    padding: 6px 10px;
    background: var(--c-warning-bg);
    border-left: 3px solid var(--c-warning);
    border-radius: 0 6px 6px 0;
    font-size: 11px; font-weight: 600; color: #78350f;
    margin-top: 8px;
}

.ticket-items { padding: 8px 0; }
.ticket-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 8px 16px;
    border-bottom: 1px solid var(--s-border);
    transition: background .15s;
}
.ticket-item:last-child { border-bottom: none; }
.ticket-item.item-ready { background: var(--c-success-bg); }

.item-qty {
    width: 30px; height: 30px; flex-shrink: 0;
    background: var(--charcoal); color: var(--orange);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 900; font-family: var(--font-mono);
}
.item-ready .item-qty { background: var(--c-success); color: #fff; }

.item-info { flex: 1; min-width: 0; }
.item-name { font-size: 13.5px; font-weight: 800; color: var(--t-main); }
.item-note { font-size: 11px; color: var(--orange); font-weight: 700; margin-top: 2px; }

.item-status-icon { font-size: 1.1rem; color: var(--c-success); }

.ticket-actions {
    padding: 12px 16px;
    display: flex; gap: 8px;
    background: rgba(0,0,0,.03);
    border-top: 1.5px solid var(--s-border);
}
.btn-mark-ready {
    flex: 1;
    background: var(--c-success); color: #fff;
    border: none; border-radius: var(--r-sm);
    padding: 8px; font-size: 12px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.btn-mark-ready:hover { filter: brightness(1.1); }
.btn-mark-served {
    flex: 1;
    background: var(--charcoal); color: var(--orange);
    border: none; border-radius: var(--r-sm);
    padding: 8px; font-size: 12px; font-weight: 800;
    cursor: pointer; transition: all .2s;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.btn-mark-served:hover { background: var(--charcoal-mid); }

.kds-empty {
    grid-column: 1/-1;
    text-align: center; padding: 80px 20px;
    color: var(--t-muted);
}
.kds-empty i { font-size: 4rem; opacity: .15; }
.kds-empty p { margin-top: 12px; font-size: 15px; font-weight: 600; }

/* Live pulse */
@keyframes pulse-border {
    0%,100% { border-color: var(--orange); }
    50% { border-color: #ffa570; }
}
.ticket.new { animation: pulse-border 1.5s ease infinite; }
</style>

<?php
$topbarTitle = 'Kitchen Display';
$topbarIcon  = 'bi-fire';
$topbarSub   = 'Live order queue';
$topbarExtra = '<button onclick="location.reload()" class="btn-rpos-ghost" style="font-size:12px;padding:6px 14px;"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>';
include 'topbar.php';
?>

<div class="page-body kds-body">

    <div class="kds-status-tabs">
        <button class="kds-tab active" onclick="filterTickets('all',this)">All Tickets</button>
        <button class="kds-tab" onclick="filterTickets('Sent',this)">
            <i class="bi bi-hourglass-split me-1"></i>In Progress
        </button>
        <button class="kds-tab" onclick="filterTickets('Ready',this)">
            <i class="bi bi-check-circle me-1"></i>Ready to Serve
        </button>
    </div>

    <div class="tickets-grid" id="ticketsGrid">
        <?php if(empty($tickets)): ?>
        <div class="kds-empty">
            <i class="bi bi-fire"></i>
            <p>No active kitchen tickets.</p>
            <small>Orders sent from the POS will appear here.</small>
        </div>
        <?php endif; ?>

        <?php foreach($tickets as $ticket):
            $hasReady  = false;
            $allReady  = true;
            foreach($ticket['items'] as $it) {
                if($it['status'] === 'Ready') $hasReady = true;
                else $allReady = false;
            }
            $ticketClass = $allReady ? 'all-ready' : ($hasReady ? 'has-ready' : '');
            $typeClass   = match($ticket['orderType']) {
                'Dine-In'  => 'type-dine',
                'Takeout'  => 'type-take',
                'Delivery' => 'type-del',
                default    => 'type-dine'
            };
            $typeIcon = match($ticket['orderType']) {
                'Dine-In'  => 'bi-people-fill',
                'Takeout'  => 'bi-bag-fill',
                'Delivery' => 'bi-bicycle',
                default    => 'bi-people-fill'
            };
            $elapsed = time() - strtotime($ticket['created_at']);
            $mins    = floor($elapsed / 60);
            $secs    = $elapsed % 60;
            $overdue = $mins >= 20;
        ?>
        <div class="ticket <?= $ticketClass ?>"
             data-order="<?= $ticket['orderID'] ?>"
             data-status="<?= $allReady ? 'Ready' : 'Sent' ?>"
             data-created="<?= strtotime($ticket['created_at']) ?>">

            <div class="ticket-header">
                <div>
                    <div class="ticket-table">
                        <i class="bi <?= $typeIcon ?>"></i>
                        <?= $ticket['tableNo'] ? 'T' . htmlspecialchars($ticket['tableNo']) : '#' . $ticket['orderID'] ?>
                        <span class="ticket-type-pill <?= $typeClass ?>"><?= $ticket['orderType'] ?></span>
                    </div>
                    <div class="ticket-meta">Order #<?= $ticket['orderID'] ?> · <?= $ticket['pax'] ?> pax</div>
                </div>
                <div class="ticket-timer <?= $overdue ? 'overdue' : '' ?>">
                    <?= sprintf('%02d:%02d', $mins, $secs) ?>
                </div>
            </div>

            <?php if($ticket['orderNotes']): ?>
            <div class="ticket-order-note">
                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars($ticket['orderNotes']) ?>
            </div>
            <?php endif; ?>

            <div class="ticket-items">
                <?php foreach($ticket['items'] as $it):
                    $isReady = $it['status'] === 'Ready';
                ?>
                <div class="ticket-item <?= $isReady ? 'item-ready' : '' ?>" data-item-id="<?= $it['orderItemID'] ?>">
                    <div class="item-qty"><?= $it['quantity'] ?></div>
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($it['itemName']) ?></div>
                        <?php if($it['itemNotes']): ?>
                        <div class="item-note"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($it['itemNotes']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php if($isReady): ?>
                    <i class="bi bi-check-circle-fill item-status-icon"></i>
                    <?php else: ?>
                    <button style="background:none;border:none;cursor:pointer;color:var(--t-muted);font-size:13px;" onclick="markItemReady(<?= $it['orderItemID'] ?>, this)" title="Mark ready">
                        <i class="bi bi-circle"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ticket-actions">
                <?php if(!$allReady): ?>
                <button class="btn-mark-ready" onclick="markAllReady(<?= $ticket['orderID'] ?>)">
                    <i class="bi bi-check-all"></i> All Ready
                </button>
                <?php endif; ?>
                <?php if($hasReady || $allReady): ?>
                <button class="btn-mark-served" onclick="markServed(<?= $ticket['orderID'] ?>)">
                    <i class="bi bi-check2-circle"></i> Mark Served
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

function post(url, data) {
    data.csrf_token = csrf();
    const fd = new FormData();
    Object.entries(data).forEach(([k,v]) => fd.append(k,v));
    return fetch(url, { method:'POST', body:fd }).then(r => r.json());
}

function filterTickets(status, tab) {
    document.querySelectorAll('.kds-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    document.querySelectorAll('.ticket').forEach(ticket => {
        if(status === 'all') { ticket.style.display = ''; return; }
        const ts = ticket.dataset.status;
        ticket.style.display = (ts === status) ? '' : 'none';
    });
}

function markItemReady(orderItemID, btn) {
    post('../backend/orderAuth.php', { markReady: 1, orderItemID })
    .then(d => {
        if(d.success) {
            const row = btn.closest('.ticket-item');
            row.classList.add('item-ready');
            btn.outerHTML = '<i class="bi bi-check-circle-fill item-status-icon"></i>';
        } else {
            Swal.fire({ icon:'error', title:'Error', text: d.message });
        }
    });
}

function markAllReady(orderID) {
    // Collect all unready item IDs from this ticket and mark each ready
    const ticket = document.querySelector(`.ticket[data-order="${orderID}"]`);
    const btns = ticket ? ticket.querySelectorAll('[onclick^="markItemReady"]') : [];
    if (btns.length === 0) { location.reload(); return; }
    const promises = [...btns].map(btn => {
        const match = btn.getAttribute('onclick').match(/markItemReady\((\d+)/);
        if (!match) return Promise.resolve();
        return post('../backend/orderAuth.php', { markReady: 1, orderItemID: match[1] });
    });
    Promise.all(promises).then(() => location.reload());
}

function markServed(orderID) {
    // Mark all ready/sent items in this ticket as Served
    const ticket = document.querySelector(`.ticket[data-order="${orderID}"]`);
    const items = ticket ? ticket.querySelectorAll('.ticket-item[data-item-id]') : [];
    const ids = [...items].map(el => el.dataset.itemId).filter(Boolean);
    if (ids.length === 0) { location.reload(); return; }
    Promise.all(ids.map(id => post('../backend/orderAuth.php', { markServed: 1, orderItemID: id })))
        .then(() => {
            if(ticket) ticket.remove();
            Swal.fire({ icon:'success', title:'Order Served!', timer:1000, showConfirmButton:false });
        })
        .catch(() => location.reload());
}

// ── Live timers (real elapsed time without reload) ───────────────────────────
setInterval(() => {
    document.querySelectorAll('.ticket-timer').forEach(el => {
        const ticket = el.closest('.ticket');
        if (!ticket) return;
        const created = parseInt(ticket.dataset.created || '0', 10);
        if (!created) return;
        const elapsed = Math.floor((Date.now() / 1000) - created);
        const mins = Math.floor(elapsed / 60);
        const secs = elapsed % 60;
        el.textContent = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
        if (mins >= 20) el.classList.add('overdue');
        else            el.classList.remove('overdue');
    });
}, 1000);
</script>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
    const PUSHER_KEY     = '<?php echo defined("PUSHER_APP_KEY")     ? PUSHER_APP_KEY     : ""; ?>';
    const PUSHER_CLUSTER = '<?php echo defined("PUSHER_APP_CLUSTER") ? PUSHER_APP_CLUSTER : ""; ?>';
</script>
<script src="pusher-content/realtime.js"></script>

<script>
// ══════════════════════════════════════════════════════════════════════════════
// Kitchen Real-Time Pusher Handlers
// Handles kitchen-order (new ticket) and kitchen-update (item ready/served)
// without requiring a page reload.
// ══════════════════════════════════════════════════════════════════════════════
(function () {
    if (typeof PUSHER_KEY === 'undefined' || !PUSHER_KEY || PUSHER_KEY === 'YOUR_APP_KEY') return;

    const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
    const channel = pusher.subscribe('pos-channel');

    // ── Helpers ───────────────────────────────────────────────────────────────
    function getGrid() { return document.getElementById('ticketsGrid'); }

    function removeEmpty() {
        const empty = getGrid().querySelector('.kds-empty');
        if (empty) empty.remove();
    }

    function showEmptyIfNone() {
        const grid = getGrid();
        if (!grid.querySelector('.ticket')) {
            grid.innerHTML = `
                <div class="kds-empty">
                    <i class="bi bi-fire"></i>
                    <p>No active kitchen tickets.</p>
                    <small>Orders sent from the POS will appear here.</small>
                </div>`;
        }
    }

    function buildItemRow(item) {
        const isReady = item.status === 'Ready';
        return `
        <div class="ticket-item ${isReady ? 'item-ready' : ''}" data-item-id="${item.orderItemID}">
            <div class="item-qty">${item.quantity}</div>
            <div class="item-info">
                <div class="item-name">${item.itemName}</div>
                ${item.notes ? `<div class="item-note"><i class="bi bi-exclamation-circle me-1"></i>${item.notes}</div>` : ''}
            </div>
            ${isReady
                ? `<i class="bi bi-check-circle-fill item-status-icon"></i>`
                : `<button style="background:none;border:none;cursor:pointer;color:var(--t-muted);font-size:13px;"
                       onclick="markItemReady(${item.orderItemID}, this)" title="Mark ready">
                       <i class="bi bi-circle"></i>
                   </button>`
            }
        </div>`;
    }

    function buildTicket(d) {
        const typeMap  = { 'Dine-In': 'type-dine', 'Takeout': 'type-take', 'Delivery': 'type-del' };
        const iconMap  = { 'Dine-In': 'bi-people-fill', 'Takeout': 'bi-bag-fill', 'Delivery': 'bi-bicycle' };
        const typeClass = typeMap[d.orderType] || 'type-dine';
        const typeIcon  = iconMap[d.orderType] || 'bi-people-fill';
        const nowSec    = Math.floor(Date.now() / 1000);
        const label     = d.tableNo ? 'T' + d.tableNo : '#' + d.orderID;
        const itemsHTML = (d.items || []).map(buildItemRow).join('');

        return `
        <div class="ticket" data-order="${d.orderID}" data-status="Sent" data-created="${nowSec}">
            <div class="ticket-header">
                <div>
                    <div class="ticket-table">
                        <i class="bi ${typeIcon}"></i>
                        ${label}
                        <span class="ticket-type-pill ${typeClass}">${d.orderType}</span>
                    </div>
                    <div class="ticket-meta">Order #${d.orderID} · ${d.pax || 1} pax</div>
                </div>
                <div class="ticket-timer">00:00</div>
            </div>
            <div class="ticket-items">${itemsHTML}</div>
            <div class="ticket-actions">
                <button class="btn-mark-ready" onclick="markAllReady(${d.orderID})">
                    <i class="bi bi-check-all"></i> All Ready
                </button>
            </div>
        </div>`;
    }

    // ── kitchen-order: new ticket arrives ─────────────────────────────────────
    channel.bind('kitchen-order', function (d) {
        const grid = getGrid();

        // If ticket already exists (e.g. duplicate event), skip
        if (grid.querySelector(`.ticket[data-order="${d.orderID}"]`)) return;

        removeEmpty();
        grid.insertAdjacentHTML('afterbegin', buildTicket(d));

        // Flash highlight
        const newTicket = grid.querySelector(`.ticket[data-order="${d.orderID}"]`);
        if (newTicket) {
            newTicket.style.transition = 'box-shadow 0.4s';
            newTicket.style.boxShadow  = '0 0 0 4px var(--orange)';
            setTimeout(() => { newTicket.style.boxShadow = ''; }, 1500);
        }

        // Bell sound (if browser allows)
        try { new Audio('https://cdn.jsdelivr.net/gh/anars/blank-audio/250-milliseconds-of-silence.mp3').play(); } catch(_) {}
    });

    // ── kitchen-update: item marked ready or served ───────────────────────────
    channel.bind('kitchen-update', function (d) {
        const grid = getGrid();

        if (d.action === 'ready') {
            const itemRow = grid.querySelector(`.ticket-item[data-item-id="${d.orderItemID}"]`);
            if (!itemRow) return;
            itemRow.classList.add('item-ready');
            const btn = itemRow.querySelector('button');
            if (btn) btn.outerHTML = '<i class="bi bi-check-circle-fill item-status-icon"></i>';

            // Update parent ticket status
            const ticket = itemRow.closest('.ticket');
            if (ticket) {
                const allReady = [...ticket.querySelectorAll('.ticket-item')].every(r => r.classList.contains('item-ready'));
                if (allReady) {
                    ticket.classList.add('all-ready');
                    ticket.dataset.status = 'Ready';
                    const actions = ticket.querySelector('.ticket-actions');
                    if (actions) actions.innerHTML = `
                        <button class="btn-mark-served" onclick="markServed(${ticket.dataset.order})">
                            <i class="bi bi-check2-circle"></i> Mark Served
                        </button>`;
                } else {
                    ticket.classList.add('has-ready');
                    // Ensure "Mark Served" button is present alongside "All Ready"
                    const actions = ticket.querySelector('.ticket-actions');
                    if (actions && !actions.querySelector('.btn-mark-served')) {
                        actions.insertAdjacentHTML('beforeend', `
                            <button class="btn-mark-served" onclick="markServed(${ticket.dataset.order})">
                                <i class="bi bi-check2-circle"></i> Mark Served
                            </button>`);
                    }
                }
            }
        }

        if (d.action === 'served') {
            const ticket = grid.querySelector(`.ticket[data-order="${d.orderItemID}"]`)
                        || [...grid.querySelectorAll('.ticket')].find(t =>
                               t.querySelector(`.ticket-item[data-item-id="${d.orderItemID}"]`));
            if (ticket) {
                ticket.style.transition = 'opacity 0.4s';
                ticket.style.opacity    = '0';
                setTimeout(() => { ticket.remove(); showEmptyIfNone(); }, 400);
            }
        }
    });

})();
</script>
<?php include 'footer.php'; ?>
</body></html>
