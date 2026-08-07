<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickWorks - Find Local Workers Instantly</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .hero-section {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.9), rgba(11, 94, 215, 0.8)), url('https://images.unsplash.com/photo-1521737604893-d14cc237f11d') no-repeat center center/cover;
            color: white;
            padding: 120px 0;
            text-align: center;
        }
        .hero-section h1 { font-weight: 700; font-size: 3.5rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.2); }
        .hero-section p { font-size: 1.25rem; margin-bottom: 40px; font-weight: 300; max-width: 800px; margin-left: auto; margin-right: auto; }
        .purpose-section { padding: 100px 0; background: #fff; }
        .purpose-box { padding: 40px; border-radius: 16px; transition: all 0.3s ease; height: 100%; background: #f8f9fa; border: 1px solid #e9ecef; }
        .purpose-box:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.04); background: #fff; border-color: #dee2e6; }
        .navbar-custom { background-color: #111827 !important; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-custom .navbar-brand { color: #38bdf8 !important; font-weight: 700; font-size: 1.8rem; }
        .navbar-custom .nav-link { color: #e5e7eb !important; font-weight: 500; margin: 0 15px; transition: color 0.3s ease; font-size: 1.1rem;}
        .navbar-custom .nav-link:hover, .navbar-custom .nav-link.active { color: #38bdf8 !important; }
        .btn-custom { background-color: #38bdf8; color: #111827; font-weight: 600; padding: 10px 25px; border-radius: 8px; transition: all 0.3s ease; }
        .btn-custom:hover { background-color: #0ea5e9; color: white; }
        .footer { background-color: #111827; color: white; padding: 30px 0; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">QuickWorks</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="jobs.php">Jobs</a></li>
                    <li class="nav-item"><a class="nav-link" href="companies.php">Hire Company</a></li>
                    <li class="nav-item ms-lg-3"><a class="btn btn-custom" href="login.php">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <h1>Connecting You with Local Talent</h1>
            <p>From plumbers and electricians to tea pickers and coconut pickers. Find the right local person for your job instantly, or join us to start earning today.</p>
            <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
                <a href="jobs.php" class="btn btn-light btn-lg px-4 py-2 fw-bold text-primary shadow-sm">Find a Worker</a>
                <a href="register.php" class="btn btn-outline-light btn-lg px-4 py-2 fw-bold">Join as Worker</a>
            </div>
        </div>
    </section>

    <!-- PURPOSE SECTION -->
    <section class="purpose-section">
        <div class="container">
            <div class="row mb-5 align-items-end">
                <div class="col-lg-6">
                    <h2 class="fw-bold display-5 mb-3">Our Purpose</h2>
                    <p class="text-muted lead mb-0">Making it easy for customers to hire local workers and empowering workers to find jobs seamlessly in their community.</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="purpose-box text-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-4 px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.9rem;">01</span>
                        <h4 class="fw-bold">Wide Range of Skills</h4>
                        <p class="text-muted mt-3 mb-0" style="line-height: 1.7;">Whether you need a plumber, electrician, tea picker, or coconut picker, our platform has local experts ready to help with any task.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="purpose-box text-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-4 px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.9rem;">02</span>
                        <h4 class="fw-bold">Seamless Connections</h4>
                        <p class="text-muted mt-3 mb-0" style="line-height: 1.7;">We bridge the gap between customers looking for help and skilled workers looking for their next gig, making hiring quick and stress-free.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="purpose-box text-start">
                        <span class="badge bg-primary bg-opacity-10 text-primary mb-4 px-3 py-2 rounded-pill fw-semibold" style="font-size: 0.9rem;">03</span>
                        <h4 class="fw-bold">Empowering Workers</h4>
                        <p class="text-muted mt-3 mb-0" style="line-height: 1.7;">We provide an easy-to-use platform for local workers to showcase their skills, find steady work, and grow their daily income.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION -->
    <section class="py-5" style="background-color: #1f2937; color: white; text-align: center;">
        <div class="container py-4">
            <h3 class="fw-bold mb-3 h2">Ready to get started?</h3>
            <p class="mb-4 lead text-gray-300">Join thousands of users who trust QuickWorks for their daily needs.</p>
            <a href="companies.php" class="btn btn-custom btn-lg me-sm-2 mb-2 mb-sm-0">Hire a Company</a>
            <a href="jobs.php" class="btn btn-outline-light btn-lg mb-2 mb-sm-0">Browse All Jobs</a>
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
