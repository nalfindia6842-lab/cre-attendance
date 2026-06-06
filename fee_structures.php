<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';

// Handle Add/Update Fee Structure
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $center_id = $_POST['center_id'];
        $fee_type = $_POST['fee_type'];
        $amount = $_POST['amount'];

        try {
            // Upsert (Insert or Update if exists)
            $stmt = $pdo->prepare("
                INSERT INTO fee_structures (center_id, fee_type, amount) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE amount = VALUES(amount)
            ");
            $stmt->execute([$center_id, $fee_type, $amount]);
            $message = "Fee structure saved successfully.";
        } catch(PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM fee_structures WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Fee structure deleted successfully.";
    }
}

// Fetch Centers
$stmt = $pdo->query("SELECT id, name FROM centers ORDER BY name ASC");
$centers = $stmt->fetchAll();

// Fetch Fee Structures
$stmt = $pdo->query("
    SELECT fs.*, c.name as center_name 
    FROM fee_structures fs 
    JOIN centers c ON fs.center_id = c.id 
    ORDER BY c.name ASC, fs.fee_type ASC
");
$structures = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Structures - Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_teachers.php">Teachers</a>
        <a href="manage_students.php">Students</a>
        <a href="manage_fees.php" class="active">Fees Management</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <div class="flex justify-between align-center mb-2">
        <h2>Fee Structures</h2>
        <a href="manage_fees.php" class="btn btn-secondary">Back to Fees</a>
    </div>

    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Set Fee Amount</h3>
        <form action="" method="POST" class="mt-1">
            <input type="hidden" name="action" value="save">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Center</label>
                    <select name="center_id" class="form-control" required>
                        <option value="">Select Center...</option>
                        <?php foreach($centers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Fee Type</label>
                    <select name="fee_type" class="form-control" required>
                        <option value="admission">Admission Fee</option>
                        <option value="monthly">Monthly Fee</option>
                        <option value="exam">Exam Fee</option>
                        <option value="other">Other Fee</option>
                    </select>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Amount (₹)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-1">Save Structure</button>
        </form>
    </div>

    <div class="card mt-2 table-responsive">
        <h3>Existing Fee Structures</h3>
        <table class="mt-1">
            <thead>
                <tr>
                    <th>Center Name</th>
                    <th>Fee Type</th>
                    <th>Amount</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($structures as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['center_name']); ?></td>
                    <td><?php echo ucfirst(htmlspecialchars($s['fee_type'])); ?></td>
                    <td>₹<?php echo number_format($s['amount'], 2); ?></td>
                    <td><?php echo date('d M Y h:i A', strtotime($s['created_at'])); ?></td>
                    <td>
                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this fee structure?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($structures) === 0): ?>
                <tr><td colspan="5" class="text-center">No fee structures found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>
