<?php
/**
 * Admin Dashboard – Wentworth Lost and Found Management System
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// ── Stats ─────────────────────────────────────────────────────────
$statsStmt = $pdo->query(
    "SELECT
        (SELECT COUNT(*) FROM users WHERE role != 'admin') AS total_users,
        COUNT(*)                                           AS total_items,
        COUNT(CASE WHEN type='lost'                THEN 1 END) AS lost_items,
        COUNT(CASE WHEN type='found'               THEN 1 END) AS found_items,
        COUNT(CASE WHEN status='open'              THEN 1 END) AS open_items,
        COUNT(CASE WHEN status='claimed'           THEN 1 END) AS claimed_items,
        COUNT(CASE WHEN status='returned'          THEN 1 END) AS returned_items,
        COUNT(CASE WHEN status='disposed'          THEN 1 END) AS disposed_items
     FROM items"
);
$stats = $statsStmt->fetch();

$pendingClaims = (int)$pdo->query(
    "SELECT COUNT(*) FROM claims WHERE claim_status = 'pending'"
)->fetchColumn();

// ── Recent Items (last 8) ─────────────────────────────────────────
$recentItemsStmt = $pdo->query(
    "SELECT i.*, u.full_name
     FROM items i
     JOIN users u ON i.user_id = u.user_id
     ORDER BY i.created_at DESC LIMIT 8"
);
$recentItems = $recentItemsStmt->fetchAll();

// ── Recent Claims (last 8) ────────────────────────────────────────
$recentClaimsStmt = $pdo->query(
    "SELECT c.*, i.item_name, u.full_name AS claimant_name
     FROM claims c
     JOIN items i ON c.item_id = i.item_id
     JOIN users u ON c.claimant_id = u.user_id
     ORDER BY c.submitted_at DESC LIMIT 8"
);
$recentClaims = $recentClaimsStmt->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once '../includes/admin-sidebar.php'; ?>

    <div class="admin-content">
        <h2 style="margin-bottom:22px; color:var(--primary);">📊 Dashboard</h2>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?= (int)$stats['total_users'] ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card stat-danger">
                <div class="stat-icon">🔍</div>
                <div class="stat-number"><?= (int)$stats['lost_items'] ?></div>
                <div class="stat-label">Lost Items</div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?= (int)$stats['found_items'] ?></div>
                <div class="stat-label">Found Items</div>
            </div>
            <div class="stat-card stat-info">
                <div class="stat-icon">🔓</div>
                <div class="stat-number"><?= (int)$stats['open_items'] ?></div>
                <div class="stat-label">Open Items</div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon">📋</div>
                <div class="stat-number"><?= (int)$stats['claimed_items'] ?></div>
                <div class="stat-label">Claimed</div>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?= (int)$stats['returned_items'] ?></div>
                <div class="stat-label">Returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🗑️</div>
                <div class="stat-number"><?= (int)$stats['disposed_items'] ?></div>
                <div class="stat-label">Disposed</div>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-icon">⏳</div>
                <div class="stat-number"><?= $pendingClaims ?></div>
                <div class="stat-label">Pending Claims</div>
            </div>
        </div>

        <!-- QUICK LINKS -->
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:30px;">
            <a href="<?= BASE_URL ?>admin/claims.php?status=pending" class="btn btn-primary">
                Review Pending Claims (<?= $pendingClaims ?>)
            </a>
            <a href="<?= BASE_URL ?>admin/items.php" class="btn btn-outline">Manage All Items</a>
        </div>

        <div class="grid grid-2">
            <!-- RECENT ITEMS -->
            <div>
                <h3 style="margin-bottom:14px; color:var(--primary);">Recent Item Reports</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Item</th><th>Type</th><th>Status</th><th>By</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentItems as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$item['item_id'] ?>"
                                           style="font-weight:600;">
                                            <?= sanitize($item['item_name']) ?>
                                        </a>
                                    </td>
                                    <td><span class="badge-status badge-<?= $item['type'] ?>"><?= ucfirst($item['type']) ?></span></td>
                                    <td><span class="badge-status <?= getStatusBadgeClass($item['status']) ?>"><?= ucfirst($item['status']) ?></span></td>
                                    <td><?= sanitize($item['full_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentItems)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No items yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RECENT CLAIMS -->
            <div>
                <h3 style="margin-bottom:14px; color:var(--primary);">Recent Claims</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Item</th><th>Claimant</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentClaims as $claim): ?>
                                <tr>
                                    <td><?= sanitize($claim['item_name']) ?></td>
                                    <td><?= sanitize($claim['claimant_name']) ?></td>
                                    <td><span class="badge-status <?= getStatusBadgeClass($claim['claim_status']) ?>"><?= ucfirst($claim['claim_status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentClaims)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No claims yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /.grid -->
    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<?php require_once '../includes/footer.php'; ?>
