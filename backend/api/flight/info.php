<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];
$userType = $user['type'];

$flightId = $_GET['flight_id'] ?? null;

if (!$flightId) {
    errorResponse("Flight ID is required");
}

try {
    if ($userType === 'company') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM flights
            WHERE id = ? AND company_id = (
                SELECT id FROM companies WHERE user_id = ?
            )
            LIMIT 1
        ");
        $stmt->execute([$flightId, $userId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT f.*, u.name AS company_name, u.id AS company_real_id, c.logo AS company_logo
            FROM flights f
            JOIN companies c ON c.id = f.company_id
            JOIN users u ON u.id = c.user_id
            WHERE f.id = ?
            LIMIT 1
        ");
        $stmt->execute([$flightId]);
    }

    $flight = $stmt->fetch();

    if (!$flight) {
        errorResponse("Flight not found", null, 404);
    }

    $stmt = $pdo->prepare("
        SELECT city, start_time, end_time, order_index
        FROM flight_itinerary
        WHERE flight_id = ?
        ORDER BY order_index ASC
    ");
    $stmt->execute([$flightId]);
    $itinerary = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.id, u.name, u.email, u.tel, b.status
        FROM bookings b
        JOIN passengers p ON p.id = b.passenger_id
        JOIN users u ON u.id = p.user_id
        WHERE b.flight_id = ?
    ");
    $stmt->execute([$flightId]);
    $allPassengers = $stmt->fetchAll();

    $passengers = [
        "registered" => array_filter($allPassengers, fn($p) => $p['status'] === 'registered'),
        "pending" => array_filter($allPassengers, fn($p) => $p['status'] === 'pending')
    ];

    successResponse("Flight info fetched", [
        "company_id" => $flight['company_id'],
        "flight_id" => $flight['id'],
        "name" => $flight['name'],
        "fees" => $flight['fees'],
        "max_passengers" => $flight['max_passengers'],
        "itinerary" => $itinerary,
        "registered_passengers" => $passengers['registered'] ?? [],
        "pending_passengers" => $passengers['pending'] ?? [],
        "company_real_id" => $flight['company_real_id'] ?? null,
    ]);
} catch (Exception $e) {
    errorResponse("Failed to fetch flight info", $e, 500);
}
