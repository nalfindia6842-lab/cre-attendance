<?php
require_once 'db.php';

$message = '';
$is_success = false;

// Fetch centers for dropdown
$stmt = $pdo->query("SELECT id, name FROM centers");
$centers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate IDs
    $year = date('Y');
    $rand = strtoupper(substr(uniqid(), -5));
    $application_id = "APP-{$year}-{$rand}";
    $admission_no = "ADM-{$year}-{$rand}";
    $student_id = $admission_no; // Using admission_no as the unique student_id

    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $blood_group = $_POST['blood_group'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $address = $_POST['address'] ?? '';
    $phone_number = $_POST['phone_number'] ?? ''; // Mobile 1
    $mobile2 = $_POST['mobile2'] ?? '';
    $whatsapp_no = $_POST['whatsapp_no'] ?? '';
    $email = $_POST['email'] ?? '';
    $class = $_POST['class'] ?? '';
    $academic_year = $_POST['academic_year'] ?? '';
    $institution_name = $_POST['institution_name'] ?? '';
    $place = $_POST['place'] ?? '';
    $center_id = $_POST['center_id'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO students (
                application_id, admission_no, name, student_id, age, gender, blood_group, dob, 
                address, phone_number, mobile2, whatsapp_no, email, class, 
                academic_year, institution_name, place, center_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 
                ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $application_id, $admission_no, $name, $student_id, $age, $gender, $blood_group, $dob,
            $address, $phone_number, $mobile2, $whatsapp_no, $email, $class,
            $academic_year, $institution_name, $place, $center_id
        ]);
        
        $is_success = true;
        $message = "Application submitted successfully! Your Application ID is: <strong>{$application_id}</strong> and Admission No is: <strong>{$admission_no}</strong>";
    } catch(PDOException $e) {
        $message = "Error submitting application. Please try again. " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Admission Form</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admission-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="admission-container fade-in">
    <div class="card">
        <div class="auth-logo">
            <img src="cre.png" alt="CRE Logo" class="logo" style="max-height: 80px;">
        </div>
        <div class="text-center mb-2">
            <h2 style="color: var(--primary-color);">Student Admission Form</h2>
            <p class="text-muted">Please fill in all the required details.</p>
        </div>

        <?php if($message): ?>
            <div class="alert mb-2" style="background-color: <?php echo $is_success ? 'var(--success-color)' : 'var(--danger-color)'; ?>; color: white; padding: 15px; border-radius: var(--border-radius); text-align: center; font-size: 1.1rem;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if(!$is_success): ?>
        <form action="" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" class="form-control">
                </div>

                <div class="form-group">
                    <label>Gender *</label>
                    <select name="gender" class="form-control" required>
                        <option value="">Select Gender...</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Blood Group</label>
                    <select name="blood_group" class="form-control">
                        <option value="">Select Blood Group...</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>

                <div class="form-group">
                    <label>Course / Class *</label>
                    <input type="text" name="class" class="form-control" required>
                </div>

                <div class="form-group full-width">
                    <label>Address *</label>
                    <textarea name="address" class="form-control" rows="3" required></textarea>
                </div>

                <div class="form-group">
                    <label>Mobile No: 1 *</label>
                    <input type="text" name="phone_number" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Mobile No: 2</label>
                    <input type="text" name="mobile2" class="form-control">
                </div>

                <div class="form-group">
                    <label>WhatsApp No</label>
                    <input type="text" name="whatsapp_no" class="form-control">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>

                <div class="form-group">
                    <label>Academic Year</label>
                    <input type="text" name="academic_year" class="form-control" placeholder="e.g. 2026-2027">
                </div>

                <div class="form-group">
                    <label>Name of Institution</label>
                    <input type="text" name="institution_name" class="form-control">
                </div>

                <div class="form-group">
                    <label>Place</label>
                    <input type="text" name="place" class="form-control">
                </div>

                <div class="form-group full-width">
                    <label>Nearest CRE Center *</label>
                    <select name="center_id" class="form-control" required>
                        <option value="">Select your nearest center...</option>
                        <?php foreach($centers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block mt-2" style="font-size: 1.1rem; padding: 12px;">Submit Application</button>
        </form>
        <?php else: ?>
            <div class="text-center mt-2">
                <a href="admission.php" class="btn btn-primary">Submit Another Application</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Floating WhatsApp Button -->
<a href="https://wa.me/918078256692" target="_blank" class="floating-wa-btn" title="Contact us on WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
</a>

<div class="footer-text">
    Developed by Wisdom Wandoor
</div>

</body>
</html>
