<?php
session_start();
require_once("../config/Database.php");

// Auth check
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != "ADMIN"){
    header("HTTP/1.1 403 Forbidden");
    exit("Access Denied");
}

if(!isset($_GET['worker_id']) || !isset($_GET['doc'])){
    header("HTTP/1.1 400 Bad Request");
    exit("Missing parameters");
}

$worker_id = intval($_GET['worker_id']);
$doc = $_GET['doc'];

$allowed_docs = ['identity_file', 'police_cert'];
if(!in_array($doc, $allowed_docs)){
    header("HTTP/1.1 400 Bad Request");
    exit("Invalid document type");
}

$name_col = $doc . "_name";

$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT `$doc`, `$name_col` FROM worker_profiles WHERE worker_id = ?");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($fileData, $fileName);

if($stmt->fetch() && !empty($fileData)){
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Determine content type
    $contentType = 'application/octet-stream';
    if(in_array($ext, ['jpg', 'jpeg'])) $contentType = 'image/jpeg';
    elseif($ext == 'png') $contentType = 'image/png';
    elseif($ext == 'pdf') $contentType = 'application/pdf';
    
    header("Content-Type: $contentType");
    header("Content-Disposition: inline; filename=\"" . htmlspecialchars($fileName) . "\"");
    echo $fileData;
} else {
    echo "Document not found or empty.";
}
?>
