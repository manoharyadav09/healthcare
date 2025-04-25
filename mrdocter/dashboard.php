<?php
session_start();
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Protect page: redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Create users table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'patient',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

// Create health_records table if not exists
$conn->query(
    "CREATE TABLE IF NOT EXISTS health_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        record_date DATE,
        diagnosis VARCHAR(255),
        doctor_name VARCHAR(100),
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )"
);

// Get user info
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];
$role = 'patient'; // You can fetch from DB if needed

// Fetch latest health metrics
$latest_metrics = null;
$sql = "SELECT * FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_metrics = $result->fetch_assoc();
    $stmt->close();
}

// Fetch health metrics history for chart
$metrics_history = [];
$dates = [];
$heart_rates = [];
$weights = [];
$systolic_bp = [];
$sql = "SELECT * FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 10";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $metrics_history[] = $row;
        $dates[] = date('M d', strtotime($row['recorded_at']));
        $heart_rates[] = $row['heart_rate'];
        $weights[] = $row['weight'];
        $systolic_bp[] = $row['systolic_bp'];
    }
    $stmt->close();
}
$dates = array_reverse($dates);
$heart_rates = array_reverse($heart_rates);
$weights = array_reverse($weights);
$systolic_bp = array_reverse($systolic_bp);

// Fetch health records
$health_records = [];
$sql = "SELECT * FROM health_records WHERE user_id = ? ORDER BY record_date DESC LIMIT 10";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $health_records[] = $row;
    }
    $stmt->close();
}

// Fetch next 3 upcoming appointments
$upcoming_appointments = [];
$sql = "SELECT * FROM appointments WHERE user_id = ? AND appointment_date >= CURDATE() ORDER BY appointment_date ASC, appointment_time ASC LIMIT 3";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_appointments[] = $row;
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthHub Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
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
            overflow-x: hidden;
        }

        /* Sidebar Styles */
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
            transform: translateX(0);
            transition: transform var(--transition-speed) ease;
        }

        .sidebar-collapsed {
            transform: translateX(calc(var(--sidebar-width) * -1));
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: center;
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

        .sidebar-link-text {
            transition: opacity var(--transition-speed) ease;
        }

        .sidebar-toggle {
            position: absolute;
            top: 20px;
            right: -15px;
            background: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            z-index: 1001;
            border: none;
            color: var(--primary-color);
        }

        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: margin var(--transition-speed) ease;
            min-height: calc(100vh - var(--header-height));
        }

        .main-content-expanded {
            margin-left: 0;
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 900;
            height: var(--header-height);
        }

        .header-title h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }

        .header-title p {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }

        .header-actions {
            display: flex;
            align-items: center;
        }

        .notification-btn, .profile-btn {
            position: relative;
            background: none;
            border: none;
            margin-left: 15px;
            cursor: pointer;
            color: #6c757d;
            font-size: 1.2rem;
            transition: color var(--transition-speed) ease;
        }

        .notification-btn:hover, .profile-btn:hover {
            color: var(--primary-color);
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.65rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-left: 15px;
            border: 2px solid var(--primary-color);
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
            color: var(--primary-color);
        }

        .card-action {
            color: var(--primary-color);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Stats Cards */
        .stats-card {
            color: white;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
        }

        .stats-card-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        .stats-card-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #2a9d8f 100%);
        }

        .stats-card-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f3722c 100%);
        }

        .stats-card-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #d62828 100%);
        }

        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
            margin-bottom: 15px;
        }

        .stats-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Health Metrics */
        .metric-card {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed) ease;
        }

        .metric-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
            color: white;
        }

        .metric-info {
            flex: 1;
        }

        .metric-title {
            font-weight: 500;
            margin-bottom: 3px;
            color: #333;
        }

        .metric-value {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--primary-color);
        }

        .metric-change {
            font-size: 0.8rem;
            display: flex;
            align-items: center;
        }

        .metric-change.up {
            color: var(--success-color);
        }

        .metric-change.down {
            color: var(--danger-color);
        }

        /* Appointments */
        .appointment-card {
            display: flex;
            padding: 15px;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary-color);
            transition: all var(--transition-speed) ease;
        }

        .appointment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .appointment-time {
            min-width: 80px;
            text-align: center;
            padding-right: 15px;
            border-right: 1px solid #eee;
        }

        .appointment-day {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .appointment-month {
            font-size: 0.8rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .appointment-details {
            padding-left: 15px;
            flex: 1;
        }

        .appointment-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .appointment-doctor {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .appointment-status {
            display: inline-block;
            padding: 3px 8px;
            font-size: 0.75rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .status-confirmed {
            background-color: rgba(74, 214, 109, 0.2);
            color: var(--success-color);
        }

        .status-pending {
            background-color: rgba(248, 150, 30, 0.2);
            color: var(--warning-color);
        }

        /* Medications */
        .medication-card {
            display: flex;
            padding: 15px;
            border-radius: 10px;
            background: white;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all var(--transition-speed) ease;
        }

        .medication-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .medication-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
            color: white;
            background: var(--primary-color);
        }

        .medication-info {
            flex: 1;
        }

        .medication-name {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .medication-dosage {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .medication-time {
            font-size: 0.85rem;
            color: var(--primary-color);
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(calc(var(--sidebar-width) * -1));
            }
            .sidebar-collapsed {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: none;
            }
        }

        /* Profile Dropdown Styles */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }

        .profile-dropdown:hover .dropdown-menu {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            min-width: 200px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 0;
            margin-top: 10px;
            display: none;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .dropdown-header strong {
            display: block;
            color: #333;
            font-size: 0.9rem;
        }

        .dropdown-header span {
            display: block;
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .dropdown-divider {
            height: 1px;
            background: #eee;
            margin: 0;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .dropdown-item:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }

        /* Add a small arrow to the dropdown */
        .dropdown-menu::before {
            content: '';
            position: absolute;
            top: -8px;
            right: 20px;
            width: 16px;
            height: 16px;
            background: white;
            transform: rotate(45deg);
            box-shadow: -2px -2px 5px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div class="sidebar-header">
            <a href="#" class="sidebar-brand">
                <i class="fas fa-heartbeat"></i>
                <span class="sidebar-link-text">HealthHub</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="#" class="sidebar-link active">
                <i class="fas fa-home"></i>
                <span class="sidebar-link-text">Dashboard</span>
            </a>
            <a href="health_metrics.php" class="sidebar-link">
                <i class="fas fa-heartbeat"></i>
                <span class="sidebar-link-text">Health Metrics</span>
            </a>
            <a href="booking.php" class="sidebar-link">
                <i class="fas fa-calendar-alt"></i>
                <span class="sidebar-link-text">Appointments</span>
            </a>
            <a href="medication.php" class="sidebar-link">
                <i class="fas fa-pills"></i>
                <span class="sidebar-link-text">Medications</span>
            </a>
            <a href="health_records.php" class="sidebar-link">
                <i class="fas fa-file-medical"></i>
                <span class="sidebar-link-text">Health Records</span>
            </a>
            <a href="logout.php" class="sidebar-link">
                <i class="fas fa-sign-out-alt"></i>
                <span class="sidebar-link-text">Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-title">
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($username); ?></p>
            </div>
            <div class="header-actions">
               
                <div class="profile-dropdown">
                    
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=random" alt="Profile" class="profile-img">
                    <div class="dropdown-menu">
                        <div class="dropdown-header">
                            <strong><?php echo htmlspecialchars($username); ?></strong>
                            <span class="text-muted"><?php echo ucfirst($role); ?></span>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="container-fluid">
            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-md-3 fade-in delay-1">
                    <div class="stats-card stats-card-primary">
                        <i class="fas fa-heartbeat stats-icon"></i>
                        <div class="stats-value"><?php echo $latest_metrics ? $latest_metrics['heart_rate'] : '--'; ?></div>
                        <div class="stats-label">Heart Rate (bpm)</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-2">
                    <div class="stats-card stats-card-success">
                        <i class="fas fa-weight stats-icon"></i>
                        <div class="stats-value"><?php echo $latest_metrics ? $latest_metrics['weight'] : '--'; ?></div>
                        <div class="stats-label">Weight (kg)</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-3">
                    <div class="stats-card stats-card-warning">
                        <i class="fas fa-tint stats-icon"></i>
                        <div class="stats-value"><?php echo $latest_metrics ? $latest_metrics['systolic_bp'] . '/' . $latest_metrics['diastolic_bp'] : '--/--'; ?></div>
                        <div class="stats-label">Blood Pressure</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in delay-4">
                    <div class="stats-card stats-card-danger">
                        <i class="fas fa-fire stats-icon"></i>
                        <div class="stats-value"><?php echo $latest_metrics ? $latest_metrics['calories'] : '--'; ?></div>
                        <div class="stats-label">Calories Today</div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Health Metrics Chart -->
                    <div class="dashboard-card fade-in">
                        <div class="card-header">
                            <h3 class="card-title">Health Metrics Overview</h3>
                            <button class="card-action">View Details</button>
                        </div>
                        <div class="chart-container">
                            <canvas id="healthChart" height="300"></canvas>
                        </div>
                    </div>

                    <!-- Recent Health Records -->
                    <div class="dashboard-card fade-in delay-1">
                        <div class="card-header">
                            <h3 class="card-title">Health Records & Metrics</h3>
                            <button class="card-action">View All</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Details</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($health_records as $record): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['record_date'])); ?></td>
                                        <td>Medical Record</td>
                                        <td>
                                            <strong>Diagnosis:</strong> <?php echo htmlspecialchars($record['diagnosis']); ?><br>
                                            <strong>Doctor:</strong> <?php echo htmlspecialchars($record['doctor_name']); ?>
                                        </td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php foreach ($metrics_history as $metric): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($metric['recorded_at'])); ?></td>
                                        <td>Health Metrics</td>
                                        <td>
                                            <strong>Heart Rate:</strong> <?php echo $metric['heart_rate']; ?> bpm<br>
                                            <strong>Blood Pressure:</strong> <?php echo $metric['systolic_bp']; ?>/<?php echo $metric['diastolic_bp']; ?><br>
                                            <strong>Weight:</strong> <?php echo $metric['weight']; ?> kg
                                        </td>
                                        <td><span class="badge bg-info">Recorded</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Daily Health Quote -->
                    <div class="dashboard-card fade-in">
                        <div class="card-header">
                            <h3 class="card-title">Daily Health Quote</h3>
                            <button class="card-action" onclick="refreshQuote()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="quote-card">
                            <div class="quote-icon">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <div class="quote-content">
                                <p class="quote-text" id="quoteText">
                                    "Health is not about the weight you lose, but the life you gain."
                                </p>
                                <p class="quote-author" id="quoteAuthor">
                                    - Dr. Josh Axe
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Health Tips -->
                    <div class="dashboard-card fade-in delay-1">
                        <div class="card-header">
                            <h3 class="card-title">Today's Health Tip</h3>
                        </div>
                        <div class="health-tip-card">
                            <div class="tip-icon">
                                <i class="fas fa-lightbulb"></i>
                            </div>
                            <div class="tip-content">
                                <p class="tip-text" id="tipText">
                                    Stay hydrated throughout the day. Aim to drink at least 8 glasses of water daily for optimal health.
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Health Tips Section (assume this is the end of health tips) -->
                    <div class="dashboard-card mt-4">
                        <div class="card-header">
                            <span class="card-title"><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</span>
                        </div>
                        <div class="card-body">
                            <?php if (count($upcoming_appointments) > 0): ?>
                                <div class="list-group">
                                    <?php foreach ($upcoming_appointments as $appt): ?>
                                        <div class="list-group-item d-flex align-items-center justify-content-between mb-2" style="border-radius:8px; box-shadow:0 2px 8px rgba(67,97,238,0.07);">
                                            <div>
                                                <strong><?php echo htmlspecialchars($appt['doctor_name']); ?></strong>
                                                <span class="badge bg-info ms-2"><?php echo htmlspecialchars($appt['specialization'] ?? ''); ?></span><br>
                                                <span class="text-muted"><i class="fas fa-calendar-day"></i> <?php echo date('D, M j, Y', strtotime($appt['appointment_date'])); ?></span>
                                                <span class="text-muted ms-3"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($appt['appointment_time']); ?></span>
                                            </div>
                                            <span class="badge <?php echo ($appt['status'] === 'confirmed') ? 'bg-success' : (($appt['status'] === 'pending') ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                                <?php echo ucfirst($appt['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">No upcoming appointments found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Sidebar Toggle
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        const sidebarToggle = document.getElementById('sidebarToggle');

        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            mainContent.classList.toggle('main-content-expanded');
            
            // Change icon based on state
            if (sidebar.classList.contains('sidebar-collapsed')) {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            } else {
                sidebarToggle.innerHTML = '<i class="fas fa-chevron-left"></i>';
            }
        });

        // Health Metrics Chart
        const ctx = document.getElementById('healthChart').getContext('2d');
        const healthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Heart Rate (bpm)',
                        data: <?php echo json_encode($heart_rates); ?>,
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Weight (kg)',
                        data: <?php echo json_encode($weights); ?>,
                        borderColor: '#4ad66d',
                        backgroundColor: 'rgba(74, 214, 109, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Systolic BP',
                        data: <?php echo json_encode($systolic_bp); ?>,
                        borderColor: '#f8961e',
                        backgroundColor: 'rgba(248, 150, 30, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });

        // Array of health quotes
        const healthQuotes = [
            {
                text: "Health is not about the weight you lose, but the life you gain.",
                author: "Dr. Josh Axe"
            },
            {
                text: "The greatest wealth is health.",
                author: "Virgil"
            },
            {
                text: "Take care of your body. It's the only place you have to live.",
                author: "Jim Rohn"
            },
            {
                text: "Health is a state of complete physical, mental and social well-being, and not merely the absence of disease or infirmity.",
                author: "World Health Organization"
            },
            {
                text: "Your health is an investment, not an expense.",
                author: "Unknown"
            },
            {
                text: "The first wealth is health.",
                author: "Ralph Waldo Emerson"
            },
            {
                text: "A healthy outside starts from the inside.",
                author: "Robert Urich"
            },
            {
                text: "Health is the greatest gift, contentment the greatest wealth, faithfulness the best relationship.",
                author: "Buddha"
            }
        ];

        // Array of health tips
        const healthTips = [
            "Stay hydrated throughout the day. Aim to drink at least 8 glasses of water daily.",
            "Get 7-8 hours of sleep each night for optimal health and recovery.",
            "Include at least 30 minutes of physical activity in your daily routine.",
            "Eat a balanced diet rich in fruits, vegetables, and whole grains.",
            "Take regular breaks from sitting to stretch and move around.",
            "Practice stress management techniques like meditation or deep breathing.",
            "Maintain good posture to prevent back and neck pain.",
            "Don't skip breakfast - it's the most important meal of the day."
        ];

        // Function to get random quote
        function getRandomQuote() {
            const randomIndex = Math.floor(Math.random() * healthQuotes.length);
            return healthQuotes[randomIndex];
        }

        // Function to get random tip
        function getRandomTip() {
            const randomIndex = Math.floor(Math.random() * healthTips.length);
            return healthTips[randomIndex];
        }

        // Function to refresh quote
        function refreshQuote() {
            const quote = getRandomQuote();
            document.getElementById('quoteText').textContent = `"${quote.text}"`;
            document.getElementById('quoteAuthor').textContent = `- ${quote.author}`;
            
            // Add animation
            const quoteCard = document.querySelector('.quote-card');
            quoteCard.style.animation = 'none';
            quoteCard.offsetHeight; // Trigger reflow
            quoteCard.style.animation = 'fadeIn 0.5s ease forwards';
        }

        // Function to refresh tip
        function refreshTip() {
            const tip = getRandomTip();
            document.getElementById('tipText').textContent = tip;
            
            // Add animation
            const tipCard = document.querySelector('.health-tip-card');
            tipCard.style.animation = 'none';
            tipCard.offsetHeight; // Trigger reflow
            tipCard.style.animation = 'fadeIn 0.5s ease forwards';
        }

        // Refresh quote and tip every 24 hours
        setInterval(() => {
            refreshQuote();
            refreshTip();
        }, 24 * 60 * 60 * 1000);

        // Add animation to elements when they come into view
        const animateOnScroll = () => {
            const elements = document.querySelectorAll('.fade-in');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;
                
                if (elementPosition < screenPosition) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        };

        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
    </script>
</body>
</html>