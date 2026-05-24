<?php
/**
 * Login Page – Wentworth Lost and Found Management System
 */
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$error    = '';
$formEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $formEmail = $email;

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['email']     = $user['email'];

            if ($user['role'] === 'admin') {
                header('Location: ' . BASE_URL . 'admin/dashboard.php');
            } else {
                header('Location: ' . BASE_URL . 'browse.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Welcome Back</h1>
        <p>Login to access the Wentworth Lost and Found system</p>
    </div>
</div>

<div class="container" style="padding-bottom: 50px;">
    <div class="form-container">
        <h2 style="text-align:center; color:var(--primary); margin-bottom:25px;">Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                       class="form-control"
                       value="<?= sanitize($formEmail) ?>"
                       placeholder="your@email.com"
                       required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control"
                       placeholder="Your password"
                       required>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="margin-top:8px;">
                Login
            </button>
        </form>

        <p style="text-align:center; margin-top:20px; color:var(--text-light);">
            Don't have an account?
            <a href="<?= BASE_URL ?>register.php"><strong>Register here</strong></a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
