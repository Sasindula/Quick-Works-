<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "ADMIN"){
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'];

// update user
$stmt = $conn->prepare("UPDATE users SET status='ACTIVE' WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

// ⚡ force session refresh marker (important)
$conn->query("UPDATE users SET created_at=created_at WHERE user_id=$id");

$_SESSION['msg'] = "User approved successfully!";
header("Location: dashboard.php");
exit();
?>
