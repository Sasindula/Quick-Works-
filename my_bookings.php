<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

/* LOGIN CHECK */
if(!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

/* GET BOOKINGS */
$stmt = $conn->prepare("
    SELECT jr.*, 
           wp.skills, u.address, wp.hourly_rate,
           u.name AS worker_name,
           p.payment_status, p.amount as paid_amount
    FROM job_requests jr
    JOIN worker_profiles wp ON jr.worker_id = wp.worker_id
    JOIN users u ON wp.worker_id = u.user_id
    LEFT JOIN payments p ON jr.job_id = p.job_id
    WHERE jr.customer_id = ?
    ORDER BY jr.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Pre-fetch all time slots for these bookings
$booking_slots = [];
$slot_query = $conn->prepare("
    SELECT job_id, slot_hour, status 
    FROM booking_time_slots 
    WHERE job_id = ? 
    ORDER BY slot_hour ASC
");

// Store results first
$bookings = [];
while($row = $result->fetch_assoc()){
    // Get time slots for this booking
    $slot_query->bind_param("i", $row['job_id']);
    $slot_query->execute();
    $slot_result = $slot_query->get_result();
    
    $slots = [];
    while($s = $slot_result->fetch_assoc()){
        $slots[] = $s;
    }
    $row['time_slots'] = $slots;
    $bookings[] = $row;
}

$current_page = 'bookings';
$page_title = 'My Bookings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - QuickWorks</title>
    <style>
        .time-slots-display {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }
        .time-slot-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .time-slot-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        .time-slot-badge.booked {
            background: #dbeafe;
            color: #1e40af;
        }
        .time-slot-badge.released {
            background: #fee2e2;
            color: #991b1b;
            text-decoration: line-through;
        }
        .time-slot-badge.completed {
            background: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($_GET['msg']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h4 class="fw-bold mb-0">My Bookings</h4>
    <a href="search.php" class="btn btn-primary" style="border-radius: 10px; font-weight: 600;">
        <i class="bi bi-plus-lg me-1"></i>New Booking
    </a>
</div>

<?php if(count($bookings) == 0): ?>
    <div class="card border-0 shadow-sm text-center py-5" style="border-radius: 14px;">
        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
        <h5 class="text-muted mt-3">No bookings yet</h5>
        <p class="text-muted">Find and book skilled workers to get started!</p>
        <a href="search.php" class="btn btn-primary mx-auto" style="border-radius: 10px; width: fit-content;">Find Workers</a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach($bookings as $row): 
            $s = $row['status'];
            $badge_class = match($s){
                'PENDING' => 'bg-warning text-dark',
                'ACCEPTED' => 'bg-info',
                'COMPLETED' => 'bg-success',
                'REJECTED' => 'bg-danger',
                default => 'bg-secondary'
            };
            $border_color = match($s){
                'PENDING' => '#fbbf24',
                'ACCEPTED' => '#06b6d4',
                'COMPLETED' => '#22c55e',
                'REJECTED' => '#ef4444',
                default => '#94a3b8'
            };
        ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 14px; border-left: 4px solid <?php echo $border_color; ?> !important;">
                <div class="card-body p-4">
                    <!-- Header -->
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                <?php echo strtoupper(substr($row['worker_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($row['worker_name']); ?></h6>
                                <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?></small>
                            </div>
                        </div>
                        <span class="badge <?php echo $badge_class; ?>" style="border-radius: 6px; padding: 5px 12px; font-weight: 500;">
                            <?php echo $s; ?>
                        </span>
                    </div>

                    <!-- Description -->
                    <p class="mb-2" style="font-size: 0.9rem; color: #475569;">
                        <?php echo htmlspecialchars(substr($row['description'], 0, 100)); ?>
                        <?php echo strlen($row['description']) > 100 ? '...' : ''; ?>
                    </p>

                    <!-- Date & Duration -->
                    <div class="d-flex gap-3 mb-2" style="font-size: 0.85rem; color: #64748b; flex-wrap: wrap;">
                        <span><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y', strtotime($row['job_date'])); ?></span>
                        <span><i class="bi bi-clock me-1"></i><?php echo $row['duration'] . ' Hour' . ($row['duration'] > 1 ? 's' : ''); ?></span>
                        <?php 
                            $rate = $row['hourly_rate'];
                            $est_total = $row['duration'] * $rate;
                        ?>
                        <span class="text-success fw-bold">Est: Rs <?php echo number_format($est_total, 0); ?></span>
                        <?php if($s == 'PENDING'): ?>
                            <span class="text-warning fw-bold"><i class="bi bi-hourglass-split me-1"></i>Waiting for approval</span>
                        <?php endif; ?>
                    </div>

                    <!-- Time Slots Display -->
                    <?php if(!empty($row['time_slots'])): ?>
                    <div class="mb-3">
                        <small class="text-muted fw-medium d-block mb-1">
                            <i class="bi bi-clock-history me-1"></i>Time Slots:
                        </small>
                        <div class="time-slots-display">
                            <?php foreach($row['time_slots'] as $slot): 
                                $h = $slot['slot_hour'];
                                $slot_label = ($h < 12) ? $h . ' AM' : (($h == 12) ? '12 PM' : ($h - 12) . ' PM');
                                $slot_status = strtolower($slot['status']);
                            ?>
                                <span class="time-slot-badge <?php echo $slot_status; ?>">
                                    <?php echo $slot_label; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="d-flex gap-2">
                        <?php if($s == 'REJECTED'): ?>
                            <a href="search.php" class="btn btn-sm btn-primary" style="border-radius: 8px; font-weight: 500;">
                                <i class="bi bi-search me-1"></i>Find Another Worker
                            </a>
                        <?php endif; ?>

                        <?php if($s == 'ACCEPTED' && (!isset($row['payment_status']) || $row['payment_status'] != 'PAID')): ?>
                            <a href="payment.php?job_id=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-success" style="border-radius: 8px; font-weight: 500;">
                                <i class="bi bi-credit-card me-1"></i>Pay Now
                            </a>
                        <?php endif; ?>

                        <?php if($s == 'COMPLETED' && (!isset($row['payment_status']) || $row['payment_status'] != 'PAID')): ?>
                            <a href="payment.php?job_id=<?php echo $row['job_id']; ?>" class="btn btn-sm btn-warning" style="border-radius: 8px; font-weight: 500;">
                                <i class="bi bi-credit-card me-1"></i>Make Payment
                            </a>
                        <?php endif; ?>

                        <?php if(isset($row['payment_status']) && $row['payment_status'] == 'PAID'): ?>
                            <span class="badge bg-success" style="border-radius: 6px; padding: 6px 12px;">
                                <i class="bi bi-check-circle me-1"></i>Paid - Rs <?php echo number_format($row['paid_amount'], 0); ?>
                            </span>
                        <?php endif; ?>

                        <?php if($s == 'COMPLETED' && isset($row['payment_status']) && $row['payment_status'] == 'PAID'): ?>
                            <a href="feedback.php?job_id=<?php echo $row['job_id']; ?>&worker_id=<?php echo $row['worker_id']; ?>" class="btn btn-sm btn-outline-warning" style="border-radius: 8px; font-weight: 500;">
                                <i class="bi bi-star me-1"></i>Feedback
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    </div><!-- /qw-content -->
</div><!-- /qw-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
