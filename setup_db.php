<?php
// Include the database connection
require_once 'db.php';

echo "<h2>Database Setup</h2>";

try {
    // Read the SQL file
    $sql_file = 'init.sql';
    
    if (!file_exists($sql_file)) {
        die("Error: $sql_file not found. Please make sure init.sql is in the same folder.");
    }

    $sql = file_get_contents($sql_file);

    // Execute the SQL queries
    $pdo->exec($sql);
    
    echo "<p style='color: green;'><b>Success:</b> All tables and initial data have been created successfully!</p>";
    echo "<p>You can now delete this <b>setup_db.php</b> file for security.</p>";
    echo "<a href='index.php'>Go to Login Page</a>";

} catch (PDOException $e) {
    echo "<p style='color: red;'><b>Error creating tables:</b> " . $e->getMessage() . "</p>";
    echo "<p>Please check your database credentials in <b>db.php</b> and try again.</p>";
}
?>
