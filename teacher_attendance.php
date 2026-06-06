<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$center_id = $_SESSION['center_id'];
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['attendance'])) {
    $attendance_data = $_POST['attendance']; // Array of student_id => status
    $submit_date = $_POST['date'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, date, status) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE status = VALUES(status)
        ");
        
        foreach ($attendance_data as $student_id => $status) {
            $stmt->execute([$student_id, $submit_date, $status]);
        }
        
        $pdo->commit();
        $message = "Attendance saved successfully!";
    } catch(PDOException $e) {
        $pdo->rollBack();
        $message = "Error saving attendance.";
    }
}

// Fetch students for this center
$stmt = $pdo->prepare("SELECT id, name, student_id, photo_url FROM students WHERE center_id = ? ORDER BY name ASC");
$stmt->execute([$center_id]);
$students = $stmt->fetchAll();

// Fetch existing attendance for the selected date
$stmt = $pdo->prepare("
    SELECT student_id, status 
    FROM attendance 
    WHERE date = ? AND student_id IN (SELECT id FROM students WHERE center_id = ?)
");
$stmt->execute([$date, $center_id]);
$existing_attendance = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher</title>
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
        <a href="teacher_attendance.php" class="active">Mark Attendance</a>
        <a href="teacher_students.php">My Students</a>
        <a href="reports.php">Reports</a>
        <a href="logout.php" class="btn btn-danger" style="color:white; padding: 5px 15px; margin-left:15px;">Logout</a>
    </div>
</header>

<div class="container fade-in">
    <div class="flex justify-between align-center mb-2">
        <h2>Mark Attendance</h2>
        <form action="" method="GET" style="display:flex; gap:10px;">
            <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>" onchange="this.form.submit()">
        </form>
    </div>

    <?php if($message): ?>
        <div class="alert" style="background-color: var(--success-color); color: white; padding: 10px; border-radius: var(--border-radius); margin-bottom: 15px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form action="teacher_attendance.php?date=<?php echo urlencode($date); ?>" method="POST">
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
        
        <div class="attendance-list">
            <?php foreach($students as $s): ?>
                <?php $status = $existing_attendance[$s['id']] ?? ''; ?>
                <div class="student-attendance-card">
                    <?php if($s['photo_url']): ?>
                        <img src="<?php echo htmlspecialchars($s['photo_url']); ?>" alt="Photo" class="student-photo">
                    <?php else: ?>
                        <div class="student-photo flex justify-center align-center"><i class="fa-solid fa-user"></i></div>
                    <?php endif; ?>
                    
                    <div class="student-info">
                        <h4><?php echo htmlspecialchars($s['name']); ?></h4>
                        <p>ID: <?php echo htmlspecialchars($s['student_id']); ?></p>
                    </div>
                    
                    <div class="attendance-actions">
                        <label style="color: var(--success-color);">
                            <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="present" <?php echo $status === 'present' ? 'checked' : ''; ?> required> Present
                        </label>
                        <label style="color: var(--danger-color);">
                            <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="absent" <?php echo $status === 'absent' ? 'checked' : ''; ?>> Absent
                        </label>
                        <label style="color: var(--warning-color);">
                            <input type="radio" name="attendance[<?php echo $s['id']; ?>]" value="late" <?php echo $status === 'late' ? 'checked' : ''; ?>> Late
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if(count($students) > 0): ?>
            <div class="mt-2 text-center" style="position: sticky; bottom: 20px; z-index: 100;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">Save Attendance</button>
            </div>
        <?php else: ?>
            <p class="text-center mt-2 text-muted">No students found in your center.</p>
        <?php endif; ?>
    </form>
</div>

<script src="app.js"></script>
</body>
</html>
