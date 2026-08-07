<?php
// We assume session_start() and auth check happen before including this.
$page_title = $page_title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - QuickWorks</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            overflow-x: hidden;
        }
        /* Sidebar Styling */
        .admin-sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #ffffff;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding-top: 20px;
            z-index: 1000;
        }
        .admin-sidebar .brand {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            text-align: center;
            margin-bottom: 30px;
            text-decoration: none;
            display: block;
        }
        .admin-sidebar .nav-link {
            color: #64748b;
            padding: 12px 24px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .admin-sidebar .nav-link:hover {
            color: #0ea5e9;
            background: #f0f9ff;
        }
        .admin-sidebar .nav-link.active {
            color: #0ea5e9;
            background: #f0f9ff;
            border-left-color: #0ea5e9;
            font-weight: 600;
        }
        .admin-sidebar .nav-link i {
            font-size: 1.2rem;
        }
        
        /* Main Content */
        .admin-main {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }
        
        /* Modern Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }
        
        /* Modern Tables */
        .table {
            vertical-align: middle;
            margin-bottom: 0;
        }
        .table thead th {
            border-bottom-width: 1px;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            color: #64748b;
            background-color: #f8fafc;
            padding: 16px;
        }
        .table tbody td {
            padding: 16px;
            color: #475569;
            border-bottom: 1px solid #f1f5f9;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 8px 16px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        /* Badges */
        .badge {
            padding: 6px 10px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
