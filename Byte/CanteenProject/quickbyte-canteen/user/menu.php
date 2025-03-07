<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../admin/auth/login.php");
    exit();
}

// Fetch all stalls for the filter dropdown
$stalls_sql = "SELECT stall_id, name FROM stalls";
$stalls_result = $con->query($stalls_sql);
$stalls = $stalls_result->fetch_all(MYSQLI_ASSOC);

// Fetch all menu items with stock information
$sql = "
    SELECT m.item_id, m.name, m.price, m.category, m.image_path, m.stall_id, s.name as stall_name, i.quantity_in_stock
    FROM menu_items m
    LEFT JOIN inventory i ON m.item_id = i.item_id
    LEFT JOIN stalls s ON m.stall_id = s.stall_id
    WHERE m.availability = 1
";
$stmt = $con->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$menuItems = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get unique categories for filter dropdown
$categories = [];
foreach ($menuItems as $item) {
    if (!in_array($item['category'], $categories) && !empty($item['category'])) {
        $categories[] = $item['category'];
    }
}
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>QuickByte Canteen - Menu</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
        .menu-item {
            background-color: rgba(255, 255, 255, 0.92);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 1rem;
            margin-bottom: 1rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .menu-item:hover {
            transform: translateY(-5px);
        }
        .menu-item img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .menu-item img:hover {
            transform: scale(1.05);
        }
        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .out-of-stock {
            opacity: 0.6;
            position: relative;
        }
        .out-of-stock::after {
            content: "OUT OF STOCK";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            background-color: rgba(255, 0, 0, 0.7);
            color: white;
            padding: 5px 10px;
            font-weight: bold;
            border-radius: 5px;
            z-index: 1;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
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
                        <a class="nav-link active" href="menu.php"><i class="bi bi-list"></i> Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="bi bi-cart"></i> Cart</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
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
        <h2 class="text-center mb-4">Menu Items</h2>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="input-group">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search menu items...">
                        <button class="btn btn-outline-secondary" type="button" onclick="filterItems()">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <select id="categoryFilter" class="form-select" onchange="filterItems()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <select id="stallFilter" class="form-select" onchange="filterItems()">
                        <option value="">All Stalls</option>
                        <?php foreach ($stalls as $stall): ?>
                            <option value="<?php echo $stall['stall_id']; ?>"><?php echo htmlspecialchars($stall['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- Menu Items -->
        <div class="row" id="menuItemsContainer">
            <?php foreach ($menuItems as $item): ?>
                <div class="col-md-4 mb-4 menu-item-card" 
                     data-name="<?php echo strtolower(htmlspecialchars($item['name'])); ?>" 
                     data-category="<?php echo strtolower(htmlspecialchars($item['category'])); ?>" 
                     data-stall="<?php echo $item['stall_id']; ?>">
                    <div class="menu-item <?php echo (!$item['quantity_in_stock'] || $item['quantity_in_stock'] <= 0) ? 'out-of-stock' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                        <p>
                            <strong>Price:</strong> $<?php echo number_format($item['price'], 2); ?><br>
                            <strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?><br>
                            <strong>Stall:</strong> <?php echo htmlspecialchars($item['stall_name']); ?>
                        </p>
                        <button class="btn btn-primary" onclick="addToCart(<?php echo $item['item_id']; ?>)" 
                                <?php echo (!$item['quantity_in_stock'] || $item['quantity_in_stock'] <= 0) ? 'disabled' : ''; ?>>
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- No Results Message -->
        <div id="noResults" class="text-center mt-4 d-none">
            <p class="text-muted">No menu items match your search criteria.</p>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
    </footer>

    <!-- JavaScript for Menu Filtering and Cart -->
    <script>
        function filterItems() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value.toLowerCase();
            const stallId = document.getElementById('stallFilter').value;
            
            const menuItems = document.querySelectorAll('.menu-item-card');
            let visibleCount = 0;
            
            menuItems.forEach(item => {
                const itemName = item.getAttribute('data-name');
                const itemCategory = item.getAttribute('data-category');
                const itemStall = item.getAttribute('data-stall');
                
                const nameMatch = itemName.includes(searchTerm);
                const categoryMatch = category === '' || itemCategory === category;
                const stallMatch = stallId === '' || itemStall === stallId;
                
                if (nameMatch && categoryMatch && stallMatch) {
                    item.style.display = 'block';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            const noResultsElement = document.getElementById('noResults');
            if (visibleCount === 0) {
                noResultsElement.classList.remove('d-none');
            } else {
                noResultsElement.classList.add('d-none');
            }
        }

        function addToCart(itemId) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart successfully!');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while adding the item to cart.');
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>