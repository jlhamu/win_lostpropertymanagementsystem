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

$stmt = $pdo->prepare('SELECT full_name, student_staff_id, email, phone, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    if (empty($errors)) {
        if (($_POST['action'] ?? '') === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($fullName === '') {
                $errors[] = 'Please enter your full name.';
            }

            if ($email === '') {
                $errors[] = 'Please enter your email address.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $errors[] = 'This email is already in use by another account.';
                }
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?');
                $stmt->execute([$fullName, $email, $phone, $userId]);
                $_SESSION['full_name'] = $fullName;
                $_SESSION['name'] = $fullName;
                $_SESSION['email'] = $email;
                $user['full_name'] = $fullName;
                $user['email'] = $email;
                $user['phone'] = $phone;
                $successMessage = 'Your profile has been updated successfully.';
            }
        }

        if (($_POST['action'] ?? '') === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '') {
                $errors[] = 'Please enter your current password.';
            }

            if ($newPassword === '') {
                $errors[] = 'Please enter a new password.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters long.';
            }

            if ($confirmPassword === '') {
                $errors[] = 'Please confirm your new password.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'New passwords do not match.';
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ? LIMIT 1');
                $stmt->execute([$userId]);
                $record = $stmt->fetch();

                if (!$record || !password_verify($currentPassword, $record['password'])) {
                    $errors[] = 'Current password is incorrect.';
                }
            }

            if (empty($errors)) {
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $stmt->execute([$passwordHash, $userId]);
                $successMessage = 'Your password has been updated successfully.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile | WIN Lost Property</title>
  <meta name="description" content="Update your user profile and password in WIN Lost Property.">
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
    .panel h1 { margin: 0 0 0.75rem; font-size: clamp(1.9rem, 2.4vw, 2.75rem); line-height: 1.05; }
    .panel p { margin: 0 0 1rem; color: var(--muted); }
    .alert { border-radius: 18px; padding: 1rem 1.25rem; margin-bottom: 1rem; }
    .alert.success { background: rgba(0,86,214,.08); border: 1px solid rgba(0,86,214,.2); color: #00317a; }
    .alert.error { background: rgba(255,112,101,.12); border: 1px solid rgba(255,112,101,.3); color: #922d1d; }
    .grid-two { display: grid; gap: 1rem; grid-template-columns: 1fr 1fr; }
    .input-group { display: grid; gap: 0.5rem; }
    label { font-weight: 600; }
    input { width: 100%; min-height: 3.2rem; border-radius: 16px; border: 1px solid rgba(20,32,59,.14); background: var(--surface-soft); padding: 0.95rem 1rem; color: var(--ink); font: inherit; }
    button { display: inline-flex; align-items: center; justify-content: center; padding: 1rem 1.5rem; border-radius: 999px; background: linear-gradient(135deg, var(--purple), var(--blue)); color: #fff; border: none; font-weight: 700; cursor: pointer; transition: transform .18s ease, box-shadow .18s ease; }
    button:hover { transform: translateY(-1px); box-shadow: 0 16px 30px rgba(0,0,0,.08); }
    .button-secondary { background: #fff; color: var(--ink); border: 1px solid var(--border); }
    .button-secondary:hover { background: var(--surface-soft); }
    .nav-links { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1rem; }
    .nav-links a { display: inline-flex; padding: 0.85rem 1rem; border-radius: 999px; background: #fff; border: 1px solid var(--border); transition: background .18s ease, transform .18s ease; }
    .nav-links a:hover { background: var(--surface-soft); transform: translateY(-1px); }
    .panel .panel { border: 1px solid rgba(20,32,59,.08); }
    @media (max-width: 860px) { .grid-two { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
  <div class="page">
    <header class="topbar">
      <div class="brand">
        <div class="brand-logo">WIN</div>
        <div>
          <strong>Profile</strong>
          <div style="font-size:0.95rem;color:var(--muted);">Manage your account.</div>
        </div>
      </div>
      <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="report_item.php">Report Item</a>
        <a href="view_items.php">View Items</a>
        <a href="my_claims.php">My Claims</a>
        <a href="contact.php">Contact</a>
      </div>
    </header>

    <section class="panel">
      <h1>Account details</h1>
      <p>Update your public profile information or change your password.</p>

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

      <div class="grid-two">
        <div class="panel" style="padding:1.75rem;">
          <h2 style="margin-top:0;">Profile information</h2>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="update_profile">
            <div class="input-group">
              <label for="full_name">Full name</label>
              <input id="full_name" name="full_name" type="text" value="<?= htmlspecialchars($_POST['full_name'] ?? $user['full_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="input-group">
              <label for="student_staff_id">Student / Staff ID</label>
              <input id="student_staff_id" type="text" value="<?= htmlspecialchars($user['student_staff_id'], ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>
            <div class="input-group">
              <label for="email">Email address</label>
              <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="input-group">
              <label for="phone">Phone number</label>
              <input id="phone" name="phone" type="tel" value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
              <button type="submit" class="button">Save changes</button>
              <a href="dashboard.php" class="button button-secondary">Back to dashboard</a>
            </div>
          </form>
        </div>

        <div class="panel" style="padding:1.75rem;">
          <h2 style="margin-top:0;">Change password</h2>
          <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(createCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="change_password">
            <div class="input-group">
              <label for="current_password">Current password</label>
              <input id="current_password" name="current_password" type="password" required>
            </div>
            <div class="input-group">
              <label for="new_password">New password</label>
              <input id="new_password" name="new_password" type="password" required>
            </div>
            <div class="input-group">
              <label for="confirm_password">Confirm new password</label>
              <input id="confirm_password" name="confirm_password" type="password" required>
            </div>
            <div style="margin-top:1rem;display:flex;gap:0.75rem;flex-wrap:wrap;">
              <button type="submit" class="button">Update password</button>
            </div>
          </form>
        </div>
      </div>
    </section>
  </div>
</body>
</html>
