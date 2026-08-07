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

/* STATS */
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalCustomers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='CUSTOMER'")->fetch_assoc()['c'];
$totalWorkers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='WORKER'")->fetch_assoc()['c'];
$totalBookings = $conn->query("SELECT COUNT(*) as c FROM job_requests")->fetch_assoc()['c'];
$totalComplaints = $conn->query("SELECT COUNT(*) as c FROM complaints")->fetch_assoc()['c'];
$totalFeedback = $conn->query("SELECT COUNT(*) as c FROM feedback")->fetch_assoc()['c'];

$page_title = 'Admin Dashboard';
$current_page = 'dashboard';
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">Welcome Admin 👋</h2>
        <p class="text-muted mb-0">Manage QuickWorks system easily and efficiently</p>
    </div>
</div>

<div class="row g-4 mb-5">
    <!-- Total Users -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Total Users</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalUsers); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#eff6ff; color:#3b82f6; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Customers -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Customers</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalCustomers); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#f0fdf4; color:#22c55e; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-person-check-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Workers -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Workers</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalWorkers); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#fefce8; color:#eab308; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Bookings</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalBookings); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#f5f3ff; color:#8b5cf6; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Complaints -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Complaints</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalComplaints); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#fef2f2; color:#ef4444; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Feedback -->
    <div class="col-md-4 col-lg-4">
        <div class="card h-100 p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted fw-bold mb-1 text-uppercase" style="font-size:0.8rem; letter-spacing:0.5px;">Feedback</p>
                    <h2 class="fw-bold mb-0 text-dark"><?php echo number_format($totalFeedback); ?></h2>
                </div>
                <div style="width:50px; height:50px; border-radius:12px; background:#fff7ed; color:#f97316; display:flex; align-items:center; justify-content:center; font-size:1.5rem;">
                    <i class="bi bi-star-fill"></i>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once("includes/footer.php"); ?>
