<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Fetch basic stats
$stmt = $pdo->query("SELECT COUNT(*) FROM students");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
$total_teachers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM centers");
$total_centers = $stmt->fetchColumn();

// Fetch today's attendance stats
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM attendance WHERE date = ? GROUP BY status");
$stmt->execute([$today]);
$attendance_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$present = $attendance_stats['present'] ?? 0;
$absent = $attendance_stats['absent'] ?? 0;
$late = $attendance_stats['late'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Attendance System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-title">
        <div class="header-logo">
            <img src="cre.png" alt="CRE Logo" class="logo">
        </div>
        <span>Admin Panel</span>
    </div>
    <div class="nav-links">
        <a href="admin_dashboard.php" class="active">Dashboard</a>
        <a href="manage_teachers.php">Teachers</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_fees.php">Fees Management</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-details">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div class="stat-details">
                <h3><?php echo $total_teachers; ?></h3>
                <p>Total Teachers</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
            <div class="stat-details">
                <h3><?php echo $total_centers; ?></h3>
                <p>Total Centers</p>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <h3>Today's Overall Attendance Overview</h3>
        <p class="text-muted mb-2"><?php echo date('F j, Y'); ?></p>
        
        <div style="max-width: 400px; margin: 0 auto;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [<?php echo $present; ?>, <?php echo $absent; ?>, <?php echo $late; ?>],
                backgroundColor: [
                    '#388e3c', // success
                    '#d32f2f', // danger
                    '#f57c00'  // warning
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
</script>
<script src="app.js"></script>
</body>
</html>
