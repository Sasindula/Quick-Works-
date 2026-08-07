<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hire Companies - QuickWorks</title>
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
        .card-box { background: white; padding: 40px 30px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: 0.3s; height: 100%; border: 1px solid #eee;}
        .card-box:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); border-color: #0d6efd; }
        .card-box img { width: 90px; height: 90px; margin-bottom: 25px; object-fit: contain; }
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
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link active" href="companies.php">Hire Company</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-custom" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- PAGE HEADER -->
    <header class="page-header">
        <div class="container">
            <h1 class="fw-bold display-5">Popular Companies</h1>
            <p class="lead mt-3">Hire established companies for your large scale and professional projects</p>
        </div>
    </header>

    <!-- COMPANIES CONTENT -->
    <section class="py-5 flex-grow-1">
        <div class="container">
            <div class="row g-4 justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card-box d-flex flex-column align-items-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/5968/5968885.png" alt="Company Logo">
                        <h4 class="fw-bold mt-2 text-dark">John Keells</h4>
                        <p class="text-muted small mt-2">Premier conglomerate providing versatile services.</p>
                        <a href="#" class="btn btn-outline-primary mt-auto w-100 fw-bold">View Profile</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card-box d-flex flex-column align-items-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/5968/5968672.png" alt="Company Logo">
                        <h4 class="fw-bold mt-2 text-dark">MAS Holdings</h4>
                        <p class="text-muted small mt-2">Specialized in advanced manufacturing and skilled labor.</p>
                        <a href="#" class="btn btn-outline-primary mt-auto w-100 fw-bold">View Profile</a>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="card-box d-flex flex-column align-items-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/5968/5968705.png" alt="Company Logo">
                        <h4 class="fw-bold mt-2 text-dark">Hayleys Group</h4>
                        <p class="text-muted small mt-2">Agricultural and diverse sector experts.</p>
                        <a href="#" class="btn btn-outline-primary mt-auto w-100 fw-bold">View Profile</a>
                    </div>
                </div>
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
