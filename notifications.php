<?php
session_start();
require_once(__DIR__ . "/../config/Database.php");

header('Content-Type: application/json');

$db = new Database();
$conn = $db->connect();

/* AUTH CHECK */
if(!isset($_SESSION['user'])){
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user']['user_id'];

/* GET NOTIFICATIONS */
if($_SERVER['REQUEST_METHOD'] == 'GET'){
    
    $stmt = $conn->prepare("
        SELECT n.*, jr.description as job_desc, jr.job_date, jr.payment_option,
               jr.duration, jr.duration_type,
               u.name as customer_name
        FROM notifications n
        LEFT JOIN job_requests jr ON n.job_id = jr.job_id
        LEFT JOIN users u ON jr.customer_id = u.user_id
        WHERE n.user_id = ?
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while($row = $result->fetch_assoc()){
        // If it's a booking request, attach time slot info
        if($row['type'] === 'booking_request' && $row['job_id']){
            $slot_stmt = $conn->prepare("
                SELECT slot_hour FROM booking_time_slots 
                WHERE job_id = ? AND status IN ('PENDING', 'BOOKED')
                ORDER BY slot_hour ASC
            ");
            $slot_stmt->bind_param("i", $row['job_id']);
            $slot_stmt->execute();
            $slot_result = $slot_stmt->get_result();
            
            $slots = [];
            while($s = $slot_result->fetch_assoc()){
                $h = $s['slot_hour'];
                $display = ($h < 12) ? $h . ':00 AM' : (($h == 12) ? '12:00 PM' : ($h - 12) . ':00 PM');
                $slots[] = $display;
            }
            $row['time_slots'] = $slots;
            $row['time_slots_text'] = implode(', ', $slots);
        }
        $notifications[] = $row;
    }

    // Get unread count
    $unread = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE user_id=$user_id AND is_read=0")->fetch_assoc()['c'];

    echo json_encode([
        'notifications' => $notifications,
        'unread_count' => $unread
    ]);
}

/* MARK AS READ */
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $data = json_decode(file_get_contents('php://input'), true);
    
    if(isset($data['mark_read'])){
        $notif_id = intval($data['mark_read']);
        $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE notification_id=? AND user_id=?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        echo json_encode(['success' => true]);
    }

    if(isset($data['mark_all_read'])){
        $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
        echo json_encode(['success' => true]);
    }
}
?>
