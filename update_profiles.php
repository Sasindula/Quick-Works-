<?php
session_start();
require_once("../config/Database.php");

$db = new Database();
$conn = $db->connect();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "WORKER"){
    header("Location: ../login.php");
    exit();
}

$worker_id = $_SESSION['user']['user_id'];

if($_POST){

    $skills = $_POST['skills'];
    $address = $_POST['address'];
    $experience = intval($_POST['experience']);
    $hourly_rate = floatval($_POST['hourly_rate']);

    // 1. Update address in users table
    $stmt1 = $conn->prepare("UPDATE users SET address = ? WHERE user_id = ?");
    $stmt1->bind_param("si", $address, $worker_id);
    $stmt1->execute();

    // 2. Update professional info in worker_profiles table
    $check = $conn->prepare("SELECT worker_id FROM worker_profiles WHERE worker_id = ?");
    $check->bind_param("i", $worker_id);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows > 0){
        $stmt2 = $conn->prepare("UPDATE worker_profiles SET skills=?, experience=?, hourly_rate=? WHERE worker_id=?");
        $stmt2->bind_param("sidi", $skills, $experience, $hourly_rate, $worker_id);
        $stmt2->execute();
    } else {
        $stmt2 = $conn->prepare("INSERT INTO worker_profiles (worker_id, skills, experience, hourly_rate) VALUES (?, ?, ?, ?)");
        $stmt2->bind_param("isid", $worker_id, $skills, $experience, $hourly_rate);
        $stmt2->execute();
    }

    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: profiles.php");
    exit();
}
?>
