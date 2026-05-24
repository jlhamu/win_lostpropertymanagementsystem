<?php
/**
 * Report Found Item – Wentworth Lost and Found Management System
 * After inserting, runs matching logic and sends email notification if a match is found.
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$errors  = [];
$formData = [
    'item_name'     => '',
    'category'      => '',
    'description'   => '',
    'location'      => '',
    'date_reported' => date('Y-m-d'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName     = trim($_POST['item_name']     ?? '');
    $category     = trim($_POST['category']      ?? '');
    $description  = trim($_POST['description']   ?? '');
    $location     = trim($_POST['location']      ?? '');
    $dateReported = trim($_POST['date_reported'] ?? '');

    $formData = [
        'item_name'     => $itemName,
        'category'      => $category,
        'description'   => $description,
        'location'      => $location,
        'date_reported' => $dateReported,
    ];

    // ── Validate ─────────────────────────────────────────────────────
    if (empty($itemName))    $errors[] = 'Item name is required.';
    if (empty($category))    $errors[] = 'Please select a category.';
    if (empty($description)) $errors[] = 'Description is required.';
    if (empty($location))    $errors[] = 'Location found is required.';
    if (empty($dateReported)) {
        $errors[] = 'Date found is required.';
    } elseif (strtotime($dateReported) > time()) {
        $errors[] = 'Date found cannot be in the future.';
    }
    if (!in_array($category, getCategories(), true)) {
        $errors[] = 'Invalid category selected.';
    }

    // ── Image upload ─────────────────────────────────────────────────
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $imagePath = uploadItemImage($_FILES['image']);
        if ($imagePath === null) {
            $errors[] = 'Image upload failed. Allowed types: JPG, PNG, GIF. Max size: 5 MB.';
        }
    }

    // ── Insert ───────────────────────────────────────────────────────
    if (empty($errors)) {
        $stmt = $pdo->prepare(
            "INSERT INTO items
                (user_id, type, item_name, category, description, location, date_reported, image_path)
             VALUES (?, 'found', ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['user_id'],
            $itemName,
            $category,
            $description,
            $location,
            $dateReported,
            $imagePath,
        ]);
        $newItemId = (int)$pdo->lastInsertId();

        // ── Matching: look for open lost items that might match ───────
        $foundItemRow = [
            'item_id'       => $newItemId,
            'item_name'     => $itemName,
            'category'      => $category,
            'description'   => $description,
            'location'      => $location,
            'date_reported' => $dateReported,
        ];

        $matches = findPotentialMatches($pdo, $foundItemRow);

        if (!empty($matches)) {
            // Load email helper (PHPMailer) – gracefully skip if vendor not installed
            $emailFile = BASE_PATH . 'vendor/autoload.php';
            if (file_exists($emailFile)) {
                require_once 'includes/email.php';
                foreach ($matches as $lostItem) {
                    // Save in-app notification
                    addNotification(
                        $pdo,
                        (int)$lostItem['user_id'],
                        $newItemId,
                        "A possible match was found for your lost item \"{$lostItem['item_name']}\". "
                        . "A \"{$itemName}\" was reported found at {$location}."
                    );
                    // Send email
                    sendMatchNotification(
                        $lostItem['email'],
                        $lostItem['full_name'],
                        $lostItem,
                        $foundItemRow
                    );
                }
            } else {
                // Still save in-app notifications even without PHPMailer
                foreach ($matches as $lostItem) {
                    addNotification(
                        $pdo,
                        (int)$lostItem['user_id'],
                        $newItemId,
                        "A possible match was found for your lost item \"{$lostItem['item_name']}\". "
                        . "A \"{$itemName}\" was reported found at {$location}."
                    );
                }
            }
        }

        header('Location: ' . BASE_URL . 'item-detail.php?id=' . $newItemId . '&reported=found');
        exit;
    }
}

$categories = getCategories();
$pageTitle  = 'Report Found Item';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>📦 Report a Found Item</h1>
        <p>Help reunite someone with their lost belonging</p>
    </div>
</div>

<div class="container" style="padding-bottom:50px;">
    <div class="form-container" style="max-width:650px;">
        <h2 style="color:var(--primary); margin-bottom:6px;">Found Item Details</h2>
        <p style="color:var(--text-light); margin-bottom:25px; font-size:.9rem;">
            If a matching lost item is found, the owner will be notified automatically.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" data-validate>

            <div class="form-group">
                <label for="item_name">Item Name <span class="req">*</span></label>
                <input type="text" id="item_name" name="item_name"
                       class="form-control"
                       value="<?= sanitize($formData['item_name']) ?>"
                       placeholder="e.g. Blue Umbrella"
                       maxlength="150" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="category">Category <span class="req">*</span></label>
                    <select id="category" name="category" class="form-control" required>
                        <option value="">— Select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= sanitize($cat) ?>"
                                <?= $formData['category'] === $cat ? 'selected' : '' ?>>
                                <?= sanitize($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="date_reported">Date Found <span class="req">*</span></label>
                    <input type="date" id="date_reported" name="date_reported"
                           class="form-control"
                           value="<?= sanitize($formData['date_reported']) ?>"
                           max="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="location">Location Found <span class="req">*</span></label>
                <input type="text" id="location" name="location"
                       class="form-control"
                       value="<?= sanitize($formData['location']) ?>"
                       placeholder="e.g. Cafeteria, Ground Floor"
                       maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="description">Description <span class="req">*</span></label>
                <textarea id="description" name="description"
                          class="form-control"
                          placeholder="Describe the item: colour, brand, condition, any identifiers…"
                          required><?= sanitize($formData['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="image">Item Photo <span style="color:var(--text-light); font-weight:400;">(optional)</span></label>
                <input type="file" id="image" name="image"
                       class="form-control"
                       accept="image/jpeg,image/png,image/gif">
                <span class="form-text">JPG, PNG or GIF · max 5 MB</span>
                <img id="imagePreview" src="#" alt="Preview" style="max-height:200px;">
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100" style="margin-top:6px;">
                Submit Found Item Report
            </button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
