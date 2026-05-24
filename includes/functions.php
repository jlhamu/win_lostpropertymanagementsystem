<?php
define('BASE_URL', 'http://localhost/win-lostproperty/');
define('BASE_PATH', 'C:/xampp/htdocs/win-lostproperty/');


/**
 * Utility Functions - Wentworth Lost and Found Management System
 */

/** Escape output to prevent XSS */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/** Return available item categories */
function getCategories(): array
{
    return [
        'Electronics',
        'Bags & Backpacks',
        'Clothing & Accessories',
        'Books & Stationery',
        'Keys & Wallets',
        'Sports Equipment',
        'Jewelry',
        'ID & Documents',
        'Glasses & Eyewear',
        'Other',
    ];
}

/** Format a date string as "01 Jan 2025" */
function formatDate(string $date): string
{
    return date('d M Y', strtotime($date));
}

/** Return CSS badge class for a given status */
function getStatusBadgeClass(string $status): string
{
    $map = [
        'open'     => 'badge-open',
        'claimed'  => 'badge-claimed',
        'returned' => 'badge-returned',
        'disposed' => 'badge-disposed',
        'pending'  => 'badge-pending',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected',
        'lost'     => 'badge-lost',
        'found'    => 'badge-found',
    ];
    return $map[$status] ?? 'badge-open';
}

/**
 * Upload an item image.
 * Returns relative path like "uploads/items/item_xxx.jpg" on success, null on failure.
 */
function uploadItemImage(array $file): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $maxSize      = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes, true)) {
        return null;
    }
    if ($file['size'] > $maxSize) {
        return null;
    }
    // Verify the file is actually an image
    if (getimagesize($file['tmp_name']) === false) {
        return null;
    }

    $uploadDir = BASE_PATH . 'uploads/items/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFilename = 'item_' . uniqid('', true) . '.' . $ext;
    $destination = $uploadDir . $newFilename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return 'uploads/items/' . $newFilename;
    }
    return null;
}

/** Fetch the most recent open items */
function getRecentItems(PDO $pdo, int $limit = 6): array
{
    $stmt = $pdo->prepare(
        "SELECT i.*, u.full_name
         FROM items i
         JOIN users u ON i.user_id = u.user_id
         WHERE i.status = 'open'
         ORDER BY i.created_at DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Find open lost items that potentially match a newly-reported found item.
 * Matches on same category + keyword overlap in name / description / location.
 */
function findPotentialMatches(PDO $pdo, array $foundItem): array
{
    $keyword  = '%' . $foundItem['item_name'] . '%';
    $locKw    = '%' . $foundItem['location'] . '%';

    $stmt = $pdo->prepare(
        "SELECT i.*, u.email, u.full_name
         FROM items i
         JOIN users u ON i.user_id = u.user_id
         WHERE i.type     = 'lost'
           AND i.status   = 'open'
           AND i.category = ?
           AND (
               i.item_name   LIKE ?
               OR i.description LIKE ?
               OR i.location    LIKE ?
           )"
    );
    $stmt->execute([$foundItem['category'], $keyword, $keyword, $locKw]);
    return $stmt->fetchAll();
}

/** Insert a notification record */
function addNotification(PDO $pdo, int $userId, int $itemId, string $message): void
{
    $stmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, item_id, message) VALUES (?, ?, ?)"
    );
    $stmt->execute([$userId, $itemId, $message]);
}

/** Count unread notifications for a user */
function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE"
    );
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

