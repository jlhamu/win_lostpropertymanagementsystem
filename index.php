<?php
/**
 * Home Page – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$recentItems = getRecentItems($pdo, 6);

// Quick stats
$stmtStats = $pdo->query(
    "SELECT
        COUNT(CASE WHEN type='lost'  AND status='open' THEN 1 END) AS lost_open,
        COUNT(CASE WHEN type='found' AND status='open' THEN 1 END) AS found_open,
        COUNT(CASE WHEN status='returned'               THEN 1 END) AS returned
     FROM items"
);
$stats = $stmtStats->fetch();

$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<!-- HERO -->
<div class="hero">
    <div class="hero-content">
        <h1>Wentworth Lost &amp; Found</h1>
        <p>
            Lost something on campus? Found an item belonging to someone else?
            Our system helps reunite students and staff at
            Wentworth Higher Institute of Education with their belongings.
        </p>
        <div class="hero-buttons">
            <a href="<?= BASE_URL ?>report-lost.php"  class="btn btn-white btn-lg">📋 Report Lost Item</a>
            <a href="<?= BASE_URL ?>report-found.php" class="btn btn-accent btn-lg">📦 Report Found Item</a>
            <a href="<?= BASE_URL ?>browse.php"        class="btn btn-lg" style="color:#fff;border:2px solid #fff;background:transparent;">🔍 Browse Listings</a>
        </div>
    </div>
</div>

<!-- STATS STRIP -->
<section style="background:#fff; padding:28px 20px; border-bottom:1px solid var(--border);">
    <div class="container">
        <div style="display:flex; justify-content:center; gap:60px; flex-wrap:wrap; text-align:center;">
            <div>
                <div style="font-size:2.1rem; font-weight:800; color:var(--danger);"><?= (int)$stats['lost_open'] ?></div>
                <div style="color:var(--text-light); font-size:.85rem; text-transform:uppercase; letter-spacing:.5px;">Open Lost Items</div>
            </div>
            <div>
                <div style="font-size:2.1rem; font-weight:800; color:var(--success);"><?= (int)$stats['found_open'] ?></div>
                <div style="color:var(--text-light); font-size:.85rem; text-transform:uppercase; letter-spacing:.5px;">Found Items</div>
            </div>
            <div>
                <div style="font-size:2.1rem; font-weight:800; color:var(--primary);"><?= (int)$stats['returned'] ?></div>
                <div style="color:var(--text-light); font-size:.85rem; text-transform:uppercase; letter-spacing:.5px;">Items Returned</div>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section style="padding:60px 20px; background:var(--secondary);">
    <div class="container">
        <div class="section-header">
            <h2>How It Works</h2>
            <div class="section-divider"></div>
            <p>Simple steps to report and recover lost items on campus</p>
        </div>
        <div class="grid grid-3" style="max-width:920px; margin:0 auto;">
            <div class="card" style="text-align:center; padding:30px 20px;">
                <div style="font-size:3rem; margin-bottom:14px;">📝</div>
                <h3 style="color:var(--primary); margin-bottom:10px;">1. Report</h3>
                <p style="color:var(--text-light);">Submit a report for your lost item or something you found on campus.</p>
            </div>
            <div class="card" style="text-align:center; padding:30px 20px;">
                <div style="font-size:3rem; margin-bottom:14px;">🔔</div>
                <h3 style="color:var(--primary); margin-bottom:10px;">2. Get Notified</h3>
                <p style="color:var(--text-light);">Receive email alerts when a possible match is detected for your lost item.</p>
            </div>
            <div class="card" style="text-align:center; padding:30px 20px;">
                <div style="font-size:3rem; margin-bottom:14px;">✅</div>
                <h3 style="color:var(--primary); margin-bottom:10px;">3. Reclaim</h3>
                <p style="color:var(--text-light);">Submit a claim with proof of ownership and collect your belonging.</p>
            </div>
        </div>
    </div>
</section>

<!-- RECENT LISTINGS -->
<section style="padding:60px 20px;">
    <div class="container">
        <div class="section-header">
            <h2>Recent Listings</h2>
            <div class="section-divider"></div>
            <p>Latest lost and found reports from the campus community</p>
        </div>

        <?php if (empty($recentItems)): ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h3>No listings yet</h3>
                <p>Be the first to report a lost or found item!</p>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>report-lost.php" class="btn btn-primary">Report an Item</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>register.php" class="btn btn-primary">Register to Get Started</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($recentItems as $item): ?>
                    <div class="card">
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
                        </div>
                        <div class="card-footer">
                            <span class="badge-status badge-<?= sanitize($item['type']) ?>"><?= ucfirst($item['type']) ?></span>
                            <a href="<?= BASE_URL ?>item-detail.php?id=<?= (int)$item['item_id'] ?>"
                               class="btn btn-primary btn-sm">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="text-align:center; margin-top:32px;">
                <a href="<?= BASE_URL ?>browse.php" class="btn btn-outline btn-lg">View All Listings →</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA STRIP (guests only) -->
<?php if (!isLoggedIn()): ?>
<section style="background:var(--primary); color:#fff; padding:55px 20px; text-align:center;">
    <div class="container">
        <h2 style="margin-bottom:12px;">Ready to help your campus community?</h2>
        <p style="opacity:.88; margin-bottom:28px; font-size:1.05rem;">
            Create a free account to report items and receive match notifications.
        </p>
        <a href="<?= BASE_URL ?>register.php" class="btn btn-accent btn-lg" style="margin-right:12px;">Register Now</a>
        <a href="<?= BASE_URL ?>login.php"    class="btn btn-white  btn-lg">Login</a>
    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
