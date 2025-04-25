<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Protect page: redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Create health_records table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS health_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        record_date DATE,
        diagnosis VARCHAR(255),
        doctor_name VARCHAR(100),
        treatment VARCHAR(255),
        prescription VARCHAR(255),
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

// Create health_metrics table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS health_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        heart_rate INT,
        systolic_bp INT,
        diastolic_bp INT,
        weight FLOAT,
        calories INT,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

// Create medication_orders table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS medication_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_date DATE,
        total_amount DECIMAL(10,2),
        items TEXT,
        status VARCHAR(20),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

// Create appointments table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        doctor_name VARCHAR(100),
        specialization VARCHAR(100),
        appointment_date DATE,
        appointment_time TIME,
        reason VARCHAR(255),
        notes TEXT,
        status VARCHAR(20),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

$user_id = $_SESSION['id'];

// Fetch health metrics history for the user
$metrics_history = [];
$sql = "SELECT * FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 10";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics_history[] = $row;
    }
    $stmt->close();
}

// Fetch medication orders
$medication_orders = [];
$sql = "SELECT * FROM medication_orders WHERE user_id = ? ORDER BY order_date DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $medication_orders[] = $row;
    }
    $stmt->close();
}

// Add debug output (remove this in production)
error_log("User ID in health_records.php: " . $user_id);
error_log("Number of medication orders found: " . count($medication_orders));
error_log("Last order date: " . ($medication_orders[0]['order_date'] ?? 'No orders'));
error_log("First order items: " . ($medication_orders[0]['items'] ?? 'No items'));

// Fetch appointments
$appointments = [];
$sql = "SELECT * FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
// Remove the medication orders section from HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Records | HealthHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4ad66d;
            --warning-color: #f8961e;
            --danger-color: #f94144;
            --sidebar-width: 280px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
            color: #333;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding-top: 20px;
            color: white;
            z-index: 1000;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            font-size: 1.5rem;
            font-weight: 600;
            color: white;
            text-decoration: none;
        }

        .sidebar-brand i {
            margin-right: 10px;
            font-size: 1.8rem;
        }

        .sidebar-nav {
            padding: 20px 0;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all var(--transition-speed) ease;
            margin: 5px 15px;
            border-radius: 8px;
        }

        .sidebar-link:hover, .sidebar-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left: 3px solid var(--accent-color);
        }

        .sidebar-link i {
            font-size: 1.1rem;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .header {
            background: white;
            padding: 15px 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .record-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .record-card:hover {
            transform: translateY(-5px);
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .record-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .record-date {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .record-details {
            margin-top: 15px;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .metric-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .metric-value {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .metric-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-medical {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .badge-metrics {
            background-color: rgba(74, 214, 109, 0.1);
            color: var(--success-color);
        }

        .badge-scheduled {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .badge-completed {
            background-color: rgba(74, 214, 109, 0.1);
            color: var(--success-color);
        }

        .badge-cancelled {
            background-color: rgba(249, 65, 68, 0.1);
            color: var(--danger-color);
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(calc(var(--sidebar-width) * -1));
            }
            .main-content {
                margin-left: 0;
            }
        }

        .medication-orders .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .medication-orders .orders-list {
            display: grid;
            gap: 20px;
        }

        .medication-orders .order-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .medication-orders .order-item:hover {
            transform: translateY(-5px);
        }

        .medication-orders .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .medication-orders .order-date {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .medication-orders .order-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .medication-orders .order-status.pending {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
        }

        .medication-orders .order-status.completed {
            background-color: rgba(74, 214, 109, 0.1);
            color: var(--success-color);
        }

        .medication-orders .order-status.cancelled {
            background-color: rgba(249, 65, 68, 0.1);
            color: var(--danger-color);
        }

        .medication-orders .order-details {
            margin-top: 15px;
        }

        .medication-orders .order-items {
            margin-top: 10px;
            padding-left: 20px;
            list-style-type: none;
        }

        .medication-orders .order-items li {
            margin-bottom: 8px;
            padding: 8px 12px;
            background-color: #f8f9fa;
            border-radius: 6px;
        }

        .clear-orders-btn {
            background-color: #dc3545;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .clear-orders-btn:hover {
            background-color: #c82333;
        }

        .no-records {
            text-align: center;
            color: #666;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .no-records a {
            color: #007bff;
            text-decoration: none;
        }

        .no-records a:hover {
            text-decoration: underline;
        }

        .appointments-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .appointments-list .record-card {
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="fas fa-heartbeat"></i>
                <span>HealthHub</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="health_metrics.php" class="sidebar-link">
                <i class="fas fa-heartbeat"></i>
                <span>Health Metrics</span>
            </a>
            <a href="booking.php" class="sidebar-link">
                <i class="fas fa-calendar-alt"></i>
                <span>Appointments</span>
            </a>
            <a href="medication.php" class="sidebar-link">
                <i class="fas fa-pills"></i>
                <span>Medications</span>
            </a>
            <a href="health_records.php" class="sidebar-link active">
                <i class="fas fa-file-medical"></i>
                <span>Health Records</span>
            </a>
            <a href="logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>Health Records</h1>
            <p class="text-muted">View your complete health history and metrics</p>
        </div>

        <div class="container">
            

            <!-- Health Metrics Section -->
            <div class="health-metrics-section mt-5">
                <div class="section-header">
                    <h3>Health Metrics History</h3>
                </div>
                <?php if (!empty($metrics_history)): ?>
                    <div class="metrics-list">
                        <?php foreach ($metrics_history as $metric): ?>
                            <div class="record-card">
                                <div class="record-header">
                                    <h3 class="record-title">Health Metrics</h3>
                                    <span class="record-date"><?php echo date('M d, Y', strtotime($metric['recorded_at'])); ?></span>
                                </div>
                                <div class="record-details">
                                    <div class="metric-grid">
                                        <div class="metric-item">
                                            <div class="metric-value"><?php echo $metric['heart_rate'] ?? '--'; ?></div>
                                            <div class="metric-label">Heart Rate (bpm)</div>
                                        </div>
                                        <div class="metric-item">
                                            <div class="metric-value"><?php echo ($metric['systolic_bp'] ?? '--') . '/' . ($metric['diastolic_bp'] ?? '--'); ?></div>
                                            <div class="metric-label">Blood Pressure</div>
                                        </div>
                                        <div class="metric-item">
                                            <div class="metric-value"><?php echo $metric['weight'] ?? '--'; ?></div>
                                            <div class="metric-label">Weight (kg)</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-records">No health metrics recorded yet</p>
                <?php endif; ?>
            </div>

            <!-- Appointments Section -->
            <div class="appointments-section mt-5">
                <div class="section-header">
                    <h3>Appointments</h3>
                    <a href="booking.php" class="btn btn-primary">Book New Appointment</a>
                </div>
                <?php if (empty($appointments)): ?>
                    <p class="no-records">No appointments found. <a href="booking.php">Book an appointment</a> with one of our specialists.</p>
                <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="record-card">
                                <div class="record-header">
                                    <h3 class="record-title">Appointment with <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                                    <span class="badge badge-<?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                </div>
                                <div class="record-details">
                                    <p><strong>Date:</strong> <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                                    <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></p>
                                    <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?> (<?php echo htmlspecialchars($appointment['specialization']); ?>)</p>
                                    <?php if (!empty($appointment['reason'])): ?>
                                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($appointment['notes'])): ?>
                                        <p><strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($appointment['status'] !== 'Cancelled'): ?>
                                        <form action="cancel_appointment.php" method="post" class="mt-3">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Cancel Appointment</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>