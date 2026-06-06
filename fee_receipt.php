<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Receipt ID missing.");
}

$id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT fc.*, s.name as student_name, s.student_id as admission_num, s.class, c.name as center_name
    FROM fee_collections fc
    JOIN students s ON fc.student_id = s.id
    LEFT JOIN centers c ON s.center_id = c.id
    WHERE fc.id = ?
");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    die("Receipt not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="crefav.png" type="image/png">
    <meta charset="UTF-8">
    <title>Fee Receipt - <?php echo htmlspecialchars($receipt['student_name']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { background-color: #fff; color: #000; padding: 20px; }
        .receipt-container { max-width: 600px; margin: 0 auto; border: 2px solid #333; padding: 20px; border-radius: 8px; }
        .receipt-header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .receipt-header img { height: 60px; margin-bottom: 10px; }
        .receipt-header h2 { margin: 0; font-size: 1.5rem; }
        .receipt-details { margin-bottom: 20px; }
        .receipt-details table { width: 100%; border-collapse: collapse; }
        .receipt-details th, .receipt-details td { padding: 8px; text-align: left; }
        .receipt-footer { text-align: right; margin-top: 40px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-container fade-in">
    <div class="receipt-header">
        <img src="cre.png" alt="CRE Logo">
        <h2><?php echo htmlspecialchars($receipt['center_name']); ?></h2>
        <p>Fee Payment Receipt</p>
    </div>

    <div class="receipt-details">
        <table>
            <tr>
                <th>Receipt No:</th>
                <td>#<?php echo str_pad($receipt['id'], 6, '0', STR_PAD_LEFT); ?></td>
                <th>Date:</th>
                <td><?php echo date('d M Y', strtotime($receipt['payment_date'])); ?></td>
            </tr>
            <tr>
                <th>Student Name:</th>
                <td><?php echo htmlspecialchars($receipt['student_name']); ?></td>
                <th>Admission No:</th>
                <td><?php echo htmlspecialchars($receipt['admission_num']); ?></td>
            </tr>
            <tr>
                <th>Class:</th>
                <td><?php echo htmlspecialchars($receipt['class']); ?></td>
                <th>Fee Type:</th>
                <td><?php echo ucfirst(htmlspecialchars($receipt['fee_type'])); ?></td>
            </tr>
            <?php if ($receipt['fee_month']): ?>
            <tr>
                <th>Fee Month:</th>
                <td colspan="3"><?php echo htmlspecialchars($receipt['fee_month']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Amount Paid:</th>
                <td colspan="3" style="font-weight: bold; font-size: 1.2rem;">₹<?php echo number_format($receipt['amount_paid'], 2); ?></td>
            </tr>
            <?php if ($receipt['remarks']): ?>
            <tr>
                <th>Remarks:</th>
                <td colspan="3"><?php echo htmlspecialchars($receipt['remarks']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="receipt-footer">
        <p>Authorized Signature: __________________</p>
    </div>
</div>

<div class="text-center mt-2 no-print">
    <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
    <a href="manage_fees.php" class="btn btn-secondary" style="margin-left: 10px;">Back</a>
</div>

</body>
</html>
