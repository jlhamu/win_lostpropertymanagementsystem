<?php
/**
 * My Reports – Wentworth Lost and Found Management System
 * Shows the current user's submitted items and submitted claims.
 * Also shows unread notifications and marks them read.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = (int)$_SESSION['user_id'];

// Flash messages
$claimSubmitted = isset($_GET['claim_submitted']);

// ── Fetch user's items ────────────────────────────────────────────
$itemsStmt = $pdo->prepare(
    "SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC"
);
$itemsStmt->execute([$userId]);
$myItems = $itemsStmt->fetchAll();

// ── Fetch user's claims ───────────────────────────────────────────
$claimsStmt = $pdo->prepare(
    "SELECT c.*, i.item_name, i.category, i.location, i.type, i.status AS item_status
     FROM claims c
     JOIN items i ON c.item_id = i.item_id
     WHERE c.claimant_id = ?
     ORDER BY c.submitted_at DESC"
);
$claimsStmt->execute([$userId]);
$myClaims = $claimsStmt->fetchAll();

// ── Fetch and mark-read notifications ────────────────────────────
$notifStmt = $pdo->prepare(
    "SELECT n.*, i.item_name
     FROM notifications n
     JOIN items i ON n.item_id = i.item_id
     WHERE n.user_id = ?
     ORDER BY n.created_at DESC
     LIMIT 30"
);
$notifStmt->execute([$userId]);
$notifications = $notifStmt->fetchAll();

// Mark all as read
$markRead = $pdo->prepare(
    "UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE"
);
$markRead->execute([$userId]);

$pageTitle = 'My Reports';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>My Reports</h1>
        <p>Your submitted items, claims and notifications</p>
    </div>
</div>

<div class="container" style="padding-bottom:50px;">

    <?php if ($claimSubmitted): ?>
        <div class="alert alert-success">✅ Your ownership claim has been submitted. The admin will review it shortly.</div>
    <?php endif; ?>

    <!-- QUICK ACTION BUTTONS -->
    <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:26px;">
        <a href="<?= BASE_URL ?>report-lost.php"  class="btn btn-primary">+ Report Lost Item</a>
        <a href="<?= BASE_URL ?>report-found.php" class="btn btn-outline">+ Report Found Item</a>
    </div>

    <!-- TABS -->
    <div class="tabs">
        <button class="tab-btn active" data-tab="tab-items">
            My Items (<?= count($myItems) ?>)
        </button>
        <button class="tab-btn" data-tab="tab-claims">
            My Claims (<?= count($myClaims) ?>)
        </button>
        <button class="tab-btn" data-tab="tab-notifications">
            Notifications (<?= count($notifications) ?>)
        </button>
    </div>

    <!-- TAB: MY ITEMS -->
    <div id="tab-items" class="tab-content active">
        <?php if (empty($myItems)): ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No items reported yet</h3>
                <p>Report a lost or found item to see it here.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myItems as $item): ?>
                            <tr>
                                <td><strong><?= sanitize($item['item_name']) ?></strong></td>
                                <td><span class="badge-status badge-<?= sanitize($item['type']) ?>"><?= ucfirst($item['type']) ?></span></td>
                                <td><?= sanitize($item['category']) ?></td>
                                <td><?= sanitize($item['location']) ?></td>
                                <td><?= formatDate($item['date_reported']) ?></td>
                                <td><span class="badge-status <?= getStatusBadgeClass($item['status']) ?>"><?= ucfirst($item['status']) ?></span></td>
                                <td>
                                    <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$item['item_id'] ?>"
                                       class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB: MY CLAIMS -->
    <div id="tab-claims" class="tab-content">
        <?php if (empty($myClaims)): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <h3>No claims submitted yet</h3>
                <p>Browse found items and submit a claim if you spot yours.</p>
                <a href="<?= BASE_URL ?>browse.php?type=found" class="btn btn-primary">Browse Found Items</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Submitted</th>
                            <th>Claim Status</th>
                            <th>Item Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myClaims as $claim): ?>
                            <tr>
                                <td><strong><?= sanitize($claim['item_name']) ?></strong></td>
                                <td><?= sanitize($claim['category']) ?></td>
                                <td><?= sanitize($claim['location']) ?></td>
                                <td><?= date('d M Y', strtotime($claim['submitted_at'])) ?></td>
                                <td><span class="badge-status <?= getStatusBadgeClass($claim['claim_status']) ?>"><?= ucfirst($claim['claim_status']) ?></span></td>
                                <td><span class="badge-status <?= getStatusBadgeClass($claim['item_status']) ?>"><?= ucfirst($claim['item_status']) ?></span></td>
                                <td>
                                    <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$claim['item_id'] ?>"
                                       class="btn btn-primary btn-sm">View Item</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- TAB: NOTIFICATIONS -->
    <div id="tab-notifications" class="tab-content">
        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <div class="empty-icon">🔔</div>
                <h3>No notifications</h3>
                <p>You will be notified here when a potential match is found for your lost items.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($notifications as $notif): ?>
                    <div class="card" style="<?= !$notif['is_read'] ? 'border-left:4px solid var(--accent);' : '' ?>">
                        <div class="card-body" style="display:flex; gap:14px; align-items:flex-start;">
                            <div style="font-size:1.8rem; line-height:1; margin-top:2px;">🔔</div>
                            <div style="flex:1;">
                                <p style="margin-bottom:6px; font-weight:<?= !$notif['is_read'] ? '700' : '400' ?>;">
                                    <?= sanitize($notif['message']) ?>
                                </p>
                                <p style="color:var(--text-light); font-size:.83rem; margin:0;">
                                    <?= date('d M Y, g:i A', strtotime($notif['created_at'])) ?>
                                </p>
                            </div>
                            <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$notif['item_id'] ?>"
                               class="btn btn-primary btn-sm">View Item</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.container -->

<?php require_once 'includes/footer.php'; ?>
