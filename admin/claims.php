<?php
/**
 * Admin Claims Management – Wentworth Lost and Found Management System
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$msgType = 'success';

// ── Handle Approve / Reject ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $claimId = (int)($_POST['claim_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($claimId > 0 && in_array($action, ['approve', 'reject'], true)) {

        // Fetch the claim so we know the item_id
        $clStmt = $pdo->prepare("SELECT * FROM claims WHERE claim_id = ?");
        $clStmt->execute([$claimId]);
        $claim = $clStmt->fetch();

        if ($claim) {
            if ($action === 'approve') {
                // 1. Approve this claim
                $pdo->prepare("UPDATE claims SET claim_status='approved' WHERE claim_id=?")
                    ->execute([$claimId]);

                // 2. Mark item as returned
                $pdo->prepare("UPDATE items SET status='returned' WHERE item_id=?")
                    ->execute([$claim['item_id']]);

                // 3. Reject all OTHER pending claims for the same item
                $pdo->prepare(
                    "UPDATE claims SET claim_status='rejected'
                     WHERE item_id=? AND claim_id != ? AND claim_status='pending'"
                )->execute([$claim['item_id'], $claimId]);

                $message = 'Claim approved. Item marked as returned. Other pending claims rejected.';

            } elseif ($action === 'reject') {
                $pdo->prepare("UPDATE claims SET claim_status='rejected' WHERE claim_id=?")
                    ->execute([$claimId]);

                // Check if there are still pending claims; if none, revert item to open
                $pendingCount = (int)$pdo->prepare(
                    "SELECT COUNT(*) FROM claims WHERE item_id=? AND claim_status='pending'"
                )->execute([$claim['item_id']]) ? 1 : 0; // re-query properly:

                $pcStmt = $pdo->prepare(
                    "SELECT COUNT(*) FROM claims WHERE item_id=? AND claim_status='pending'"
                );
                $pcStmt->execute([$claim['item_id']]);
                $pendingCount = (int)$pcStmt->fetchColumn();

                if ($pendingCount === 0) {
                    $pdo->prepare("UPDATE items SET status='open' WHERE item_id=? AND status='claimed'")
                        ->execute([$claim['item_id']]);
                }

                $message = 'Claim rejected.';
            }
        } else {
            $message = 'Claim not found.';
            $msgType = 'error';
        }
    }
}

// ── Filters ──────────────────────────────────────────────────────
$filterStatus = in_array($_GET['status'] ?? '', ['pending','approved','rejected',''])
                ? ($_GET['status'] ?? '') : '';

$where  = ['1=1'];
$params = [];
if ($filterStatus) { $where[] = "c.claim_status = ?"; $params[] = $filterStatus; }
$whereSQL = implode(' AND ', $where);

// Pagination
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM claims c WHERE {$whereSQL}");
$totalStmt->execute($params);
$total      = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$dataStmt = $pdo->prepare(
    "SELECT c.*, i.item_name, i.type AS item_type, i.location, i.status AS item_status,
            u.full_name AS claimant_name, u.email AS claimant_email
     FROM claims c
     JOIN items i ON c.item_id = i.item_id
     JOIN users u ON c.claimant_id = u.user_id
     WHERE {$whereSQL}
     ORDER BY c.submitted_at DESC
     LIMIT ? OFFSET ?"
);
$dataStmt->execute(array_merge($params, [$perPage, $offset]));
$claims = $dataStmt->fetchAll();

$pageTitle = 'Admin – Manage Claims';
require_once '../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once '../includes/admin-sidebar.php'; ?>

    <div class="admin-content">
        <h2 style="margin-bottom:20px; color:var(--primary);">📝 Manage Claims</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
                <?= sanitize($message) ?>
            </div>
        <?php endif; ?>

        <!-- FILTER -->
        <form method="GET" style="margin-bottom:20px;">
            <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-size:.85rem; font-weight:600; margin-bottom:5px; color:var(--text-light); text-transform:uppercase;">Status</label>
                    <select name="status" class="form-control" style="width:180px;">
                        <option value="">All Statuses</option>
                        <option value="pending"  <?= $filterStatus==='pending'  ? 'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $filterStatus==='approved' ? 'selected':'' ?>>Approved</option>
                        <option value="rejected" <?= $filterStatus==='rejected' ? 'selected':'' ?>>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($filterStatus): ?>
                    <a href="<?= BASE_URL ?>admin/claims.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <p style="margin-bottom:14px; color:var(--text-light); font-size:.9rem;">
            Showing <?= count($claims) ?> of <?= $total ?> claims
        </p>

        <!-- CLAIMS TABLE -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item</th>
                        <th>Claimant</th>
                        <th>Ownership Description</th>
                        <th>Submitted</th>
                        <th>Claim Status</th>
                        <th>Item Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($claims)): ?>
                        <tr><td colspan="8" class="text-center text-muted" style="padding:30px;">No claims found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td><?= (int)$claim['claim_id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$claim['item_id'] ?>"
                                   style="font-weight:600;">
                                    <?= sanitize($claim['item_name']) ?>
                                </a>
                                <br>
                                <span class="badge-status badge-<?= $claim['item_type'] ?>" style="margin-top:3px;">
                                    <?= ucfirst($claim['item_type']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= sanitize($claim['claimant_name']) ?></strong><br>
                                <small style="color:var(--text-light);"><?= sanitize($claim['claimant_email']) ?></small>
                            </td>
                            <td>
                                <div style="max-width:250px; max-height:70px; overflow:hidden; font-size:.87rem; line-height:1.5;">
                                    <?= sanitize($claim['description']) ?>
                                </div>
                            </td>
                            <td><?= date('d M Y', strtotime($claim['submitted_at'])) ?></td>
                            <td>
                                <span class="badge-status <?= getStatusBadgeClass($claim['claim_status']) ?>">
                                    <?= ucfirst($claim['claim_status']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status <?= getStatusBadgeClass($claim['item_status']) ?>">
                                    <?= ucfirst($claim['item_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($claim['claim_status'] === 'pending'): ?>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        <form method="POST">
                                            <input type="hidden" name="claim_id" value="<?= (int)$claim['claim_id'] ?>">
                                            <button type="submit" name="action" value="approve"
                                                    class="btn btn-success btn-sm"
                                                    data-confirm="Approve this claim? The item will be marked as returned.">
                                                Approve
                                            </button>
                                        </form>
                                        <form method="POST">
                                            <input type="hidden" name="claim_id" value="<?= (int)$claim['claim_id'] ?>">
                                            <button type="submit" name="action" value="reject"
                                                    class="btn btn-danger btn-sm"
                                                    data-confirm="Reject this claim?">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--text-light); font-size:.85rem;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1):
            $qp = array_filter(['status' => $filterStatus]);
        ?>
            <div class="pagination" style="margin-top:20px;">
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($qp, ['page' => $p])) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

    </div><!-- /.admin-content -->
</div><!-- /.admin-layout -->

<?php require_once '../includes/footer.php'; ?>
