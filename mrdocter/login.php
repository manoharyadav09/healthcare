
<?php
require_once __DIR__ . '/db.php';

$username = $password = '';
$username_err = $password_err = $login_err = $terms_err = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Username validation
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter your username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Password validation
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Terms validation
    if (!isset($_POST['terms'])) {
        $terms_err = "You must accept the terms and conditions.";
    }

    // Authenticate user
    if (empty($username_err) && empty($password_err) && empty($terms_err)) {
        $sql = "SELECT id, username, password FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username_db, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Successful login
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username_db;
                            header("location: dashboard.php"); // Change to your dashboard page
                            exit;
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                $login_err = "Something went wrong. Please try again later.";
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
    <title>Login - Healthcare & Biomedical Devices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
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
        .btn-login:hover {
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
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-heartbeat"></i>
            <h2>Welcome Back</h2>
            <p>Login to access your healthcare dashboard</p>
        </div>

        <?php
        if (!empty($login_err)) {
            echo '<div class="alert alert-danger">' . $login_err . '</div>';
        }
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>">
                <span class="invalid-feedback"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
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
                <button type="submit" class="btn-login">Login</button>
            </div>
            <p class="text-center">Don't have an account? <a href="signup.php">Sign up now</a></p>
        </form>
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