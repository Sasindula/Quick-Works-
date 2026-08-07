<?php
session_start();
require_once("config/Database.php");

$db = new Database();
$conn = $db->connect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jobs - QuickWorks</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .navbar-custom { background-color: #111827 !important; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-custom .navbar-brand { color: #38bdf8 !important; font-weight: 700; font-size: 1.8rem; }
        .navbar-custom .nav-link { color: #e5e7eb !important; font-weight: 500; margin: 0 15px; transition: color 0.3s ease; font-size: 1.1rem;}
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active { color: #38bdf8 !important; }
        .btn-custom { background-color: #38bdf8; color: #111827; font-weight: 600; padding: 10px 25px; border-radius: 8px; transition: all 0.3s ease; }
        .btn-custom:hover { background-color: #0ea5e9; color: white; }
        .page-header { background: linear-gradient(135deg, rgba(13, 110, 253, 0.9), rgba(11, 94, 215, 0.8)); color: white; padding: 80px 0; text-align: center; }
        .card-box { background: white; padding: 30px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; border: 1px solid #eee;}
        .card-box:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .card-box img { width: 70px; margin-bottom: 20px; }
        .footer { background-color: #111827; color: white; padding: 30px 0; margin-top: auto; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">QuickWorks</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="companies.php">Hire Company</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-custom" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <h1 class="fw-bold display-5">Popular Job Categories</h1>
            <p class="lead mt-3">Find the right professional for your specific needs</p>
        </div>
    </header>

    <!-- JOBS CONTENT -->
    <section class="py-5 flex-grow-1">
        <div class="container">
            <div class="row g-4">
                <?php
                if($conn) {
                    $result = $conn->query("SELECT * FROM services");
                    if($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()):
                ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card-box d-flex flex-column">
                        <div class="mb-auto">
                            <img src="https://cdn-icons-png.flaticon.com/512/3063/3063822.png" alt="Service Icon">
                            <h4 class="fw-bold mt-2 text-dark"><?php echo htmlspecialchars($row['service_name']); ?></h4>
                            <p class="text-muted small mt-3"><?php echo htmlspecialchars($row['description']); ?></p>
                        </div>
                        <a href="search.php?service=<?php echo urlencode($row['service_name']); ?>" class="btn btn-outline-primary w-100 mt-4 fw-bold">View Workers</a>
                    </div>
                </div>
                <?php 
                        endwhile; 
                    } else {
                        echo "<div class='col-12 text-center'><p class='text-muted'>No job categories found.</p></div>";
                    }
                } else {
                    echo "<div class='col-12 text-center'><p class='text-muted'>Database connection error.</p></div>";
                }
                ?>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer text-center">
        <div class="container">
            <p class="mb-0 text-gray-400">&copy; <?php echo date('Y'); ?> QuickWorks. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
