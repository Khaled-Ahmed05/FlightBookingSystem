<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];
$userType = $user['type'];

$data = json_decode(file_get_contents("php://input"), true);

$receiverId   = $data['receiver_id'] ?? null;
$receiverType = $data['receiver_type'] ?? null;
$flightId     = $data['flight_id'] ?? null;
$message      = trim($data['message'] ?? '');

if (!$receiverId || !$receiverType || !$message) {
    errorResponse("Receiver ID, receiver type, and message are required");
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, sender_type, receiver_type, flight_id, message)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $receiverId, $userType, $receiverType, $flightId, $message]);

    successResponse("Message sent successfully", [
        "message_id" => $pdo->lastInsertId()
    ], 201);
} catch (Exception $e) {
    errorResponse("Failed to send message", $e, 500);
}
