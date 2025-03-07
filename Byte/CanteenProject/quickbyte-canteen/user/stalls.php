<?php
session_start();
include '../config.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch all stalls
$sql = "SELECT stall_id, name, description, image_path FROM stalls";
$stmt = $con->prepare($sql);

if (!$stmt) {
    die("Error preparing SQL statement: " . htmlspecialchars($con->error));
}

$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    die("Error fetching results: " . htmlspecialchars($stmt->error));
}

$stalls = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stalls - QuickByte Canteen</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure the body takes up at least the full viewport height */
        }
        .navbar {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        .navbar-brand {
            font-weight: bold; /* Make the brand more prominent */
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
        .stall-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            padding: 1rem;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .stall-card:hover {
            transform: translateY(-5px);
        }
        .stall-card img {
            max-height: 150px;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .stall-card .card-body {
            flex-grow: 1;
            padding: 0;
            margin-top: 1rem;
        }
        .stall-card .card-body h5 {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .stall-card .card-body p {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        .btn-view-menu {
            background-color: #e44d26;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            transition: opacity 0.3s ease;
            width: 100%;
            margin-top: auto;
        }
        .btn-view-menu:hover {
            opacity: 0.8;
        }
        footer {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            text-align: center;
            padding: 1rem 0;
            margin-top: auto; /* Push the footer to the bottom */
        }
        .hero-section {
            background: linear-gradient(135deg, #e44d26, #ff7f50);
            color: white;
            text-align: center;
            padding: 2rem 0;
        }
        .hero-section h2 {
            font-size: 2.5rem;
            font-weight: bold;
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h2>Discover Our Stalls</h2>
        </div>
    </section>

    <!-- Stalls Section -->
    <section class="container mt-4">
        <div class="row">
            <?php if (!empty($stalls)): ?>
                <?php foreach ($stalls as $stall): ?>
                    <div class="col-md-4 mb-4">
                        <div class="stall-card">
                            <img src="<?php echo htmlspecialchars($stall['image_path']); ?>" alt="<?php echo htmlspecialchars($stall['name']); ?>">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($stall['name']); ?></h5>
                                <p><?php echo htmlspecialchars($stall['description'] ?? 'No description available.'); ?></p>
                                <a href="food_items.php?stall_id=<?php echo $stall['stall_id']; ?>" class="btn btn-view-menu">
                                    <i class="bi bi-utensils"></i> View Menu
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>No stalls available.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 QuickByte Canteen. All rights reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
