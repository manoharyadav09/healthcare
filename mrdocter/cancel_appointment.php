<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $user_id = $_SESSION['id'];
    
    $stmt = $GLOBALS['conn']->prepare(
        "UPDATE appointments SET status = 'Cancelled' 
        WHERE id = ? AND user_id = ? AND status != 'Cancelled'"
    );
    $stmt->bind_param("ii", $appointment_id, $user_id);
    $stmt->execute();
    
    header("location: health_records.php");
    exit;
}
?>