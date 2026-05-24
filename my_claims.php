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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_claim') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $claimId = (int)($_POST['claim_id'] ?? 0);
    if ($claimId <= 0) {
        $errors[] = 'Invalid claim selection.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT c.id, c.status FROM claims c WHERE c.id = ? AND c.claimed_by = ? LIMIT 1');
        $stmt->execute([$claimId, $userId]);
        $claim = $stmt->fetch();

        if (!$claim) {
            $errors[] = 'Claim not found.';
        } elseif ($claim['status'] !== 'pending') {
            $errors[] = 'Only pending claims can be cancelled.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('DELETE FROM claims WHERE id = ? AND claimed_by = ?');
        $stmt->execute([$claimId, $userId]);
        $successMessage = 'Your pending claim has been cancelled.';
    }
}

$stmt = $pdo->prepare('SELECT c.id, c.claim_description, c.status, c.created_at, li.title, li.location_found, li.date_found, li.status AS item_status FROM claims c JOIN lost_items li ON c.item_id = li.id WHERE c.claimed_by = ? ORDER BY c.created_at DESC');
$stmt->execute([$userId]);
$claims = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Claims | WIN Lost Property</title>
  <meta name="description" content="Review and manage your lost property claims.">
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
    .panel p { margin: 0 0 1rem; color: var(--muted); }
    .alert { border-radius: 18px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .alert.success { background: rgba(0,86,214,.08); border: 1px solid rgba(0,86,214,.2); color: #00317a; }
    .alert.error { background: rgba(255,112,101,.12); border: 1px solid rgba(255,112,101,.3); color: #922d1d; }
    .table-wrap { overflow-x: auto; margin-top: 1.5rem; border-radius: 24px; background: #fff; border: 1px solid rgba(20,32,59,.08); }
    table { width: 100%; border-collapse: collapse; min-width: 820px; }
    thead th { text-align: left; color: var(--muted); font-size: 0.95rem; padding: 1rem 1rem 0.75rem; border-bottom: 1px solid rgba(20,32,59,.12); background: #f7f8fc; }
    td { padding: 1rem 1rem; vertical-align: middle; }
    tbody tr { border-bottom: 1px solid rgba(20,32,59,.08); }
    tbody tr:last-child { border-bottom: none; }
    .badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.45rem 0.85rem; border-radius: 999px; font-size: 0.84rem; color: #fff; }
    .badge.pending { background: rgba(255,159,67,.95); }
    .badge.approved { background: rgba(12,133,88,.9); }
    .badge.rejected { background: rgba(220,53,69,.9); }
    .badge.unclaimed { background: rgba(0,86,214,.9); }
    .button { display: inline-flex; align-items: center; justify-content: center; padding: 0.95rem 1.4rem; border-radius: 999px; border: none; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; font-weight: 700; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
    .button:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(0,0,0,.08); }
    .button-secondary { background: #fff; color: var(--ink); border: 1px solid var(--border); }
    .button-secondary:hover { background: var(--surface-soft); }
    .nav-links { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
    .nav-links a { display: inline-flex; padding: 0.85rem 1rem; border-radius: 999px; background: #fff; border: 1px solid var(--border); transition: background .18s ease, transform .18s ease; }
    .nav-links a:hover { background: var(--surface-soft); transform: translateY(-1px); }
    .table-actions form { margin: 0; }
    .table-actions .button-secondary { padding: 0.7rem 1rem; font-size: 0.92rem; }
    @media (max-width: 900px) { .table-wrap { min-width: auto; } }
    @media (max-width: 720px) { .topbar { flex-direction: column; align-items: stretch; } .page { padding: 1rem; } .nav-links { justify-content: flex-start; } }
  </style>
</head>
<body>
  <div class="page">
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo">WIN</div>
        <div>
          <strong>My Claims</strong>
          <div style="font-size:0.95rem;color:var(--muted);">Manage your claim requests.</div>
        </div>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_item.php">Report Item</a>
        <a href="view_items.php">View Items</a>
        <a href="profile.php">Profile</a>
        <a href="contact.php">Contact</a>
      </div>
    </header>

    <section class="panel">
      <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:1rem;">
        <div>
          <h1 style="margin:0;font-size:2rem;">Your claims</h1>
          <p style="margin:0.75rem 0 0;color:var(--muted);">Review status and cancel any pending claims.</p>
        </div>
        <a href="view_items.php" class="button">View lost items</a>
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

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Item</th>
              <th>Location</th>
              <th>Claim status</th>
              <th>Item status</th>
              <th>Requested</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($claims)): ?>
              <tr>
                <td colspan="6" style="padding:1.5rem;text-align:center;color:var(--muted);">You have not submitted any claims yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($claims as $claim): ?>
                <tr>
                  <td><?= htmlspecialchars($claim['title'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><?= htmlspecialchars($claim['location_found'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td><span class="badge <?= htmlspecialchars($claim['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($claim['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><span class="badge <?= htmlspecialchars($claim['item_status'] === 'claimed' ? 'approved' : 'unclaimed', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($claim['item_status'] === 'claimed' ? 'Claimed' : 'Available', ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td><?= htmlspecialchars(date('M j, Y', strtotime($claim['created_at'])), ENT_QUOTES, 'UTF-8') ?></td>
                  <td>
                    <?php if ($claim['status'] === 'pending'): ?>
                      <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="cancel_claim">
                        <input type="hidden" name="claim_id" value="<?= (int)$claim['id'] ?>">
                        <button type="submit" class="button button-secondary">Cancel</button>
                      </form>
                    <?php else: ?>
                      <span style="color:var(--muted);font-size:0.95rem;">No actions</span>
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
