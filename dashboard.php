<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$recentItems = [];

define('DEFAULT_LIMIT', 6);

$stmt = $pdo->query('SELECT COUNT(*) AS total FROM lost_items');
$totalItems = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM lost_items WHERE status = ?');
$stmt->execute(['claimed']);
$claimedItems = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM claims WHERE status = ?');
$stmt->execute(['pending']);
$pendingClaims = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM lost_items WHERE reported_by = ?');
$stmt->execute([$user['id']]);
$myReports = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT li.title, li.category, li.location_found, li.date_found, li.status, u.full_name AS reported_by FROM lost_items li JOIN users u ON li.reported_by = u.id ORDER BY li.date_found DESC LIMIT ?');
$stmt->bindValue(1, DEFAULT_LIMIT, PDO::PARAM_INT);
$stmt->execute();
$recentItems = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | WIN Lost Property Management System</title>
  <meta name="description" content="Professional dashboard for WIN Lost Property Management System.">
  <style>
    :root {
      --purple: #5b2c91;
      --blue: #0056d6;
      --blue-soft: #e4ecff;
      --ink: #14203b;
      --text: #16253d;
      --muted: #5e6d84;
      --surface: #ffffff;
      --surface-soft: #f7f8fc;
      --border: rgba(20,32,59,0.12);
      --shadow: 0 28px 80px rgba(20,32,59,.08);
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      color-scheme: light;
    }

    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body { margin: 0; min-height: 100vh; background: radial-gradient(circle at top left, rgba(91,44,145,.12), transparent 24%), radial-gradient(circle at bottom right, rgba(0,86,214,.08), transparent 26%), #f9f9fe; color: var(--text); }
    a { color: inherit; text-decoration: none; }
    .topbar { position: sticky; top: 0; z-index: 20; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.5rem; background: rgba(255,255,255,.96); border-bottom: 1px solid rgba(20,32,59,.08); backdrop-filter: blur(18px); }
    .brand { display: inline-flex; align-items: center; gap: 0.85rem; font-weight: 700; color: var(--ink); }
    .brand-logo { width: 44px; height: 44px; display: grid; place-items: center; border-radius: 18px; background: linear-gradient(135deg, var(--purple), var(--blue)); box-shadow: 0 12px 34px rgba(91,44,145,.16); color: #fff; font-size: 1rem; }
    .brand-text { display: grid; gap: 0.08rem; }
    .brand-text strong { font-size: 1rem; }
    .brand-text span { color: var(--muted); font-size: 0.85rem; }
    .button { display: inline-flex; align-items: center; justify-content: center; padding: 0.95rem 1.5rem; border-radius: 999px; font-weight: 700; transition: transform .22s ease, box-shadow .22s ease, background-color .22s ease; }
    .button:hover { transform: translateY(-1px); }
    .button-primary { background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; box-shadow: 0 18px 32px rgba(91,44,145,.18); }
    .button-secondary { background: #fff; color: var(--ink); border: 1px solid rgba(20,32,59,.12); }
    .page { display: grid; grid-template-columns: minmax(260px, 320px) 1fr; gap: 1.75rem; max-width: 1420px; margin: 0 auto; padding: 1.5rem; }
    .sidebar { display: grid; gap: 1rem; position: sticky; top: 1rem; align-self: start; }
    .panel { border-radius: 32px; background: rgba(255,255,255,.98); border: 1px solid rgba(20,32,59,.08); box-shadow: var(--shadow); padding: 1.75rem; }
    .sidebar-heading { margin: 0 0 1rem; font-size: 0.95rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--purple); }
    .sidebar-summary { display: grid; gap: 0.85rem; }
    .sidebar-summary-card { padding: 1rem; border-radius: 22px; background: #f7f8fc; }
    .sidebar-nav { display: grid; gap: 0.75rem; }
    .sidebar-nav a { display: block; padding: 1rem 1.1rem; border-radius: 18px; background: #f7f8fc; color: var(--ink); font-weight: 600; transition: background .18s ease, transform .18s ease; }
    .sidebar-nav a:hover { background: rgba(0,86,214,.08); transform: translateX(1px); }
    .sidebar-nav a.active { background: rgba(0,86,214,.12); }
    main { display: grid; gap: 1.5rem; }
    .hero { display: grid; gap: 1.5rem; margin-top: 1rem; }
    .welcome-card { display: grid; gap: 1.25rem; }
    .eyebrow { display: inline-flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; font-size: 0.95rem; font-weight: 700; color: var(--purple); letter-spacing: 0.16em; text-transform: uppercase; }
    .eyebrow::before { content: ''; width: 40px; height: 2px; border-radius: 999px; background: var(--purple); }
    .welcome-card h1 { margin: 0; font-size: clamp(2rem, 3vw, 2.8rem); line-height: 1.02; }
    .welcome-card p { margin: 0; color: var(--muted); max-width: 52rem; }
    .user-chip { display: inline-flex; align-items: center; gap: 0.75rem; padding: 0.85rem 1rem; border-radius: 999px; background: rgba(0,86,214,.08); color: var(--ink); font-weight: 700; }
    .stats-grid { display: grid; gap: 1rem; grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .metric-card { display: grid; gap: 0.75rem; padding: 1.5rem; border-radius: 28px; background: #fff; border: 1px solid rgba(20,32,59,.08); }
    .metric-card strong { font-size: 2rem; color: var(--blue); }
    .metric-card span { color: var(--muted); }
    .quick-actions { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 1rem; }
    .action-card { display: grid; gap: 0.75rem; padding: 1.35rem; border-radius: 28px; background: #fff; border: 1px solid rgba(20,32,59,.08); transition: transform .18s ease, box-shadow .18s ease; }
    .action-card:hover { transform: translateY(-2px); box-shadow: 0 18px 36px rgba(20,32,59,.1); }
    .action-card h3 { margin: 0; font-size: 1.05rem; }
    .action-card p { margin: 0; color: var(--muted); font-size: 0.98rem; }
    .table-wrap { overflow-x: auto; margin-top: 1rem; border-radius: 24px; background: #fff; border: 1px solid rgba(20,32,59,.08); }
    table { width: 100%; border-collapse: collapse; min-width: 720px; }
    thead th { text-align: left; color: var(--muted); font-size: 0.95rem; padding: 1rem 1rem 0.75rem; border-bottom: 1px solid rgba(20,32,59,.08); background: #f7f8fc; }
    tbody tr { border-bottom: 1px solid rgba(20,32,59,.08); }
    tbody td { padding: 1rem 1rem; vertical-align: middle; }
    tbody tr:last-child { border-bottom: none; }
    .table-actions { display: inline-flex; gap: 0.5rem; flex-wrap: wrap; }
    .badge { display: inline-flex; align-items: center; justify-content: center; min-width: 98px; padding: 0.55rem 0.85rem; border-radius: 999px; font-size: 0.85rem; font-weight: 700; color: #fff; }
    .badge.unclaimed { background: rgba(0,86,214,.9); }
    .badge.claimed { background: rgba(12,133,88,.9); }
    .badge.pending { background: rgba(255,159,67,.95); }
    .footer-note { color: var(--muted); font-size: 0.95rem; margin-top: 0.75rem; }
    @media (max-width: 1120px) {
      .page { grid-template-columns: 1fr; }
      .sidebar { position: static; }
      .stats-grid, .quick-actions { grid-template-columns: 1fr; }
      .hero { margin-top: 0; }
    }
    @media (max-width: 720px) {
      .topbar { flex-wrap: wrap; justify-content: center; }
      .topbar { padding: 1rem; }
      .page { padding: 1rem; }
      .button { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="brand-logo">WIN</div>
      <div class="brand-text">
        <strong>WIN Lost Property</strong>
        <span>Management System</span>
      </div>
    </div>
    <a href="logout.php" class="button button-secondary">Logout</a>
  </header>

  <div class="page">
    <aside class="sidebar">
      <div class="panel">
        <p class="sidebar-heading">Workspace</p>
        <nav class="sidebar-nav">
          <a class="active" href="dashboard.php">Dashboard</a>
          <a href="report_item.php">Report Item</a>
          <a href="view_items.php">View Items</a>
          <a href="my_claims.php">My Claims</a>
          <a href="profile.php">Profile</a>
          <a href="contact.php">Contact</a>
          <a href="logout.php">Logout</a>
        </nav>
      </div>

      <div class="panel">
        <p class="sidebar-heading">Account summary</p>
        <div class="sidebar-summary">
          <div class="sidebar-summary-card"><strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong><br><span style="color:var(--muted);font-size:0.95rem;">Name</span></div>
          <div class="sidebar-summary-card"><strong><?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></strong><br><span style="color:var(--muted);font-size:0.95rem;">Role</span></div>
          <div class="sidebar-summary-card"><strong><?= htmlspecialchars($user['student_staff_id'], ENT_QUOTES, 'UTF-8') ?></strong><br><span style="color:var(--muted);font-size:0.95rem;">Campus ID</span></div>
        </div>
      </div>
    </aside>

    <main>
      <section class="hero panel welcome-card">
        <div style="display:grid;gap:1rem;">
          <div>
            <p class="eyebrow">Welcome back</p>
            <h1>Good to see you, <?= htmlspecialchars(explode(' ', $user['full_name'])[0], ENT_QUOTES, 'UTF-8') ?>.</h1>
            <p>Use this dashboard to keep lost property workflows moving smoothly across the campus, from intake to claim resolution.</p>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
            <div class="user-chip">Role: <?= htmlspecialchars(ucfirst($user['role']), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-chip">ID: <?= htmlspecialchars($user['student_staff_id'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-chip">Email: <?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
          </div>
        </div>
      </section>

      <section class="stats-grid">
        <div class="metric-card">
          <span>Total Lost Items</span>
          <strong><?= $totalItems ?></strong>
          <p class="footer-note">All items recorded in the system since setup.</p>
        </div>
        <div class="metric-card">
          <span>Claimed Items</span>
          <strong><?= $claimedItems ?></strong>
          <p class="footer-note">Items that have already been returned.</p>
        </div>
        <div class="metric-card">
          <span>Pending Claims</span>
          <strong><?= $pendingClaims ?></strong>
          <p class="footer-note">Claims currently awaiting review.</p>
        </div>
        <div class="metric-card">
          <span>My Reports</span>
          <strong><?= $myReports ?></strong>
          <p class="footer-note">Lost items you have reported personally.</p>
        </div>
      </section>

      <section class="panel">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.25rem;">
          <div>
            <h2 style="margin:0;font-size:1.4rem;">Quick actions</h2>
            <p style="margin:0.5rem 0 0;color:var(--muted);">Navigate to the most common workflows instantly.</p>
          </div>
          <div style="display:flex;flex-wrap:wrap;gap:0.75rem;">
            <a href="report_item.php" class="button button-primary">Report Lost Item</a>
            <a href="view_items.php" class="button button-secondary">View All Items</a>
            <a href="my_claims.php" class="button button-secondary">My Claims</a>
          </div>
        </div>
        <div class="quick-actions">
          <a href="report_item.php" class="action-card">
            <h3>Report Lost Item</h3>
            <p>Create a new report and help the lost item find its owner.</p>
          </a>
          <a href="view_items.php" class="action-card">
            <h3>View Items</h3>
            <p>Browse the lost property inventory and review item details.</p>
          </a>
          <a href="my_claims.php" class="action-card">
            <h3>Claim Tracker</h3>
            <p>Follow the status of claims you have submitted.</p>
          </a>
        </div>
      </section>

      <section class="panel">
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;">
          <div>
            <h2 style="margin:0;font-size:1.4rem;">Recent lost items</h2>
            <p style="margin:0.5rem 0 0;color:var(--muted);">Latest reports from the lost property desk.</p>
          </div>
          <a href="view_items.php" class="button button-secondary">See all items</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Location</th>
                <th>Date Found</th>
                <th>Status</th>
                <th>Reported by</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($recentItems)): ?>
                <tr>
                  <td colspan="6" style="padding:1.5rem;text-align:center;color:var(--muted);">No recent lost item reports found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($recentItems as $item): ?>
                  <tr>
                    <td><?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($item['location_found'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('M j, Y', strtotime($item['date_found'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="badge <?= htmlspecialchars($item['status'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($item['status']), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars($item['reported_by'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
