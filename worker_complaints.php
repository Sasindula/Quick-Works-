<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* AUTH */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "WORKER"){
    header("Location: ../login.php");
    exit();
}

$worker_id = $_SESSION['user']['user_id'];
$worker_name = $_SESSION['user']['name'];

/* SUBMIT COMPLAINT */
if(isset($_POST['send'])){
    $message = trim($_POST['message']);

    if(!empty($message)){
        $stmt = $conn->prepare("
            INSERT INTO complaints (user_id, message, status, created_at)
            VALUES (?, ?, 'OPEN', NOW())
        ");
        $stmt->bind_param("is", $worker_id, $message);
        $stmt->execute();

        $_SESSION['success'] = "Complaint submitted successfully!";
        header("Location: worker_complaints.php");
        exit();
    } else {
        $_SESSION['error'] = "Message cannot be empty!";
        header("Location: worker_complaints.php");
        exit();
    }
}

/* UNPAID CUSTOMERS */
$unpaid = $conn->query("
    SELECT 
        jr.job_id,
        u.name AS customer_name,
        jr.description,
        jr.job_date
    FROM job_requests jr
    JOIN users u ON jr.customer_id = u.user_id
    LEFT JOIN payments p ON jr.job_id = p.job_id
    WHERE jr.worker_id = $worker_id
      AND jr.status IN ('ACCEPTED','COMPLETED')
      AND (p.payment_status IS NULL OR p.payment_status != 'PAID')
");

/* MY COMPLAINTS */
$myComplaints = $conn->query("
    SELECT * FROM complaints
    WHERE user_id = $worker_id
    ORDER BY complaint_id DESC
");

/* UNREAD NOTIFICATIONS */
$unread = $conn->query("
    SELECT COUNT(*) as c FROM notifications WHERE user_id=$worker_id AND is_read=0
")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints - QuickWorks Worker</title>
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
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="w-sidebar">
    <div class="brand"><i class="bi bi-wrench-adjustable-circle-fill"></i>Worker Panel</div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a></li>
        <li><a href="jobs.php"><i class="bi bi-list-check"></i><span>Jobs</span></a></li>
        <li><a href="worker_Payment.php"><i class="bi bi-wallet2"></i><span>Payments</span></a></li>
        <li><a href="worker_complaints.php" class="active"><i class="bi bi-exclamation-triangle"></i><span>Complaints</span></a></li>
        <li><a href="profiles.php"><i class="bi bi-person-fill"></i><span>Profile</span></a></li>
        <li style="position: absolute; bottom: 16px; width: calc(100% - 24px);">
            <a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </li>
    </ul>
</div>

<!-- MAIN -->
<div class="w-main">
    <div class="w-topbar">
        <h5 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Complaints</h5>
        <div class="d-flex align-items-center gap-3">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <?php echo strtoupper(substr($worker_name, 0, 1)); ?>
            </div>
            <span class="fw-medium"><?php echo htmlspecialchars($worker_name); ?></span>
        </div>
    </div>

    <div class="w-content">

        <!-- SUCCESS / ERROR ALERTS -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- SUBMIT COMPLAINT FORM -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-send me-2 text-danger"></i>Submit Complaint</h5>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-medium">Complaint Message</label>
                                <textarea name="message" class="form-control" rows="5" required
                                    placeholder="Describe your issue in detail..."
                                    style="border-radius: 10px; border-color: #e2e8f0;"></textarea>
                            </div>
                            <button type="submit" name="send" class="btn btn-danger w-100" style="border-radius: 10px; font-weight: 600; padding: 12px;">
                                <i class="bi bi-send me-2"></i>Submit Complaint
                            </button>
                        </form>
                    </div>
                </div>

                <!-- UNPAID CUSTOMERS -->
                <?php if($unpaid->num_rows > 0): ?>
                <div class="card border-0 shadow-sm mt-4" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-exclamation-diamond me-2 text-warning"></i>Unpaid Jobs</h5>
                        <p class="text-muted mb-3" style="font-size: 0.85rem;">These jobs have not been paid yet.</p>
                        <?php while($row = $unpaid->fetch_assoc()): ?>
                        <div class="d-flex align-items-center justify-content-between p-3 mb-2" style="background: #fffbeb; border-radius: 10px; border: 1px solid #fde68a;">
                            <div>
                                <h6 class="fw-bold mb-0" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['customer_name']); ?></h6>
                                <small class="text-muted">Job #<?php echo $row['job_id']; ?> • <?php echo date('M d, Y', strtotime($row['job_date'])); ?></small>
                            </div>
                            <span class="badge bg-warning text-dark" style="border-radius: 6px;">Unpaid</span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- COMPLAINT HISTORY -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-4"><i class="bi bi-clock-history me-2 text-info"></i>My Complaints</h5>

                        <?php if($myComplaints->num_rows == 0): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #22c55e;"></i>
                                <p class="text-muted mt-2">No complaints submitted</p>
                            </div>
                        <?php else: ?>
                            <?php while($row = $myComplaints->fetch_assoc()):
                                $cs = $row['status'];
                                $cb = match($cs){
                                    'OPEN' => 'bg-warning text-dark',
                                    'IN_PROGRESS' => 'bg-info text-white',
                                    'RESOLVED' => 'bg-success text-white',
                                    default => 'bg-secondary'
                                };
                                $ci = match($cs){
                                    'OPEN' => 'bi-hourglass-split',
                                    'IN_PROGRESS' => 'bi-arrow-repeat',
                                    'RESOLVED' => 'bi-check-circle-fill',
                                    default => 'bi-question-circle'
                                };
                            ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div class="flex-grow-1">
                                        <p class="mb-1" style="font-size: 0.9rem; color: #334155;"><?php echo htmlspecialchars($row['message']); ?></p>
                                        <small class="text-muted"><i class="bi bi-calendar3 me-1"></i><?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?></small>
                                    </div>
                                    <span class="badge <?php echo $cb; ?> ms-3" style="border-radius: 6px; padding: 5px 12px; white-space: nowrap;">
                                        <i class="bi <?php echo $ci; ?> me-1"></i><?php echo $cs; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
