<?php
session_start();
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Protect page: redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

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

$user_id = $_SESSION['id'];
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $heart_rate = intval($_POST['heart_rate']);
    $weight = floatval($_POST['weight']);
    $systolic_bp = intval($_POST['systolic_bp']);
    $diastolic_bp = intval($_POST['diastolic_bp']);
    $calories = intval($_POST['calories']);

    $stmt = $conn->prepare("INSERT INTO health_metrics (user_id, heart_rate, systolic_bp, diastolic_bp, weight, calories) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiiiid", $user_id, $heart_rate, $systolic_bp, $diastolic_bp, $weight, $calories);
        if ($stmt->execute()) {
            $success_message = "Health metrics recorded successfully!";
        } else {
            $error_message = "Failed to record metrics. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = "Database error. Please try again.";
    }
}

// Fetch latest metrics for this user
$latest_metrics = null;
$stmt = $conn->prepare("SELECT * FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest_metrics = $result->fetch_assoc();
    $stmt->close();
}
?>
<!-- ... existing HTML ... -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Metrics | HealthHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --success-color: #4ad66d;
            --warning-color: #f8961e;
            --danger-color: #f94144;
        }

        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Main background with medical pattern */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('https://img.freepik.com/free-vector/medical-background-with-abstract-hexagonal-pattern_1017-26358.jpg');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
            z-index: -1;
        }

        /* Decorative medical elements */
        .medical-bg-element {
            position: fixed;
            z-index: -1;
            opacity: 0.05;
            background-size: contain;
            background-repeat: no-repeat;
        }

        .medical-bg-element.heart {
            top: 10%;
            right: 5%;
            width: 200px;
            height: 200px;
            background-image: url('https://img.freepik.com/free-vector/heart-pulse-medical-background_1017-26359.jpg');
        }

        .medical-bg-element.dna {
            bottom: 10%;
            left: 5%;
            width: 250px;
            height: 250px;
            background-image: url('https://img.freepik.com/free-vector/dna-helix-abstract-background_1017-26360.jpg');
        }

        .medical-bg-element.pills {
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 300px;
            height: 300px;
            background-image: url('https://img.freepik.com/free-vector/medical-pills-capsules-background_1017-26361.jpg');
        }

        .metrics-form {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .metrics-form::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(67, 97, 238, 0.1) 0%, rgba(67, 97, 238, 0) 70%);
            transform: rotate(45deg);
        }

        .form-title {
            color: var(--primary-color);
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            position: relative;
            padding-bottom: 1rem;
        }

        .form-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 3px;
        }

        .metric-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(67, 97, 238, 0.1);
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            opacity: 0.7;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 0.8rem 2rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-outline-secondary {
            border: 2px solid #e9ecef;
            color: #666;
            padding: 0.8rem 2rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            background: #f8f9fa;
            border-color: #dee2e6;
            color: #495057;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #2a9d8f 100%);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #d62828 100%);
            color: white;
        }

        .metric-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            opacity: 0.8;
        }

        /* Floating medical icons */
        .floating-icon {
            position: absolute;
            font-size: 1.5rem;
            color: var(--primary-color);
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }

        .floating-icon:nth-child(1) {
            top: 10%;
            left: 10%;
            animation-delay: 0s;
        }

        .floating-icon:nth-child(2) {
            top: 20%;
            right: 15%;
            animation-delay: 1s;
        }

        .floating-icon:nth-child(3) {
            bottom: 15%;
            left: 20%;
            animation-delay: 2s;
        }

        .floating-icon:nth-child(4) {
            bottom: 25%;
            right: 10%;
            animation-delay: 3s;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        @media (max-width: 768px) {
            .metrics-form {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .medical-bg-element {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Decorative background elements -->
    <div class="medical-bg-element heart"></div>
    <div class="medical-bg-element dna"></div>
    <div class="medical-bg-element pills"></div>

    <div class="container">
        <div class="metrics-form">
            <!-- Floating medical icons -->
            <i class="fas fa-heartbeat floating-icon"></i>
            <i class="fas fa-pills floating-icon"></i>
            <i class="fas fa-stethoscope floating-icon"></i>
            <i class="fas fa-notes-medical floating-icon"></i>

            <h2 class="form-title">Record Your Health Metrics</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-heartbeat metric-icon"></i>
                            <label for="heart_rate" class="form-label">Heart Rate (bpm)</label>
                            <input type="number" class="form-control" id="heart_rate" name="heart_rate" required min="40" max="200">
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-weight metric-icon"></i>
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" required step="0.1" min="20" max="300">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-tint metric-icon"></i>
                            <label class="form-label">Blood Pressure</label>
                            <div class="row">
                                <div class="col">
                                    <input type="number" class="form-control" name="systolic_bp" placeholder="Systolic" required min="70" max="200">
                                </div>
                                <div class="col">
                                    <input type="number" class="form-control" name="diastolic_bp" placeholder="Diastolic" required min="40" max="130">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-fire metric-icon"></i>
                            <label for="calories" class="form-label">Calories Today</label>
                            <input type="number" class="form-control" id="calories" name="calories" required min="0" max="10000">
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Record Metrics
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </form>

            <?php if ($latest_metrics): ?>
            <div class="mt-5">
                <h4 class="form-title">Latest Recorded Metrics</h4>
                <div class="row">
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-heartbeat metric-icon"></i>
                            <div class="metric-value"><?php echo $latest_metrics['heart_rate']; ?> bpm</div>
                            <div class="metric-label">Heart Rate</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-weight metric-icon"></i>
                            <div class="metric-value"><?php echo $latest_metrics['weight']; ?> kg</div>
                            <div class="metric-label">Weight</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-tint metric-icon"></i>
                            <div class="metric-value"><?php echo $latest_metrics['systolic_bp']; ?>/<?php echo $latest_metrics['diastolic_bp']; ?></div>
                            <div class="metric-label">Blood Pressure</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="metric-card">
                            <i class="fas fa-fire metric-icon"></i>
                            <div class="metric-value"><?php echo $latest_metrics['calories']; ?></div>
                            <div class="metric-label">Calories</div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Recorded on <?php echo date('F j, Y, g:i a', strtotime($latest_metrics['recorded_at'])); ?>
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 