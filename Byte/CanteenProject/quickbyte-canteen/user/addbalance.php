<?php
session_start();
include '../config.php';

// Enable detailed error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Function to send JSON response
function sendJsonResponse($success, $message = null) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(false, 'User not logged in.');
}

$user_id = $_SESSION['user_id'];

// Retrieve data from the POST request
$amount = $_POST['amount'];
$payment_method = 'gcash'; // Payment method is always GCash
$accountNumber = $_POST['accountNumber'];
$accountName = $_POST['accountName'];

// Validate input data
if (empty($amount) || empty($accountNumber) || empty($accountName)) {
    sendJsonResponse(false, 'All fields are required.');
}

if (!is_numeric($amount) || $amount <= 0 || $amount > 1000) {
    sendJsonResponse(false, 'Amount must be a number between 10 and 1000.');
}

// Perform the balance update
// Retrieve GCash account details
$sql = "SELECT account_id, balance FROM gcash_accounts WHERE account_number = ? AND account_name = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("ss", $accountNumber, $accountName);
$stmt->execute();
$result = $stmt->get_result();
$gcashAccount = $result->fetch_assoc();
$stmt->close();

if (!$gcashAccount) {
    sendJsonResponse(false, 'GCash account not found.');
}

if ($gcashAccount['balance'] < $amount) {
    sendJsonResponse(false, 'Insufficient balance in GCash account.');
}

try {
    // Start a transaction
    $con->begin_transaction();

    // Update user balance
    $sql = "UPDATE users SET balance = balance + ? WHERE user_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("di", $amount, $user_id);
    $stmt->execute();

    // Update GCash balance
    $sql = "UPDATE gcash_accounts SET balance = balance - ? WHERE account_number = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ds", $amount, $accountNumber);
    $stmt->execute();

    // Commit the transaction
    $con->commit();

    sendJsonResponse(true, 'Balance added successfully!');

} catch (mysqli_sql_exception $exception) {
    $con->rollback();
    sendJsonResponse(false, 'Transaction failed: ' . $exception->getMessage());
}
?>
