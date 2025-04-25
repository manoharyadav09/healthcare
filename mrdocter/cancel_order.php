<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $user_id = $_SESSION['id'];
    
    $stmt = $GLOBALS['conn']->prepare(
        "UPDATE medication_orders SET status = 'Cancelled' 
        WHERE id = ? AND user_id = ? AND status != 'Cancelled'"
    );
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    
    header("location: health_records.php");
    exit;
}
?>