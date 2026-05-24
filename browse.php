<?php
/**
 * Browse Listings – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Pagination
$perPage     = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

// Filters (from GET for bookmarkable URLs)
$filterType     = in_array($_GET['type'] ?? '', ['lost', 'found', '']) ? ($_GET['type'] ?? '') : '';
$filterCategory = trim($_GET['category'] ?? '');
$filterStatus   = in_array($_GET['status'] ?? '', ['open', 'claimed', 'returned', 'disposed', '']) ? ($_GET['status'] ?? '') : '';
$search         = trim($_GET['search'] ?? '');

// Build WHERE clause
$where  = ['1=1'];
$params = [];

if ($filterType) {
    $where[]  = "i.type = ?";
    $params[] = $filterType;
}
if ($filterCategory) {
    $where[]  = "i.category = ?";
    $params[] = $filterCategory;
}
if ($filterStatus) {
    $where[]  = "i.status = ?";
    $params[] = $filterStatus;
} else {
    $where[] = "i.status != 'disposed'"; // hide disposed by default
}
if ($search) {
    $where[]  = "(i.item_name LIKE ? OR i.description LIKE ? OR i.location LIKE ?)";
    $kw = "%{$search}%";
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

$whereSQL = implode(' AND ', $where);

// Count total matching
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i WHERE {$whereSQL}");
$countStmt->execute($params);
$totalItems  = (int)$countStmt->fetchColumn();
$totalPages  = (int)ceil($totalItems / $perPage);

// Fetch page
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

$categories = getCategories();
$pageTitle  = 'Browse Listings';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Browse Lost &amp; Found Listings</h1>
        <p><?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?> found</p>
    </div>
</div>

<div class="container" style="padding-bottom:50px;">

    <!-- FILTER BAR -->
    <form method="GET" id="filterForm">
        <div class="filter-bar">
            <div class="filter-group" style="flex:2; min-width:220px;">
                <label for="searchInput">Search</label>
                <input type="text" id="searchInput" name="search"
                       class="form-control"
                       placeholder="Item name, location…"
                       value="<?= sanitize($search) ?>">
            </div>
            <div class="filter-group">
                <label for="filterType">Type</label>
                <select id="filterType" name="type" class="form-control">
                    <option value="">All Types</option>
                    <option value="lost"  <?= $filterType === 'lost'  ? 'selected' : '' ?>>Lost</option>
                    <option value="found" <?= $filterType === 'found' ? 'selected' : '' ?>>Found</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterCategory">Category</label>
                <select id="filterCategory" name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= sanitize($cat) ?>"
                            <?= $filterCategory === $cat ? 'selected' : '' ?>>
                            <?= sanitize($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="filterStatus">Status</label>
                <select id="filterStatus" name="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="open"     <?= $filterStatus === 'open'     ? 'selected' : '' ?>>Open</option>
                    <option value="claimed"  <?= $filterStatus === 'claimed'  ? 'selected' : '' ?>>Claimed</option>
                    <option value="returned" <?= $filterStatus === 'returned' ? 'selected' : '' ?>>Returned</option>
                    <option value="disposed" <?= $filterStatus === 'disposed' ? 'selected' : '' ?>>Disposed</option>
                </select>
            </div>
            <div>
                <label style="visibility:hidden;">Go</label>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if ($search || $filterType || $filterCategory || $filterStatus): ?>
                    <a href="<?= BASE_URL ?>browse.php" class="btn btn-outline" style="margin-left:8px;">Clear</a>
                <?php endif; ?>
            </div>
        </div>
    </form>

    <!-- RESULTS -->
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="empty-icon">🔎</div>
            <h3>No items match your search</h3>
            <p>Try adjusting your filters or search terms.</p>
            <a href="<?= BASE_URL ?>browse.php" class="btn btn-primary">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="grid grid-3" id="itemsGrid">
            <?php foreach ($items as $item): ?>
                <div class="card item-card"
                     data-name="<?= sanitize($item['item_name']) ?>"
                     data-type="<?= sanitize($item['type']) ?>"
                     data-category="<?= sanitize($item['category']) ?>"
                     data-location="<?= sanitize($item['location']) ?>"
                     data-description="<?= sanitize($item['description']) ?>"
                     data-status="<?= sanitize($item['status']) ?>">

                    <?php if (!empty($item['image_path']) && file_exists(BASE_PATH . $item['image_path'])): ?>
                        <img src="<?= BASE_URL . sanitize($item['image_path']) ?>"
                             alt="<?= sanitize($item['item_name']) ?>"
                             class="card-img">
                    <?php else: ?>
                        <div class="card-img-placeholder">
                            <?= $item['type'] === 'lost' ? '🔍' : '📦' ?>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="card-title"><?= sanitize($item['item_name']) ?></div>
                        <p class="card-text">📁 <?= sanitize($item['category']) ?></p>
                        <p class="card-text">📍 <?= sanitize($item['location']) ?></p>
                        <p class="card-text">📅 <?= formatDate($item['date_reported']) ?></p>
                        <p class="card-text">👤 <?= sanitize($item['full_name']) ?></p>
                    </div>

                    <div class="card-footer">
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <span class="badge-status badge-<?= sanitize($item['type']) ?>"><?= ucfirst($item['type']) ?></span>
                            <span class="badge-status <?= getStatusBadgeClass($item['status']) ?>"><?= ucfirst($item['status']) ?></span>
                        </div>
                        <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$item['item_id'] ?>"
                           class="btn btn-primary btn-sm">Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- No-results message for JS live filter -->
        <div id="noResults" class="empty-state" style="display:none;">
            <div class="empty-icon">🔎</div>
            <h3>No items match your search</h3>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <?php
            $queryParams = array_filter([
                'type'     => $filterType,
                'category' => $filterCategory,
                'status'   => $filterStatus,
                'search'   => $search,
            ]);
            ?>
            <div class="pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) ?>">&laquo;</a>
                <?php endif; ?>

                <?php for ($p = max(1, $currentPage - 2); $p <= min($totalPages, $currentPage + 2); $p++): ?>
                    <?php if ($p === $currentPage): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $p])) ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) ?>">&raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
