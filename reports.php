<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = $_SESSION['role'];
$user_center_id = $_SESSION['center_id'];

// Default filters
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$selected_center = isset($_GET['center_id']) ? $_GET['center_id'] : ($role === 'teacher' ? $user_center_id : '');

// Restrict teacher to their own center
if ($role === 'teacher') {
    $selected_center = $user_center_id;
}

// Fetch Centers for dropdown (Admin only)
$centers = [];
if ($role === 'admin') {
    $stmt = $pdo->query("SELECT id, name FROM centers");
    $centers = $stmt->fetchAll();
}

// Fetch Report Data
$report_data = [];
$total_present = 0;
$total_absent = 0;
$total_late = 0;

if ($selected_center) {
    $stmt = $pdo->prepare("
        SELECT s.name, s.student_id, a.date, a.status 
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.center_id = ? AND MONTH(a.date) = ? AND YEAR(a.date) = ?
        ORDER BY a.date DESC, s.name ASC
    ");
    $stmt->execute([$selected_center, $selected_month, $selected_year]);
    $report_data = $stmt->fetchAll();

    foreach($report_data as $row) {
        if ($row['status'] === 'present') $total_present++;
        if ($row['status'] === 'absent') $total_absent++;
        if ($row['status'] === 'late') $total_late++;
    }
}

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $selected_center) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . $selected_year . '_' . $selected_month . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Student Name', 'Student ID', 'Date', 'Status']);
    
    foreach ($report_data as $row) {
        fputcsv($output, [$row['name'], $row['student_id'], $row['date'], ucfirst($row['status'])]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Attendance System</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background-color: white; }
            .container { padding: 0; max-width: 100%; }
            .card { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<header class="dashboard-header no-print">
    <div class="dashboard-title">
        <div class="header-logo">
            <img src="cre.png" alt="CRE Logo" class="logo">
        </div>
        <span>Reports</span>
    </div>
    <div class="nav-links">
        <?php if ($role === 'admin'): ?>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_teachers.php">Teachers</a>
            <a href="manage_students.php">Students</a>
        <?php else: ?>
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_attendance.php">Mark Attendance</a>
        <?php endif; ?>
        <a href="reports.php" class="active">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <h2 class="mb-2">Monthly Attendance Report</h2>

    <div class="card no-print">
        <form action="" method="GET" class="flex" style="gap: 15px; align-items: flex-end; flex-wrap: wrap;">
            <?php if ($role === 'admin'): ?>
            <div class="form-group" style="flex: 1; margin: 0; min-width: 150px;">
                <label>Center</label>
                <select name="center_id" class="form-control" required>
                    <option value="">Select Center...</option>
                    <?php foreach($centers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $selected_center == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <div class="form-group" style="flex: 1; margin: 0; min-width: 150px;">
                <label>Month</label>
                <select name="month" class="form-control">
                    <?php for($m=1; $m<=12; ++$m): ?>
                        <option value="<?php echo sprintf("%02d", $m); ?>" <?php echo $selected_month == sprintf("%02d", $m) ? 'selected' : ''; ?>>
                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="form-group" style="flex: 1; margin: 0; min-width: 150px;">
                <label>Year</label>
                <select name="year" class="form-control">
                    <?php 
                    $current_year = date('Y');
                    for($y = $current_year; $y >= $current_year - 5; $y--): 
                    ?>
                        <option value="<?php echo $y; ?>" <?php echo $selected_year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary" style="margin-bottom: 2px;">Generate Report</button>
        </form>
    </div>

    <?php if ($selected_center): ?>
        <div class="stats-grid mt-2 mb-2">
            <div class="stat-card" style="padding: 10px 20px;">
                <div class="stat-details">
                    <p>Total Present</p>
                    <h3 style="color: var(--success-color);"><?php echo $total_present; ?></h3>
                </div>
            </div>
            <div class="stat-card" style="padding: 10px 20px;">
                <div class="stat-details">
                    <p>Total Absent</p>
                    <h3 style="color: var(--danger-color);"><?php echo $total_absent; ?></h3>
                </div>
            </div>
            <div class="stat-card" style="padding: 10px 20px;">
                <div class="stat-details">
                    <p>Total Late</p>
                    <h3 style="color: var(--warning-color);"><?php echo $total_late; ?></h3>
                </div>
            </div>
        </div>

        <div class="card mt-2">
            <div class="flex justify-between align-center mb-1 no-print">
                <h3>Report Data</h3>
                <div style="display:flex; gap: 10px;">
                    <a href="reports.php?<?php echo $_SERVER['QUERY_STRING']; ?>&export=csv" class="btn" style="background: #1d6f42; color: white;"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                    <button onclick="window.print()" class="btn" style="background: #333; color: white;"><i class="fa-solid fa-print"></i> Print / PDF</button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($report_data as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['date']); ?></td>
                            <td>
                                <?php if($row['status'] === 'present'): ?>
                                    <span class="badge badge-present">Present</span>
                                <?php elseif($row['status'] === 'absent'): ?>
                                    <span class="badge badge-absent">Absent</span>
                                <?php else: ?>
                                    <span class="badge badge-late">Late</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(count($report_data) === 0): ?>
                        <tr><td colspan="4" class="text-center">No attendance records found for this period.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="card text-center text-muted mt-2">
            Please select a center to generate the report.
        </div>
    <?php endif; ?>
</div>

<script src="app.js"></script>
</body>
</html>
