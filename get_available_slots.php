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

$worker_id = intval($_GET['worker_id'] ?? 0);
$date = $_GET['date'] ?? '';

if($worker_id <= 0 || empty($date)){
    echo json_encode(['error' => 'Missing worker_id or date']);
    exit();
}

// Validate date format
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)){
    echo json_encode(['error' => 'Invalid date format']);
    exit();
}

// Get all slots for this worker on this date that are PENDING or BOOKED
$stmt = $conn->prepare("
    SELECT bts.slot_hour, bts.status, bts.job_id
    FROM booking_time_slots bts
    WHERE bts.worker_id = ? 
      AND bts.slot_date = ? 
      AND bts.status IN ('PENDING', 'BOOKED')
    ORDER BY bts.slot_hour ASC
");
$stmt->bind_param("is", $worker_id, $date);
$stmt->execute();
$result = $stmt->get_result();

// Build a map of hour => status
$slot_map = [];
while($row = $result->fetch_assoc()){
    $hour = $row['slot_hour'];
    // If already BOOKED, always show as booked (highest priority)
    if(isset($slot_map[$hour]) && $slot_map[$hour] === 'booked'){
        continue;
    }
    if($row['status'] === 'BOOKED'){
        $slot_map[$hour] = 'booked';
    } else {
        $slot_map[$hour] = 'pending';
    }
}

// Build the 12 slots (6 AM to 5 PM, each representing a 1-hour block)
$slots = [];
for($h = 6; $h <= 17; $h++){
    $status = $slot_map[$h] ?? 'available';
    
    // Format hour for display
    $display = ($h < 12) ? $h . ':00 AM' : (($h == 12) ? '12:00 PM' : ($h - 12) . ':00 PM');
    $end_h = $h + 1;
    $end_display = ($end_h < 12) ? $end_h . ':00 AM' : (($end_h == 12) ? '12:00 PM' : ($end_h - 12) . ':00 PM');
    
    $slots[] = [
        'hour' => $h,
        'label' => $display . ' - ' . $end_display,
        'status' => $status
    ];
}

echo json_encode([
    'success' => true,
    'date' => $date,
    'worker_id' => $worker_id,
    'slots' => $slots
]);
?>
