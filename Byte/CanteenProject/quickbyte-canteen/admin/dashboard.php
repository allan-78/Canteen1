<?php
session_start();

// Check if the admin is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Database connection details (assuming you have it in config.php)
include '../config.php';

// Check if config.php is working and the database connection is established
if (!isset($con)) {
    die("config.php is NOT working correctly. Database connection is NOT established.  Check your database credentials and connection code in config.php.  Also, check the file path in the include statement.");
}

// Function to sanitize user inputs (using prepared statements)
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to display error messages
function display_error($message) {
    echo "<div class='alert alert-danger' role='alert'><i class='bi bi-exclamation-triangle-fill me-2'></i>" . htmlspecialchars($message) . "</div>";
}

// Function to display success messages
function display_success($message) {
    echo "<div class='alert alert-success' role='alert'><i class='bi bi-check-circle-fill me-2'></i>" . htmlspecialchars($message) . "</div>";
}

// Fetch admin's name for display
$admin_name = $_SESSION['user_name'];

// --- CRUD Operations for Users and Retailers ---
$success_message = ""; // Initialize success message variable
$error_message = ""; // Initialize error message variable

// Handle Create/Update User/Retailer
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST['add_user']) || isset($_POST['update_user']))) {
    $user_id = isset($_POST['user_id']) ? sanitize_input($_POST['user_id']) : null;
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $role = sanitize_input($_POST['role']);
    $password = $_POST['password']; // Password field is always required during add

    // Validation
    if (empty($name) || empty($email) || empty($role)) {
        $error_message = "Please fill in all required fields.";
    } else {
        if (isset($_POST['add_user'])) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Prepare the SQL statement
            $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($stmt->execute()) {
                $success_message = "User added successfully.";
            } else {
                $error_message = "Error adding user: " . $stmt->error;
            }

            $stmt->close();
        } elseif (isset($_POST['update_user'])) {
            // Check if a password is provided for update
            if (!empty($_POST['password'])) {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE user_id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ssssi", $name, $email, $role, $hashedPassword, $user_id);
            } else {
                // If no password, update other fields only
                $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("sssi", $name, $email, $role, $user_id);
            }

            if ($stmt->execute()) {
                $success_message = "User updated successfully.";
            } else {
                $error_message = "Error updating user: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

// Handle Delete User/Retailer
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];

    $delete_sql = "DELETE FROM users WHERE user_id = ?";
    $stmt = $con->prepare($delete_sql);
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $success_message = "User deleted successfully.";
    } else {
        $error_message = "Error deleting user: " . $stmt->error;
    }

    $stmt->close();
}

// --- Fetch Data ---
// Fetch Users and Retailers
$fetch_users_sql = "SELECT * FROM users WHERE role IN ('Student', 'Retailer')";
$fetch_users_result = $con->query($fetch_users_sql);

// Fetch Stalls
$fetch_stalls_sql = "SELECT * FROM stalls";
$fetch_stalls_result = $con->query($fetch_stalls_sql);

// Number of Users
$users_sql = "SELECT COUNT(*) AS total_users FROM users";
$users_result = $con->query($users_sql);

if ($users_result && $users_result->num_rows > 0) {
    $users_row = $users_result->fetch_assoc();
    $total_users = $users_row['total_users'];
} else {
    $total_users = "Error loading users.";
}

// Number of Items
$items_sql = "SELECT COUNT(*) AS total_items FROM menu_items";
$items_result = $con->query($items_sql);

if ($items_result && $items_result->num_rows > 0) {
    $items_row = $items_result->fetch_assoc();
    $total_items = $items_row['total_items'];
} else {
    $total_items = "Error loading items.";
}

// Number of Transactions
$orders_sql = "SELECT COUNT(*) AS total_orders FROM orders";
$orders_result = $con->query($orders_sql);

if ($orders_result && $orders_result->num_rows > 0) {
    $orders_row = $orders_result->fetch_assoc();
    $total_orders = $orders_row['total_orders'];
} else {
    $total_orders = "Error loading orders.";
}

// Total Revenue
$payments_sql = "SELECT SUM(amount) AS total_revenue FROM payments";
$payments_result = $con->query($payments_sql);

if ($payments_result && $payments_result->num_rows > 0) {
    $payments_row = $payments_result->fetch_assoc();
    $total_revenue = $payments_row['total_revenue'];
} else {
    $total_revenue = "Error loading revenue.";
}

// Fetch retailer items and inventory
$fetch_retailer_inventory_sql = "
    SELECT
        m.item_id,
        m.name AS item_name,
        m.price,
        m.category,
        m.image_path,
        s.name AS stall_name,
        inv.quantity_in_stock
    FROM
        menu_items m
    JOIN
        stalls s ON m.stall_id = s.stall_id
    LEFT JOIN
        inventory inv ON m.item_id = inv.item_id
    ORDER BY
        s.name;
";

$fetch_retailer_inventory_result = $con->query($fetch_retailer_inventory_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - QuickByte Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e63946;
            --primary-light: #f9dfe1;
            --primary-dark: #c31c2c;
            --secondary-color: #1d3557;
            --light-color: #f1faee;
            --dark-color: #2b2d42;
            --text-color: #333333;
            --card-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
            padding-bottom: 2rem;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 280px;
            background: var(--secondary-color);
            padding: 2rem 0;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar-shrink {
            width: 80px;
        }

        .logo-container {
            padding: 0 1.5rem 2rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .logo {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
        }

        .logo i {
            color: var(--primary-color);
            margin-right: 0.5rem;
            font-size: 1.8rem;
        }

        .toggle-sidebar {
            color: white;
            background: transparent;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.8rem 1.5rem;
            display: flex;
            align-items: center;
            transition: var(--transition);
            margin: 0.2rem 0;
        }

        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid var(--primary-color);
        }

        .nav-link i {
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            transition: var(--transition);
        }

        .main-content-expanded {
            margin-left: 80px;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h1 {
            color: var(--dark-color);
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .welcome-text p {
            color: #6c757d;
            margin-bottom: 0;
        }

        .user-actions {
            display: flex;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            margin-right: 1.5rem;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 0.75rem;
        }

        .user-name {
            font-weight: 500;
        }

        .logout-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: var(--transition);
            text-decoration: none;
        }

        .logout-btn:hover {
            background-color: var(--primary-dark);
            color: white;
        }

        .logout-btn i {
            margin-right: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            border-top: 5px solid var(--primary-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-light);
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .stat-title {
            color: #6c757d;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            color: var(--dark-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .content-section {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .section-title {
            color: var(--dark-color);
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0;
        }

        .section-action-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            transition: var(--transition);
        }

        .section-action-btn:hover {
            background-color: var(--primary-dark);
        }

        .section-action-btn i {
            margin-right: 0.5rem;
        }

        table.custom-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .custom-table th {
            background-color: var(--light-color);
            color: var(--secondary-color);
            font-weight: 600;
            text-align: left;
            padding: 1rem;
            border-bottom: 2px solid #e9ecef;
        }

        .custom-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .custom-table tbody tr:hover {
            background-color: rgba(230, 57, 70, 0.03);
        }

        .custom-table .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-edit {
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .btn-edit:hover {
            background-color: #3a56d4;
        }

        .btn-delete {
            background-color: #ef233c;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            transition: var(--transition);
        }

        .btn-delete:hover {
            background-color: #d90429;
        }

        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 5px solid #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 5px solid #721c24;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            border: none;
        }

        .modal-header {
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.25rem;
        }

        .modal-title {
            font-weight: 600;
        }

        .btn-close {
            filter: brightness(0) invert(1);
            opacity: 0.8;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-control {
            padding: 0.75rem;
            border-radius: 8px;
            border: 1px solid #ced4da;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(230, 57, 70, 0.25);
        }

        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid #e9ecef;
        }

        /* For small screens */
        @media (max-width: 992px) {
            .sidebar {
                width: 0;
                padding: 1rem 0;
            }
            
            .sidebar.show {
                width: 280px;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-actions {
                margin-top: 1rem;
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Custom thumbnail styles */
        .img-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="logo-container">
                <div class="logo">
                    <i class="bi bi-cup-hot-fill"></i>
                    <span>QuickByte</span>
                </div>
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="bi bi-arrow-left"></i>
                </button>
            </div>
            
            <nav>
                <a href="#" class="nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#users" class="nav-link">
                    <i class="bi bi-people"></i>
                    <span>User Management</span>
                </a>
                <a href="#stalls" class="nav-link">
                    <i class="bi bi-shop"></i>
                    <span>Stall Management</span>
                </a>
                <a href="#inventory" class="nav-link">
                    <i class="bi bi-box-seam"></i>
                    <span>Inventory</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="bi bi-cart"></i>
                    <span>Orders</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="bi bi-graph-up"></i>
                    <span>Analytics</span>
                </a>
                <a href="#" class="nav-link">
                    <i class="bi bi-gear"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Header -->
            <div class="header">
                <div class="welcome-text">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
                </div>
                <div class="user-actions">
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php echo substr(htmlspecialchars($admin_name), 0, 1); ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                    </div>
                    <a href="../auth/logout.php" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            <?php
            if (!empty($success_message)) {
                display_success($success_message);
            }
            if (!empty($error_message)) {
                display_error($error_message);
            }
            ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-title">Total Users</div>
                    <div class="stat-value"><?php echo htmlspecialchars($total_users); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                    <div class="stat-title">Total Items</div>
                    <div class="stat-value"><?php echo htmlspecialchars($total_items); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-cart-fill"></i>
                    </div>
                    <div class="stat-title">Total Orders</div>
                    <div class="stat-value"><?php echo htmlspecialchars($total_orders); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="stat-title">Total Revenue</div>
                    <div class="stat-value">$<?php echo htmlspecialchars($total_revenue); ?></div>
                </div>
            </div>

            <!-- User Management Section -->
            <div class="content-section" id="users">
                <div class="section-header">
                    <h2 class="section-title">User Management</h2>
                    <button type="button" class="section-action-btn" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bi bi-plus-lg"></i>
                        Add New User
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Check if there are results
                            if ($fetch_users_result->num_rows > 0) {
                                // Output data of each row
                                while($row = $fetch_users_result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>".$row["user_id"]."</td>";
                                    echo "<td>".$row["name"]."</td>";
                                    echo "<td>".$row["email"]."</td>";
                                    echo "<td><span class='badge bg-" . ($row["role"] == 'Student' ? 'info' : 'warning') . "'>".$row["role"]."</span></td>";
                                    echo "<td>
                                        <div class='action-buttons'>
                                            <button type='button' class='btn-edit' data-bs-toggle='modal' data-bs-target='#editUserModal".$row["user_id"]."'>
                                                <i class='bi bi-pencil'></i>
                                            </button>
                                            <a href='?delete_user=".$row["user_id"]."' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this user?\")'>
                                                <i class='bi bi-trash'></i>
                                            </a>
                                        </div>
                                      </td>";
                                    echo "</tr>";
                                    
                                    // Display user update form in a modal
                                    echo "<div class='modal fade' id='editUserModal".$row["user_id"]."' tabindex='-1' aria-labelledby='editUserModalLabel".$row["user_id"]."' aria-hidden='true'>";
                                    echo "<div class='modal-dialog'>";
                                    echo "<div class='modal-content'>";
                                    echo "<div class='modal-header'>";
                                    echo "<h5 class='modal-title' id='editUserModalLabel".$row["user_id"]."'>Edit User</h5>";
                                    echo "<button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>";
                                    echo "</div>";
                                    echo "<div class='modal-body'>";
                                    echo "<form method='POST'>";
                                    echo "<input type='hidden' name='user_id' value='".$row["user_id"]."'>";
                                    echo "<div class='mb-3'>";
                                    echo "<label for='name' class='form-label'>Name</label>";
                                    echo "<input type='text' class='form-control' id='name' name='name' value='".$row["name"]."' required>";
                                    echo "</div>";
                                    echo "<div class='mb-3'>";
                                    echo "<label for='email' class='form-label'>Email</label>";
                                    echo "<input type='email' class='form-control' id='email' name='email' value='".$row["email"]."' required>";
                                    echo "</div>";
                                    echo "<div class='mb-3'>";
                                    echo "<label for='password' class='form-label'>Password (leave blank to keep current)</label>";
                                    echo "<input type='password' class='form-control' id='password' name='password'>";
                                    echo "</div>";
                                    echo "<div class='mb-3'>";
                                    echo "<label for='role' class='form-label'>Role</label>";
                                    echo "<select class='form-control' id='role' name='role' required>";
                                    echo "<option value='Student' ".($row["role"] == 'Student' ? 'selected' : '').">Student</option>";
                                    echo "<option value='Retailer' ".($row["role"] == 'Retailer' ? 'selected' : '').">Retailer</option>";
                                    echo "<option value='Admin' ".($row["role"] == 'Admin' ? 'selected' : '').">Admin</option>";
                                    echo "</select>";
                                    echo "</div>";
                                    echo "<button type='submit' class='section-action-btn' name='update_user'>Update User</button>";
                                    echo "</form>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                    echo "</div>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>No users found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stall Management Section -->
            <div class="content-section" id="stalls">
                <div class="section-header">
                    <h2 class="section-title">Stall Management</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Image</th>
                            </tr>
                        </thead>

                        <!-- Stall Management Section continued -->
<tbody>
    <?php
    // Check if there are results
    if ($fetch_stalls_result->num_rows > 0) {
        // Output data of each row
        while($row = $fetch_stalls_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>".$row["stall_id"]."</td>";
            echo "<td>".$row["name"]."</td>";
            echo "<td>".$row["description"]."</td>";
            echo "<td><img src='../".$row["image_path"]."' class='img-thumbnail' alt='".$row["name"]."'></td>";
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>No stalls found</td></tr>";
    }
    ?>
</tbody>
</table>
</div>
</div>

<!-- Inventory Management Section -->
<div class="content-section" id="inventory">
    <div class="section-header">
        <h2 class="section-title">Inventory Management</h2>
    </div>
    
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Category</th>
                    <th>Stall</th>
                    <th>Quantity</th>
                    <th>Image</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if there are results
                if ($fetch_retailer_inventory_result->num_rows > 0) {
                    // Output data of each row
                    while($row = $fetch_retailer_inventory_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>".$row["item_id"]."</td>";
                        echo "<td>".$row["item_name"]."</td>";
                        echo "<td>$".$row["price"]."</td>";
                        echo "<td>".$row["category"]."</td>";
                        echo "<td>".$row["stall_name"]."</td>";
                        echo "<td>".$row["quantity_in_stock"]."</td>";
                        echo "<td><img src='../".$row["image_path"]."' class='img-thumbnail' alt='".$row["item_name"]."'></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='text-center'>No inventory items found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Orders Section -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">Recent Orders</h2>
        <a href="#" class="section-action-btn">
            <i class="bi bi-eye"></i>
            View All Orders
        </a>
    </div>
    
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- This would be populated with PHP in a production environment -->
                <tr>
                    <td>#ORD-2023-001</td>
                    <td>John Smith</td>
                    <td>Mar 5, 2025</td>
                    <td>3</td>
                    <td>$15.99</td>
                    <td><span class="badge bg-success">Completed</span></td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-edit">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#ORD-2023-002</td>
                    <td>Sarah Johnson</td>
                    <td>Mar 5, 2025</td>
                    <td>2</td>
                    <td>$12.50</td>
                    <td><span class="badge bg-warning">Processing</span></td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-edit">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td>#ORD-2023-003</td>
                    <td>Michael Brown</td>
                    <td>Mar 4, 2025</td>
                    <td>5</td>
                    <td>$27.75</td>
                    <td><span class="badge bg-info">Ready</span></td>
                    <td>
                        <div class="action-buttons">
                            <button type="button" class="btn-edit">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Analytics Section -->
<div class="content-section">
    <div class="section-header">
        <h2 class="section-title">Sales Analytics</h2>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Revenue</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Popular Items</h5>
                </div>
                <div class="card-body">
                    <canvas id="itemsChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="Student">Student</option>
                            <option value="Retailer">Retailer</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="section-action-btn" name="add_user">Add User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap & Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Toggle Sidebar
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebar.classList.toggle('sidebar-shrink');
        mainContent.classList.toggle('main-content-expanded');
        
        const icon = this.querySelector('i');
        if (icon.classList.contains('bi-arrow-left')) {
            icon.classList.replace('bi-arrow-left', 'bi-arrow-right');
        } else {
            icon.classList.replace('bi-arrow-right', 'bi-arrow-left');
        }
    });

    // Sample data for charts
    const monthlyRevenue = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Revenue ($)',
            data: [12000, 19000, 15000, 21000, 18000, 23000],
            backgroundColor: 'rgba(230, 57, 70, 0.2)',
            borderColor: 'rgba(230, 57, 70, 1)',
            borderWidth: 2,
            tension: 0.4
        }]
    };

    const popularItems = {
        labels: ['Coffee', 'Sandwich', 'Burgers', 'Pasta', 'Pizza'],
        datasets: [{
            label: 'Units Sold',
            data: [150, 120, 180, 90, 110],
            backgroundColor: [
                'rgba(230, 57, 70, 0.7)',
                'rgba(29, 53, 87, 0.7)',
                'rgba(69, 123, 157, 0.7)',
                'rgba(168, 218, 220, 0.7)',
                'rgba(241, 250, 238, 0.7)'
            ],
            borderWidth: 1
        }]
    };

    // Initialize Charts
    window.addEventListener('DOMContentLoaded', function() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: monthlyRevenue,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Popular Items Chart
        const itemsCtx = document.getElementById('itemsChart').getContext('2d');
        new Chart(itemsCtx, {
            type: 'bar',
            data: popularItems,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

</body>
</html>