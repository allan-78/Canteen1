<?php
// includes/style.php

// Option 1: Bootstrap CDN
echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';




// Custom Styles (Move your existing styles from assets/css/style.css here)
echo '<style>';
echo '
body {
    background-color: #f8f9fa; /* Light gray background */
}

.card {
    border: none;
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.15); /* Subtle shadow */
}

.card-header {
    background-color: #007bff; /* Primary color */
    color: white;
    border-bottom: none;
}

.btn-primary {
    background-color: #007bff;
    border-color: #007bff;
}

.btn-primary:hover {
    background-color: #0056b3;
    border-color: #0056b3;
}

/* Add more custom styles here! */

'; //End of style echo
echo '</style>';
?>
