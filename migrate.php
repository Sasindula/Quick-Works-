<?php
require_once("config/Database.php");
$db = new Database();
$conn = $db->connect();

// Suppress exceptions for duplicate column
mysqli_report(MYSQLI_REPORT_OFF);

$queries = [
    "ALTER TABLE worker_profiles ADD COLUMN daily_rate DECIMAL(10,2) DEFAULT NULL",
    "ALTER TABLE job_requests ADD COLUMN payment_option ENUM('LATER','CARD') DEFAULT 'LATER'",
    "ALTER TABLE job_requests MODIFY COLUMN payment_option ENUM('LATER','CARD') DEFAULT 'LATER'",
    "ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'info'",
    "ALTER TABLE notifications ADD COLUMN job_id INT DEFAULT NULL",
    "UPDATE users SET status='ACTIVE' WHERE role='ADMIN'",

    // New columns for multi-step registration
    "ALTER TABLE users ADD COLUMN nic VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN gender VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE worker_profiles ADD COLUMN grama_cert VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE worker_profiles ADD COLUMN police_cert VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE worker_profiles ADD COLUMN other_qualifications TEXT DEFAULT NULL"
];

foreach($queries as $sql){
    if($conn->query($sql)){
        echo "OK: $sql\n";
    } else {
        echo "SKIP: " . $conn->error . "\n";
    }
}
echo "\nAll migrations done!";
?>
