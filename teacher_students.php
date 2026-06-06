<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$center_id = $_SESSION['center_id'];
$message = '';

// Handle Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=my_students_export_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Application ID', 'Admission No', 'Roll No', 'Name', 'Age', 'Gender', 'Blood Group', 'DOB', 'Phone', 'Mobile 2', 'WhatsApp', 'Email', 'Class', 'Academic Year', 'Institution', 'Place']);
    
    $stmt = $pdo->prepare("SELECT * FROM students WHERE center_id = ? ORDER BY created_at DESC");
    $stmt->execute([$center_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['application_id'], $row['admission_no'], $row['roll_no'], $row['name'], $row['age'], $row['gender'], 
            $row['blood_group'], $row['dob'], $row['phone_number'], $row['mobile2'], $row['whatsapp_no'], 
            $row['email'], $row['class'], $row['academic_year'], $row['institution_name'], $row['place']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    
    // Verify that this student actually belongs to this teacher's center
    $stmt = $pdo->prepare("SELECT center_id FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $student = $stmt->fetch();
    
    if ($student && $student['center_id'] == $center_id) {
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Student deleted successfully.";
    } else {
        $message = "Access denied or student not found.";
    }
}

// Fetch Students for this center
$stmt = $pdo->prepare("SELECT * FROM students WHERE center_id = ? ORDER BY created_at DESC");
$stmt->execute([$center_id]);
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="teacher_dashboard.php">Dashboard</a>
        <a href="teacher_attendance.php">Mark Attendance</a>
        <a href="teacher_students.php" class="active">My Students</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <div class="flex justify-between align-center mb-2">
        <h2>My Students</h2>
        <a href="teacher_students.php?export=csv" class="btn btn-success" style="background-color: var(--success-color); color: white;"><i class="fa-solid fa-download"></i> Export CSV</a>
    </div>

    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="card table-responsive">
        <table class="mt-1">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Roll No</th>
                    <th>Admission No</th>
                    <th>Class</th>
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
                    <td><?php echo htmlspecialchars($s['admission_no'] ?? $s['student_id']); ?></td>
                    <td><?php echo htmlspecialchars($s['class']); ?></td>
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
                <tr><td colspan="6" class="text-center">No students found for your center.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>
