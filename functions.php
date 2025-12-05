<?php
// functions.php - Helper functions and session management

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Display alert message
 */
function display_alert($type = 'info', $message = '') {
    if (!empty($message)) {
        $alert_class = "alert-$type";
        return "<div class='alert $alert_class'>" . htmlspecialchars($message) . "</div>";
    }
    return '';
}

/**
 * Set flash message for next request
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Display and clear flash message
 */
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        echo display_alert($flash['type'], $flash['message']);
        unset($_SESSION['flash_message']);
    }
}

// Authentication helper functions

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user']['logged_in']) && $_SESSION['user']['logged_in'] === true;
}

/**
 * Get current username
 */
function get_current_username() {
    return $_SESSION['user']['username'] ?? 'Guest';
}

/**
 * Require user to be logged in, redirect if not
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require admin privileges (placeholder for future use)
 */
function require_admin() {
    require_login();
    // Add admin-specific checks here when needed
}
?>