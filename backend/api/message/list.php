<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];
$userType = $user['type'];

$withId   = $_GET['with_id'] ?? null;
$withType = $_GET['with_type'] ?? null;
$flightId = $_GET['flight_id'] ?? null;

if (!$withId || !$withType) {
    errorResponse("with_id and with_type are required");
}

try {
    $query = "
        SELECT *
        FROM messages
        WHERE
            ((sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?)
          OR (sender_id = ? AND sender_type = ? AND receiver_id = ? AND receiver_type = ?))
    ";
    $params = [$userId, $userType, $withId, $withType, $withId, $withType, $userId, $userType];

    if ($flightId) {
        $query .= " AND flight_id = ?";
        $params[] = $flightId;
    }

    $query .= " ORDER BY created_at ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    successResponse("Messages fetched successfully", $messages);
} catch (Exception $e) {
    errorResponse("Failed to fetch messages", $e, 500);
}
