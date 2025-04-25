<?php
session_start();
require_once __DIR__ . '/db.php';
$conn = $GLOBALS['conn'];

// Protect page: redirect if not logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
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
?>
<!-- ... existing HTML ... -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | HealthHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="booking.css">
</head>
<body>
    <!-- Header -->
    <header class="booking-header">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-heartbeat me-2"></i>HealthHub
                </a>
                <div class="ms-auto">
                    <a href="dashboard.php" class="btn btn-outline-primary">Back to Dashboard</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="booking-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1>Book Your Doctor's Appointment</h1>
                    <p class="lead">Schedule your visit with our specialists in just a few clicks. Quality healthcare made simple.</p>
                    <div class="hero-features">
                        <div class="feature-item">
                            <i class="fas fa-calendar-check"></i>
                            <span>Easy Scheduling</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-user-md"></i>
                            <span>Expert Doctors</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-clock"></i>
                            <span>24/7 Availability</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="booking-card">
                        <h3>Schedule Appointment</h3>
                        <form id="appointmentForm">
                            <div class="form-group">
                                <label for="doctor">Select Doctor</label>
                                <select class="form-select" id="doctor" name="doctor" required>
                                    <option value="" selected disabled>Choose a doctor</option>
                                    <option value="Dr. Sarah Johnson">Dr. Sarah Johnson - Cardiologist</option>
                                    <option value="Dr. Michael Chen">Dr. Michael Chen - Neurologist</option>
                                    <option value="Dr. Emily Wilson">Dr. Emily Wilson - Pediatrician</option>
                                    <option value="Dr. David Kim">Dr. David Kim - Orthopedist</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date">Appointment Date</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                            <div class="form-group">
                                <label for="time">Preferred Time</label>
                                <select class="form-select" id="time" name="time" required>
                                    <option value="" selected disabled>Select time slot</option>
                                    <option value="09:00 AM">09:00 AM</option>
                                    <option value="10:00 AM">10:00 AM</option>
                                    <option value="11:00 AM">11:00 AM</option>
                                    <option value="02:00 PM">02:00 PM</option>
                                    <option value="03:00 PM">03:00 PM</option>
                                    <option value="04:00 PM">04:00 PM</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="reason">Reason for Visit</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" placeholder="Briefly describe your symptoms or reason for appointment"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Book Appointment</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section class="doctors-section">
        <div class="container">
            <h2 class="section-title">Our Specialist Doctors</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="doctor-card">
                        <img src="https://randomuser.me/api/portraits/women/65.jpg" alt="Dr. Sarah Johnson">
                        <h4>Dr. Sarah Johnson</h4>
                        <p>Cardiologist</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                            <span>4.7</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="doctor-card">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Dr. Michael Chen">
                        <h4>Dr. Michael Chen</h4>
                        <p>Neurologist</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>5.0</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="doctor-card">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Dr. Emily Wilson">
                        <h4>Dr. Emily Wilson</h4>
                        <p>Pediatrician</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>4.9</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="doctor-card">
                        <img src="https://randomuser.me/api/portraits/men/75.jpg" alt="Dr. David Kim">
                        <h4>Dr. David Kim</h4>
                        <p>Orthopedist</p>
                        <div class="rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <span>4.8</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="booking-footer">
        <div class="container">
            <p>&copy; 2025 HealthHub. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="booking.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('appointmentForm');
            if (form) {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    // Get form values
                    const doctor = document.getElementById('doctor').value;
                    const date = document.getElementById('date').value;
                    const time = document.getElementById('time').value;
                    const reason = document.getElementById('reason').value || 'Not specified';
                    
                    // Format date for display
                    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
                        weekday: 'long', 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric' 
                    });
                    
                    try {
                        // Save appointment to database
                        const response = await fetch('save_appointment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                doctor: doctor,
                                date: date,
                                time: time,
                                reason: reason
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            // Show success alert
                            const alertMessage = `
Appointment Booked Successfully!

Doctor: ${doctor}
Date: ${formattedDate}
Time: ${time}
Reason: ${reason}

Thank you for choosing HealthHub!
                            `;
                            alert(alertMessage);
                            
                            // Reset form
                            form.reset();
                        } else {
                            // Show error alert
                            alert('Error: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred while booking the appointment. Please try again.');
                    }
                });
            }
        });
    </script>
</body>
</html>