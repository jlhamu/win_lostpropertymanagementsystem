<!-- ══ ADMIN SIDEBAR ════════════════════════════════════════════════ -->
<aside class="admin-sidebar">
    <div style="padding: 20px 25px; border-bottom: 1px solid rgba(255,255,255,0.15);">
        <p style="color: rgba(255,255,255,0.6); font-size: 0.75rem; text-transform: uppercase;
                  letter-spacing: 1px; margin: 0 0 4px;">Admin Panel</p>
        <p style="color: white; font-weight: 700; margin: 0; font-size: 1rem;">
            <?= sanitize($_SESSION['full_name']) ?>
        </p>
    </div>
    <p class="sidebar-title">Management</p>
    <ul>
        <li>
            <a href="<?= BASE_URL ?>admin/dashboard.php"
               <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'class="active"' : '' ?>>
                &#128202; Dashboard
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/items.php"
               <?= basename($_SERVER['PHP_SELF']) === 'items.php' ? 'class="active"' : '' ?>>
                &#128203; Manage Items
            </a>
        </li>
        <li>
            <a href="<?= BASE_URL ?>admin/claims.php"
               <?= basename($_SERVER['PHP_SELF']) === 'claims.php' ? 'class="active"' : '' ?>>
                &#128221; Manage Claims
            </a>
        </li>
    </ul>
    <p class="sidebar-title">Site</p>
    <ul>
        <li><a href="<?= BASE_URL ?>browse.php">&#128269; Browse Listings</a></li>
        <li><a href="<?= BASE_URL ?>index.php">&#127968; Home Page</a></li>
        <li><a href="<?= BASE_URL ?>logout.php">&#128682; Logout</a></li>
    </ul>
</aside>
<!-- ═══════════════════════════════════════════════════════════════ -->
