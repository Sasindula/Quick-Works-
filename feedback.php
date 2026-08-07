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
$msg = "";

/* FETCH WORKERS HIRED BY THIS CUSTOMER FOR COMPLETED JOBS */
$hired_workers = $conn->query("
    SELECT DISTINCT jr.worker_id, u.name, jr.job_id
    FROM job_requests jr
    JOIN users u ON jr.worker_id = u.user_id
    WHERE jr.customer_id = $user_id AND jr.status = 'COMPLETED'
    ORDER BY jr.job_id DESC
");

/* SUBMIT FEEDBACK */
if(isset($_POST['submit'])){
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    // Get from dropdown selection if hidden fields are empty
    $job_and_worker = explode('|', $_POST['job_worker_select'] ?? '');
    
    $job_id = (isset($_POST['job_id']) && $_POST['job_id'] !== "") ? intval($_POST['job_id']) : ($job_and_worker[0] ?? null);
    $worker_id = (isset($_POST['worker_id']) && $_POST['worker_id'] !== "") ? intval($_POST['worker_id']) : ($job_and_worker[1] ?? null);

    $stmt = $conn->prepare("
        INSERT INTO feedback (job_id, customer_id, worker_id, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiis", $job_id, $user_id, $worker_id, $rating, $comment);
    
    if($stmt->execute()){
        $msg = "success";
        
        // Update worker rating average
        if($worker_id){
            $conn->query("
                UPDATE worker_profiles SET rating = (
                    SELECT AVG(rating) FROM feedback WHERE worker_id = $worker_id
                ) WHERE worker_id = $worker_id
            ");
        }
    }
}

/* GET FEEDBACK HISTORY */
$history = $conn->query("
    SELECT f.*, u.name as worker_name 
    FROM feedback f
    LEFT JOIN users u ON f.worker_id = u.user_id
    WHERE f.customer_id = $user_id
    ORDER BY f.created_at DESC
");

$current_page = 'feedback';
$page_title = 'Feedback';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - QuickWorks</title>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<?php if($msg == "success"): ?>
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Thank you! Your feedback has been submitted.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Submit Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-star-fill me-2 text-warning"></i>Submit Feedback</h5>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-medium">Select Job & Worker</label>
                        <?php if(isset($_GET['worker_id']) && isset($_GET['job_id'])): ?>
                            <!-- Fixed Selection from My Bookings -->
                            <?php 
                                $w_id = intval($_GET['worker_id']);
                                $j_id = intval($_GET['job_id']);
                                $w_res = $conn->query("SELECT name FROM users WHERE user_id = $w_id")->fetch_assoc();
                            ?>
                            <input type="text" class="form-control" value="Job #<?php echo $j_id; ?> - <?php echo htmlspecialchars($w_res['name']); ?>" disabled style="border-radius: 10px; background: #f8fafc;">
                            <input type="hidden" name="job_id" value="<?php echo $j_id; ?>">
                            <input type="hidden" name="worker_id" value="<?php echo $w_id; ?>">
                        <?php else: ?>
                            <!-- Dropdown for manual selection -->
                            <select name="job_worker_select" class="form-select" style="border-radius: 10px;" required>
                                <option value="">Choose a completed job...</option>
                                <?php while($hw = $hired_workers->fetch_assoc()): ?>
                                    <option value="<?php echo $hw['job_id'].'|'.$hw['worker_id']; ?>">
                                        Job #<?php echo $hw['job_id']; ?> - <?php echo htmlspecialchars($hw['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Your Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user']['name']); ?>" disabled style="border-radius: 10px; background: #f8fafc;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Rating</label>
                        <div class="d-flex gap-2" id="starRating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <span class="star-btn" data-val="<?php echo $i; ?>" style="font-size: 1.8rem; cursor: pointer; color: #e2e8f0; transition: all 0.2s;">★</span>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="5" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-medium">Comment</label>
                        <textarea name="comment" class="form-control" rows="4" required
                            placeholder="Share your experience..." style="border-radius: 10px;"></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary w-100" style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-send me-2"></i>Submit Feedback
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- History -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-clock-history me-2 text-info"></i>My Feedback History</h5>

                <?php if($history->num_rows == 0): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-dots" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                        <p class="text-muted mt-2">No feedback submitted yet</p>
                    </div>
                <?php else: ?>
                    <?php while($f = $history->fetch_assoc()): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-medium"><?php echo htmlspecialchars($f['worker_name'] ?? 'General'); ?></span>
                            <span style="color: #f59e0b;">
                                <?php for($i = 0; $i < $f['rating']; $i++) echo '★'; ?>
                                <?php for($i = $f['rating']; $i < 5; $i++) echo '<span style="color:#e2e8f0;">★</span>'; ?>
                            </span>
                        </div>
                        <p class="text-muted mb-1" style="font-size: 0.9rem;"><?php echo htmlspecialchars($f['comment']); ?></p>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($f['created_at'])); ?></small>
                    </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

    </div><!-- /qw-content -->
</div><!-- /qw-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Star rating
const stars = document.querySelectorAll('.star-btn');
const ratingInput = document.getElementById('ratingInput');
stars.forEach(star => {
    star.addEventListener('click', () => {
        const val = parseInt(star.dataset.val);
        ratingInput.value = val;
        stars.forEach((s, i) => {
            s.style.color = (i < val) ? '#f59e0b' : '#e2e8f0';
        });
    });
    star.addEventListener('mouseenter', () => {
        const val = parseInt(star.dataset.val);
        stars.forEach((s, i) => {
            s.style.color = (i < val) ? '#fbbf24' : '#e2e8f0';
        });
    });
});
document.getElementById('starRating').addEventListener('mouseleave', () => {
    const val = parseInt(ratingInput.value);
    stars.forEach((s, i) => {
        s.style.color = (i < val) ? '#f59e0b' : '#e2e8f0';
    });
});
// Init stars
stars.forEach((s, i) => { s.style.color = (i < 5) ? '#f59e0b' : '#e2e8f0'; });
</script>
</body>
</html>
