<?php
session_start();
include '../config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate the input
if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit();
}

$amount = floatval($data['amount']);

// Update the user's balance
$sql = "UPDATE users SET balance = balance + ? WHERE user_id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("di", $amount, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$stmt->close();
$con->close();
?>