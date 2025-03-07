<?php
session_start();
include '../config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST["full_name"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    if (empty($full_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Check for duplicate full name
        $check_name_sql = "SELECT user_id FROM users WHERE name = ?";
        $check_name_stmt = $con->prepare($check_name_sql);
        $check_name_stmt->bind_param("s", $full_name);
        $check_name_stmt->execute();
        $check_name_stmt->store_result();

        if ($check_name_stmt->num_rows > 0) {
            $error = "This full name is already registered. Please use a different name.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssss", $full_name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
        $check_name_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register - QuickByte Canteen</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)),
                             url('../assets/img/canteen-background.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 15px;
            overflow: hidden;
        }

        .register-container {
            background-color: rgba(255, 255, 255, 0.92);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            margin: auto;
            position: relative;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-circle {
            background-color: #e44d26;
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            margin-bottom: 1rem;
        }

        .logo-circle i {
            font-size: 2rem;
            color: white;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #e44d26;
        }

        .form-control {
            padding-left: 2.5rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .form-select {
            padding-left: 2.5rem;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .btn-primary {
            background-color: #e44d26;
            border: none;
            padding: 0.8rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #d13d17;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(228, 77, 38, 0.2);
        }

        .floating-icons {
            position: fixed;
            width: 100vw;
            height: 100vh;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }

        .floating-icon {
            position: absolute;
            font-size: 1.2rem;
            color: #e44d26;
            opacity: 0.4;
            animation: floatIcon 4s ease-in-out infinite;
        }

        @keyframes floatIcon {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.4;
            }
            50% {
                transform: translate(5px, -5px) rotate(10deg);
                opacity: 0.6;
            }
        }
    </style>
</head>
<body>
    <!-- Floating Icons -->
    <div class="floating-icons">
        <i class="fas fa-utensils floating-icon" style="top: 15%; left: 15%;"></i>
        <i class="fas fa-coffee floating-icon" style="top: 25%; left: 85%;"></i>
        <i class="fas fa-hamburger floating-icon" style="top: 45%; left: 25%;"></i>
        <i class="fas fa-pizza-slice floating-icon" style="top: 65%; left: 75%;"></i>
        <i class="fas fa-ice-cream floating-icon" style="top: 85%; left: 35%;"></i>
    </div>

    <div class="register-container">
        <div class="logo-container">
            <div class="logo-circle">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2 class="text-center mb-4">Create Account</h2>
        </div>

        <form method="POST">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" class="form-control" id="full_name" name="full_name" 
                       placeholder="Full Name" required>
            </div>

            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Email Address" required>
            </div>

            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Password" required>
            </div>

            <div class="form-group">
                <i class="fas fa-user-tag"></i>
                <select class="form-select" id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Student">Student</option>
                    <option value="Retailer">Retailer</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Register</button>

            <div class="text-center mt-4">
                <p class="mb-0">Already have an account? 
                    <a href="login.php" class="text-primary">Login here</a>
                </p>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
