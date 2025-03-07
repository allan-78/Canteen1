<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Retailer Dashboard</title>
    <link href="../../css/styles.css" rel="stylesheet" />
</head>
<body>

    <nav class="navbar">
        <div class="logo">Retailer Panel</div>
        <ul class="nav-links">
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Settings</a></li>
            <li><a href="#">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h1>Welcome, Retailer!</h1>
        <p>Select the stall you want to manage:</p>
        <form action="redirect.php" method="POST">
            <select name="stall_name" required>
                <option value="" disabled selected>Choose a stall</option>
                <option value="Kael Food Store">Stall 1 - Kael Food Store</option>
                <option value="Bonapetite">Stall 2 - Bonapetite</option>
            </select>
            <button type="submit">Manage Stall</button>
        </form>
    </div>

</body>
</html>
