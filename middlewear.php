<?php
session_start();

// ✅ check if user logged in
function checkAuth() {
    if(!isset($_SESSION['user'])) {
        header("Location: ../login.php");
        exit();
    }
}

// ✅ role-based access control
function checkRole($role) {

    if(!isset($_SESSION['user'])) {
        header("Location: ../login.php");
        exit();
    }

    if($_SESSION['user']['role'] != $role) {
        echo "Access Denied!";
        exit();
    }
}
?>
