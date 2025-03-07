<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle cancel order action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = $_POST['order_id'];

    // Update order status to "Canceled"
    $sql_cancel = "UPDATE orders SET status = 'Canceled' WHERE order_id = ? AND user_id = ?";
    $stmt_cancel = $con->prepare($sql_cancel);
    $stmt_cancel->bind_param("si", $order_id, $user_id);

    if ($stmt_cancel->execute()) {
        echo '<script>alert("Order cancelled successfully!"); window.location.href = "order_history.php";</script>';
        exit();
    } else {
        echo '<script>alert("Failed to cancel the order.");</script>';
    }

    $stmt_cancel->close();
}

// Build the SQL query with optional status filtering
$sql = "
    SELECT o.order_id, o.order_date, o.total_price, o.status,
           GROUP_CONCAT(m.name SEPARATOR ', ') AS items,
           GROUP_CONCAT(od.quantity SEPARATOR ', ') AS quantities,
           GROUP_CONCAT(od.price SEPARATOR ', ') AS prices
    FROM orders o
    LEFT JOIN order_details od ON o.order_id = od.order_id
    LEFT JOIN menu_items m ON od.item_id = m.item_id
    WHERE o.user_id = ?
";

if ($filter_status !== 'all') {
    $sql .= " AND o.status = ?";
}

$sql .= " GROUP BY o.order_id ORDER BY o.order_date DESC";

$stmt = $con->prepare($sql);

if ($filter_status !== 'all') {
    $stmt->bind_param("is", $user_id, $filter_status);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Styles -->
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa, #e4e5f1);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        footer {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto;
        }
        h2 {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
        }
        .filters a {
            text-decoration: none;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: inline-block; /* Ensure inline display */
            margin: 0.25rem; /* Add some spacing */
        }
        .filters a:hover {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
        }
        .filters a.active {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .order-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
        }
        .progress-tracker {
            display: flex;
            align-items: center;
            position: relative;
            margin-top: 1rem;
        }
        .step {
            text-align: center;
            flex: 1;
        }
        .circle {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #ccc;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        .circle.active {
            background-color: #1976d2;
            color: white;
            border-color: #1976d2;
        }
        .progress-bar {
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #ccc;
            z-index: -1;
        }
        .progress {
            height: 100%;
            width: 33.33%; /* Adjust based on active step */
            background-color: #1976d2;
            transition: width 0.3s ease;
        }
        .actions button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }
        .actions button.cancel {
            background-color: #f44336;
            color: white;
        }
        .actions button.cancel:hover {
            background-color: #d32f2f;
        }
        .view-details-btn {
            background-color: transparent;
            border: 1px solid #333;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .view-details-btn:hover {
            background-color: #333;
            color: white;
        }
        @media (max-width: 768px) {
            h2 {
                font-size: 2rem;
            }
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
                            <li><a class="dropdown-item" href="food.php"><i class="bi bi-egg-fried"></i> Food Items</a></li>
                            <li><a class="dropdown-item" href="stalls.php"><i class="bi bi-shop-window"></i> Stalls</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
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
    <div class="container mt-4">
        <h2><i class="bi bi-clock-history"></i> Order History</h2>
        <div class="filters text-center mb-4">
            <a href="?status=all" class="btn <?php echo $filter_status === 'all' ? 'active' : ''; ?>"><i class="bi bi-list"></i> All Orders</a>
            <a href="?status=Pending" class="btn <?php echo $filter_status === 'Pending' ? 'active' : ''; ?>"><i class="bi bi-hourglass-split"></i> Pending</a>
            <a href="?status=Completed" class="btn <?php echo $filter_status === 'Completed' ? 'active' : ''; ?>"><i class="bi bi-check-circle"></i> Completed</a>
            <a href="?status=Canceled" class="btn <?php echo $filter_status === 'Canceled' ? 'active' : ''; ?>"><i class="bi bi-x-circle"></i> Cancelled</a>
        </div>

        <?php if (empty($orders)): ?>
            <p class="text-center text-muted"><i class="bi bi-exclamation-triangle"></i> No orders found for the selected filter.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order['order_id']); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
                    </div>
                    <div class="order-details">
                        <?php
                        $items = explode(',', $order['items']);
                        $quantities = explode(',', $order['quantities']);
                        $prices = explode(',', $order['prices']);
                        foreach ($items as $index => $item) {
                            echo "<p><i class='bi bi-caret-right-fill'></i> $item (Qty: {$quantities[$index]}, Price: $" . number_format((float)$prices[$index], 2) . ")</p>";
                        }
                        ?>
                        <p><strong>Total:</strong> $<?php echo number_format((float)$order['total_price'], 2); ?></p>
                    </div>
                    <div class="progress-tracker">
                        <div class="step">
                            <span class="circle <?php echo $order['status'] === 'Pending' || $order['status'] === 'Completed' || $order['status'] === 'Canceled' ? 'active' : ''; ?>">1</span>
                            <p>PLACED</p>
                        </div>
                        <div class="step">
                            <span class="circle <?php echo $order['status'] === 'Completed' || $order['status'] === 'Canceled' ? 'active' : ''; ?>">2</span>
                            <p>SHIPPED</p>
                        </div>
                        <div class="step">
                            <span class="circle <?php echo $order['status'] === 'Completed' ? 'active' : ''; ?>">3</span>
                            <p>DELIVERED</p>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php
                                switch ($order['status']) {
                                    case 'Pending':
                                        echo '33.33%';
                                        break;
                                    case 'Completed':
                                        echo '100%';
                                        break;
                                    case 'Canceled':
                                        echo '0%';
                                        break;
                                    default:
                                        echo '0%';
                                }
                            ?>;"></div>
                        </div>
                    </div>
                    <div class="actions">
                        <?php if ($order['status'] === 'Pending'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <button type="submit" name="cancel_order" class="btn cancel"><i class="bi bi-x-circle"></i> Cancel Order</button>
                            </form>
                        <?php endif; ?>
                        <a href="view_details.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="view-details-btn"><i class="bi bi-eye"></i> View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <h5>Contact Us</h5>
            <p>
                Email: <a href="mailto:support@quickbyte.com">support@quickbyte.com</a><br>
                Phone: <a href="tel:+1234567890">+123 456 7890</a>
            </p>
            <p>Follow us on social media:</p>
            <div>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-twitter"></i></a>
            </div>
            <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
