<?php
/**
 * pusher-broadcast.php
 * 
 * Helper function para mag-trigger ng Pusher events.
 * I-require lang ito sa backend files mo.
 * 
 * HOW TO USE:
 *   require_once 'pusher-broadcast.php';
 *   pusherBroadcast('sale-completed', ['salesID' => 123, 'total' => 500.00]);
 */

require_once __DIR__ . '/pusher.php';

function pusherBroadcast(string $event, array $data): void
{
    // ── 1. Load Pusher PHP SDK via Composer ──────────────────────────────────
    $autoload = __DIR__ . '/../vendor/autoload.php';

    if (!file_exists($autoload)) {
        error_log('[Pusher] vendor/autoload.php not found. Run: composer require pusher/pusher-php-server');
        return;
    }

    require_once $autoload;

    // ── 2. Build Pusher instance ─────────────────────────────────────────────
    try {
        $pusher = new Pusher\Pusher(
            PUSHER_APP_KEY,
            PUSHER_APP_SECRET,
            PUSHER_APP_ID,
            [
                'cluster'   => PUSHER_APP_CLUSTER,
                'useTLS'    => true,   // Always use secure connection
            ]
        );

        // ── 3. Trigger the event on the shared POS channel ──────────────────
        $pusher->trigger('pos-channel', $event, $data);

    } catch (\Exception $e) {
        // Don't crash the page — just log the error silently
        error_log('[Pusher] Broadcast failed: ' . $e->getMessage());
    }
}
