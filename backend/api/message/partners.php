<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireLogin();
$user = currentUser();
$userId = $user['id'];
$userType = $user['type'];

try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS id,
            CASE WHEN sender_id = ? THEN receiver_type ELSE sender_type END AS type,
            CASE WHEN sender_id = ? THEN (SELECT name FROM users WHERE id = receiver_id) ELSE (SELECT name FROM users WHERE id = sender_id) END AS name
        FROM messages
        WHERE sender_id = ? OR receiver_id = ?
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId]);
    $partners = $stmt->fetchAll();

    successResponse("Partners fetched", $partners);
} catch (Exception $e) {
    errorResponse("Failed to fetch partners", $e, 500);
}
