<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["stall_name"])) {
        $stall = $_POST["stall_name"]; // Get stall name

        // Define a mapping of stall names to their respective PHP pages
        $stallPages = [
            "Kael Food Store" => "kael.php",
            "Bonapetite" => "bonapetite.php"
        ];

        // Check if the selected stall exists in the mapping
        if (array_key_exists($stall, $stallPages)) {
            $redirectPage = $stallPages[$stall];
            header("Location: $redirectPage?stall=" . urlencode($stall));
        } else {
            // Redirect to a default page if stall is not found
            header("Location: retailer_dashboard.php");
        }
    } else {
        // Redirect back if no stall is selected
        header("Location: retailer_dashboard.php");
    }
    exit();
}
?>
