<?php
session_start();
include '../config.php';

// Enable detailed error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Function to generate a unique order ID
function generateOrderId() {
    return uniqid('ORDER_');
}

// Function to send JSON response
function sendJsonResponse($success, $message = null, $redirect = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message, 'redirect' => $redirect]);
    exit();
}

// Fetch cart items with item names and prices
$sql = "
    SELECT c.cart_id, m.item_id, m.name AS item_name, m.price, c.quantity
    FROM cart c
    JOIN menu_items m ON c.item_id = m.item_id
    WHERE c.user_id = ?
";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Check if cart is empty
if (empty($cart_items)) {
    $error = "Your cart is empty.";
} else {
    // Calculate total cost
    $total_cost = 0;
    foreach ($cart_items as $item) {
        if (isset($item['price']) && isset($item['quantity'])) {
            $total_cost += $item['price'] * $item['quantity'];
        }
    }

    // Handle payment form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $payment_method = $_POST['payment_method'];

        if (empty($payment_method)) {
            $error = "Please select a payment method.";
        }

        if ($payment_method === 'gcash') {
            $account_number = $_POST['accountNumber'] ?? null;
            $account_name = $_POST['accountName'] ?? null;

            if (empty($account_number) || empty($account_name)) {
                $error = "Please fill in all GCash payment details.";
            } else {
                // Retrieve GCash account details
                $sql = "SELECT account_id, balance FROM gcash_accounts WHERE account_number = ? AND account_name = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ss", $account_number, $account_name);
                $stmt->execute();
                $result = $stmt->get_result();
                $gcashAccount = $result->fetch_assoc();
                $stmt->close();

                if (!$gcashAccount) {
                    $error = "GCash account not found.";
                } elseif ($gcashAccount['balance'] < $total_cost) {
                    $error = "Insufficient balance in GCash account.";
                } else {
                    try {
                        // Start transaction
                        $con->begin_transaction();

                        // Generate order ID
                        $order_id = generateOrderId();

                        // Insert into orders table
                        $sql = "INSERT INTO orders (order_id, user_id, total_price) VALUES (?, ?, ?)";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("sid", $order_id, $user_id, $total_cost);
                        $stmt->execute();

                        // Insert into order_details table
                        foreach ($cart_items as $item) {
                            $item_id = $item['item_id'];
                            $quantity = $item['quantity'];
                            $price = $item['price'];
                            $subtotal = $item['price'] * $item['quantity'];

                            $sql = "INSERT INTO order_details (order_id, item_id, quantity, subtotal, price) VALUES (?, ?, ?, ?, ?)";
                            $stmt_order_details = $con->prepare($sql);
                            $stmt_order_details->bind_param("siidd", $order_id, $item_id, $quantity, $subtotal, $price);
                            $stmt_order_details->execute();
                            $stmt_order_details->close();
                        }

                        // Update user balance
                        $sql = "UPDATE users SET balance = balance + ? WHERE user_id = ?";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("di", $total_cost, $user_id);
                        $stmt->execute();

                        // Update GCash account balance
                        $sql = "UPDATE gcash_accounts SET balance = balance - ? WHERE account_number = ?";
                        $stmt = $con->prepare($sql);
                        $stmt->bind_param("ds", $total_cost, $account_number);
                        $stmt->execute();

                        // Clear cart after successful payment
                        $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
                        $stmt_clear_cart = $con->prepare($clear_cart_sql);
                        $stmt_clear_cart->bind_param("i", $user_id);
                        $stmt_clear_cart->execute();
                        $stmt_clear_cart->close();

                        // Commit transaction
                        $con->commit();

                        $message = "Payment processed successfully. Order placed.";
                    } catch (mysqli_sql_exception $exception) {
                        $con->rollback();
                        $error = "Transaction failed: " . $exception->getMessage();
                    }
                }
            }
        } elseif ($payment_method === 'balance') {
            // Simulate balance deduction logic here
            // Example: Check user's balance and deduct the total cost
            $balance_sql = "SELECT balance FROM users WHERE user_id = ?";
            $stmt_balance = $con->prepare($balance_sql);
            $stmt_balance->bind_param("i", $user_id);
            $stmt_balance->execute();
            $result_balance = $stmt_balance->get_result();
            $user_balance = $result_balance->fetch_assoc()['balance'];
            $stmt_balance->close();

            if ($user_balance >= $total_cost) {
                try {
                    // Start transaction
                    $con->begin_transaction();
                    // Generate order ID
                    $order_id = generateOrderId();

                    // Insert into orders table
                    $sql = "INSERT INTO orders (order_id, user_id, total_price) VALUES (?, ?, ?)";
                    $stmt = $con->prepare($sql);
                    $stmt->bind_param("sid", $order_id, $user_id, $total_cost);
                    $stmt->execute();

                    // Insert into order_details table
                    foreach ($cart_items as $item) {
                        $item_id = $item['item_id'];
                        $quantity = $item['quantity'];
                        $price = $item['price'];
                        $subtotal = $item['price'] * $item['quantity'];

                        $sql = "INSERT INTO order_details (order_id, item_id, quantity, subtotal, price) VALUES (?, ?, ?, ?, ?)";
                        $stmt_order_details = $con->prepare($sql);
                        $stmt_order_details->bind_param("siidd", $order_id, $item_id, $quantity, $subtotal, $price);
                        $stmt_order_details->execute();
                        $stmt_order_details->close();
                    }

                    // Deduct balance
                    $new_balance = $user_balance - $total_cost;
                    $update_balance_sql = "UPDATE users SET balance = ? WHERE user_id = ?";
                    $stmt_update_balance = $con->prepare($update_balance_sql);
                    $stmt_update_balance->bind_param("di", $new_balance, $user_id);
                    $stmt_update_balance->execute();
                    $stmt_update_balance->close();

                    // Clear cart
                    $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
                    $stmt_clear_cart = $con->prepare($clear_cart_sql);
                    $stmt_clear_cart->bind_param("i", $user_id);
                    $stmt_clear_cart->execute();
                    $stmt_clear_cart->close();

                    // Commit transaction
                    $con->commit();

                    $message = "Payment processed successfully using your balance. Order placed.";
                } catch (mysqli_sql_exception $exception) {
                    $con->rollback();
                    $error = "Transaction failed: " . $exception->getMessage();
                }
            } else {
                $error = "Insufficient balance to complete the transaction.";
            }
        }
    }
}
?>... <!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - Checkout</title>
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
        .checkout-container {
            max-width: 800px;
            margin: 2rem auto;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            padding: 2rem;
        }
        .checkout-header {
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
        <div class="checkout-container">
            <div class="checkout-header">
                <h2>Checkout</h2>
                <p>Review your order and proceed to payment.</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (isset($message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                <a href="index.php" class="btn btn-primary">Continue Shopping</a>
            <?php else: ?>
                <h4>Order Summary</h4>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3">Total:</td>
                            <td>$<?php echo number_format($total_cost, 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Payment Form -->
                <form method="post">
                    <h4>Payment Details</h4>
                    <div class="mb-3">
                        <label for="paymentMethod" class="form-label">Payment Method:</label>
                        <select class="form-select" id="paymentMethod" name="payment_method" required>
                            <option value="">Select Payment Method</option>
                            <option value="gcash">GCash</option>
                            <option value="balance">Balance</option>
                        </select>
                    </div>

                    <!-- GCash Payment Details -->
                    <div id="gcashDetails" style="display:none;">
                        <div class="mb-3">
                            <label for="accountNumber" class="form-label">GCash Account Number:</label>
                            <input type="text" class="form-control" id="accountNumber" name="accountNumber">
                        </div>
                        <div class="mb-3">
                            <label for="accountName" class="form-label">GCash Account Name:</label>
                            <input type="text" class="form-control" id="accountName" name="accountName">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-success"><i class="bi bi-credit-card"></i> Proceed to Payment</button>

                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer><p>&copy; 2025 QuickByte Canteen. All rights reserved.</p></footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('paymentMethod').addEventListener('change', function () {
            const gcashDetailsDiv = document.getElementById('gcashDetails');
            gcashDetailsDiv.style.display = this.value === 'gcash' ? 'block' : 'none';
        });
    </script>
</body>
</html>
