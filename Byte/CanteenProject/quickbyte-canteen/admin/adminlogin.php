<?php
session_start();

// Database connection details (assuming you have it in config.php)
include '../config.php';

// Check if config.php is working and the database connection is established
if (!isset($con)) {
    die("config.php is NOT working correctly. Database connection is NOT established. Check your database credentials and connection code in config.php. Also, check the file path in the include statement.");
}

$error = ''; // Initialize error message
$failed_attempts = 0; // Initialize failed attempts counter

// Function to sanitize user inputs
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize email and password inputs
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $password = isset($_POST['password']) ? sanitize_input($_POST['password']) : '';

    // Basic input validation
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields!";
    } else {
        // Validate User - Check if the user exists AND has the Admin role
        $admin_check_sql = "SELECT * FROM users WHERE email = ? AND role = 'Admin'";
        $stmt = $con->prepare($admin_check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify the password
            if (password_verify($password, $admin['password'])) {
                // Check if we need to add failed_attempts column to the table if it doesn't exist
                if (!isset($admin['failed_attempts'])) {
                    // You might want to add this column to your database schema
                    // For now, just proceed with login
                    
                    // Set session variables with admin details
                    $_SESSION['user_id'] = $admin['user_id'];
                    $_SESSION['user_name'] = $admin['name']; 
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_role'] = 'Admin';

                    // Redirect to dashboard and display success message
                    $_SESSION['login_message'] = "Welcome back, " . htmlspecialchars($admin['name']) . "!";
                    header("Location: dashboard.php");
                    exit();
                } else {
                    // Reset failed attempts on successful login
                    $reset_attempts_sql = "UPDATE users SET failed_attempts = 0 WHERE user_id = ?";
                    $stmt = $con->prepare($reset_attempts_sql);
                    $stmt->bind_param("i", $admin['user_id']);
                    $stmt->execute();

                    // Set session variables with admin details
                    $_SESSION['user_id'] = $admin['user_id'];
                    $_SESSION['user_name'] = $admin['name']; 
                    $_SESSION['user_email'] = $admin['email'];
                    $_SESSION['user_role'] = 'Admin';

                    // Redirect to dashboard and display success message
                    $_SESSION['login_message'] = "Welcome back, " . htmlspecialchars($admin['name']) . "!";
                    header("Location: dashboard.php");
                    exit();
                }
            } else {
                // Handle failed attempts - if the column exists
                if (isset($admin['failed_attempts'])) {
                    $failed_attempts = $admin['failed_attempts'] + 1;

                    // Lock account after 3 failed attempts
                    if ($failed_attempts >= 3) {
                        $update_attempts_sql = "UPDATE users SET failed_attempts = ? WHERE user_id = ?";
                        $stmt = $con->prepare($update_attempts_sql);
                        $stmt->bind_param("ii", $failed_attempts, $admin['user_id']);
                        $stmt->execute();
                        header("Location: alert.php");
                        exit();
                    } else {
                        // Update failed attempts count
                        $update_attempts_sql = "UPDATE users SET failed_attempts = ? WHERE user_id = ?";
                        $stmt = $con->prepare($update_attempts_sql);
                        $stmt->bind_param("ii", $failed_attempts, $admin['user_id']);
                        $stmt->execute();
                    }
                }
                $error = "Invalid password!";
            }
        } else {
            $error = "Account is not authorized to access admin portal!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - QuickByte Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa, #c3cfe2);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .floating-icons {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .floating-icon {
            position: absolute;
            font-size: 2rem;
            color: rgba(0, 0, 0, 0.1);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .login-wrapper {
            position: relative;
            z-index: 1;
        }

        .login-container {
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .logo-container {
            margin-bottom: 1.5rem;
        }

        .logo-circle {
            background: #0d6efd;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .logo-circle i {
            font-size: 2rem;
            color: #fff;
        }

        .admin-badge {
            font-size: 1.2rem;
            color: #0d6efd;
            margin-top: 0.5rem;
            display: block;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .form-control {
            padding-left: 3rem;
        }

        .attempts-warning {
            font-size: 0.9rem;
            color: #dc3545;
            margin-top: 0.5rem;
        }

        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #0d6efd;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="floating-icons">
        <i class="bi bi-shield-lock floating-icon" style="top: 15%; left: 15%;"></i>
        <i class="bi bi-gear floating-icon" style="top: 40%; left: 80%;"></i>
        <i class="bi bi-person-badge floating-icon" style="top: 75%; left: 25%;"></i>
        <i class="bi bi-graph-up floating-icon" style="top: 20%; left: 60%;"></i>
        <i class="bi bi-grid-3x3-gap floating-icon" style="top: 60%; left: 10%;"></i>
        <i class="bi bi-sliders floating-icon" style="top: 85%; left: 75%;"></i>
    </div>

    <div class="login-wrapper">
        <div class="login-container">
            <div class="logo-container">
                <div class="logo-circle">
                    <i class="bi bi-person-workspace"></i>
                </div>
                <span class="admin-badge">Admin Portal</span>
            </div>

            <h2>QuickByte Dashboard Login</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="Enter your admin email" required>
                </div>

                <div class="form-group">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Enter your admin password" required>
                    
                    <?php if ($failed_attempts > 0): ?>
                        <div class="attempts-warning">
                            <?php echo (3 - $failed_attempts); ?> attempts remaining
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-key-fill me-2"></i>Secure Login
                </button>
            </form>

            <a href="../auth/login.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Return to main login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>