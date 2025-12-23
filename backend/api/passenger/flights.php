<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('passenger');

$user = currentUser();
$userId = $user['id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM passengers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $passenger = $stmt->fetch();
    if (!$passenger) {
        errorResponse("Passenger not found", null, 404);
    }
    $passengerId = $passenger['id'];

    $stmt = $pdo->prepare("
        SELECT f.*, u.name AS company_name, c.logo AS company_logo
        FROM bookings b
        JOIN flights f ON f.id = b.flight_id
        JOIN companies c ON c.id = f.company_id
        JOIN users u ON u.id = c.user_id
        WHERE b.passenger_id = ? AND f.is_completed = 1 AND b.status='registered'
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$passengerId]);
    $completedFlights = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT f.*, u.name AS company_name, c.logo AS company_logo, b.status AS booking_status
        FROM bookings b
        JOIN flights f ON f.id = b.flight_id
        JOIN companies c ON c.id = f.company_id
        JOIN users u ON u.id = c.user_id
        WHERE b.passenger_id = ? AND f.is_completed = 0 AND (b.status='registered' OR b.status='pending')
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$passengerId]);
    $currentFlights = $stmt->fetchAll();

    $stmtItinerary = $pdo->prepare("
        SELECT city, start_time, end_time, order_index
        FROM flight_itinerary
        WHERE flight_id = ?
        ORDER BY order_index ASC
    ");

    foreach ($completedFlights as &$flight) {
        $stmtItinerary->execute([$flight['id']]);
        $flight['itinerary'] = $stmtItinerary->fetchAll();
    }

    foreach ($currentFlights as &$flight) {
        $stmtItinerary->execute([$flight['id']]);
        $flight['itinerary'] = $stmtItinerary->fetchAll();
    }

    successResponse("Passenger flights fetched", [
        "completed" => $completedFlights,
        "current" => $currentFlights
    ]);
} catch (Exception $e) {
    errorResponse("Failed to fetch flights", $e, 500);
}
