/**
 * realtime.js — Pusher listener para sa 7Evelyn POS
 * Handles lahat ng events sa pos-channel.
 *
 * PUSHER_KEY and PUSHER_CLUSTER are injected server-side by header.php
 * from the .env file — they are NOT hardcoded here or in any JS file.
 *
 * Required includes (already in header.php):
 *   <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
 *   <!-- PUSHER_KEY and PUSHER_CLUSTER are set as JS vars by header.php -->
 *   <script src="pusher-content/realtime.js"></script>
 */

(function () {
    'use strict';

    if (typeof PUSHER_KEY === 'undefined' || !PUSHER_KEY || PUSHER_KEY === 'YOUR_APP_KEY') {
        console.warn('[Pusher] Credentials not set. Real-time disabled.');
        return;
    }

    Pusher.logToConsole = false;
    const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
    const channel = pusher.subscribe('pos-channel');

    // ── Toast helper ─────────────────────────────────────────────────────────
    function toast(msg, icon) {
        if (typeof Swal === 'undefined') { console.log('[Pusher]', msg); return; }
        Swal.mixin({
            toast: true, position: 'bottom-end',
            showConfirmButton: false, timer: 3500, timerProgressBar: true,
        }).fire({ icon: icon || 'info', title: msg });
    }

    // ── Format peso ──────────────────────────────────────────────────────────
    function peso(n) {
        return '₱' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    }

    // ── Update POS product card stock ────────────────────────────────────────
    function updatePosCard(productID, newQty) {
        const card = document.querySelector('[data-id="' + productID + '"]');
        if (!card) return;
        const badge = card.querySelector('.stock-badge');
        if (badge) {
            if (newQty <= 0)        { badge.textContent = 'Out'; badge.removeAttribute('style'); }
            else if (newQty <= 10)  { badge.textContent = 'Low'; badge.style.cssText = 'background:#fff8e1;color:#e65100;'; }
            else                    { badge.textContent = ''; badge.removeAttribute('style'); }
        }
        const lbl = card.querySelector('.prod-stock');
        if (lbl) lbl.textContent = 'Stock: ' + newQty;
        card.setAttribute('data-stock', newQty);
        card.style.transition = 'box-shadow 0.3s';
        card.style.boxShadow  = '0 0 0 3px #6a11cb55';
        setTimeout(function () { card.style.boxShadow = ''; }, 1000);
        newQty <= 0 ? card.classList.add('out-of-stock') : card.classList.remove('out-of-stock');
    }

    // ══════════════════════════════════════════════════════════════════════════
    // sale-completed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('sale-completed', function (d) {
        if (d.updatedStock) d.updatedStock.forEach(function (s) { updatePosCard(s.productID, s.stock_quantity); });
        if (!document.body.classList.contains('page-pos')) {
            toast('💳 Sale #' + d.salesID + ' — ' + peso(d.total) + ' (' + d.payment + ') by ' + (d.cashier || ''), 'success');
        }
    });

    // ══════════════════════════════════════════════════════════════════════════
    // stock-updated  (from stocksAuth & purchaseAuth)
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('stock-updated', function (d) {
        updatePosCard(d.productID, d.stock_quantity);
        const icons  = { IN: '📦', OUT: '📤', ADJUST: '🔧' };
        const labels = { IN: 'Stock In', OUT: 'Stock Out', ADJUST: 'Adjusted' };
        toast(
            (icons[d.type] || '📦') + ' ' + (labels[d.type] || d.type) +
            ': ' + d.productName + ' → ' + d.stock_quantity + ' pcs (by ' + (d.by || '') + ')',
            d.type === 'OUT' ? 'warning' : 'info'
        );
    });

    // ══════════════════════════════════════════════════════════════════════════
    // purchase-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('purchase-changed', function (d) {
        const msgs = {
            created:   '🛒 New Purchase Order #' + d.poID + ' — ' + (d.supplier || '') + ' (' + (d.items || 0) + ' items) by ' + d.by,
            received:  '✅ PO #' + d.poID + ' received — stock updated (by ' + d.by + ')',
            cancelled: '❌ PO #' + d.poID + ' cancelled by ' + d.by,
        };
        const icons = { created: 'info', received: 'success', cancelled: 'warning' };
        toast(msgs[d.action] || 'Purchase order updated', icons[d.action] || 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // expense-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('expense-changed', function (d) {
        const msgs = {
            added:            '💸 Expense recorded: ' + peso(d.amount) + (d.description ? ' — ' + d.description : '') + ' (by ' + d.by + ')',
            deleted:          '🗑️ Expense deleted by ' + d.by,
            'category-added': '📂 Expense category added: ' + (d.categoryName || '') + ' by ' + d.by,
        };
        toast(msgs[d.action] || 'Expense updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // customer-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('customer-changed', function (d) {
        const msgs = {
            added:   '👤 New customer: ' + (d.customerName || '') + ' (by ' + d.by + ')',
            updated: '✏️ Customer updated: ' + (d.customerName || '') + ' (by ' + d.by + ')',
            deleted: '🗑️ Customer removed by ' + d.by,
        };
        toast(msgs[d.action] || 'Customer updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // credit-changed  (utang / payment)
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('credit-changed', function (d) {
        const msgs = {
            utang:   '🔴 Utang added: ' + (d.customerName || '') + ' +' + peso(d.amount) + ' | Balance: ' + peso(d.balance) + ' (by ' + d.by + ')',
            payment: '🟢 Credit payment: ' + (d.customerName || '') + ' -' + peso(d.amount) + ' | Balance: ' + peso(d.balance) + ' (by ' + d.by + ')',
        };
        toast(msgs[d.action] || 'Credit updated', d.action === 'utang' ? 'warning' : 'success');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // product-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('product-changed', function (d) {
        const msgs = {
            added:       '🆕 New product: ' + (d.productName || '') + ' (by ' + d.by + ')',
            updated:     '✏️ Product updated: ' + (d.productName || '') + ' (by ' + d.by + ')',
            deactivated: '⚠️ Product deactivated: ID #' + d.productID + ' (by ' + d.by + ')',
            reactivated: '✅ Product reactivated: ID #' + d.productID + ' (by ' + d.by + ')',
        };
        const icons = { added: 'success', updated: 'info', deactivated: 'warning', reactivated: 'success' };
        toast(msgs[d.action] || 'Product updated', icons[d.action] || 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // category-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('category-changed', function (d) {
        const msgs = {
            added:   '📁 Category added: ' + (d.categoryName || '') + ' (by ' + d.by + ')',
            updated: '✏️ Category updated: ' + (d.categoryName || '') + ' (by ' + d.by + ')',
            deleted: '🗑️ Category deleted by ' + d.by,
        };
        toast(msgs[d.action] || 'Category updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // supplier-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('supplier-changed', function (d) {
        const msgs = {
            added:   '🏭 New supplier: ' + (d.companyName || '') + ' (by ' + d.by + ')',
            updated: '✏️ Supplier updated: ' + (d.companyName || '') + ' (by ' + d.by + ')',
            deleted: '🗑️ Supplier removed by ' + d.by,
        };
        toast(msgs[d.action] || 'Supplier updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // user-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('user-changed', function (d) {
        const msgs = {
            added:            '👤 New user account: ' + (d.name || '') + ' (by ' + d.by + ')',
            updated:          '✏️ User updated: ' + (d.name || '') + ' (by ' + d.by + ')',
            deleted:          '🗑️ User removed by ' + d.by,
            'password-changed': '🔑 Password changed for user #' + d.userID + ' (by ' + d.by + ')',
        };
        toast(msgs[d.action] || 'User updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ══════════════════════════════════════════════════════════════════════════
    // role-changed
    // ══════════════════════════════════════════════════════════════════════════
    channel.bind('role-changed', function (d) {
        const msgs = {
            added:   '🎭 New role: ' + (d.roleName || '') + ' (by ' + d.by + ')',
            updated: '✏️ Role updated: ' + (d.roleName || '') + ' (by ' + d.by + ')',
            deleted: '🗑️ Role deleted by ' + d.by,
        };
        toast(msgs[d.action] || 'Role updated', d.action === 'deleted' ? 'warning' : 'info');
    });

    // ── Connection status ─────────────────────────────────────────────────────
    pusher.connection.bind('connected',    function () { console.log('[Pusher] Connected ✔'); });
    pusher.connection.bind('disconnected', function () { console.warn('[Pusher] Disconnected'); });
    pusher.connection.bind('error',        function (e) { console.error('[Pusher] Error', e); });

})();
