<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header("Location: index.php");
    exit;
}

$is_admin = $_SESSION['role'] === 'admin';
$center_id = $_SESSION['center_id'] ?? 0;

// AJAX endpoint to get fee amount
if (isset($_GET['action']) && $_GET['action'] === 'get_amount') {
    $student_id = $_GET['student_id'] ?? 0;
    $fee_type = $_GET['fee_type'] ?? '';
    
    // Find center for student
    $stmt = $pdo->prepare("SELECT center_id FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $center_id = $stmt->fetchColumn();
    
    if ($center_id) {
        $stmt = $pdo->prepare("SELECT amount FROM fee_structures WHERE center_id = ? AND fee_type = ?");
        $stmt->execute([$center_id, $fee_type]);
        $amount = $stmt->fetchColumn();
        echo json_encode(['amount' => $amount ?: 0]);
    } else {
        echo json_encode(['amount' => 0]);
    }
    exit;
}

$message = '';

// Handle Fee Collection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'collect') {
    $student_id = $_POST['student_id'];
    $fee_type = $_POST['fee_type'];
    $amount_paid = $_POST['amount_paid'];
    $fee_month = $_POST['fee_month'] ?? null;
    $payment_date = $_POST['payment_date'] ?: date('Y-m-d');
    $remarks = $_POST['remarks'] ?? '';

    try {
        $stmt = $pdo->prepare("
            INSERT INTO fee_collections (student_id, fee_type, amount_paid, fee_month, payment_date, remarks) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $fee_type, $amount_paid, $fee_month, $payment_date, $remarks]);
        $message = "Fee collected successfully.";
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

if ($is_admin) {
    $stmt = $pdo->query("
        SELECT s.id, s.name, s.student_id, c.name as center_name 
        FROM students s 
        LEFT JOIN centers c ON s.center_id = c.id 
        ORDER BY s.name ASC
    ");
    $students = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.student_id, c.name as center_name 
        FROM students s 
        LEFT JOIN centers c ON s.center_id = c.id 
        WHERE s.center_id = ?
        ORDER BY s.name ASC
    ");
    $stmt->execute([$center_id]);
    $students = $stmt->fetchAll();
}

if ($is_admin) {
    $stmt = $pdo->query("
        SELECT fc.*, s.name as student_name, s.student_id as admission_num, c.name as center_name
        FROM fee_collections fc
        JOIN students s ON fc.student_id = s.id
        LEFT JOIN centers c ON s.center_id = c.id
        ORDER BY fc.created_at DESC
        LIMIT 50
    ");
    $collections = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT fc.*, s.name as student_name, s.student_id as admission_num, c.name as center_name
        FROM fee_collections fc
        JOIN students s ON fc.student_id = s.id
        LEFT JOIN centers c ON s.center_id = c.id
        WHERE s.center_id = ?
        ORDER BY fc.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$center_id]);
    $collections = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-title">
        <div class="header-logo">
            <img src="cre.png" alt="CRE Logo" class="logo">
        </div>
        <span><?php echo $is_admin ? 'Admin Panel' : 'Teacher Panel'; ?></span>
    </div>
    <div class="nav-links">
        <?php if ($is_admin): ?>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_teachers.php">Teachers</a>
            <a href="manage_students.php">Students</a>
            <a href="manage_fees.php" class="active">Fees Management</a>
            <a href="reports.php">Reports</a>
        <?php else: ?>
            <a href="teacher_dashboard.php">Dashboard</a>
            <a href="teacher_attendance.php">Mark Attendance</a>
            <a href="teacher_students.php">My Students</a>
            <a href="manage_fees.php" class="active">Fees Management</a>
            <a href="reports.php">Reports</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <div class="flex justify-between align-center mb-2">
        <h2>Fees Management</h2>
        <?php if ($is_admin): ?>
            <a href="fee_structures.php" class="btn btn-secondary">Configure Fee Structures</a>
        <?php endif; ?>
    </div>

    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Collect Fee</h3>
        <form action="" method="POST" class="mt-1">
            <input type="hidden" name="action" value="collect">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 250px;">
                    <label>Student</label>
                    <select name="student_id" id="student_id" class="form-control" required onchange="fetchFeeAmount()">
                        <option value="">Select Student...</option>
                        <?php foreach($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['name']) . ' (' . htmlspecialchars($s['student_id']) . ') - ' . htmlspecialchars($s['center_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label>Fee Type</label>
                    <select name="fee_type" id="fee_type" class="form-control" required onchange="fetchFeeAmount()">
                        <option value="admission">Admission Fee</option>
                        <option value="monthly">Monthly Fee</option>
                        <option value="exam">Exam Fee</option>
                        <option value="other">Other Fee</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px; display: none;" id="month_group">
                    <label>Fee Month</label>
                    <select name="fee_month" class="form-control">
                        <option value="">Select Month</option>
                        <?php
                        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                        foreach($months as $m) echo "<option value=\"$m\">$m</option>";
                        ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label>Amount Paid (₹)</label>
                    <input type="number" step="0.01" name="amount_paid" id="amount_paid" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 150px;">
                    <label>Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group" style="flex: 2; min-width: 250px;">
                    <label>Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="Optional notes...">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-1">Collect Fee</button>
        </form>
    </div>

    <div class="card mt-2 table-responsive">
        <h3>Recent Collections</h3>
        <table class="mt-1">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>Center</th>
                    <th>Fee Type</th>
                    <th>Month</th>
                    <th>Amount</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($collections as $c): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($c['payment_date'])); ?></td>
                    <td><?php echo htmlspecialchars($c['student_name']) . '<br><small class="text-muted">' . htmlspecialchars($c['admission_num']) . '</small>'; ?></td>
                    <td><?php echo htmlspecialchars($c['center_name']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($c['fee_type'])); ?></td>
                    <td><?php echo htmlspecialchars($c['fee_month'] ?: '-'); ?></td>
                    <td style="color: var(--success-color); font-weight: bold;">₹<?php echo number_format($c['amount_paid'], 2); ?></td>
                    <td><?php echo htmlspecialchars($c['remarks'] ?: '-'); ?></td>
                    <td>
                        <a href="fee_receipt.php?id=<?php echo $c['id']; ?>" class="btn btn-secondary" style="padding: 3px 8px; font-size: 0.8rem;" target="_blank"><i class="fa-solid fa-print"></i> Print</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($collections) === 0): ?>
                <tr><td colspan="8" class="text-center">No fee collections found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function fetchFeeAmount() {
    const studentId = document.getElementById('student_id').value;
    const feeType = document.getElementById('fee_type').value;
    const monthGroup = document.getElementById('month_group');
    
    // Toggle month dropdown visibility
    if (feeType === 'monthly') {
        monthGroup.style.display = 'block';
    } else {
        monthGroup.style.display = 'none';
    }

    if (studentId && feeType) {
        fetch(`manage_fees.php?action=get_amount&student_id=${studentId}&fee_type=${feeType}`)
            .then(response => response.json())
            .then(data => {
                if (data.amount && data.amount > 0) {
                    document.getElementById('amount_paid').value = data.amount;
                }
            })
            .catch(err => console.error(err));
    }
}
</script>
<script src="app.js"></script>
</body>
</html>
