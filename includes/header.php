<?php
/**
 * Shared Header - Wentworth Lost and Found Management System
 * Included at the top of every page AFTER db.php, auth.php, functions.php are loaded.
 */

// Compute unread notification count for logged-in users
$_unreadCount = 0;
if (isLoggedIn() && isset($pdo)) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE"
    );
    $stmt->execute([$_SESSION['user_id']]);
    $_unreadCount = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' – ' : '' ?>WIN Lost &amp; Found</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>

<!-- ══ NAVBAR ══════════════════════════════════════════════════════ -->
<nav class="navbar">
    <div class="nav-container">
        <a class="nav-brand" href="<?= BASE_URL ?>index.php">
            🔍 WIN Lost &amp; Found
        </a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">&#9776;</button>
        <ul class="nav-links" id="navLinks">
            <li><a href="<?= BASE_URL ?>index.php">Home</a></li>
            <li><a href="<?= BASE_URL ?>browse.php">Browse</a></li>

            <?php if (isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>report-lost.php">Report Lost</a></li>
                <li><a href="<?= BASE_URL ?>report-found.php">Report Found</a></li>
                <li><a href="<?= BASE_URL ?>my-reports.php">My Reports
                    <?php if ($_unreadCount > 0): ?>
                        <span class="badge"><?= $_unreadCount ?></span>
                    <?php endif; ?>
                </a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= BASE_URL ?>admin/dashboard.php">&#9881; Admin</a></li>
                <?php endif; ?>
                <li>
                    <a href="<?= BASE_URL ?>logout.php" class="btn-logout">
                        Logout (<?= sanitize($_SESSION['full_name']) ?>)
                    </a>
                </li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>login.php">Login</a></li>
                <li><a href="<?= BASE_URL ?>register.php" class="btn-register">Register</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<!-- ═══════════════════════════════════════════════════════════════ -->
