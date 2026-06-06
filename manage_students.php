<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';

// Handle Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Application ID', 'Admission No', 'Roll No', 'Name', 'Age', 'Gender', 'Blood Group', 'DOB', 'Phone', 'Mobile 2', 'WhatsApp', 'Email', 'Class', 'Academic Year', 'Institution', 'Place', 'Center']);
    
    $stmt = $pdo->query("SELECT s.*, c.name as center_name FROM students s LEFT JOIN centers c ON s.center_id = c.id ORDER BY s.created_at DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['application_id'], $row['admission_no'], $row['roll_no'], $row['name'], $row['age'], $row['gender'], 
            $row['blood_group'], $row['dob'], $row['phone_number'], $row['mobile2'], $row['whatsapp_no'], 
            $row['email'], $row['class'], $row['academic_year'], $row['institution_name'], $row['place'], 
            $row['center_name']
        ]);
    }
    fclose($output);
    exit;
}
// Handle Add/Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $name = $_POST['name'];
        $student_id = $_POST['student_id'];
        $phone = $_POST['phone_number'];
        $class = $_POST['class'];
        $center_id = $_POST['center_id'];
        
        // Handle Photo Upload
        $photo_url = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $upload_dir = 'students/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . '_' . basename($_FILES['photo']['name']);
            $target_file = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_url = $upload_dir . $filename;
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO students (name, student_id, phone_number, class, center_id, photo_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $student_id, $phone, $class, $center_id, $photo_url]);
            $message = "Student added successfully.";
        } catch(PDOException $e) {
            $message = "Error: Student ID might already exist.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Student deleted successfully.";
    }
}

// Fetch Students
$stmt = $pdo->query("
    SELECT s.*, c.name as center_name 
    FROM students s 
    LEFT JOIN centers c ON s.center_id = c.id 
    ORDER BY s.created_at DESC
");
$students = $stmt->fetchAll();

// Fetch Centers
$stmt = $pdo->query("SELECT id, name FROM centers");
$centers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
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
        <a href="manage_students.php" class="active">Students</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <div class="flex justify-between align-center mb-2">
        <h2>Manage Students</h2>
        <a href="manage_students.php?export=csv" class="btn btn-success" style="background-color: var(--success-color); color: white;"><i class="fa-solid fa-download"></i> Export CSV</a>
    </div>

    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Add New Student</h3>
        <form action="" method="POST" enctype="multipart/form-data" class="mt-1">
            <input type="hidden" name="action" value="add">
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Student ID</label>
                    <input type="text" name="student_id" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1; min-width: 200px;">
                    <label>Class</label>
                    <input type="text" name="class" class="form-control" required>
                </div>
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
                    <label>Photo (Optional)</label>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-1">Add Student</button>
        </form>
    </div>

    <div class="card mt-2 table-responsive">
        <h3>Student List</h3>
        <table class="mt-1">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Student ID</th>
                    <th>Class</th>
                    <th>Center</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($students as $s): ?>
                <tr>
                    <td>
                        <?php if($s['photo_url']): ?>
                            <img src="<?php echo htmlspecialchars($s['photo_url']); ?>" alt="Photo" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <?php else: ?>
                            <div style="width:40px; height:40px; border-radius:50%; background:#eee; display:flex; align-items:center; justify-content:center;"><i class="fa-solid fa-user"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                    <td><?php echo htmlspecialchars($s['roll_no'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($s['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($s['class']); ?></td>
                    <td><?php echo htmlspecialchars($s['center_name']); ?></td>
                    <td>
                        <a href="edit_student.php?id=<?php echo $s['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">Edit</a>
                        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this student?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                            <button type="submit" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(count($students) === 0): ?>
                <tr><td colspan="7" class="text-center">No students found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>
