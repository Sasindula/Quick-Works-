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

/* SUBMIT COMPLAINT */
if(isset($_POST['submit'])){
    $message = trim($_POST['message']);
    $status = "PENDING";

    $stmt = $conn->prepare("
        INSERT INTO complaints (user_id, message, status)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $user_id, $message, $status);
    
    if($stmt->execute()){
        $msg = "success";
    }
}

/* GET COMPLAINTS */
$result = $conn->prepare("
    SELECT * FROM complaints WHERE user_id = ? ORDER BY created_at DESC
");
$result->bind_param("i", $user_id);
$result->execute();
$complaints = $result->get_result();

$current_page = 'complaints';
$page_title = 'Complaints';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints - QuickWorks</title>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<?php if($msg == "success"): ?>
    <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Complaint submitted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Submit Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i>Submit Complaint</h5>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label fw-medium">Your Complaint</label>
                        <textarea name="message" class="form-control" rows="6" required
                            placeholder="Describe your issue in detail..." style="border-radius: 10px;"></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-danger w-100" style="border-radius: 10px; font-weight: 600;">
                        <i class="bi bi-send me-2"></i>Submit Complaint
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- History -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm" style="border-radius: 14px;">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-list-check me-2 text-primary"></i>My Complaints</h5>

                <?php if($complaints->num_rows == 0): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-check-circle" style="font-size: 2.5rem; color: #22c55e;"></i>
                        <p class="text-muted mt-2">No complaints submitted</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Message</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Status</th>
                                    <th style="border: none; color: #64748b; font-size: 0.8rem; font-weight: 600;">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $complaints->fetch_assoc()): 
                                    $cs = $row['status'];
                                    $cb = match($cs){
                                        'PENDING' => 'bg-warning text-dark',
                                        'RESOLVED' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                ?>
                                <tr>
                                    <td style="border-color: #f1f5f9; max-width: 300px;">
                                        <p class="mb-0" style="font-size: 0.9rem;"><?php echo htmlspecialchars($row['message']); ?></p>
                                    </td>
                                    <td style="border-color: #f1f5f9;">
                                        <span class="badge <?php echo $cb; ?>" style="border-radius: 6px; padding: 5px 12px;"><?php echo $cs; ?></span>
                                    </td>
                                    <td style="border-color: #f1f5f9; color: #64748b; font-size: 0.85rem;">
                                        <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
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
