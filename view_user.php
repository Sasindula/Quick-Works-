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

if(!isset($_GET['id'])){
    header("Location: users.php");
    exit();
}

$user_id = intval($_GET['id']);

/* GET USER DETAILS */
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    header("Location: users.php");
    exit();
}

/* GET ADDITIONAL WORKER DETAILS IF APPLICABLE */
$worker = null;
if($user['role'] == 'WORKER') {
    $w_stmt = $conn->prepare("SELECT * FROM worker_profiles WHERE worker_id = ?");
    $w_stmt->bind_param("i", $user_id);
    $w_stmt->execute();
    $worker = $w_stmt->get_result()->fetch_assoc();
}

$page_title = 'User Details Verification';
$current_page = strtolower($user['role']) . 's'; // 'users' or 'workers'
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">User Details Verification</h2>
        <p class="text-muted mb-0">Review the details and documents below before making a decision.</p>
    </div>
</div>

<div class="card p-4 mx-auto mb-5" style="max-width: 800px;">
    
    <div class="d-flex align-items-center gap-4 border-bottom pb-4 mb-4">
        <?php if($user['profile_image']): ?>
            <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" class="rounded-circle shadow-sm" style="width:100px; height:100px; object-fit:cover;" alt="Profile">
        <?php else: ?>
            <div class="rounded-circle shadow-sm d-flex align-items-center justify-content-center" style="width:100px; height:100px; background:#f1f5f9; color:#94a3b8; font-size:2rem; font-weight:bold;">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
        <?php endif; ?>
        
        <div>
            <h2 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($user['name']); ?></h2>
            <div class="d-flex gap-2">
                <span class="badge bg-secondary-subtle text-secondary border"><?php echo $user['role']; ?></span>
                <?php if($user['status'] == 'PENDING'): ?>
                    <span class="badge bg-warning text-dark border">Pending</span>
                <?php elseif($user['status'] == 'ACTIVE'): ?>
                    <span class="badge bg-success-subtle text-success border">Active</span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border">Blocked</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-sm-6">
            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Email Address</span>
            <span class="fs-5 text-dark"><?php echo htmlspecialchars($user['email']); ?></span>
        </div>
        <div class="col-sm-6">
            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Phone Number</span>
            <span class="fs-5 text-dark"><?php echo htmlspecialchars($user['phone']); ?></span>
        </div>
        <div class="col-sm-6">
            <span class="text-muted small fw-bold text-uppercase d-block mb-1">NIC Number</span>
            <span class="fs-5 text-dark"><?php echo htmlspecialchars($user['nic'] ?? 'N/A'); ?></span>
        </div>
        <div class="col-sm-6">
            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Gender</span>
            <span class="fs-5 text-dark"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></span>
        </div>
        <div class="col-12">
            <span class="text-muted small fw-bold text-uppercase d-block mb-1">Address</span>
            <span class="fs-5 text-dark"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></span>
        </div>
    </div>

    <?php if($user['role'] == 'WORKER' && $worker): ?>
        <hr class="text-muted my-4">
        
        <h4 class="fw-bold mb-4 text-secondary">Professional Details</h4>
        
        <div class="row g-4 mb-4">

            <div class="col-sm-6">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Experience</span>
                <span class="fs-5 text-dark"><?php echo $worker['experience']; ?> Years</span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Hourly Rate</span>
                <span class="fs-5 text-dark">Rs <?php echo number_format($worker['hourly_rate'], 0); ?></span>
            </div>
            <div class="col-sm-6">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Skills</span>
                <span class="fs-5 text-dark"><?php echo htmlspecialchars($worker['skills'] ?? 'N/A'); ?></span>
            </div>
            <div class="col-12">
                <span class="text-muted small fw-bold text-uppercase d-block mb-1">Other Qualifications</span>
                <span class="fs-5 text-dark"><?php echo htmlspecialchars($worker['other_qualifications'] ?? 'None'); ?></span>
            </div>
        </div>

        <h4 class="fw-bold mb-3 text-secondary">Uploaded Documents</h4>
        
        <div class="d-flex flex-wrap gap-3 mb-4">
            <?php if(!empty($worker['identity_file_name'])): ?>
                <a href="serve_doc.php?worker_id=<?php echo $user_id; ?>&doc=identity_file" target="_blank" class="btn btn-outline-primary shadow-sm rounded-pill px-4">
                    <i class="bi bi-file-earmark-person me-2"></i> View Identity Document
                </a>
            <?php endif; ?>

            <?php if(!empty($worker['police_cert_name'])): ?>
                <a href="serve_doc.php?worker_id=<?php echo $user_id; ?>&doc=police_cert" target="_blank" class="btn btn-outline-primary shadow-sm rounded-pill px-4">
                    <i class="bi bi-shield-check me-2"></i> View Police Cert
                </a>
            <?php endif; ?>
            
            <?php if(empty($worker['identity_file_name']) && empty($worker['police_cert_name'])): ?>
                <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i> No documents uploaded.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <hr class="text-muted my-4">

    <div class="d-flex gap-3">
        <?php $back_url = ($user['role'] == 'WORKER') ? 'workers.php' : 'users.php'; ?>
        
        <a href="<?php echo $back_url; ?>" class="btn btn-secondary px-4">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
        
        <div class="ms-auto d-flex gap-2">
            <?php if($user['status'] == 'PENDING'): ?>
                <a href="<?php echo $back_url; ?>?approve=<?php echo $user_id; ?>" class="btn btn-success px-4">
                    <i class="bi bi-check-circle me-1"></i> Approve
                </a>
                <a href="<?php echo $back_url; ?>?reject=<?php echo $user_id; ?>" class="btn btn-danger px-4" onclick="return confirm('Are you sure you want to reject and delete this user?');">
                    <i class="bi bi-x-circle me-1"></i> Reject
                </a>
            <?php else: ?>
                <?php if($user['status'] == 'ACTIVE'): ?>
                    <a href="<?php echo $back_url; ?>?block=<?php echo $user_id; ?>" class="btn btn-outline-danger px-4">
                        <i class="bi bi-slash-circle me-1"></i> Block
                    </a>
                <?php else: ?>
                    <a href="<?php echo $back_url; ?>?unblock=<?php echo $user_id; ?>" class="btn btn-outline-success px-4">
                        <i class="bi bi-check-circle me-1"></i> Unblock
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require_once("includes/footer.php"); ?>
