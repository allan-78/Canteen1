<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in or is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../admin/auth/login.php");
    exit();
}

// Fetch all menu items with their stock levels
$sql = "
    SELECT m.item_id, m.name, m.price, m.category, i.quantity_in_stock
    FROM menu_items m
    LEFT JOIN inventory i ON m.item_id = i.item_id
";
$stmt = $con->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$menuItems = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle stock updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'];
    $new_stock = intval($_POST['new_stock']);

    // Update stock level
    $sql = "INSERT INTO inventory (item_id, quantity_in_stock) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE quantity_in_stock = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("iii", $item_id, $new_stock, $new_stock);
    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
        echo "<script>alert('Stock updated successfully!');</script>";
    } else {
        echo "<script>alert('Failed to update stock.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - Stock Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: #e44d26;
            color: white;
        }
        footer {
            background-color: #e44d26;
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto;
        }
        .stock-table {
            margin-top: 2rem;
        }
        .stock-table th, .stock-table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #e44d26;">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="bi bi-shop"></i> QuickByte Canteen</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="bi bi-house"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <h2 class="text-center mb-4">Stock Management</h2>

        <!-- Stock Table -->
        <form method="POST" class="stock-table">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Current Stock</th>
                        <th>New Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menuItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td><?php echo $item['quantity_in_stock'] ?? 0; ?></td>
                            <td>
                                <input type="number" name="new_stock" class="form-control" value="<?php echo $item['quantity_in_stock'] ?? 0; ?>" min="0">
                                <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                            </td>
                            <td>
                                <button type="submit" class="btn btn-success">Update Stock</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>