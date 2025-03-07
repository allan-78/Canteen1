<?php
session_start();
include '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cart_id = $data['cart_id'];

    $sql = "DELETE FROM cart WHERE cart_id = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $cart_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success]);
}
?>