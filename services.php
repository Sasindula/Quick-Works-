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

$message = "";
$msgType = "";

/* CREATE SERVICE */
if(isset($_POST['add_service'])){
    $name = trim($_POST['service_name']);
    $desc = trim($_POST['description']);
    
    if(!empty($name)){
        $stmt = $conn->prepare("INSERT INTO services (service_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        if($stmt->execute()){
            $message = "Service added successfully!";
            $msgType = "success";
        } else {
            $message = "Error adding service.";
            $msgType = "danger";
        }
    }
}

/* UPDATE SERVICE */
if(isset($_POST['update_service'])){
    $id = intval($_POST['service_id']);
    $name = trim($_POST['service_name']);
    $desc = trim($_POST['description']);
    
    $stmt = $conn->prepare("UPDATE services SET service_name=?, description=? WHERE service_id=?");
    $stmt->bind_param("ssi", $name, $desc, $id);
    if($stmt->execute()){
        $message = "Service updated successfully!";
        $msgType = "success";
    } else {
        $message = "Error updating service.";
        $msgType = "danger";
    }
}

/* DELETE SERVICE */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    // Check if any job request uses this service? 
    // Usually we set foreign keys to ON DELETE SET NULL, so it's safe.
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id=?");
    $stmt->bind_param("i", $id);
    if($stmt->execute()){
        $message = "Service deleted successfully!";
        $msgType = "success";
    } else {
        $message = "Error deleting service.";
        $msgType = "danger";
    }
}

$services = $conn->query("SELECT * FROM services ORDER BY service_name");

$page_title = 'Service Management';
$current_page = 'services';
require_once("includes/header.php");
require_once("includes/sidebar.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
    <div>
        <h2 class="fw-bold mb-1">Service Management</h2>
        <p class="text-muted mb-0">Manage job categories and service types</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
        <i class="bi bi-plus-circle me-1"></i> Add New Service
    </button>
</div>

<?php if($message): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Service Name</th>
                        <th>Description</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($services->num_rows > 0): ?>
                        <?php while($s = $services->fetch_assoc()): ?>
                        <tr>
                            <td><span class="text-muted fw-bold">#<?php echo $s['service_id']; ?></span></td>
                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($s['service_name']); ?></td>
                            <td>
                                <p class="mb-0 text-muted small" style="max-width: 400px;">
                                    <?php echo htmlspecialchars($s['description']); ?>
                                </p>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="editService(<?php echo $s['service_id']; ?>, '<?php echo addslashes($s['service_name']); ?>', '<?php echo addslashes($s['description']); ?>')"
                                        data-bs-toggle="modal" data-bs-target="#editServiceModal">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                                <a href="?delete=<?php echo $s['service_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this service?')">
                                    <i class="bi bi-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-5 text-muted">No services found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Service Modal -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" style="border-radius: 12px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Add New Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">SERVICE NAME</label>
                    <input type="text" name="service_name" class="form-control" required placeholder="e.g. Electrical">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">DESCRIPTION</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Describe this service..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="add_service" class="btn btn-primary">Add Service</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Service Modal -->
<div class="modal fade" id="editServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content" style="border-radius: 12px;">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold">Edit Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="service_id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">SERVICE NAME</label>
                    <input type="text" name="service_name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">DESCRIPTION</label>
                    <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="update_service" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editService(id, name, desc) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_desc').value = desc;
}
</script>

<?php require_once("includes/footer.php"); ?>
