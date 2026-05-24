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
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'claim_item') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $itemId = (int)($_POST['item_id'] ?? 0);
    $claimDescription = trim($_POST['claim_description'] ?? '');

    if ($itemId <= 0) {
        $errors[] = 'Invalid item selection.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, reported_by, status FROM lost_items WHERE id = ? LIMIT 1');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if (!$item) {
            $errors[] = 'The selected item was not found.';
        } elseif ($item['reported_by'] === $userId) {
            $errors[] = 'You cannot claim an item that you reported.';
        } elseif ($item['status'] !== 'unclaimed') {
            $errors[] = 'This item is no longer available for claim.';
        }
    }

    if (empty($errors)) {
        if ($claimDescription === '') {
            $claimDescription = 'Claim request submitted by user.';
        }

        $stmt = $pdo->prepare('INSERT INTO claims (item_id, claimed_by, claim_description, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$itemId, $userId, $claimDescription, 'pending']);

        $successMessage = 'Your claim has been submitted and is pending review.';
    }
}

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = '(li.title LIKE ? OR li.description LIKE ? OR li.location_found LIKE ? OR u.full_name LIKE ?)';
    $queryValue = '%' . $search . '%';
    $params[] = $queryValue;
    $params[] = $queryValue;
    $params[] = $queryValue;
    $params[] = $queryValue;
}

if (in_array($statusFilter, ['unclaimed', 'claimed'], true)) {
    $whereClauses[] = 'li.status = ?';
    $params[] = $statusFilter;
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

$stmt = $pdo->prepare("SELECT li.id, li.title, li.category, li.location_found, li.date_found, li.status, li.image_path, u.full_name AS reporter_name, li.reported_by FROM lost_items li JOIN users u ON li.reported_by = u.id $whereSql ORDER BY li.date_found DESC");
$stmt->execute($params);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>View Lost Items | WIN Lost Property</title>
  <meta name="description" content="Browse lost property items and submit claims through WIN Lost Property.">
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
    .page { max-width: 1240px; margin: 0 auto; padding: 1.5rem; display: grid; gap: 1.5rem; }
    .topbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 0; }
    .brand { display: inline-flex; align-items: center; gap: 0.85rem; font-weight: 700; color: var(--ink); }
    .brand-logo { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 18px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; }
    .panel { background: var(--surface); border-radius: 32px; border: 1px solid var(--border); box-shadow: var(--shadow); padding: 2rem; }
    .panel h1 { margin: 0 0 0.75rem; font-size: clamp(1.9rem, 2.4vw, 2.75rem); line-height: 1.05; }
    .panel p { margin: 0 0 1rem; color: var(--muted); }
    .search-grid { display: grid; gap: 1rem; grid-template-columns: 1fr 240px; margin-top: 1.5rem; }
    .input-group { display: grid; gap: 0.5rem; }
    input, select, textarea { width: 100%; min-height: 3.2rem; border-radius: 16px; border: 1px solid rgba(20,32,59,.14); background: var(--surface-soft); padding: 0.95rem 1rem; color: var(--ink); font: inherit; }
    button { display: inline-flex; align-items: center; justify-content: center; padding: 1rem 1.5rem; border-radius: 999px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; border: none; font-weight: 700; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
    button:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(0,0,0,.08); }
    .button-secondary { background: #fff; color: var(--ink); border: 1px solid var(--border); }
    .button-secondary:hover { background: var(--surface-soft); }
    .alert { border-radius: 18px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .alert.success { background: rgba(0,86,214,.08); border: 1px solid rgba(0,86,214,.2); color: #00317a; }
    .alert.error { background: rgba(255,112,101,.12); border: 1px solid rgba(255,112,101,.3); color: #922d1d; }
    .table-wrap { overflow-x: auto; margin-top: 1.5rem; border-radius: 24px; background: #fff; border: 1px solid rgba(20,32,59,.08); }
    table { width: 100%; border-collapse: collapse; min-width: 860px; }
    thead th { text-align: left; color: var(--muted); font-size: 0.95rem; padding: 1rem 1rem 0.75rem; border-bottom: 1px solid rgba(20,32,59,.12); background: #f7f8fc; }
    tbody tr { border-bottom: 1px solid rgba(20,32,59,.08); }
    tbody tr:last-child { border-bottom: none; }
    td { padding: 1rem 1rem; vertical-align: middle; }
    .badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.45rem 0.85rem; border-radius: 999px; font-size: 0.84rem; color: #fff; }
    .badge.unclaimed { background: rgba(0,86,214,.9); }
    .badge.claimed { background: rgba(12,133,88,.9); }
    .claim-action { display: inline-flex; gap: 0.5rem; flex-wrap: wrap; }
    .claim-form { display: grid; gap: 0.75rem; }
    .nav-links { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
    .nav-links a { display: inline-flex; padding: 0.85rem 1rem; border-radius: 999px; background: #fff; border: 1px solid var(--border); transition: background .18s ease, transform .18s ease; }
    .nav-links a:hover { background: var(--surface-soft); transform: translateY(-1px); }
    @media (max-width: 860px) { .search-grid { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="page">
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo">WIN</div>
        <div>
          <strong>View Lost Items</strong>
          <div style="font-size:0.95rem;color:var(--muted);">Browse, filter, and claim available items.</div>
        </div>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_item.php">Report Item</a>
        <a href="my_claims.php">My Claims</a>
        <a href="profile.php">Profile</a>
        <a href="contact.php">Contact</a>
      </div>
    </header>

    <section class="panel">
      <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;">
        <div>
          <h1 style="margin:0;font-size:2rem;">Available lost items</h1>
          <p style="margin:0.75rem 0 0;color:var(--muted);">Filter by keyword or status to find the item you need.</p>
        </div>
        <a href="report_item.php" class="button">Report a new item</a>
      </div>

      <?php if ($successMessage): ?>
        <div class="alert success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>
      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul style="margin:0;padding-left:1.2rem;">
            <?php foreach ($errors as $error): ?>
              <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="get" class="search-grid">
        <div class="input-group">
          <label for="search">Search</label>
          <input id="search" name="search" type="search" placeholder="Title, location, reporter..." value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="input-group">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="">All statuses</option>
            <option value="unclaimed"<?= $statusFilter === 'unclaimed' ? ' selected' : '' ?>>Unclaimed</option>
            <option value="claimed"<?= $statusFilter === 'claimed' ? ' selected' : '' ?>>Claimed</option>
          </select>
        </div>
        <div style="display:flex;align-items:flex-end;">
          <button type="submit" class="button">Apply filter</button>
        </div>
      </form>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Location</th>
              <th>Date Found</th>
              <th>Reporter</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="7" style="padding:1.5rem;text-align:center;color:var(--muted);">No lost items match your search.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($items as $item): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td><?= htmlspecialchars($item['category'] ?: 'Other', ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($item['location_found'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars(date('M j, Y', strtotime($item['date_found'])), ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($item['reporter_name'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge <?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($item['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td>
                    <?php if ($item['reported_by'] === $userId): ?>
                      <span style="color:var(--muted);font-size:0.95rem;">Your report</span>
                    <?php elseif ($item['status'] !== 'unclaimed'): ?>
                      <span style="color:var(--muted);font-size:0.95rem;">Not available</span>
                    <?php else: ?>
                      <form method="post" class="claim-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="claim_item">
                        <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                        <input type="hidden" name="claim_description" value="I would like to claim this item.">
                        <button type="submit" class="button">Claim this item</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</body>
</html>
