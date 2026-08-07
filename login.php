<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();

$error = "";

// ✅ LOGIN PROCESS
if(isset($_POST['login'])) {

    $email = $_POST['email'];
    $password = $_POST['password'];

    // 🔐 DB QUERY
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    // check user exists
    if($result->num_rows == 1) {

        $user = $result->fetch_assoc();

        // ❗ STATUS CHECK
        if($user['status'] != "ACTIVE"){

            $error = "Your account is not approved yet!";

        } else {

            // PASSWORD CHECK
            if(password_verify($password, $user['password'])){

                // SESSION CREATE
                $_SESSION['user'] = [
                    "user_id" => $user['user_id'],
                    "name" => $user['name'],
                    "email" => $user['email'],
                    "role" => $user['role']
                ];

                // ROLE REDIRECT
                if($user['role'] == "ADMIN"){
                    header("Location: admin/dashboard.php");
                }
                elseif($user['role'] == "WORKER"){
                    header("Location: worker/dashboard.php");
                }
                else{
                    header("Location: customer.php");
                }

                exit();

            } else {
                $error = "Wrong password!";
            }
        }

    } else {
        $error = "User not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - QuickWorks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=3">
</head>
<body>

<div class="login-page">

    <!-- Left Brand Panel -->
    <div class="brand-panel">
        <div class="brand-content">
            <a href="index.php" class="brand-logo">QuickWorks</a>
            <h2>Welcome Back</h2>
            <p>Login to your account and continue connecting with skilled local workers or managing your jobs.</p>
            <div class="brand-stats">
                <div class="stat-item">
                    <span class="stat-value">500+</span>
                    <span class="stat-label">Workers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">1000+</span>
                    <span class="stat-label">Jobs Done</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value">4.8</span>
                    <span class="stat-label">Rating</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="form-panel">
        <div class="form-wrapper">

            <h2 class="form-title">Sign In</h2>
            <p class="form-subtitle">Enter your credentials to access your account</p>

            <?php if($error != ""): ?>
                <div class="alert alert-danger py-2 px-3 small" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary w-100">Sign In</button>

            </form>

            <div class="text-center mt-4">
                <p class="mb-2 small text-muted">
                    Don't have an account? <a href="register.php" class="text-primary fw-semibold text-decoration-none">Create one</a>
                </p>
                <a href="index.php" class="btn btn-outline-secondary btn-sm mt-2">← Back to Home</a>
            </div>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/login.js?v=3"></script>
</body>
</html>
