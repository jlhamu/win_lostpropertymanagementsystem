<?php
/**
 * Admin Items Management – Wentworth Lost and Found Management System
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

$message = '';
$msgType = 'success';

// ── Handle status update via POST ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $updateItemId  = (int)($_POST['item_id'] ?? 0);
    $updateStatus  = $_POST['new_status'] ?? '';
    $allowedStatus = ['open', 'claimed', 'returned', 'disposed'];

    if ($updateItemId > 0 && in_array($updateStatus, $allowedStatus, true)) {
        $stmt = $pdo->prepare("UPDATE items SET status = ? WHERE item_id = ?");
        $stmt->execute([$updateStatus, $updateItemId]);
        $message = 'Item status updated successfully.';
    } else {
        $message = 'Invalid request.';
        $msgType = 'error';
    }
}

// ── Handle delete ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $delItemId = (int)($_POST['item_id'] ?? 0);
    if ($delItemId > 0) {
        // Delete image file if present
        $imgStmt = $pdo->prepare("SELECT image_path FROM items WHERE item_id = ?");
        $imgStmt->execute([$delItemId]);
        $imgRow = $imgStmt->fetch();
        if ($imgRow && $imgRow['image_path'] && file_exists(BASE_PATH . $imgRow['image_path'])) {
            unlink(BASE_PATH . $imgRow['image_path']);
        }

        $del = $pdo->prepare("DELETE FROM items WHERE item_id = ?");
        $del->execute([$delItemId]);
        $message = 'Item deleted.';
    }
}

// ── Filters ──────────────────────────────────────────────────────
$filterType   = in_array($_GET['type']   ?? '', ['lost','found','']) ? ($_GET['type'] ?? '') : '';
$filterStatus = in_array($_GET['status'] ?? '', ['open','claimed','returned','disposed','']) ? ($_GET['status'] ?? '') : '';
$search       = trim($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterType)   { $where[] = "i.type = ?";   $params[] = $filterType; }
if ($filterStatus) { $where[] = "i.status = ?";  $params[] = $filterStatus; }
if ($search) {
    $where[] = "(i.item_name LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
    $kw = "%{$search}%"; $params[] = $kw; $params[] = $kw; $params[] = $kw;
}
$whereSQL = implode(' AND ', $where);

// Pagination
$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM items i WHERE {$whereSQL}");
$totalStmt->execute($params);
$total      = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$dataStmt = $pdo->prepare(
    "SELECT i.*, u.full_name
     FROM items i
     JOIN users u ON i.user_id = u.user_id
     WHERE {$whereSQL}
     ORDER BY i.created_at DESC
     LIMIT ? OFFSET ?"
);
$dataStmt->execute(array_merge($params, [$perPage, $offset]));
$items = $dataStmt->fetchAll();

$pageTitle = 'Admin – Manage Items';
require_once '../includes/header.php';
?>

<div class="admin-layout">
    <?php require_once '../includes/admin-sidebar.php'; ?>

    <div class="admin-content">
        <h2 style="margin-bottom:20px; color:var(--primary);">📋 Manage Items</h2>

        <?php if ($message): ?>
            <div class="alert alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
                <?= sanitize($message) ?>
            </div>
        <?php endif; ?>

        <!-- FILTER BAR -->
        <form method="GET">
            <div class="filter-bar">
                <div class="filter-group" style="flex:2; min-width:200px;">
                    <label>Search</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Item name, location…"
                           value="<?= sanitize($search) ?>">
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="lost"  <?= $filterType === 'lost'  ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $filterType === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="open"     <?= $filterStatus==='open'     ? 'selected':'' ?>>Open</option>
                        <option value="claimed"  <?= $filterStatus==='claimed'  ? 'selected':'' ?>>Claimed</option>
                        <option value="returned" <?= $filterStatus==='returned' ? 'selected':'' ?>>Returned</option>
                        <option value="disposed" <?= $filterStatus==='disposed' ? 'selected':'' ?>>Disposed</option>
                    </select>
                </div>
                <div>
                    <label style="visibility:hidden;">Go</label>
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <?php if ($search || $filterType || $filterStatus): ?>
                        <a href="<?= BASE_URL ?>admin/items.php" class="btn btn-outline" style="margin-left:8px;">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <p style="margin-bottom:14px; color:var(--text-light); font-size:.9rem;">
            Showing <?= count($items) ?> of <?= $total ?> items
        </p>

        <!-- ITEMS TABLE -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Reported By</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">No items found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= (int)$item['item_id'] ?></td>
                            <td>
                                <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$item['item_id'] ?>"
                                   style="font-weight:600;">
                                    <?= sanitize($item['item_name']) ?>
                                </a>
                            </td>
                            <td><span class="badge-status badge-<?= $item['type'] ?>"><?= ucfirst($item['type']) ?></span></td>
                            <td><?= sanitize($item['category']) ?></td>
                            <td><?= sanitize($item['location']) ?></td>
                            <td><?= sanitize($item['full_name']) ?></td>
                            <td><?= formatDate($item['date_reported']) ?></td>
                            <td><span class="badge-status <?= getStatusBadgeClass($item['status']) ?>"><?= ucfirst($item['status']) ?></span></td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                                    <!-- Update Status -->
                                    <form method="POST" style="display:flex; gap:5px; align-items:center;">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                        <select name="new_status" class="form-control" style="padding:5px 8px; font-size:.82rem; width:auto;">
                                            <?php foreach (['open','claimed','returned','disposed'] as $s): ?>
                                                <option value="<?= $s ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-success btn-sm">Update</button>
                                    </form>
                                    <!-- Delete -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="item_id" value="<?= (int)$item['item_id'] ?>">
                                        <button type="submit" name="delete_item"
                                                class="btn btn-danger btn-sm"
                                                data-confirm="Delete this item permanently? This cannot be undone.">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1):
            $qp = array_filter(['type' => $filterType, 'status' => $filterStatus, 'search' => $search]);
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
