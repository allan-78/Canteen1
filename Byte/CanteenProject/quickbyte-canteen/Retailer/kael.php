<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stall Management</title>
    <link href="../../css/styles.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar">
        <div class="logo">Retailer Panel</div>
        <ul class="nav-links">
            <li><a href="retailer_dashboard.php">Dashboard</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h1>Welcome to 
            <?php echo isset($_GET['stall']) ? htmlspecialchars($_GET['stall']) : "Unknown Stall"; ?>!
        </h1>
        <p>Manage your stall efficiently with the options below.</p>

        <div class="management-options">
            <a href="#" class="option">Manage Menu</a>
            <a href="#" class="option">View Orders</a>
            <a href="#" class="option">Update Settings</a>
        </div>
    </div>

</body>
</html>
