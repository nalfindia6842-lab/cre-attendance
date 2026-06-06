<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$center_id = $_SESSION['center_id'];

// Fetch center info
$stmt = $pdo->prepare("SELECT name, whatsapp_group_link FROM centers WHERE id = ?");
$stmt->execute([$center_id]);
$center = $stmt->fetch();

// Fetch total students in this center
$stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE center_id = ?");
$stmt->execute([$center_id]);
$total_students = $stmt->fetchColumn();

// Fetch today's detailed attendance stats for this center
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT s.name, s.roll_no, a.status 
    FROM attendance a
    JOIN students s ON a.student_id = s.id
    WHERE a.date = ? AND s.center_id = ?
    ORDER BY s.roll_no ASC, s.name ASC
");
$stmt->execute([$today, $center_id]);
$attendance_details = $stmt->fetchAll();

$present_list = [];
$absent_list = [];
$late_list = [];
$present = 0; $absent = 0; $late = 0;

foreach ($attendance_details as $row) {
    $roll = $row['roll_no'] ? " (Roll: {$row['roll_no']})" : "";
    $entry = "- " . $row['name'] . $roll;
    
    if ($row['status'] === 'present') {
        $present++;
        $present_list[] = $entry;
    } elseif ($row['status'] === 'absent') {
        $absent++;
        $absent_list[] = $entry;
    } elseif ($row['status'] === 'late') {
        $late++;
        $late_list[] = $entry;
    }
}

// Build WhatsApp Message
$wa_msg = "*Daily Attendance Report*\n";
$wa_msg .= "*Center:* " . ($center['name'] ?? 'Unknown') . "\n";
$wa_msg .= "*Date:* " . date('d/m/Y') . "\n\n";

if (count($present_list) > 0) {
    $wa_msg .= "*✅ Present:*\n" . implode("\n", $present_list) . "\n\n";
}
if (count($absent_list) > 0) {
    $wa_msg .= "*❌ Absent:*\n" . implode("\n", $absent_list) . "\n\n";
}
if (count($late_list) > 0) {
    $wa_msg .= "*⚠️ Late:*\n" . implode("\n", $late_list) . "\n\n";
}

$wa_msg .= "*Summary:*\nPresent: $present | Absent: $absent | Late: $late | Total: $total_students";
$wa_encoded = rawurlencode($wa_msg);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Attendance System</title>
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
        <span>Teacher Panel</span>
    </div>
    <div class="nav-links">
        <a href="teacher_dashboard.php" class="active">Dashboard</a>
        <a href="teacher_attendance.php">Mark Attendance</a>
        <a href="teacher_students.php">My Students</a>
        <a href="manage_fees.php">Fees Management</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <h2 class="mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
    <p class="text-muted mb-2">Assigned Center: <strong><?php echo htmlspecialchars($center['name'] ?? 'None'); ?></strong></p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
            <div class="stat-details">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <div class="stat-icon" style="color: var(--success-color);"><i class="fa-solid fa-check"></i></div>
            <div class="stat-details">
                <h3><?php echo $present; ?></h3>
                <p>Present Today</p>
            </div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <div class="stat-icon" style="color: var(--danger-color);"><i class="fa-solid fa-xmark"></i></div>
            <div class="stat-details">
                <h3><?php echo $absent; ?></h3>
                <p>Absent Today</p>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="flex justify-between align-center mb-1">
            <h3>Today's Attendance (<?php echo date('F j, Y'); ?>)</h3>
            <a href="https://wa.me/?text=<?php echo $wa_encoded; ?>" target="_blank" class="btn btn-whatsapp">
                <i class="fa-brands fa-whatsapp"></i> Share Report
            </a>
        </div>
        
        <div style="max-width: 400px; margin: 0 auto;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Present', 'Absent', 'Late', 'Unmarked'],
            datasets: [{
                data: [
                    <?php echo $present; ?>, 
                    <?php echo $absent; ?>, 
                    <?php echo $late; ?>, 
                    <?php echo max(0, $total_students - ($present + $absent + $late)); ?>
                ],
                backgroundColor: [
                    '#388e3c', // success
                    '#d32f2f', // danger
                    '#f57c00', // warning
                    '#e0e0e0'  // unmarked
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
