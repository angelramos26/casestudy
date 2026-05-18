<?php
/**
 * csrf.php — CSRF Token Helpers
 * Robust version: always ensures session is active before touching tokens.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): void
{
    echo '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function csrf_verify(bool $json = false): void
{
    // Ensure session is active
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_samesite', 'Lax');
        session_start();
    }

    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $expected  = $_SESSION['csrf_token'] ?? '';

    if (!$expected || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh the page and try again.']);
        } else {
            echo '<p style="font-family:sans-serif;color:#b0192a;padding:2rem;">
                    <strong>403 – CSRF token mismatch.</strong><br>
                    Please <a href="javascript:history.back()">go back</a> and try again.
                  </p>';
        }
        exit();
    }
}

function csrf_regenerate(): void
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
