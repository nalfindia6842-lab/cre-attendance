<?php
require_once 'db.php';

try {
    // Create fee_structures table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS fee_structures (
        id INT AUTO_INCREMENT PRIMARY KEY,
        center_id INT NOT NULL,
        fee_type ENUM('admission', 'monthly', 'exam', 'other') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_center_fee (center_id, fee_type),
        FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
    )");
    
    // Create fee_collections table
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS fee_collections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        fee_type ENUM('admission', 'monthly', 'exam', 'other') NOT NULL,
        amount_paid DECIMAL(10,2) NOT NULL,
        fee_month VARCHAR(20) DEFAULT NULL,
        payment_date DATE NOT NULL,
        remarks TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");

    echo "Migration successful. Tables fee_structures and fee_collections have been created.";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>
