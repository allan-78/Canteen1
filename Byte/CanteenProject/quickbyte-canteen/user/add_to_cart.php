<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the JSON data from the request
    $data = json_decode(file_get_contents('php://input'), true);

    $item_id = $data['item_id'];
    $user_id = $_SESSION['user_id'];

    // Check if the item exists in inventory and has sufficient stock
    $sql = "SELECT quantity_in_stock FROM inventory WHERE item_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $inventory = $result->fetch_assoc();
    $stmt->close();

    if (!$inventory || $inventory['quantity_in_stock'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Item is out of stock.']);
        exit();
    }

    // Check if the item already exists in the cart
    $sql = "SELECT * FROM cart WHERE user_id = ? AND item_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $user_id, $item_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the quantity if the item already exists
        $sql = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND item_id = ?";
    } else {
        // Insert a new row if the item doesn't exist
        $sql = "INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, 1)";
    }

    $stmt = $con->prepare($sql);
    $stmt->bind_param("ii", $user_id, $item_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success]);
}
?>