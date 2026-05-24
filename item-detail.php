<?php
/**
 * Item Detail Page – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    header('Location: ' . BASE_URL . 'browse.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT i.*, u.full_name, u.email
     FROM items i
     JOIN users u ON i.user_id = u.user_id
     WHERE i.item_id = ?"
);
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item) {
    header('Location: ' . BASE_URL . 'browse.php');
    exit;
}

// Has the current user already submitted a claim for this item?
$userHasClaimed = false;
if (isLoggedIn()) {
    $clStmt = $pdo->prepare(
        "SELECT claim_id FROM claims WHERE item_id = ? AND claimant_id = ?"
    );
    $clStmt->execute([$itemId, $_SESSION['user_id']]);
    $userHasClaimed = (bool)$clStmt->fetch();
}

$isOwner = isLoggedIn() && (int)$item['user_id'] === (int)$_SESSION['user_id'];

$pageTitle = sanitize($item['item_name']);
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <a href="<?= BASE_URL ?>browse.php" style="color:rgba(255,255,255,.8);">&larr; Back to Browse</a>
        &nbsp;/&nbsp;
        <span><?= sanitize($item['item_name']) ?></span>
    </div>
</div>

<div class="container" style="padding-bottom:50px;">
    <div class="item-detail-container">
        <div class="item-detail-card">

            <!-- Image -->
            <?php if (!empty($item['image_path']) && file_exists(BASE_PATH . $item['image_path'])): ?>
                <img src="<?= BASE_URL . sanitize($item['image_path']) ?>"
                     alt="<?= sanitize($item['item_name']) ?>"
                     class="item-detail-image">
            <?php else: ?>
                <div class="item-detail-img-placeholder">
                    <?= $item['type'] === 'lost' ? '🔍' : '📦' ?>
                </div>
            <?php endif; ?>

            <div class="item-detail-body">
                <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                    <span class="badge-status badge-<?= sanitize($item['type']) ?>"><?= ucfirst($item['type']) ?></span>
                    <span class="badge-status <?= getStatusBadgeClass($item['status']) ?>"><?= ucfirst($item['status']) ?></span>
                </div>

                <h1><?= sanitize($item['item_name']) ?></h1>

                <!-- Meta grid -->
                <div class="item-meta">
                    <div class="item-meta-item">
                        <span class="meta-label">Category</span>
                        <span class="meta-value">📁 <?= sanitize($item['category']) ?></span>
                    </div>
                    <div class="item-meta-item">
                        <span class="meta-label">Location</span>
                        <span class="meta-value">📍 <?= sanitize($item['location']) ?></span>
                    </div>
                    <div class="item-meta-item">
                        <span class="meta-label">Date Reported</span>
                        <span class="meta-value">📅 <?= formatDate($item['date_reported']) ?></span>
                    </div>
                    <div class="item-meta-item">
                        <span class="meta-label">Reported By</span>
                        <span class="meta-value">👤 <?= sanitize($item['full_name']) ?></span>
                    </div>
                </div>

                <!-- Description -->
                <h3 style="color:var(--primary); margin-bottom:10px;">Description</h3>
                <p style="line-height:1.75; color:var(--text);"><?= nl2br(sanitize($item['description'])) ?></p>

                <!-- Action Buttons -->
                <div style="margin-top:28px; display:flex; gap:12px; flex-wrap:wrap;">
                    <?php if ($item['type'] === 'found' && $item['status'] === 'open'): ?>
                        <?php if (!isLoggedIn()): ?>
                            <a href="<?= BASE_URL ?>login.php" class="btn btn-primary">
                                Login to Submit Claim
                            </a>
                        <?php elseif ($isOwner): ?>
                            <div class="alert alert-info" style="margin:0;">
                                This is your own reported item.
                            </div>
                        <?php elseif ($userHasClaimed): ?>
                            <div class="alert alert-success" style="margin:0;">
                                ✅ You have already submitted a claim for this item.
                            </div>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>claim-item.php?id=<?= (int)$item['item_id'] ?>"
                               class="btn btn-primary btn-lg">
                                Submit Ownership Claim
                            </a>
                        <?php endif; ?>
                    <?php elseif ($item['status'] !== 'open'): ?>
                        <div class="alert alert-warning" style="margin:0;">
                            This item is currently marked as <strong><?= ucfirst($item['status']) ?></strong>
                            and is no longer accepting new claims.
                        </div>
                    <?php endif; ?>

                    <?php if ($isOwner): ?>
                        <a href="<?= BASE_URL ?>my-reports.php" class="btn btn-outline">My Reports</a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>browse.php" class="btn btn-outline">&larr; Back to Listings</a>
                </div>
            </div>
        </div><!-- /.item-detail-card -->
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
