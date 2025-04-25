<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Create current_medications table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS current_medications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        med_name VARCHAR(100),
        dosage VARCHAR(50),
        frequency VARCHAR(50),
        instructions TEXT,
        start_date DATE DEFAULT CURRENT_DATE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['med_name']) || !isset($data['dosage']) || !isset($data['frequency'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid medication data.']);
    exit;
}

$user_id = $_SESSION['id'];
$med_name = $data['med_name'];
$dosage = $data['dosage'];
$frequency = $data['frequency'];
$instructions = $data['instructions'] ?? '';

$stmt = $conn->prepare(
    "INSERT INTO current_medications (user_id, med_name, dosage, frequency, instructions) VALUES (?, ?, ?, ?, ?)"
);
if ($stmt) {
    $stmt->bind_param("issss", $user_id, $med_name, $dosage, $frequency, $instructions);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'med_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>