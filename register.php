<?php
/**
 * Register Page – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Already logged in? Redirect away
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$errors  = [];
$success = '';
$formData = ['full_name' => '', 'email' => '', 'role' => 'student'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and trim inputs
    $fullName        = trim($_POST['full_name']        ?? '');
    $email           = trim($_POST['email']            ?? '');
    $password        = $_POST['password']              ?? '';
    $confirmPassword = $_POST['confirm_password']      ?? '';
    $role            = $_POST['role']                  ?? 'student';

    // Keep form values for re-display
    $formData = ['full_name' => $fullName, 'email' => $email, 'role' => $role];

    // ── Validation ──────────────────────────────────────────────────
    if (empty($fullName)) {
        $errors[] = 'Full name is required.';
    } elseif (strlen($fullName) > 150) {
        $errors[] = 'Full name must be 150 characters or fewer.';
    }

    if (empty($email)) {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, ['student', 'staff'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    // ── Check for duplicate email ────────────────────────────────────
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'That email address is already registered. Please login.';
        }
    }

    // ── Insert new user ─────────────────────────────────────────────
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$fullName, $email, $hash, $role]);
        $success = 'Account created successfully! You can now login.';
        $formData = ['full_name' => '', 'email' => '', 'role' => 'student'];
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Create Your Account</h1>
        <p>Join the Wentworth Lost and Found community</p>
    </div>
</div>

<div class="container" style="padding-bottom: 50px;">
    <div class="form-container">
        <h2 style="text-align:center; color:var(--primary); margin-bottom:25px;">Register</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= sanitize($success) ?>
                <br><a href="<?= BASE_URL ?>login.php"><strong>→ Go to Login</strong></a>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin:0; padding-left:18px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= sanitize($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="full_name">Full Name <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name"
                       class="form-control"
                       value="<?= sanitize($formData['full_name']) ?>"
                       placeholder="e.g. Jane Smith"
                       maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       value="<?= sanitize($formData['email']) ?>"
                       placeholder="your@email.com"
                       maxlength="150" required>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="req">*</span></label>
                <input type="password" id="password" name="password"
                       class="form-control"
                       placeholder="At least 6 characters"
                       required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password <span class="req">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password"
                       class="form-control"
                       placeholder="Repeat your password"
                       required>
            </div>

            <div class="form-group">
                <label for="role">I am a… <span class="req">*</span></label>
                <select id="role" name="role" class="form-control" required>
                    <option value="student" <?= $formData['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                    <option value="staff"   <?= $formData['role'] === 'staff'   ? 'selected' : '' ?>>Staff</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="margin-top:8px;">
                Create Account
            </button>
        </form>

        <p style="text-align:center; margin-top:20px; color:var(--text-light);">
            Already have an account?
            <a href="<?= BASE_URL ?>login.php"><strong>Login here</strong></a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
