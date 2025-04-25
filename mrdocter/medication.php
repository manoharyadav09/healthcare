<?php
session_start();
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Protect page: redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

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

// Fetch current medications for the user (optional, for PHP rendering)
$user_id = $_SESSION['id'];
$current_meds = [];
$sql = "SELECT * FROM current_medications WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $current_meds[] = $row;
    }
    $stmt->close();
}

// Fetch medication orders for the user (optional, for PHP rendering)
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Center | HealthHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="medication.css">
</head>
<body>
    <!-- Header -->
    <header class="medication-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-pills me-2"></i>HealthHub Pharmacy
                </a>
                <div class="ms-auto d-flex align-items-center">
                    <a href="dashboard.php" class="btn btn-outline-primary me-3">Back to Dashboard</a>
                    <div class="cart-icon" id="cartIcon">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="medication-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Your Personal Medication Center</h1>
                    <p class="lead">Manage prescriptions, order medications, and get expert health advice all in one place.</p>
                    <div class="hero-features">
                        <div class="feature-item">
                            <i class="fas fa-bolt"></i>
                            <span>Same-Day Delivery</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-user-md"></i>
                            <span>Doctor Approved</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Authentic Medicines</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="floating-meds">
                        <div class="med-icon" style="--delay: 0s">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div class="med-icon" style="--delay: 0.2s">
                            <i class="fas fa-prescription-bottle-alt"></i>
                        </div>
                        <div class="med-icon" style="--delay: 0.4s">
                            <i class="fas fa-syringe"></i>
                        </div>
                        <div class="med-icon" style="--delay: 0.6s">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container medication-container">
        <div class="row">
            <!-- Medication Shop -->
            <div class="col-lg-8">
                <div class="medication-card fade-in">
                    <div class="card-header">
                        <h3 class="card-title">Pharmacy Shop</h3>
                        <div class="search-box">
                            <input type="text" placeholder="Search medications..." id="medSearch">
                            <i class="fas fa-search"></i>
                        </div>
                    </div>
                    <div class="medication-grid" id="medicationGrid">
                        <!-- Medications will be loaded here via JS -->
                    </div>
                </div>

                <!-- Injury Information -->
                <div class="medication-card fade-in delay-1">
                    <div class="card-header">
                        <h3 class="card-title">Common Injury Guide</h3>
                    </div>
                    <div class="injury-accordion" id="injuryAccordion">
                        <!-- Accordion items will be loaded here via JS -->
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Current Medications -->
                <div class="medication-card fade-in">
                    <div class="card-header">
                        <h3 class="card-title">Your Current Medications</h3>
                        <button class="card-action" id="addMedBtn">+ Add New</button>
                    </div>
                    <div class="current-meds" id="currentMeds">
                        <!-- Current meds will be loaded here via JS -->
                        <div class="empty-state">
                            <i class="fas fa-prescription-bottle-alt"></i>
                            <p>No medications added yet</p>
                        </div>
                    </div>
                </div>

                <!-- Shopping Cart -->
                <div class="medication-card fade-in delay-1">
                    <div class="card-header">
                        <h3 class="card-title">Your Cart</h3>
                        <span class="badge bg-primary" id="cartBadge">0 items</span>
                    </div>
                    <div class="cart-items" id="cartItems">
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Your cart is empty</p>
                        </div>
                    </div>
                    <div class="cart-footer">
                        <div class="cart-total">
                            <span>Total:</span>
                            <span id="cartTotal">₹0.00</span>
                        </div>
                        <button class="btn btn-primary w-100" id="checkoutBtn">Proceed to Checkout</button>
                    </div>
                </div>

                <!-- Health Recommendations -->
                <div class="medication-card fade-in delay-2">
                    <div class="card-header">
                        <h3 class="card-title">Quick Health Recommendations</h3>
                    </div>
                    <div class="recommendations">
                        <div class="recommendation-item">
                            <div class="recommendation-icon">
                                <i class="fas fa-head-side-cough"></i>
                            </div>
                            <div class="recommendation-content">
                                <h5>Common Cold</h5>
                                <p>Rest, fluids, and over-the-counter pain relievers</p>
                                <button class="btn-recommendation" data-condition="cold">View Medications</button>
                            </div>
                        </div>
                        <div class="recommendation-item">
                            <div class="recommendation-icon">
                                <i class="fas fa-bone"></i>
                            </div>
                            <div class="recommendation-content">
                                <h5>Sprains</h5>
                                <p>RICE method: Rest, Ice, Compression, Elevation</p>
                                <button class="btn-recommendation" data-condition="sprain">View Medications</button>
                            </div>
                        </div>
                        <div class="recommendation-item">
                            <div class="recommendation-icon">
                                <i class="fas fa-thermometer-half"></i>
                            </div>
                            <div class="recommendation-content">
                                <h5>Fever</h5>
                                <p>Stay hydrated and use fever reducers</p>
                                <button class="btn-recommendation" data-condition="fever">View Medications</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Medication Modal -->
    <div class="modal fade" id="addMedModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addMedForm">
                        <div class="mb-3">
                            <label for="medName" class="form-label">Medication Name</label>
                            <input type="text" class="form-control" id="medName" required>
                        </div>
                        <div class="mb-3">
                            <label for="medDosage" class="form-label">Dosage</label>
                            <input type="text" class="form-control" id="medDosage" placeholder="e.g., 500mg" required>
                        </div>
                        <div class="mb-3">
                            <label for="medFrequency" class="form-label">Frequency</label>
                            <select class="form-select" id="medFrequency" required>
                                <option value="" selected disabled>Select frequency</option>
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily">Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="medInstructions" class="form-label">Special Instructions</label>
                            <textarea class="form-control" id="medInstructions" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMedBtn">Save Medication</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Confirmation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="checkout-success">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h4>Your Order is Complete!</h4>
                        <p id="deliveryMessage">Your medications will be delivered within 24 hours.</p>
                        <div class="order-summary" id="orderSummary">
                            <!-- Order details will be inserted here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Back to Medications</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="medication.js"></script>
</body>
</html>

<?php foreach ($medicines as $medicine): ?>
    <div class="medicine-card">
        <div class="medicine-logo">
            <img src="<?php echo htmlspecialchars($medicine['image_url']); ?>" alt="<?php echo htmlspecialchars($medicine['name']); ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">
        </div>
        <div class="medicine-info">
            <h5><?php echo htmlspecialchars($medicine['name']); ?></h5>
            <p><?php echo htmlspecialchars($medicine['description']); ?></p>
            <span class="medicine-price">₹<?php echo number_format($medicine['price'], 2); ?></span>
            <!-- Add to cart button or other controls here -->
        </div>
    </div>
<?php endforeach; ?>