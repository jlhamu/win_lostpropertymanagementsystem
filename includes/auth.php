<?php
/**
 * Authentication Functions - Wentworth Lost and Found Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Check if a user is currently logged in */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/** Redirect to login if not logged in */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/** Redirect to login if not an admin */
function requireAdmin(): void
{
    if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/** Return current user's session data or null */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'email'     => $_SESSION['email'] ?? '',
    ];
}
