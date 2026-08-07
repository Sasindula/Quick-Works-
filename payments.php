<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

/* AUTH CHECK */
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "ADMIN"){
    header("Location: ../login.php");
    exit();
}

/* FETCH BOOKINGS + PAYMENTS */
$result = $conn->query("
    SELECT 
        jr.job_id,
        c.name AS customer_name,
        w.name AS worker_name,
        jr.description,
        jr.job_date,

        p.amount,
        p.payment_method,
        p.payment_status,
        p.payment_date

    FROM job_requests jr

    JOIN users c ON jr.customer_id = c.user_id
    JOIN users w ON jr.worker_id = w.user_id

    LEFT JOIN payments p ON jr.job_id = p.job_id

    ORDER BY jr.job_id DESC
");

$page_title = 'Payment Management';
$current_page = 'payments';
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">Payment Management</h2>
        <p class="text-muted mb-0">Check Paid & Unpaid Bookings across the platform</p>
    </div>
</div>

<div class="card mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-credit-card me-2"></i>Transaction History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Customer</th>
                        <th>Worker</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Paid Date</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?php echo $row['job_id']; ?></span></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['worker_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['job_date'])); ?></td>
                            
                            <td class="fw-bold text-dark">
                                <?php echo $row['amount'] ? "Rs " . number_format($row['amount'], 0) : "<span class='text-muted fw-normal'>N/A</span>"; ?>
                            </td>

                            <td>
                                <?php if($row['payment_method']): ?>
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-shield-check me-1"></i><?php echo htmlspecialchars($row['payment_method']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo $row['payment_date'] ? date('M d, Y H:i', strtotime($row['payment_date'])) : "<span class='text-muted'>-</span>"; ?>
                            </td>

                            <td class="text-end">
                                <?php 
                                    $status = $row['payment_status'] ?? 'UNPAID';
                                    if($status == 'PAID'): 
                                ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-clock-history me-1"></i>Unpaid</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No transactions found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once("includes/footer.php"); ?>
