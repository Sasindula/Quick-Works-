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

/* FEEDBACK DATA */
$result = $conn->query("
    SELECT 
        f.feedback_id,
        f.job_id,
        f.comment,
        f.rating,
        f.created_at,
        c.name AS customer_name,
        w.name AS worker_name
    FROM feedback f
    LEFT JOIN users c ON f.customer_id = c.user_id
    LEFT JOIN users w ON f.worker_id = w.user_id
    ORDER BY f.feedback_id DESC
");

$page_title = 'Feedback Management';
$current_page = 'feedback';
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">Feedback Management</h2>
        <p class="text-muted mb-0">View all customer and worker feedback</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-primary text-white p-4">
            <div class="d-flex align-items-center">
                <div>
                    <h6 class="text-white-50 text-uppercase fw-bold mb-1" style="letter-spacing: 0.5px;">Total Feedback</h6>
                    <h2 class="fw-bold mb-0"><?php echo $result->num_rows; ?></h2>
                </div>
                <div class="ms-auto" style="font-size: 2.5rem; opacity: 0.8;">
                    <i class="bi bi-star-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-5">
    <div class="card-header bg-white py-3 border-bottom d-flex align-items-center">
        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-chat-right-quote me-2"></i>Recent Reviews</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Customer</th>
                        <th>Worker</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?php echo $row['job_id']; ?></span></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['customer_name'] ?? 'N/A'); ?></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['worker_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div class="text-warning">
                                    <?php
                                    $rating = (int)$row['rating'];
                                    for($i=1; $i<=5; $i++){
                                        echo $i <= $rating ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star text-muted"></i>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <p class="mb-0 text-wrap" style="max-width: 300px; font-size: 0.95rem; font-style: italic; color: #475569;">
                                    "<?php echo htmlspecialchars($row['comment']); ?>"
                                </p>
                            </td>
                            <td class="text-nowrap text-muted small"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No feedback available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once("includes/footer.php"); ?>
