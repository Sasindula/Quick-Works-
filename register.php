<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

$message = "";
$msgType = "";

// Fetch services for the dropdown
$services = [];
$sResult = $conn->query("SELECT * FROM services ORDER BY service_name");
if($sResult) {
    while($s = $sResult->fetch_assoc()) {
        $services[] = $s;
    }
}

if(isset($_POST['register'])) {

    // Step 1 fields
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $repass   = $_POST['repassword'];
    $role     = $_POST['role'];

    // Step 2 common fields
    $nic     = trim($_POST['nic'] ?? '');
    $gender  = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');

    // Validate password match
    if($password !== $repass) {
        $message = "Passwords do not match!";
        $msgType = "error";
    } elseif ($role == 'WORKER' && (!isset($_FILES['identity_file']) || $_FILES['identity_file']['error'] != 0)) {
        $message = "Identity file is required for workers!";
        $msgType = "error";
    } else {

        // Check email already exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if($res->num_rows > 0){
            $message = "Email already exists!";
            $msgType = "error";
        } else {

            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user (basic info only - status defaults to PENDING)
            $stmt = $conn->prepare("
                INSERT INTO users(name, email, phone, password, role)
                VALUES(?,?,?,?,?)
            ");
            $stmt->bind_param("sssss", $name, $email, $phone, $hashed, $role);

            if($stmt->execute()) {
                $userId = $conn->insert_id;

                // Read profile image as binary
                $profileImage = null;
                if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $profileImage = file_get_contents($_FILES['profile_image']['tmp_name']);
                }

                if($role == "WORKER") {
                    // Worker extra fields
                    $hourlyRate  = $_POST['hourly_rate'] ?? 0;
                    $experience  = $_POST['experience'] ?? 0;
                    $otherQual   = trim($_POST['other_qualifications'] ?? '');

                    // Read identity file
                    $identityFile = null;
                    $identityFileName = null;
                    if(isset($_FILES['identity_file']) && $_FILES['identity_file']['error'] == 0) {
                        $identityFile = file_get_contents($_FILES['identity_file']['tmp_name']);
                        $identityFileName = $_FILES['identity_file']['name'];
                    }

                    $policeCert = null;
                    $policeCertName = null;
                    if(isset($_FILES['police_cert']) && $_FILES['police_cert']['error'] == 0) {
                        $policeCert = file_get_contents($_FILES['police_cert']['tmp_name']);
                        $policeCertName = $_FILES['police_cert']['name'];
                    }

                    // Update address and image in users table for worker
                    $uStmt = $conn->prepare("
                        UPDATE users SET nic=?, gender=?, address=?, profile_image=?
                        WHERE user_id=?
                    ");
                    $imagePath = null;
                    if($profileImage) {
                        $uploadDir = "uploads/profiles/";
                        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $imageName = uniqid('profile_') . '.' . $ext;
                        file_put_contents($uploadDir . $imageName, $profileImage);
                        $imagePath = $uploadDir . $imageName;
                    }
                    $uStmt->bind_param("ssssi", $nic, $gender, $address, $imagePath, $userId);
                    $uStmt->execute();

                    // Insert ONLY professional info into worker_profiles
                    $wStmt = $conn->prepare("
                        INSERT INTO worker_profiles(
                            worker_id, skills, hourly_rate, experience,
                            identity_file, identity_file_name,
                            police_cert, police_cert_name,
                            other_qualifications
                        ) VALUES(?,?,?,?,?,?,?,?,?)
                    ");
                    $nullVar = null;
                    $wStmt->bind_param(
                        "isdibsbss",
                        $userId, $_POST['service_type'], $hourlyRate, $experience,
                        $nullVar, $identityFileName,
                        $nullVar, $policeCertName,
                        $otherQual
                    );

                    if($identityFile) $wStmt->send_long_data(4, $identityFile);
                    if($policeCert) $wStmt->send_long_data(6, $policeCert);

                    $wStmt->execute();

                } else {
                    // CUSTOMER - store NIC, gender, address, image in users table
                    $imagePath = null;
                    if($profileImage) {
                        $uploadDir = "uploads/profiles/";
                        if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                        $imageName = uniqid('profile_') . '.' . $ext;
                        file_put_contents($uploadDir . $imageName, $profileImage);
                        $imagePath = $uploadDir . $imageName;
                    }

                    $uStmt = $conn->prepare("
                        UPDATE users SET nic=?, gender=?, address=?, profile_image=?
                        WHERE user_id=?
                    ");
                    $uStmt->bind_param("ssssi", $nic, $gender, $address, $imagePath, $userId);
                    $uStmt->execute();
                }

                $message = "Registration successful! Please wait for admin approval.";
                $msgType = "success";
            } else {
                $message = "Registration failed! Please try again.";
                $msgType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - QuickWorks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css?v=2">
</head>
<body>

<div class="register-page">

    <!-- Left Brand Panel -->
    <div class="brand-panel">
        <div class="brand-content">
            <a href="index.php" class="brand-logo">QuickWorks</a>
            <h2>Join Our Community</h2>
            <p>Create your account and start connecting with local talent or find your next job opportunity today.</p>
            <div class="brand-features">
                <div class="feature-item">
                    <span class="feature-num">01</span>
                    <span>Create your profile</span>
                </div>
                <div class="feature-item">
                    <span class="feature-num">02</span>
                    <span>Get verified by admin</span>
                </div>
                <div class="feature-item">
                    <span class="feature-num">03</span>
                    <span>Start hiring or working</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="form-panel">
        <div class="form-wrapper">

            <h2 class="form-title">Create Account</h2>
            <p class="form-subtitle">Fill in your details to get started</p>

            <?php if($message != ""): ?>
                <div class="alert alert-<?php echo $msgType == 'success' ? 'success' : 'danger'; ?> py-2 px-3 small" role="alert">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step active" id="stepInd1">
                    <span class="step-number">1</span>
                    <span class="step-label">Basic Info</span>
                </div>
                <div class="step-line" id="stepLine"></div>
                <div class="step" id="stepInd2">
                    <span class="step-number">2</span>
                    <span class="step-label">Verification</span>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="registerForm">

                <!-- ========== STEP 1 ========== -->
                <div id="step1" class="form-step active">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="07XXXXXXXX" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Min 6 chars" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Re-enter Password</label>
                            <input type="password" name="repassword" id="repassword" class="form-control" placeholder="Confirm" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">I want to join as</label>
                        <div class="role-selector">
                            <label class="role-option">
                                <input type="radio" name="role" value="CUSTOMER" checked>
                                <div class="role-card">
                                    <strong>Customer</strong>
                                    <small>I want to hire workers</small>
                                </div>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="role" value="WORKER">
                                <div class="role-card">
                                    <strong>Worker</strong>
                                    <small>I want to find work</small>
                                </div>
                            </label>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100 btn-next" onclick="goToStep2()">
                        Continue
                    </button>
                </div>

                <!-- ========== STEP 2 ========== -->
                <div id="step2" class="form-step">

                    <!-- Common fields for both roles -->
                    <div class="mb-3">
                        <label class="form-label">NIC Number</label>
                        <input type="text" name="nic" class="form-control" placeholder="Enter NIC number" required>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="profile_image" class="form-control" accept="image/*" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Enter your full address" required></textarea>
                    </div>

                    <!-- Worker-only fields -->
                    <div id="workerFields" style="display:none;">

                        <hr class="my-3">
                        <p class="text-muted small fw-semibold mb-3">Worker Information</p>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Service Type</label>
                                <select name="service_type" class="form-select">
                                    <option value="">Select service</option>
                                    <?php foreach($services as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s['service_name']); ?>">
                                            <?php echo htmlspecialchars($s['service_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Experience (Years)</label>
                                <input type="number" name="experience" class="form-control" placeholder="e.g. 3" min="0">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Hourly Rate (Rs.)</label>
                            <input type="number" name="hourly_rate" class="form-control" placeholder="e.g. 500" min="0" step="50">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Identity File (NIC Front/Back Image or PDF)</label>
                            <input type="file" name="identity_file" class="form-control" accept="image/*,.pdf">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Police Certificate (PDF)</label>
                            <input type="file" name="police_cert" class="form-control" accept=".pdf">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Other Qualifications <span class="text-muted">(optional)</span></label>
                            <textarea name="other_qualifications" class="form-control" rows="2" placeholder="Any other qualifications or certifications..."></textarea>
                        </div>

                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary flex-fill" onclick="goToStep1()">Back</button>
                        <button type="submit" name="register" class="btn btn-primary flex-fill">Register</button>
                    </div>

                </div>

            </form>

            <p class="text-center mt-4 mb-0 small text-muted">
                Already have an account? <a href="login.php" class="text-primary fw-semibold text-decoration-none">Login here</a>
            </p>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/register.js?v=2"></script>
</body>
</html>
