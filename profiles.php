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

$user_id = $_SESSION['user']['user_id'];
$worker_name = $_SESSION['user']['name'];

/* USER */
$userStmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

/* WORKER PROFILE */
$workerStmt = $conn->prepare("SELECT * FROM worker_profiles WHERE worker_id=?");
$workerStmt->bind_param("i", $user_id);
$workerStmt->execute();
$worker = $workerStmt->get_result()->fetch_assoc();

/* GET FEEDBACK FOR THIS WORKER */
$feedback = $conn->query("
    SELECT f.*, u.name AS customer_name
    FROM feedback f
    LEFT JOIN users u ON f.customer_id = u.user_id
    WHERE f.worker_id = $user_id
    ORDER BY f.created_at DESC
    LIMIT 10
");
$feedback_count = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE worker_id = $user_id")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - QuickWorks Worker</title>
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

        .profile-banner {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 16px;
            padding: 32px 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .profile-banner::after {
            content: '';
            position: absolute; top: -40px; right: -20px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        .profile-avatar {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.4);
            object-fit: cover;
        }
        .profile-avatar-placeholder {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; font-weight: 700;
            border: 3px solid rgba(255,255,255,0.4);
        }
        .info-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 6px 14px; border-radius: 8px;
            font-size: 0.85rem; font-weight: 500;
            background: rgba(255,255,255,0.15);
            margin: 3px;
        }
        .star-display { color: #f59e0b; }
        .star-empty { color: #e2e8f0; }
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
        <li><a href="worker_complaints.php"><i class="bi bi-exclamation-triangle"></i><span>Complaints</span></a></li>
        <li><a href="profiles.php" class="active"><i class="bi bi-person-fill"></i><span>Profile</span></a></li>
        <li style="position: absolute; bottom: 16px; width: calc(100% - 24px);">
            <a href="../logout.php" style="color: #f87171;"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
        </li>
    </ul>
</div>

<!-- MAIN -->
<div class="w-main">
    <div class="w-topbar">
        <h5 class="fw-bold mb-0"><i class="bi bi-person-fill me-2"></i>My Profile</h5>
        <div class="d-flex align-items-center gap-3">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600;">
                <?php echo strtoupper(substr($worker_name, 0, 1)); ?>
            </div>
            <span class="fw-medium"><?php echo htmlspecialchars($worker_name); ?></span>
        </div>
    </div>

    <div class="w-content">

        <!-- SUCCESS MESSAGE -->
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Banner -->
        <div class="profile-banner">
            <div class="d-flex align-items-center gap-4">
                <?php if($user['profile_image']): ?>
                    <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>" class="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h2>
                    <div class="d-flex flex-wrap">
                        <span class="info-badge"><i class="bi bi-envelope"></i><?php echo htmlspecialchars($user['email']); ?></span>
                        <span class="info-badge"><i class="bi bi-phone"></i><?php echo htmlspecialchars($user['phone']); ?></span>
                        <span class="info-badge"><i class="bi bi-shield-check"></i><?php echo $user['status']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Info & Update -->
            <div class="col-lg-7">

                <!-- Professional Info -->
                <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-briefcase me-2 text-primary"></i>Professional Details</h5>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3" style="background: #f8fafc; border-radius: 10px;">
                                    <small class="text-muted d-block">Skills</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($worker['skills'] ?? 'Not Set'); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3" style="background: #f8fafc; border-radius: 10px;">
                                    <small class="text-muted d-block">Experience</small>
                                    <span class="fw-medium"><?php echo $worker['experience'] ?? 0; ?> Years</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3" style="background: #f8fafc; border-radius: 10px;">
                                    <small class="text-muted d-block">Hourly Rate</small>
                                    <span class="fw-medium text-success">Rs <?php echo number_format($worker['hourly_rate'] ?? 0, 0); ?></span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3" style="background: #f8fafc; border-radius: 10px;">
                                    <small class="text-muted d-block">Rating</small>
                                    <span class="fw-medium">
                                        <?php 
                                        $rating = $worker['rating'] ?? 0;
                                        for($i = 1; $i <= 5; $i++){
                                            echo $i <= round($rating) ? '<span class="star-display">★</span>' : '<span class="star-empty">★</span>';
                                        }
                                        echo ' ' . number_format($rating, 1);
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3" style="background: #f8fafc; border-radius: 10px;">
                                    <small class="text-muted d-block">Address</small>
                                    <span class="fw-medium"><?php echo htmlspecialchars($user['address'] ?? 'Not Set'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Update Form -->
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <h5 class="fw-bold mb-3"><i class="bi bi-pencil-square me-2 text-warning"></i>Update Profile</h5>
                        <form method="POST" action="update_profiles.php">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Skills</label>
                                    <input type="text" name="skills" class="form-control" 
                                        value="<?php echo htmlspecialchars($worker['skills'] ?? ''); ?>"
                                        placeholder="e.g. Plumbing, Electrical"
                                        style="border-radius: 10px; border-color: #e2e8f0;">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Address</label>
                                    <input type="text" name="address" class="form-control"
                                        value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                                        placeholder="Your address"
                                        style="border-radius: 10px; border-color: #e2e8f0;">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Experience (Years)</label>
                                    <input type="number" name="experience" class="form-control"
                                        value="<?php echo $worker['experience'] ?? 0; ?>" min="0"
                                        style="border-radius: 10px; border-color: #e2e8f0;">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-medium">Hourly Rate (Rs)</label>
                                    <input type="number" name="hourly_rate" class="form-control"
                                        value="<?php echo $worker['hourly_rate'] ?? 0; ?>" min="0" step="50"
                                        style="border-radius: 10px; border-color: #e2e8f0;">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary w-100" style="border-radius: 10px; font-weight: 600; padding: 12px;">
                                        <i class="bi bi-save me-2"></i>Save Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right: Feedback -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm" style="border-radius: 14px;">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-star-fill me-2 text-warning"></i>Customer Feedback</h5>
                            <span class="badge bg-primary" style="border-radius: 6px;"><?php echo $feedback_count; ?> reviews</span>
                        </div>

                        <?php if($feedback->num_rows == 0): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-chat-dots" style="font-size: 2.5rem; color: #cbd5e1;"></i>
                                <p class="text-muted mt-2">No feedback received yet</p>
                            </div>
                        <?php else: ?>
                            <?php while($f = $feedback->fetch_assoc()): ?>
                            <div class="pb-3 mb-3" style="border-bottom: 1px solid #f1f5f9;">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <div style="width: 30px; height: 30px; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.7rem;">
                                            <?php echo strtoupper(substr($f['customer_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <span class="fw-medium" style="font-size: 0.9rem;"><?php echo htmlspecialchars($f['customer_name'] ?? 'Unknown'); ?></span>
                                    </div>
                                    <span style="color: #f59e0b; font-size: 0.85rem;">
                                        <?php for($i = 0; $i < $f['rating']; $i++) echo '★'; ?>
                                        <?php for($i = $f['rating']; $i < 5; $i++) echo '<span style="color:#e2e8f0;">★</span>'; ?>
                                    </span>
                                </div>
                                <p class="text-muted mb-1" style="font-size: 0.85rem;"><?php echo htmlspecialchars($f['comment']); ?></p>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($f['created_at'])); ?></small>
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
