<?php
/**
 * Claim Item – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$itemId = (int)($_GET['id'] ?? 0);
if ($itemId <= 0) {
    header('Location: ' . BASE_URL . 'browse.php');
    exit;
}

// Load item
$stmt = $pdo->prepare(
    "SELECT i.*, u.full_name AS reporter_name
     FROM items i
     JOIN users u ON i.user_id = u.user_id
     WHERE i.item_id = ?"
);
$stmt->execute([$itemId]);
$item = $stmt->fetch();

if (!$item || $item['type'] !== 'found' || $item['status'] !== 'open') {
    header('Location: ' . BASE_URL . 'browse.php');
    exit;
}

// Owner cannot claim their own item
if ((int)$item['user_id'] === (int)$_SESSION['user_id']) {
    header('Location: ' . BASE_URL . 'item-detail.php?id=' . $itemId);
    exit;
}

// Already claimed?
$chkStmt = $pdo->prepare(
    "SELECT claim_id FROM claims WHERE item_id = ? AND claimant_id = ?"
);
$chkStmt->execute([$itemId, $_SESSION['user_id']]);
if ($chkStmt->fetch()) {
    header('Location: ' . BASE_URL . 'item-detail.php?id=' . $itemId . '&already_claimed=1');
    exit;
}

$errors      = [];
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description'] ?? '');

    if (empty($description)) {
        $errors[] = 'Please describe how you can prove ownership.';
    } elseif (strlen($description) < 20) {
        $errors[] = 'Please provide more detail (at least 20 characters).';
    }

    if (empty($errors)) {
        // Insert claim
        $ins = $pdo->prepare(
            "INSERT INTO claims (item_id, claimant_id, description) VALUES (?, ?, ?)"
        );
        $ins->execute([$itemId, $_SESSION['user_id'], $description]);

        // Mark item as claimed
        $upd = $pdo->prepare("UPDATE items SET status = 'claimed' WHERE item_id = ?");
        $upd->execute([$itemId]);

        header('Location: ' . BASE_URL . 'my-reports.php?claim_submitted=1');
        exit;
    }
}

$pageTitle = 'Submit Claim';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>📝 Submit Ownership Claim</h1>
        <p>Claim: <?= sanitize($item['item_name']) ?></p>
    </div>
</div>

<div class="container" style="padding-bottom:50px;">

    <!-- Item Summary Card -->
    <div style="max-width:650px; margin:0 auto 24px;">
        <div class="card" style="display:flex; gap:0; flex-direction:row; overflow:hidden; align-items:stretch;">
            <?php if (!empty($item['image_path']) && file_exists(BASE_PATH . $item['image_path'])): ?>
                <img src="<?= BASE_URL . sanitize($item['image_path']) ?>"
                     alt="<?= sanitize($item['item_name']) ?>"
                     style="width:130px; object-fit:cover; flex-shrink:0;">
            <?php else: ?>
                <div style="width:110px; background:var(--secondary); display:flex; align-items:center; justify-content:center; font-size:2.5rem; flex-shrink:0;">📦</div>
            <?php endif; ?>
            <div class="card-body">
                <div class="card-title"><?= sanitize($item['item_name']) ?></div>
                <p class="card-text">📁 <?= sanitize($item['category']) ?></p>
                <p class="card-text">📍 <?= sanitize($item['location']) ?></p>
                <p class="card-text">📅 <?= formatDate($item['date_reported']) ?></p>
            </div>
        </div>
    </div>

    <div class="form-container" style="max-width:650px;">
        <h2 style="color:var(--primary); margin-bottom:8px;">Proof of Ownership</h2>
        <p style="color:var(--text-light); margin-bottom:22px; font-size:.9rem;">
            Describe unique features that prove the item belongs to you — colour, brand,
            contents, serial number, unusual marks, or any other identifiers.
            The admin will review your claim.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $e): ?><p><?= sanitize($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="description">Ownership Description <span class="req">*</span></label>
                <textarea id="description" name="description"
                          class="form-control"
                          style="min-height:160px;"
                          placeholder="Example: Black Samsonite laptop bag, has a red keyring attached, contains a MacBook Air charger and blue notebook with my name 'Alex' written inside the cover."
                          required><?= sanitize($description) ?></textarea>
                <span class="form-text">Be specific — vague claims are less likely to be approved.</span>
            </div>

            <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:8px;">
                <button type="submit" class="btn btn-primary btn-lg">Submit Claim</button>
                <a href="<?= BASE_URL ?>item-detail.php?id=<?= $itemId ?>" class="btn btn-outline btn-lg">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
