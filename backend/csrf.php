<?php
/**
 * csrf.php — CSRF Token Helpers
 *
 * Usage (backend — validate before processing POST):
 *   require_once 'csrf.php';
 *   csrf_verify();          // dies with 403 if token is missing/invalid
 *
 * Usage (frontend — emit a hidden input inside every <form>):
 *   <?php require_once '../backend/csrf.php'; ?>
 *   <form method="POST" action="...">
 *       <?php csrf_field(); ?>
 *       ...
 *   </form>
 *
 * Usage (JavaScript fetch / FormData — append the token):
 *   formData.append('csrf_token', '<?= csrf_token() ?>');
 *
 * Token lifetime: per-session, regenerated on login/logout.
 */

// Session must already be started before requiring this file.
// All backend/*Auth.php files call session_start() before require_once 'csrf.php'.

/**
 * Return (and lazily create) the session CSRF token.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Echo a hidden <input> field carrying the CSRF token.
 * Call this inside every HTML <form>.
 */
function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Verify the CSRF token submitted with the current POST request.
 * Sends HTTP 403 and exits if the token is absent or wrong.
 *
 * @param bool $json  When true, responds with JSON (for AJAX endpoints).
 */
function csrf_verify(bool $json = false): void
{
    $submitted = $_POST['csrf_token'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!$expected || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page and try again.']);
        } else {
            echo '<p style="font-family:sans-serif;color:#b0192a;padding:2rem;">
                    <strong>403 – CSRF token mismatch.</strong><br>
                    Please <a href="javascript:history.back()">go back</a> and try again.
                  </p>';
        }
        exit();
    }
}

/**
 * Regenerate the token (call after successful login or logout).
 */
function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
