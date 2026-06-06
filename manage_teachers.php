<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $center_id = $_POST['center_id'];

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role, center_id) VALUES (?, ?, ?, 'teacher', ?)");
            $stmt->execute([$name, $username, $password, $center_id]);
            $message = "Teacher added successfully.";
        } catch(PDOException $e) {
            $message = "Error: Username might already exist.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
        $stmt->execute([$id]);
        $message = "Teacher deleted successfully.";
    }
}

// Fetch Teachers
$stmt = $pdo->query("
    SELECT u.id, u.name, u.username, c.name as center_name 
    FROM users u 
    LEFT JOIN centers c ON u.center_id = c.id 
    WHERE u.role = 'teacher'
");
$teachers = $stmt->fetchAll();

// Fetch Centers for dropdown
$stmt = $pdo->query("SELECT id, name FROM centers");
$centers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin</title>
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
        <a href="manage_teachers.php" class="active">Teachers</a>
        <a href="manage_students.php">Students</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <h2 class="mb-2">Manage Teachers</h2>
    
    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Add New Teacher</h3>
        <form action="" method="POST" class="mt-1">
            <input type="hidden" name="action" value="add">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Assign Center</label>
                    <select name="center_id" class="form-control" required>
                        <option value="">Select Center...</option>
                        <?php foreach($centers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-1">Add Teacher</button>
        </form>
    </div>

    <div class="card mt-2 table-responsive">
        <h3>Teacher List</h3>
        <table class="mt-1">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Assigned Center</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($teachers as $t): ?>
                <tr>
                    <td><?php echo htmlspecialchars($t['name']); ?></td>
                    <td><?php echo htmlspecialchars($t['username']); ?></td>
                    <td><?php echo htmlspecialchars($t['center_name'] ?? 'Unassigned'); ?></td>
                    <td>
                        <form action="" method="POST" onsubmit="return confirmDelete();" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($teachers) === 0): ?>
                <tr><td colspan="4" class="text-center">No teachers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>
