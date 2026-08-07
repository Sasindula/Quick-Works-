<?php $current_page = $current_page ?? 'dashboard'; ?>
<div class="admin-sidebar d-flex flex-column">
    <a href="dashboard.php" class="brand">
        Quick<span style="color:#0ea5e9;">Works</span>
    </a>
    
    <div class="flex-grow-1">
        <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="users.php" class="nav-link <?php echo $current_page == 'users' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="workers.php" class="nav-link <?php echo $current_page == 'workers' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i> Workers
        </a>
        <a href="services.php" class="nav-link <?php echo $current_page == 'services' ? 'active' : ''; ?>">
            <i class="bi bi-tags"></i> Services
        </a>
        <a href="bookings.php" class="nav-link <?php echo $current_page == 'bookings' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i> Bookings
        </a>
        <a href="payments.php" class="nav-link <?php echo $current_page == 'payments' ? 'active' : ''; ?>">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a href="complaints.php" class="nav-link <?php echo $current_page == 'complaints' ? 'active' : ''; ?>">
            <i class="bi bi-exclamation-triangle"></i> Complaints
        </a>
        <a href="feedback.php" class="nav-link <?php echo $current_page == 'feedback' ? 'active' : ''; ?>">
            <i class="bi bi-star"></i> Feedback
        </a>
    </div>
    
    <div class="mt-auto mb-4 w-100 px-3">
        <a href="../logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<div class="admin-main">
