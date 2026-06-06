<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Attendance System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="auth-wrapper">
    <div class="card auth-card fade-in">
        <div class="auth-logo">
            <img src="cre.png" alt="CRE Logo" class="logo">
            <h2 class="mt-1">Attendance System</h2>
            <p class="text-muted" style="color: var(--text-muted); font-size: 0.9rem;">Please login to your account</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div style="background-color: var(--danger-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px; text-align: center; font-size: 0.9rem;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <form action="login_action.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary btn-block mt-2">Sign In</button>
        </form>
    </div>
</div>

<script src="app.js"></script>

<div class="footer-text">
    Developed by Wisdom Wandoor
</div>

</body>
</html>
