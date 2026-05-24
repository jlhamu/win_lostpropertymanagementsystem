<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $locationFound = trim($_POST['location_found'] ?? '');
    $dateFound = trim($_POST['date_found'] ?? '');
    $imagePath = null;

    if ($title === '') {
        $errors[] = 'Please enter a title for the lost item.';
    }

    if ($category === '') {
        $errors[] = 'Please select a category.';
    }

    if ($locationFound === '') {
        $errors[] = 'Please enter the location where the item was found.';
    }

    if ($dateFound === '') {
        $errors[] = 'Please choose the date the item was found.';
    }

    if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if ($_FILES['item_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading the image.';
        } elseif (!in_array($_FILES['item_image']['type'], $allowedTypes, true)) {
            $errors[] = 'Only JPG, PNG, and GIF images are allowed.';
        } elseif ($_FILES['item_image']['size'] > 4 * 1024 * 1024) {
            $errors[] = 'The image size must be less than 4MB.';
        } else {
            $uploadsDir = __DIR__ . '/uploads';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }
            $extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
            $fileName = 'item_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
            $destination = $uploadsDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['item_image']['tmp_name'], $destination)) {
                $imagePath = 'uploads/' . $fileName;
            } else {
                $errors[] = 'Unable to save the uploaded image. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO lost_items (title, description, category, location_found, date_found, image_path, status, reported_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $description, $category, $locationFound, $dateFound, $imagePath, 'unclaimed', $userId]);

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Lost Item | WIN Lost Property</title>
  <meta name="description" content="Report a lost item to the WIN Lost Property Management System.">
  <style>
    :root {
      --purple: #5b2c91;
      --blue: #0056d6;
      --ink: #14203b;
      --text: #16253d;
      --muted: #5e6d84;
      --surface: #ffffff;
      --surface-soft: #f7f8fc;
      --border: rgba(20,32,59,0.12);
      --shadow: 0 28px 80px rgba(20,32,59,.08);
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #f4f6fb; color: var(--text); }
    a { color: inherit; text-decoration: none; }
    .page { max-width: 1180px; margin: 0 auto; padding: 1.5rem; display: grid; gap: 1.5rem; }
    .topbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 0; }
    .brand { display: inline-flex; align-items: center; gap: 0.85rem; font-weight: 700; color: var(--ink); }
    .brand-logo { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 18px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; }
    .panel { background: var(--surface); border-radius: 32px; border: 1px solid var(--border); box-shadow: var(--shadow); padding: 2rem; }
    .panel h1 { margin: 0 0 0.75rem; font-size: clamp(1.9rem, 2.4vw, 2.6rem); line-height: 1.05; }
    .panel p { margin: 0 0 1.5rem; color: var(--muted); }
    .form-grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .input-group { display: grid; gap: 0.5rem; }
    label { font-weight: 600; }
    input, textarea, select { width: 100%; min-height: 3.2rem; border-radius: 16px; border: 1px solid rgba(20,32,59,.14); background: var(--surface-soft); padding: 0.95rem 1rem; color: var(--ink); font: inherit; }
    textarea { min-height: 10rem; resize: vertical; }
    .button { display: inline-flex; align-items: center; justify-content: center; padding: 1rem 1.5rem; border-radius: 999px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; border: none; font-weight: 700; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
    .button:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(0,0,0,.08); }
    .button-secondary { background: #fff; color: var(--ink); border: 1px solid var(--border); }
    .alert { border-radius: 18px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .alert.error { background: rgba(255,112,101,.12); border: 1px solid rgba(255,112,101,.3); color: #922d1d; }
    .nav-links { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
    .nav-links a { display: inline-flex; padding: 0.85rem 1rem; border-radius: 999px; background: #fff; border: 1px solid var(--border); transition: background .18s ease, transform .18s ease; }
    .nav-links a:hover { background: var(--surface-soft); transform: translateY(-1px); }
    .form-grid .input-group:last-child { grid-column: 1 / -1; }
    .form-actions { display: flex; flex-wrap: wrap; gap: 0.85rem; margin-top: 1.5rem; }
    @media (max-width: 900px) { .form-grid { grid-template-columns: 1fr; } }
    @media (max-width: 720px) { .topbar { flex-direction: column; align-items: stretch; } .page { padding: 1rem; } }
  </style>
</head>
<body>
  <div class="page">
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo">WIN</div>
        <div>
          <strong>WIN Lost Property</strong>
          <div style="font-size:0.95rem;color:var(--muted);">Report a lost item</div>
        </div>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="view_items.php">View Items</a>
        <a href="my_claims.php">My Claims</a>
        <a href="profile.php">Profile</a>
        <a href="contact.php">Contact</a>
      </div>
    </header>

    <section class="panel">
      <h1>Report lost property</h1>
      <p>Submit a new lost item report so campus security can begin the recovery process.</p>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <strong>Unable to submit report.</strong>
          <ul style="margin:0.75rem 0 0;padding-left:1.2rem;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <div class="form-grid">
          <div class="input-group">
            <label for="title">Item title</label>
            <input id="title" name="title" type="text" value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="input-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
              <option value="">Select category</option>
              <option value="Electronics"<?= (($_POST['category'] ?? '') === 'Electronics') ? ' selected' : '' ?>>Electronics</option>
              <option value="Accessories"<?= (($_POST['category'] ?? '') === 'Accessories') ? ' selected' : '' ?>>Accessories</option>
              <option value="Stationery"<?= (($_POST['category'] ?? '') === 'Stationery') ? ' selected' : '' ?>>Stationery</option>
              <option value="Apparel"<?= (($_POST['category'] ?? '') === 'Apparel') ? ' selected' : '' ?>>Apparel</option>
              <option value="Other"<?= (($_POST['category'] ?? '') === 'Other') ? ' selected' : '' ?>>Other</option>
            </select>
          </div>
          <div class="input-group">
            <label for="location_found">Location found</label>
            <input id="location_found" name="location_found" type="text" value="<?= htmlspecialchars($_POST['location_found'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="input-group">
            <label for="date_found">Date found</label>
            <input id="date_found" name="date_found" type="date" value="<?= htmlspecialchars($_POST['date_found'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
          </div>
          <div class="input-group" style="grid-column:1/-1;">
            <label for="description">Description</label>
            <textarea id="description" name="description" placeholder="Provide any details that will help identify the item."><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          </div>
          <div class="input-group" style="grid-column:1/-1;">
            <label for="item_image">Image (optional)</label>
            <input id="item_image" name="item_image" type="file" accept="image/jpeg,image/png,image/gif">
          </div>
        </div>
        <div style="margin-top:1.5rem;display:flex;gap:0.85rem;flex-wrap:wrap;">
          <button type="submit" class="button">Submit report</button>
          <a href="dashboard.php" class="button button-secondary">Back to dashboard</a>
        </div>
      </form>
    </section>
  </div>
</body>
</html>
