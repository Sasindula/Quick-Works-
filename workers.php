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

/* APPROVE WORKER */
if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);
    $conn->query("UPDATE users SET status='ACTIVE' WHERE user_id=$id AND role='WORKER'");
    header("Location: workers.php");
    exit();
}

/* REJECT WORKER - delete worker_profiles row + user row */
if(isset($_GET['reject'])){
    $id = intval($_GET['reject']);
    $conn->query("DELETE FROM worker_profiles WHERE worker_id=$id");
    $conn->query("DELETE FROM users WHERE user_id=$id AND role='WORKER'");
    header("Location: workers.php");
    exit();
}

/* BLOCK WORKER */
if(isset($_GET['block'])){
    $id = intval($_GET['block']);
    $conn->query("UPDATE users SET status='BLOCKED' WHERE user_id=$id AND role='WORKER'");
    header("Location: workers.php");
    exit();
}

/* UNBLOCK WORKER */
if(isset($_GET['unblock'])){
    $id = intval($_GET['unblock']);
    $conn->query("UPDATE users SET status='ACTIVE' WHERE user_id=$id AND role='WORKER'");
    header("Location: workers.php");
    exit();
}

/* PENDING WORKERS */
$pending = $conn->query("
    SELECT * FROM users 
    WHERE role='WORKER' AND status='PENDING'
");

/* ACTIVE WORKERS */
$active = $conn->query("
    SELECT * FROM users 
    WHERE role='WORKER' AND status!='PENDING'
");

$page_title = 'Workers Management';
$current_page = 'workers';
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">Workers Management</h2>
        <p class="text-muted mb-0">Approve and manage all registered workers</p>
    </div>
</div>

<!-- Pending Workers -->
<div class="card mb-5">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="fw-bold mb-0 text-warning"><i class="bi bi-person-bounding-box me-2"></i>Pending Worker Requests</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($pending->num_rows > 0): ?>
                        <?php while($row = $pending->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?php echo $row['user_id']; ?></span></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><span class="badge bg-warning text-dark border">Pending</span></td>
                            <td class="text-end">
                                <a href="view_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-info text-white">
                                    <i class="bi bi-eye"></i> Details
                                </a>
                                <a href="?approve=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-check-lg"></i>
                                </a>
                                <a href="?reject=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this worker?')">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No pending worker requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Active Workers -->
<div class="card">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="fw-bold mb-0 text-success"><i class="bi bi-person-badge me-2"></i>Active Workers</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($active->num_rows > 0): ?>
                        <?php while($row = $active->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?php echo $row['user_id']; ?></span></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td>
                                <?php if($row['status'] == 'ACTIVE'): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Blocked</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="view_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-info text-white me-1">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if($row['status'] == 'ACTIVE'): ?>
                                    <a href="?block=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-slash-circle"></i> Block
                                    </a>
                                <?php else: ?>
                                    <a href="?unblock=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Unblock
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No active workers.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once("includes/footer.php"); ?>
