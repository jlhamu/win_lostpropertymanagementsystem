<?php
/**
 * Admin Update Status (AJAX-friendly endpoint)
 * Also handles inline status updates from forms.
 * Wentworth Lost and Found Management System
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$itemId    = (int)($_POST['item_id'] ?? 0);
$newStatus = trim($_POST['status']   ?? '');
$allowed   = ['open', 'claimed', 'returned', 'disposed'];
$redirect  = $_POST['redirect'] ?? (BASE_URL . 'admin/items.php');

if ($itemId > 0 && in_array($newStatus, $allowed, true)) {
    $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE item_id = ?");
    $stmt->execute([$newStatus, $itemId]);

    // If JSON was requested (AJAX), respond with JSON
    if ($_SERVER['HTTP_ACCEPT'] ?? '' === 'application/json') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $newStatus]);
        exit;
    }
}

// Redirect back
header('Location: ' . $redirect);
exit;
