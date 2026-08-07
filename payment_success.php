<?php
session_start();
require_once("config/Database.php");

if(!isset($_GET['job_id'])) {
    header("Location: my_bookings.php");
    exit();
}

$db = new Database();
$conn = $db->connect();

$job_id = intval($_GET['job_id']);
$amount = floatval($_GET['amount']);
$session_id = $_GET['session_id'];

// Check if already paid to avoid double entry
$check = $conn->prepare("SELECT payment_id FROM payments WHERE job_id = ? AND payment_status = 'PAID'");
$check->bind_param("i", $job_id);
$check->execute();
if($check->get_result()->num_rows == 0) {

    // Insert payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (job_id, amount, payment_method, payment_status, payment_date)
        VALUES (?, ?, 'STRIPE', 'PAID', NOW())
    ");
    $stmt->bind_param("id", $job_id, $amount);
    
    if($stmt->execute()) {
        // Notify worker
        $notif = $conn->prepare("
            INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at)
            VALUES ((SELECT worker_id FROM job_requests WHERE job_id = ?), ?, 'payment_received', ?, 0, NOW())
        ");
        $notif_msg = "Payment of Rs " . number_format($amount, 0) . " received via Stripe for job #$job_id";
        $notif->bind_param("isi", $job_id, $notif_msg, $job_id);
        $notif->execute();
    }
}

header("Location: my_bookings.php?msg=Payment Successful via Stripe!");
exit();
?>
