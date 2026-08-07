<?php
session_start();
require_once(__DIR__ . "/../config/Database.php");

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

/* AUTH CHECK - WORKER ONLY */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'WORKER'){
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$worker_id = $_SESSION['user']['user_id'];
$worker_name = $_SESSION['user']['name'];

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $job_id = intval($data['job_id'] ?? 0);
    $action = $data['action'] ?? '';

    if($job_id <= 0 || !in_array($action, ['accept', 'reject'])){
        echo json_encode(['error' => 'Invalid request']);
        exit();
    }

    // Verify this job belongs to the worker
    $check = $conn->prepare("SELECT * FROM job_requests WHERE job_id=? AND worker_id=? AND status='PENDING'");
    $check->bind_param("ii", $job_id, $worker_id);
    $check->execute();
    $job = $check->get_result()->fetch_assoc();

    if(!$job){
        echo json_encode(['error' => 'Job not found or already processed']);
        exit();
    }

    // Update job status
    $new_status = ($action == 'accept') ? 'ACCEPTED' : 'REJECTED';
    $update = $conn->prepare("UPDATE job_requests SET status=? WHERE job_id=?");
    $update->bind_param("si", $new_status, $job_id);
    $update->execute();

    // Update time slot statuses
    if($action == 'accept'){
        // Check if any of the requested slots are already BOOKED by another job
        $conflict_check = $conn->prepare("
            SELECT bts2.slot_hour 
            FROM booking_time_slots bts1
            JOIN booking_time_slots bts2 ON bts2.worker_id = bts1.worker_id 
                AND bts2.slot_date = bts1.slot_date 
                AND bts2.slot_hour = bts1.slot_hour 
                AND bts2.status = 'BOOKED'
                AND bts2.job_id != bts1.job_id
            WHERE bts1.job_id = ?
        ");
        $conflict_check->bind_param("i", $job_id);
        $conflict_check->execute();
        $conflicts = $conflict_check->get_result();
        
        if($conflicts->num_rows > 0){
            // Revert the job status
            $conn->query("UPDATE job_requests SET status='PENDING' WHERE job_id=$job_id");
            echo json_encode(['error' => 'Some time slots have already been booked by another customer. Please reject this booking.']);
            exit();
        }
        
        // Mark slots as BOOKED
        $slot_update = $conn->prepare("UPDATE booking_time_slots SET status='BOOKED' WHERE job_id=? AND status='PENDING'");
        $slot_update->bind_param("i", $job_id);
        $slot_update->execute();
    } else {
        // Mark slots as RELEASED
        $slot_update = $conn->prepare("UPDATE booking_time_slots SET status='RELEASED' WHERE job_id=? AND status='PENDING'");
        $slot_update->bind_param("i", $job_id);
        $slot_update->execute();
    }

    // Notify customer
    $customer_id = $job['customer_id'];
    $payment_option = $job['payment_option'] ?? 'LATER';
    
    if($action == 'accept'){
        $pay_text = ($payment_option == 'NOW') ? 'Payment has been made upfront.' : 'Customer will pay after work completion.';
        $notif_msg = "Great news! $worker_name has accepted your booking (Job #$job_id). $pay_text";
        $notif_type = 'booking_accepted';
    } else {
        $notif_msg = "$worker_name has declined your booking request (Job #$job_id). Please try another worker.";
        $notif_type = 'booking_rejected';
    }

    $notif = $conn->prepare("
        INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at)
        VALUES (?, ?, ?, ?, 0, NOW())
    ");
    $notif->bind_param("issi", $customer_id, $notif_msg, $notif_type, $job_id);
    $notif->execute();

    // Mark the original notification as read
    if(isset($data['notification_id'])){
        $nid = intval($data['notification_id']);
        $conn->query("UPDATE notifications SET is_read=1 WHERE notification_id=$nid");
    }

    echo json_encode([
        'success' => true,
        'status' => $new_status,
        'message' => "Job $action" . "ed successfully!",
        'payment_option' => $payment_option
    ]);
}
?>
