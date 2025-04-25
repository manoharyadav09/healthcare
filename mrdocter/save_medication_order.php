<?php
session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $GLOBALS['conn']->prepare(
        "INSERT INTO medication_orders 
        (user_id, order_date, total_amount, items, status) 
        VALUES (?, NOW(), ?, ?, ?)"
    );
    
    $itemsJson = json_encode($data['items']);
    
    $stmt->bind_param(
        "idss", 
        $_SESSION['id'],
        $data['total_amount'],
        $itemsJson,
        $data['status']
    );
    
    $stmt->execute();
    $orderId = $GLOBALS['conn']->insert_id;
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>