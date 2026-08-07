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

/* GET USER + PROFILE DATA */
$stmt = $conn->prepare("
    SELECT u.name, u.email, u.phone, u.status, u.role, u.profile_image, u.address
    FROM users u
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$name = $data['name'];
$email = $data['email'];
$phone = $data['phone'];
$status = $data['status'] ?? 'ACTIVE';
$role = $data['role'] ?? 'CUSTOMER';
$profile_image = $data['profile_image'] ?? "";
$address = $data['address'] ?? "";

$current_page = 'profile';
$page_title = 'My Profile';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - QuickWorks</title>
    <!-- Add extra modern styles specifically for the profile page -->
    <style>
        .profile-header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            padding: 40px;
            color: #fff;
            position: relative;
            overflow: hidden;
            margin-bottom: 24px;
        }
        .profile-header::after {
            content: "";
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(59,130,246,0.3) 0%, rgba(0,0,0,0) 70%);
            border-radius: 50%;
        }
        .avatar-lg {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            border: 4px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }
        .detail-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .info-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 500;
        }
        .form-control:disabled {
            background-color: #f8fafc;
            opacity: 1;
            border-color: #e2e8f0;
            color: #64748b;
        }
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
    </style>
</head>
<body>

<?php require_once("includes/customer_navbar.php"); ?>

<?php if(isset($_GET['msg']) && $_GET['msg'] == "success"): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius: 12px; border-left: 5px solid #10b981;" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-3" style="font-size: 1.5rem; color: #10b981;"></i>
            <div>
                <strong>Success!</strong><br>
                Your profile has been updated successfully.
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Modern Profile UI -->
<div class="container-fluid px-0">
    <!-- Top Header Banner -->
    <div class="profile-header d-flex flex-column flex-md-row align-items-center gap-4">
        <div class="avatar-lg" <?php if($profile_image) echo 'style="background: none; border: none; box-shadow: none;"'; ?>>
            <?php if($profile_image && file_exists($profile_image)): ?>
                <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile Image" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255, 255, 255, 0.5); box-shadow: 0 8px 16px rgba(0,0,0,0.2);">
            <?php else: ?>
                <?php echo strtoupper(substr($name, 0, 1)); ?>
            <?php endif; ?>
        </div>
        <div class="text-center text-md-start z-1">
            <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($name); ?></h2>
            <p class="mb-2" style="color: #cbd5e1; font-size: 1.1rem;">
                <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($email); ?>
            </p>
            <div class="d-flex align-items-center gap-3 justify-content-center justify-content-md-start mt-3">
                <span class="badge-status">
                    <i class="bi bi-shield-check me-1"></i> <?php echo htmlspecialchars($status); ?> ACCOUNT
                </span>
                <span class="badge bg-primary" style="padding: 6px 12px; border-radius: 20px;">
                    <i class="bi bi-person-badge me-1"></i> <?php echo htmlspecialchars($role); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: User Details Overview -->
        <div class="col-lg-5">
            <div class="card detail-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-3">
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i>Personal Information
                    </h5>
                    
                    <div class="mb-4 d-flex align-items-center gap-3">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #3b82f6; font-size: 1.2rem;">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($name); ?></div>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-3">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: #fef2f2; display: flex; align-items: center; justify-content: center; color: #ef4444; font-size: 1.2rem;">
                            <i class="bi bi-envelope"></i>
                        </div>
                        <div>
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($email); ?></div>
                        </div>
                    </div>

                    <div class="mb-4 d-flex align-items-center gap-3">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: #ecfdf5; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 1.2rem;">
                            <i class="bi bi-telephone"></i>
                        </div>
                        <div>
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?php echo htmlspecialchars($phone); ?></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 45px; height: 45px; border-radius: 12px; background: #f5f3ff; display: flex; align-items: center; justify-content: center; color: #8b5cf6; font-size: 1.2rem;">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <div class="info-label">Current Address</div>
                            <div class="info-value"><?php echo $address ? htmlspecialchars($address) : '<span class="text-muted fst-italic">Not specified</span>'; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Edit Profile Form -->
        <div class="col-lg-7">
            <div class="card detail-card h-100">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4 border-bottom pb-3">
                        <i class="bi bi-pencil-square me-2 text-primary"></i>Edit Address Settings
                    </h5>

                    <form action="update_profile.php" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Full Name</label>
                                <input type="text" class="form-control form-control-lg" value="<?php echo htmlspecialchars($name); ?>" disabled style="border-radius: 10px;">
                                <div class="form-text">Name changes are restricted for security.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium text-secondary">Email Address</label>
                                <input type="email" class="form-control form-control-lg" value="<?php echo htmlspecialchars($email); ?>" disabled style="border-radius: 10px;">
                                <div class="form-text">Contact support to change email.</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium text-secondary">Phone Number</label>
                            <input type="text" class="form-control form-control-lg" value="<?php echo htmlspecialchars($phone); ?>" disabled style="border-radius: 10px;">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium text-dark">Home/Work Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control form-control-lg" rows="3" placeholder="Enter your full street address, city, and zip code..." style="border-radius: 12px; resize: none;"><?php echo htmlspecialchars($address); ?></textarea>
                            <div class="form-text">This address helps us find workers near your location.</div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" style="border-radius: 12px; font-weight: 600;">
                                <i class="bi bi-save2 me-2"></i>Save Address Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    </div><!-- /qw-content -->
</div><!-- /qw-main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add subtle entrance animation
    document.addEventListener("DOMContentLoaded", function() {
        const cards = document.querySelectorAll('.detail-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.5s ease-out';
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 * index);
        });
    });
</script>
</body>
</html>
