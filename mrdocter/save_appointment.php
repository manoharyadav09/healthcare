<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Only allow logged-in users
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Create appointments table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        doctor_name VARCHAR(100),
        specialization VARCHAR(100),
        appointment_date DATE,
        appointment_time VARCHAR(20),
        reason VARCHAR(255),
        notes TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$user_id = $_SESSION['id'];
$doctor = $data['doctor'] ?? '';
$date = $data['date'] ?? '';
$time = $data['time'] ?? '';
$reason = $data['reason'] ?? '';
$specialization = '';

// Map doctor to specialization (optional, based on your select options)
$doctor_specializations = [
    'Dr. Sarah Johnson' => 'Cardiologist',
    'Dr. Michael Chen' => 'Neurologist',
    'Dr. Emily Wilson' => 'Pediatrician',
    'Dr. David Kim' => 'Orthopedist'
];
if (isset($doctor_specializations[$doctor])) {
    $specialization = $doctor_specializations[$doctor];
}

// Validate required fields
if (empty($doctor) || empty($date) || empty($time)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
    exit;
}

// Insert appointment
$stmt = $conn->prepare(
    "INSERT INTO appointments (user_id, doctor_name, specialization, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?, ?)"
);
if ($stmt) {
    $stmt->bind_param("isssss", $user_id, $doctor, $specialization, $date, $time, $reason);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
}
?>