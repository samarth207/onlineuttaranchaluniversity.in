<?php
/**
 * Admin Auth Check — Include at top of every admin page
 * Redirects to login if not authenticated.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
    header('Location: /admin/login.php' . ($redirect ? '?redirect=' . $redirect : ''));
    exit;
}
