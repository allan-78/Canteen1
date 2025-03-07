<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle password change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['change_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate passwords
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $password_error = "Please fill in all password fields.";
        } elseif ($new_password !== $confirm_password) {
            $password_error = "New password and confirm password do not match.";
        } else {
            // Check old password
            $check_sql = "SELECT password FROM users WHERE user_id = ?";
            $stmt_check = $con->prepare($check_sql);
            $stmt_check->bind_param("i", $user_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $user_password = $result_check->fetch_assoc();
            $stmt_check->close();

            if (password_verify($old_password, $user_password['password'])) {
                // Update password in the database
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt_update_password = $con->prepare($update_password_sql);
                $stmt_update_password->bind_param("si", $hashed_password, $user_id);

                if ($stmt_update_password->execute()) {
                    $password_message = "Password updated successfully.";
                } else {
                    $password_error = "Error updating password: " . htmlspecialchars($stmt_update_password->error);
                }
                $stmt_update_password->close();
            } else {
                $password_error = "Old password is incorrect.";
            }
        }
    }

    // Handle account deactivation
    if (isset($_POST['deactivate_account'])) {
        // Update account status in the database
        $deactivate_sql = "UPDATE users SET active = 0 WHERE user_id = ?";
        $stmt_deactivate = $con->prepare($deactivate_sql);
        $stmt_deactivate->bind_param("i", $user_id);

        if ($stmt_deactivate->execute()) {
            $deactivate_message = "Account deactivated successfully.";
            // Log out the user after deactivation
            session_destroy();
            header("Location: ../auth/login.php");
            exit();
        } else {
            $deactivate_error = "Error deactivating account: " . htmlspecialchars($stmt_deactivate->error);
        }
        $stmt_deactivate->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - Settings</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Poppins', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand {
            font-weight: bold;
        }
        .dropdown-menu {
            background-color: #fff;
            border: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .dropdown-item {
            color: #333;
            transition: all 0.3s ease;
        }
        .dropdown-item:hover {
            background-color: #e44d26;
            color: white;
        }
        .settings-container {
            max-width: 800px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            padding: 2rem;
        }
        .settings-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        footer {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php"><i class="bi bi-shop"></i> QuickByte Canteen</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="index.php" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Home
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="food.php">Food Items</a></li>
                            <li><a class="dropdown-item" href="stalls.php">Stalls</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="settings.php"><i class="bi bi-gear"></i> Settings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="settings-container">
            <div class="settings-header">
                <h2>Account Settings</h2>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="post">
                <h4>Change Password</h4>
                <div class="mb-3">
                    <label for="old_password" class="form-label">Old Password:</label>
                    <input type="password" class="form-control" id="old_password" name="old_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm Password:</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                <?php if (isset($password_error)): ?>
                    <div class="alert alert-danger"><?php echo $password_error; ?></div>
                <?php endif; ?>
                <?php if (isset($password_message)): ?>
                    <div class="alert alert-success"><?php echo $password_message; ?></div>
                <?php endif; ?>
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="bi bi-lock"></i> Change Password
                </button>
            </form>

            <hr>

            <form method="post">
                <h4>Account Management</h4>
                <button type="submit" name="deactivate_account" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> Deactivate Account
                </button>
                <?php if (isset($deactivate_error)): ?>
                    <div class="alert alert-danger"><?php echo $deactivate_error; ?></div>
                <?php endif; ?>
                <?php if (isset($deactivate_message)): ?>
                    <div class="alert alert-success"><?php echo $deactivate_message; ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
