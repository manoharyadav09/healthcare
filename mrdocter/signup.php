
<?php
// Include database connection
require_once __DIR__ . '/db.php';

// Add this after including db.php
$conn = $GLOBALS['conn'];

// Create users table if it doesn't exist
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

$username = $email = $password = $confirm_password = $role = '';
$username_err = $email_err = $password_err = $confirm_password_err = $terms_err = '';
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Username validation
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Email validation
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } else {
        $email = trim($_POST["email"]);
    }

    // Password validation
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Confirm password validation
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Passwords do not match.";
        }
    }

    // Terms validation
    if (!isset($_POST['terms'])) {
        $terms_err = "You must accept the terms and conditions.";
    }

    // Role (only patient for now)
    $role = 'patient';

    // Check for errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($terms_err)) {
        // Check if username or email already exists
        $sql = "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $param_username, $param_email);
            $param_username = $username;
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $email_err = "Username or email already taken.";
                } else {
                    // Insert new user
                    $sql_insert = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                    if ($stmt_insert = $conn->prepare($sql_insert)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt_insert->bind_param("ssss", $param_username, $param_email, $param_password, $param_role);
                        $param_username = $username;
                        $param_email = $email;
                        $param_password = $hashed_password;
                        $param_role = $role;
                        if ($stmt_insert->execute()) {
                            $success_msg = "Registration successful! You can now <a href='login.php'>login</a>.";
                            $username = $email = $password = $confirm_password = '';
                        } else {
                            $email_err = "Something went wrong. Please try again later.";
                        }
                        $stmt_insert->close();
                    }
                }
            } else {
                $email_err = "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Healthcare & Biomedical Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .signup-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-signup {
            background: #4CAF50;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-signup:hover {
            background: #45a049;
            transform: translateY(-2px);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .logo i {
            font-size: 48px;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="signup-container">
            <div class="logo">
                <i class="fas fa-heartbeat"></i>
                <h2>Create Account</h2>
                <p>Join our healthcare management system</p>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                    <span class="invalid-feedback"><?php echo $email_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $password; ?>">
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $confirm_password; ?>">
                    <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control">
                        <option value="patient">Patient</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="terms" class="form-check-input <?php echo (!empty($terms_err)) ? 'is-invalid' : ''; ?>" id="terms">
                        <label class="form-check-label" for="terms">
                            I accept the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                        </label>
                        <span class="invalid-feedback"><?php echo $terms_err; ?></span>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn-signup">Sign Up</button>
                </div>
                <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Acceptance of Terms</h6>
                    <p>By accessing and using this healthcare management system, you agree to be bound by these terms and conditions.</p>
                    
                    <h6>2. Privacy Policy</h6>
                    <p>We are committed to protecting your privacy and handling your personal information in accordance with applicable data protection laws.</p>
                    
                    <h6>3. User Responsibilities</h6>
                    <p>You are responsible for maintaining the confidentiality of your account and for all activities that occur under your account.</p>
                    
                    <h6>4. Medical Information</h6>
                    <p>The information provided through this system is for general healthcare management purposes only and should not be considered as medical advice.</p>
                    
                    <h6>5. Data Security</h6>
                    <p>We implement appropriate security measures to protect your personal and medical information.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>