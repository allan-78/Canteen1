<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submission for creating GCash account
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_number = $_POST['accountNumber'];
    $account_name = $_POST['accountName'];

    // Validate input
    if (empty($account_number) || empty($account_name)) {
        $error = "Please fill in all fields.";
    } else {
        // Check if account number already exists
        $check_sql = "SELECT * FROM gcash_accounts WHERE account_number = ?";
        $check_stmt = $con->prepare($check_sql);
        $check_stmt->bind_param("s", $account_number);
        $check_stmt->execute();
        $result_check = $check_stmt->get_result();
        $account_exists = $result_check->fetch_assoc();
        $check_stmt->close();

        if ($account_exists) {
            $error = "GCash account number already exists.";
        } else {
            // Create GCash account
            $create_sql = "INSERT INTO gcash_accounts (account_number, account_name) VALUES (?, ?)";
            $create_stmt = $con->prepare($create_sql);
            $create_stmt->bind_param("ss", $account_number, $account_name);

            if ($create_stmt->execute()) {
                // Update user's GCash number
                $update_sql = "UPDATE users SET gcash_number = ? WHERE user_id = ?";
                $update_stmt = $con->prepare($update_sql);
                $update_stmt->bind_param("si", $account_number, $user_id);
                if ($update_stmt->execute()) {
                    $message = "GCash account created successfully.";
                    // Redirect back to add balance page
                    header("Location: addbalance.php");
                    exit();
                }
                $update_stmt->close();
            } else {
                $error = "Error creating GCash account: " . htmlspecialchars($create_stmt->error);
            }
            $create_stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - Create GCash Account</title>
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
        .create-gcash-container {
            max-width: 400px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            padding: 2rem;
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
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="create-gcash-container">
            <h2>Create GCash Account</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-3">
                    <label for="accountNumber" class="form-label">GCash Account Number:</label>
                    <input type="text" class="form-control" id="accountNumber" name="accountNumber" required>
                </div>
                <div class="mb-3">
                    <label for="accountName" class="form-label">GCash Account Name:</label>
                    <input type="text" class="form-control" id="accountName" name="accountName" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus"></i> Create Account
                </button>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer><p>&copy; 2025 QuickByte Canteen. All rights reserved.</p></footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
