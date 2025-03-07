<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get the stall ID from the query string
if (!isset($_GET['stall_id']) || empty($_GET['stall_id'])) {
    header("Location: index.php");
    exit();
}

$stall_id = $_GET['stall_id'];

// Fetch stall details
$sql = "SELECT name, description, image_path FROM stalls WHERE stall_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $stall_id);
$stmt->execute();
$result = $stmt->get_result();
$stall = $result->fetch_assoc();
$stmt->close();

if (!$stall) {
    header("Location: index.php");
    exit();
}

// Fetch menu items for the stall with stock information
$sql = "
    SELECT m.item_id, m.name, m.price, m.category, m.image_path, i.quantity_in_stock
    FROM menu_items m
    LEFT JOIN inventory i ON m.item_id = i.item_id
    WHERE m.stall_id = ? AND m.availability = 1
";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $stall_id);
$stmt->execute();
$result = $stmt->get_result();
$menuItems = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickByte Canteen - <?php echo htmlspecialchars($stall['name']); ?></title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .menu-item {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .menu-item:hover {
            transform: translateY(-5px);
        }
        .menu-item img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .menu-item img:hover {
            transform: scale(1.05);
        }
        footer {
            background-color: #e44d26;
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto;
        }
        .sold-out {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .stock-info {
            font-size: 0.9em;
            color: #6c757d;
        }
        .btn-add-cart {
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            transition: opacity 0.3s ease;
        }
        .btn-add-cart:hover {
            opacity: 0.8;
        }
        .btn-sold-out {
            background-color: transparent;
            border: 1px solid #ccc;
            color: #ccc;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: not-allowed;
        }
        .btn-view-product {
            background-color: transparent;
            border: 1px solid #333;
            color: #333;
            border-radius: 4px;
            padding: 10px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-view-product:hover {
            background-color: #333;
            color: white;
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
                        <a class="nav-link" href="index.php"><i class="bi bi-house"></i> Home</a>
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

    <!-- Stall Details -->
    <div class="container mt-4">
        <h2 class="text-center mb-4"><?php echo htmlspecialchars($stall['name']); ?></h2>
        <p class="text-center text-muted mb-5"><?php echo htmlspecialchars($stall['description']); ?></p>

        <!-- Menu Items -->
        <div class="row">
            <?php foreach ($menuItems as $item): ?>
                <div class="col-md-4">
                    <div class="menu-item <?php echo $item['quantity_in_stock'] <= 0 ? 'sold-out' : ''; ?>">
                        <img src="images/food/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                        <p><strong>Price:</strong> $<?php echo number_format($item['price'], 2); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
                        <p class="stock-info">
                            <?php if ($item['quantity_in_stock'] > 0): ?>
                                <strong>In Stock:</strong> <?php echo $item['quantity_in_stock']; ?> left
                            <?php else: ?>
                                <strong>Sold Out</strong>
                            <?php endif; ?>
                        </p>
                        <button
                            class="<?php echo $item['quantity_in_stock'] > 0 ? 'btn-add-cart' : 'btn-sold-out'; ?>"
                            onclick="addToCart(<?php echo $item['item_id']; ?>)"
                            <?php echo $item['quantity_in_stock'] <= 0 ? 'disabled' : ''; ?>
                        >
                            <?php echo $item['quantity_in_stock'] > 0 ? '<i class="fas fa-cart-plus"></i> Add to Cart' : 'Sold Out'; ?>
                        </button>
                        <!-- View Product Button -->
                        <a href="product_details.php?item_id=<?php echo $item['item_id']; ?>" class="btn-view-product">
                            <i class="fas fa-eye"></i> View Product
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
    </footer>

    <!-- JavaScript for Add to Cart -->
    <script>
        function addToCart(itemId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: itemId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart!');
                } else {
                    alert(data.message || 'Failed to add item to cart.');
                }
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>