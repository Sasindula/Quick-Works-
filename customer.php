<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

/* LOGIN + ROLE CHECK */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "CUSTOMER"){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user']['user_id'];

/* TOTAL BOOKINGS */
$totalBookings = $conn->query("
    SELECT COUNT(*) as total FROM job_requests WHERE customer_id = $user_id
")->fetch_assoc()['total'];

/* ACTIVE JOBS */
$activeJobs = $conn->query("
    SELECT COUNT(*) as total FROM job_requests 
    WHERE customer_id = $user_id AND status IN ('PENDING','ACCEPTED')
")->fetch_assoc()['total'];

/* COMPLETED JOBS */
$completedJobs = $conn->query("
    SELECT COUNT(*) as total FROM job_requests 
    WHERE customer_id = $user_id AND status = 'COMPLETED'
")->fetch_assoc()['total'];

/* PENDING PAYMENTS */
$pendingPayments = $conn->query("
    SELECT COUNT(*) as total FROM job_requests jr
    LEFT JOIN payments p ON jr.job_id = p.job_id
    WHERE jr.customer_id = $user_id AND jr.status = 'COMPLETED' AND p.job_id IS NULL
")->fetch_assoc()['total'];

/* RECENT BOOKINGS */
$recent = $conn->query("
    SELECT jr.*, u.name as worker_name, wp.skills 
    FROM job_requests jr
    JOIN users u ON jr.worker_id = u.user_id
    JOIN worker_profiles wp ON jr.worker_id = wp.worker_id
    WHERE jr.customer_id = $user_id
    ORDER BY jr.created_at DESC LIMIT 5
");

$current_page = 'dashboard';
$page_title = 'Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - QuickWorks</title>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<!-- Welcome Banner -->
<div class="row mb-4">
    <div class="col-12">
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%); border-radius: 16px; padding: 32px 40px; color: #fff; position: relative; overflow: hidden;">
            <div style="position: absolute; top: -40px; right: -20px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
            <div style="position: absolute; bottom: -60px; right: 80px; width: 150px; height: 150px; background: rgba(255,255,255,0.05); border-radius: 50%;"></div>
            <h2 style="font-weight: 700; margin-bottom: 8px;">Welcome back, <?php echo htmlspecialchars($_SESSION['user']['name']); ?>! 👋</h2>
            <p style="opacity: 0.9; margin-bottom: 20px; font-size: 1.05rem;">Find the best skilled workers for your everyday needs.</p>
            <a href="search.php" class="btn btn-light btn-lg" style="font-weight: 600; border-radius: 10px; padding: 10px 28px;">
                <i class="bi bi-search me-2"></i>Find Workers
            </a>
        </div>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 0.85rem;">Total Bookings</p>
                        <h3 class="fw-bold mb-0"><?php echo $totalBookings; ?></h3>
                    </div>
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-calendar-check text-primary" style="font-size: 1.4rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 0.85rem;">Active Jobs</p>
                        <h3 class="fw-bold mb-0"><?php echo $activeJobs; ?></h3>
                    </div>
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-clock-fill text-warning" style="font-size: 1.4rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 0.85rem;">Completed</p>
                        <h3 class="fw-bold mb-0"><?php echo $completedJobs; ?></h3>
                    </div>
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 1.4rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted mb-1" style="font-size: 0.85rem;">Pending Pay</p>
                        <h3 class="fw-bold mb-0"><?php echo $pendingPayments; ?></h3>
                    </div>
                    <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #ede9fe, #ddd6fe); display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-credit-card-fill text-purple" style="font-size: 1.4rem; color: #8b5cf6;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold mb-0">Recent Bookings</h5>
                    <a href="my_bookings.php" class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">View All</a>
                </div>

                <?php if($recent->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <p class="text-muted mt-2">No bookings yet. Start by finding workers!</p>
                    </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead style="background: #f8fafc;">
                            <tr>
                                <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Worker</th>
                                <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Skills</th>
                                <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Date</th>
                                <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600; text-transform: uppercase;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td style="border-color: #f1f5f9;">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.8rem;">
                                            <?php echo strtoupper(substr($row['worker_name'], 0, 1)); ?>
                                        </div>
                                        <span class="fw-medium"><?php echo htmlspecialchars($row['worker_name']); ?></span>
                                    </div>
                                </td>
                                <td style="border-color: #f1f5f9; color: #64748b; font-size: 0.9rem;"><?php echo htmlspecialchars($row['skills']); ?></td>
                                <td style="border-color: #f1f5f9; color: #64748b; font-size: 0.9rem;"><?php echo $row['job_date']; ?></td>
                                <td style="border-color: #f1f5f9;">
                                    <?php
                                    $s = $row['status'];
                                    $badge = match($s){
                                        'PENDING' => 'bg-warning text-dark',
                                        'ACCEPTED' => 'bg-info text-white',
                                        'COMPLETED' => 'bg-success',
                                        'REJECTED' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $badge; ?>" style="border-radius: 6px; font-weight: 500; padding: 5px 12px;"><?php echo $s; ?></span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    </div><!-- /qw-content -->
</div><!-- /qw-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
