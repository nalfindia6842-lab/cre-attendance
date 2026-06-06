<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit;
}

// Check permissions
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student not found.");
}

// If teacher, must belong to their center
if ($_SESSION['role'] === 'teacher' && $student['center_id'] != $_SESSION['center_id']) {
    die("Access denied.");
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $student_id = $_POST['student_id'];
    $roll_no = $_POST['roll_no'] ?: null;
    $age = $_POST['age'] ?: null;
    $gender = $_POST['gender'] ?: null;
    $blood_group = $_POST['blood_group'] ?: null;
    $dob = $_POST['dob'] ?: null;
    $address = $_POST['address'];
    $phone_number = $_POST['phone_number'];
    $mobile2 = $_POST['mobile2'];
    $whatsapp_no = $_POST['whatsapp_no'];
    $email = $_POST['email'];
    $class = $_POST['class'];
    $academic_year = $_POST['academic_year'];
    $institution_name = $_POST['institution_name'];
    $place = $_POST['place'];
    $center_id = $_POST['center_id'] ?? $student['center_id']; // teacher might not send center_id
    
    try {
        $updateStmt = $pdo->prepare("
            UPDATE students SET 
                name=?, student_id=?, roll_no=?, age=?, gender=?, blood_group=?, dob=?, address=?, 
                phone_number=?, mobile2=?, whatsapp_no=?, email=?, class=?, 
                academic_year=?, institution_name=?, place=?, center_id=? 
            WHERE id=?
        ");
        $updateStmt->execute([
            $name, $student_id, $roll_no, $age, $gender, $blood_group, $dob, $address,
            $phone_number, $mobile2, $whatsapp_no, $email, $class,
            $academic_year, $institution_name, $place, $center_id, $id
        ]);
        $message = "Student details updated successfully.";
        
        // Refresh student data
        $stmt->execute([$id]);
        $student = $stmt->fetch();
    } catch(PDOException $e) {
        $message = "Error updating student. " . $e->getMessage();
    }
}

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
    <title>Edit Student</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .full-width { grid-column: 1 / -1; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header class="dashboard-header">
    <div class="dashboard-title">
        <div class="header-logo">
            <img src="cre.png" alt="CRE Logo" class="logo">
        </div>
        <span>Edit Student</span>
    </div>
    <div class="nav-links">
        <?php if($_SESSION['role'] === 'admin'): ?>
            <a href="manage_students.php" class="btn btn-primary" style="color:white; padding: 5px 15px;">&larr; Back</a>
        <?php else: ?>
            <a href="teacher_students.php" class="btn btn-primary" style="color:white; padding: 5px 15px;">&larr; Back</a>
        <?php endif; ?>
    </div>
</header>

<div class="container fade-in">
    <div class="card">
        <h3>Edit Student: <?php echo htmlspecialchars($student['name']); ?></h3>
        
        <?php if($message): ?>
            <div class="alert mt-1 mb-2" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius);">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="mt-2">
            <div class="form-grid">
                <div class="form-group">
                    <label>Application ID (Read-only)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['application_id'] ?? ''); ?>" readonly style="background:#eee;">
                </div>
                <div class="form-group">
                    <label>Admission No (Read-only)</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['admission_no'] ?? ''); ?>" readonly style="background:#eee;">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($student['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Roll No</label>
                    <input type="text" name="roll_no" class="form-control" value="<?php echo htmlspecialchars($student['roll_no'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" class="form-control" value="<?php echo htmlspecialchars($student['age'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-control">
                        <option value="">Select...</option>
                        <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Select...</option>
                        <?php 
                        $bgs = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                        foreach($bgs as $bg) {
                            $sel = $student['blood_group'] === $bg ? 'selected' : '';
                            echo "<option value=\"$bg\" $sel>$bg</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($student['dob'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Course / Class</label>
                    <input type="text" name="class" class="form-control" value="<?php echo htmlspecialchars($student['class']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($student['academic_year'] ?? ''); ?>">
                </div>
                
                <div class="form-group full-width">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($student['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Mobile No: 1</label>
                    <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($student['phone_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Mobile No: 2</label>
                    <input type="text" name="mobile2" class="form-control" value="<?php echo htmlspecialchars($student['mobile2'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>WhatsApp No</label>
                    <input type="text" name="whatsapp_no" class="form-control" value="<?php echo htmlspecialchars($student['whatsapp_no'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Institution Name</label>
                    <input type="text" name="institution_name" class="form-control" value="<?php echo htmlspecialchars($student['institution_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="place" class="form-control" value="<?php echo htmlspecialchars($student['place'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Center</label>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <select name="center_id" class="form-control" required>
                            <?php foreach($centers as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $student['center_id'] == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="hidden" name="center_id" value="<?php echo $student['center_id']; ?>">
                        <input type="text" class="form-control" value="Assigned Center Only" readonly style="background:#eee;">
                    <?php endif; ?>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary mt-2">Save Changes</button>
        </form>
    </div>
</div>

<script src="app.js"></script>
</body>
</html>
