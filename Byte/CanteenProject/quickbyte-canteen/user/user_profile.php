<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql = "SELECT name, email, role, balance, phone, address, image_path FROM users WHERE user_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Ensure default values for optional fields
$user['phone'] = $user['phone'] ?? 'Not Provided';
$user['address'] = $user['address'] ?? 'Not Provided';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - User Profile</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
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
        .profile-container {
            max-width: 900px;
            margin: 3rem auto;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            padding: 2rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1rem;
            border: 5px solid #f8f9fa;
        }
        .profile-info {
            margin-bottom: 2rem;
        }
        .profile-info h4 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .profile-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .profile-details div {
            flex: 1 1 45%;
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
        }
        .profile-details div strong {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        .profile-actions {
            text-align: center;
            margin-top: 2rem;
        }
        .profile-actions .btn {
            margin: 0.5rem;
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
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
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
                            <li><a class="dropdown-item" href="food.php"><i class="bi bi-egg-fried"></i> Food Items</a></li>
                            <li><a class="dropdown-item" href="stalls.php"><i class="bi bi-shop-window"></i> Stalls</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="user_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php"><i class="bi bi-gear"></i> Settings</a>
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
        <div class="profile-container">
            <div class="profile-header">
                <h2>User Profile</h2>
            </div>

            <div class="profile-info">
                <img src="<?php echo htmlspecialchars($user['image_path']); ?>" alt="Profile Picture" class="profile-image">
                <h4 class="text-center"><?php echo htmlspecialchars($user['name']); ?></h4>
                <p class="text-center text-muted"><?php echo htmlspecialchars($user['role']); ?></p>
            </div>

            <div class="profile-details">
                <div>
                    <strong>Balance:</strong>
                    <p>$<?php echo number_format($user['balance'], 2); ?></p>
                </div>
                <div>
                    <strong>Email:</strong>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <strong>Phone:</strong>
                    <p><?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
                <div>
                    <strong>Address:</strong>
                    <p><?php echo htmlspecialchars($user['address']); ?></p>
                </div>
            </div>

            <div class="profile-actions">
                <a href="update_profile.php" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Edit Profile
                </a>
                <a href="order_history.php" class="btn btn-secondary">
                    <i class="bi bi-clock-history"></i> Order History
                </a>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBalanceModal">
                    <i class="bi bi-plus-circle"></i> Add Balance
                </button>
            </div>
        </div>
    </div>

    <!-- Add Balance Modal -->
    <div class="modal fade" id="addBalanceModal" tabindex="-1" aria-labelledby="addBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBalanceModalLabel">Add Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addBalanceForm" method="post" action="addbalance.php">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Amount to Add:</label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="10" max="1000">
                            <div class="form-text">Minimum amount: $10, Maximum amount: $1000</div>
                        </div>
                        <input type="hidden" name="payment_method" value="gcash">
                        <div id="paymentDetails">
                            <!-- GCash account details -->
                            <div class="mb-3">
                                <label for="accountNumber" class="form-label">GCash Account Number:</label>
                                <input type="text" class="form-control" id="accountNumber" name="accountNumber" required>
                            </div>
                            <div class="mb-3">
                                <label for="accountName" class="form-label">GCash Account Name:</label>
                                <input type="text" class="form-control" id="accountName" name="accountName" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Add Balance
                        </button>
                    </form>
                </div>
            </div>
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