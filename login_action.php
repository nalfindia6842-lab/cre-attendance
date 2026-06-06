<?php
session_start();
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        header("Location: index.php?error=Please fill in all fields");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, username, password_hash, role, center_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Success
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['center_id'] = $user['center_id'];

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: teacher_dashboard.php");
            }
            exit;
        } else {
            // Failure
            header("Location: index.php?error=Invalid username or password");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: index.php?error=Database error");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}
