<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* AUTH CHECK */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "WORKER"){
    header("Location: ../login.php");
    exit();
}

$worker_id = $_SESSION['user']['user_id'];

/* ===== ACTIONS ===== */

/* ACCEPT */
if(isset($_GET['accept'])){
    $id = intval($_GET['accept']);
    
    // Check for slot conflicts before accepting
    $conflict = $conn->prepare("
        SELECT bts1.slot_hour 
        FROM booking_time_slots bts1
        JOIN booking_time_slots bts2 ON bts2.worker_id = bts1.worker_id 
            AND bts2.slot_date = bts1.slot_date 
            AND bts2.slot_hour = bts1.slot_hour 
            AND bts2.status = 'BOOKED'
            AND bts2.job_id != bts1.job_id
        WHERE bts1.job_id = ? AND bts1.status = 'PENDING'
    ");
    $conflict->bind_param("i", $id);
    $conflict->execute();
    $conflicts = $conflict->get_result();
    
    if($conflicts->num_rows > 0){
        $error_msg = "Cannot accept — some time slots are already booked by another customer.";
    } else {
        $conn->query("UPDATE job_requests SET status='ACCEPTED' WHERE job_id=$id AND worker_id=$worker_id");
        $conn->query("UPDATE booking_time_slots SET status='BOOKED' WHERE job_id=$id AND status='PENDING'");
        
        // Notify customer
        $job_info = $conn->query("SELECT customer_id FROM job_requests WHERE job_id=$id")->fetch_assoc();
        if($job_info){
            $worker_name = $_SESSION['user']['name'];
            $notif_msg = "Great news! $worker_name has accepted your booking (Job #$id).";
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at) VALUES (?, ?, 'booking_accepted', ?, 0, NOW())");
            $stmt->bind_param("isi", $job_info['customer_id'], $notif_msg, $id);
            $stmt->execute();
        }
    }
}

/* REJECT */
if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);
    $conn->query("UPDATE job_requests SET status='REJECTED' WHERE job_id=$id AND worker_id=$worker_id");
    $conn->query("UPDATE booking_time_slots SET status='RELEASED' WHERE job_id=$id AND status='PENDING'");
    
    // Notify customer
    $job_info = $conn->query("SELECT customer_id FROM job_requests WHERE job_id=$id")->fetch_assoc();
    if($job_info){
        $worker_name = $_SESSION['user']['name'];
        $notif_msg = "$worker_name has declined your booking request (Job #$id). Please try another worker.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, job_id, is_read, created_at) VALUES (?, ?, 'booking_rejected', ?, 0, NOW())");
        $stmt->bind_param("isi", $job_info['customer_id'], $notif_msg, $id);
        $stmt->execute();
    }
}

/* COMPLETE */
if(isset($_GET['complete'])){
    $id = intval($_GET['complete']);
    $conn->query("UPDATE job_requests SET status='COMPLETED' WHERE job_id=$id AND worker_id=$worker_id");
    $conn->query("UPDATE booking_time_slots SET status='COMPLETED' WHERE job_id=$id AND status='BOOKED'");
}

/* ===== FETCH JOBS ===== */
$result = $conn->query("
SELECT 
    jr.*, 
    u.name AS customer_name
FROM job_requests jr
JOIN users u ON jr.customer_id = u.user_id
WHERE jr.worker_id = $worker_id
ORDER BY jr.job_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Jobs - QuickWorks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; margin: 0; }

        .w-sidebar {
            position: fixed; top: 0; left: 0;
            width: 260px; height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff; z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .w-sidebar .brand {
            padding: 28px 24px 20px; font-size: 1.5rem; font-weight: 700;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }
        .w-sidebar .brand i { color: #f59e0b; margin-right: 8px; }
        .w-sidebar .nav-links { padding: 16px 12px; list-style: none; margin: 0; }
        .w-sidebar .nav-links li { margin-bottom: 4px; }
        .w-sidebar .nav-links a {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px; border-radius: 10px;
            color: #94a3b8; text-decoration: none;
            font-size: 0.9rem; font-weight: 500; transition: all 0.2s;
        }
        .w-sidebar .nav-links a:hover { background: rgba(255,255,255,0.08); color: #e2e8f0; }
        .w-sidebar .nav-links a.active {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; box-shadow: 0 4px 12px rgba(245,158,11,0.3);
        }
        .w-sidebar .nav-links a i { font-size: 1.1rem; width: 22px; text-align: center; }

        .w-main { margin-left: 260px; padding: 0; min-height: 100vh; }
        .w-topbar {
            background: #fff; padding: 16px 32px;
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100;
        }
        .w-content { padding: 32px; }

        /* Time slot badges */
        .slot-badge {
            display: inline-flex; align-items: center; gap: 3px;
            padding: 2px 8px; border-radius: 5px;
            font-size: 0.72rem; font-weight: 600; margin: 1px;
        }
        .slot-badge.pending { background: #fef3c7; color: #92400e; }
        .slot-badge.booked { background: #dbeafe; color: #1e40af; }
        .slot-badge.released { background: #fee2e2; color: #991b1b; text-decoration: line-through; }
        .slot-badge.completed { background: #dcfce7; color: #166534; }

        /* Job card */
        .job-card {
            background: #fff;
            border-radius: 14px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 16px;
            transition: all 0.2s;
        }
        .job-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .conflict-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 14px 20px;
            margin-bottom: 20px;
            color: #991b1b;
            font-weight: 500;
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="w-sidebar">
    <div class="brand"><i class="bi bi-wrench-adjustable-circle-fill"></i>Worker Panel</div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a></li>
        <li><a href="jobs.php" class="active"><i class="bi bi-list-check"></i><span>Jobs</span></a></li>
        <li><a href="worker_Payment.php"><i class="bi bi-wallet2"></i><span>Payments</span></a></li>
        <li><a href="worker_complaints.php"><i class="bi bi-exclamation-triangle"></i><span>Complaints</span></a></li>
        <li><a href="profiles.php"><i class="bi bi-person-fill"></i><span>Profile</span></a></li>
        <li style="position: absolute; bottom: 16px; width: calc(100% - 24px);">
            <a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </li>
    </ul>
</div>

<!-- MAIN -->
<div class="w-main">
    <div class="w-topbar">
        <h5 class="fw-bold mb-0"><i class="bi bi-list-check me-2"></i>My Jobs</h5>
    </div>

    <div class="w-content">

        <?php if(isset($error_msg)): ?>
            <div class="conflict-alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_msg; ?>
            </div>
        <?php endif; ?>

        <?php if($result->num_rows == 0): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                <h5 class="text-muted mt-3">No jobs yet</h5>
                <p class="text-muted">Your job requests will appear here.</p>
            </div>
        <?php else: ?>

            <?php while($row = $result->fetch_assoc()): 
                // Fetch time slots for this job
                $slot_stmt = $conn->prepare("SELECT slot_hour, status FROM booking_time_slots WHERE job_id = ? ORDER BY slot_hour ASC");
                $slot_stmt->bind_param("i", $row['job_id']);
                $slot_stmt->execute();
                $slots = $slot_stmt->get_result();
                $time_slots = [];
                while($sl = $slots->fetch_assoc()) $time_slots[] = $sl;

                $status_color = match($row['status']){
                    'PENDING' => '#f59e0b',
                    'ACCEPTED' => '#06b6d4',
                    'COMPLETED' => '#22c55e',
                    'REJECTED' => '#ef4444',
                    default => '#94a3b8'
                };
                $status_bg = match($row['status']){
                    'PENDING' => 'bg-warning text-dark',
                    'ACCEPTED' => 'bg-info text-white',
                    'COMPLETED' => 'bg-success text-white',
                    'REJECTED' => 'bg-danger text-white',
                    default => 'bg-secondary'
                };
            ?>
            <div class="job-card" style="border-left: 4px solid <?php echo $status_color; ?>;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($row['customer_name']); ?></h6>
                                <span class="badge <?php echo $status_bg; ?>" style="border-radius: 6px; font-size: 0.75rem;">
                                    <?php echo $row['status']; ?>
                                </span>
                            </div>
                            <small class="text-muted">Job #<?php echo $row['job_id']; ?></small>
                        </div>
                        <div class="text-end">
                            <?php if($row['payment_option'] == 'CARD'): ?>
                                <span class="badge bg-primary-subtle text-primary border" style="border-radius: 6px;">
                                    <i class="bi bi-credit-card me-1"></i>Card
                                </span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border" style="border-radius: 6px;">
                                    <i class="bi bi-cash me-1"></i>Cash
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="mb-2" style="color: #475569; font-size: 0.9rem;"><?php echo htmlspecialchars($row['description']); ?></p>

                    <div class="d-flex gap-3 mb-2" style="font-size: 0.85rem; color: #64748b; flex-wrap: wrap;">
                        <span><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($row['job_date'])); ?></span>
                        <span><i class="bi bi-clock me-1"></i><?php echo $row['duration'] . ' Hour' . ($row['duration'] > 1 ? 's' : ''); ?></span>
                    </div>

                    <!-- Time Slots -->
                    <?php if(!empty($time_slots)): ?>
                    <div class="mb-3">
                        <small class="text-muted fw-medium"><i class="bi bi-clock-history me-1"></i>Time Slots:</small>
                        <div class="mt-1">
                            <?php foreach($time_slots as $ts): 
                                $h = $ts['slot_hour'];
                                $label = ($h < 12) ? $h . ':00 AM' : (($h == 12) ? '12:00 PM' : ($h - 12) . ':00 PM');
                                $ts_status = strtolower($ts['status']);
                            ?>
                                <span class="slot-badge <?php echo $ts_status; ?>"><?php echo $label; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <?php if($row['status'] == 'PENDING'): ?>
                            <a href="?accept=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-success" style="border-radius: 8px; font-weight: 600;">
                                <i class="bi bi-check-circle me-1"></i>Accept
                            </a>
                            <a href="?reject=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-danger" style="border-radius: 8px; font-weight: 600;">
                                <i class="bi bi-x-circle me-1"></i>Reject
                            </a>
                        <?php elseif($row['status'] == 'ACCEPTED'): ?>
                            <a href="?complete=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-primary" style="border-radius: 8px; font-weight: 600;">
                                <i class="bi bi-check-all me-1"></i>Mark Complete
                            </a>
                        <?php else: ?>
                            <span class="text-muted" style="font-size: 0.85rem;">No actions available</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
