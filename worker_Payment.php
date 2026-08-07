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
$worker_name = $_SESSION['user']['name'];

/* FETCH JOB + PAYMENT */
$result = $conn->query("
    SELECT 
        jr.job_id,
        u.name AS customer_name,
        jr.description,
        jr.job_date,
        jr.duration,
        jr.status AS job_status,
        p.amount,
        p.payment_method,
        p.payment_status,
        p.payment_date
    FROM job_requests jr
    JOIN users u ON jr.customer_id = u.user_id
    LEFT JOIN payments p ON jr.job_id = p.job_id
    WHERE jr.worker_id = $worker_id
    ORDER BY jr.job_id DESC
");

/* TOTAL EARNINGS */
$earnings = $conn->query("
    SELECT IFNULL(SUM(p.amount),0) AS total
    FROM payments p
    JOIN job_requests jr ON p.job_id = jr.job_id
    WHERE jr.worker_id = $worker_id AND p.payment_status = 'PAID'
")->fetch_assoc()['total'];

/* PENDING PAYMENTS */
$pending_pay = $conn->query("
    SELECT COUNT(*) AS c
    FROM job_requests jr
    LEFT JOIN payments p ON jr.job_id = p.job_id
    WHERE jr.worker_id = $worker_id 
      AND jr.status IN ('ACCEPTED','COMPLETED')
      AND (p.payment_status IS NULL OR p.payment_status != 'PAID')
")->fetch_assoc()['c'];

/* PAID COUNT */
$paid_count = $conn->query("
    SELECT COUNT(*) AS c
    FROM payments p
    JOIN job_requests jr ON p.job_id = jr.job_id
    WHERE jr.worker_id = $worker_id AND p.payment_status = 'PAID'
")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - QuickWorks Worker</title>
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

        .pay-card {
            background: #fff; border-radius: 14px;
            border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 12px; transition: all 0.2s;
        }
        .pay-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="w-sidebar">
    <div class="brand"><i class="bi bi-wrench-adjustable-circle-fill"></i>Worker Panel</div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="bi bi-house-door-fill"></i><span>Dashboard</span></a></li>
        <li><a href="jobs.php"><i class="bi bi-list-check"></i><span>Jobs</span></a></li>
        <li><a href="worker_Payment.php" class="active"><i class="bi bi-wallet2"></i><span>Payments</span></a></li>
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
        <h5 class="fw-bold mb-0"><i class="bi bi-wallet2 me-2"></i>Payments</h5>
        <div class="d-flex align-items-center gap-3">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <?php echo strtoupper(substr($worker_name, 0, 1)); ?>
            </div>
            <span class="fw-medium"><?php echo htmlspecialchars($worker_name); ?></span>
        </div>
    </div>

    <div class="w-content">

        <!-- Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Total Earnings</p>
                                <h3 class="fw-bold mb-0">Rs <?php echo number_format($earnings, 0); ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #dcfce7, #bbf7d0); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-cash-stack text-success" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Paid Jobs</p>
                                <h3 class="fw-bold mb-0"><?php echo $paid_count; ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-check-circle-fill text-primary" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;">Pending Payments</p>
                                <h3 class="fw-bold mb-0"><?php echo $pending_pay; ?></h3>
                            </div>
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, #fef3c7, #fde68a); display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-clock-fill text-warning" style="font-size: 1.4rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment List -->
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-receipt me-2 text-primary"></i>Payment History</h5>

                <?php if($result->num_rows == 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem; color: #cbd5e1;"></i>
                        <h5 class="text-muted mt-3">No payments yet</h5>
                        <p class="text-muted">Your payment records will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Job</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Customer</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Date</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Amount</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Method</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $result->fetch_assoc()):
                                    $pay_status = $row['payment_status'] ?? 'UNPAID';
                                    $pay_badge = match($pay_status){
                                        'PAID' => 'bg-success',
                                        'PENDING' => 'bg-warning text-dark',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <tr>
                                    <td style="border-color: #f1f5f9;">
                                        <span class="fw-medium">#<?php echo $row['job_id']; ?></span>
                                    </td>
                                    <td style="border-color: #f1f5f9;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.75rem;">
                                                <?php echo strtoupper(substr($row['customer_name'], 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($row['customer_name']); ?></span>
                                        </div>
                                    </td>
                                    <td style="border-color: #f1f5f9; color: #64748b; font-size: 0.85rem;">
                                        <?php echo date('M d, Y', strtotime($row['job_date'])); ?>
                                    </td>
                                    <td style="border-color: #f1f5f9;">
                                        <?php if($row['amount']): ?>
                                            <span class="fw-bold text-success">Rs <?php echo number_format($row['amount'], 0); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="border-color: #f1f5f9;">
                                        <?php if($row['payment_method']): ?>
                                            <?php if($row['payment_method'] == 'CASH' || $row['payment_method'] == 'cash'): ?>
                                                <span class="badge bg-success-subtle text-success border" style="border-radius: 6px;"><i class="bi bi-cash me-1"></i>Cash</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary border" style="border-radius: 6px;"><i class="bi bi-credit-card me-1"></i><?php echo $row['payment_method']; ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="border-color: #f1f5f9;">
                                        <span class="badge <?php echo $pay_badge; ?>" style="border-radius: 6px; padding: 5px 12px;">
                                            <?php echo $pay_status; ?>
                                        </span>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
