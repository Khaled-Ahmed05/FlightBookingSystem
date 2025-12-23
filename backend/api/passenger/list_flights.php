<?php

require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('passenger');

try {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name, f.fees, f.max_passengers, f.is_completed, u.name AS company_name, c.logo AS company_logo
        FROM flights f
        JOIN companies c ON c.id = f.company_id
        JOIN users u ON u.id = c.user_id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute();

    $flights = $stmt->fetchAll();

    $stmtItinerary = $pdo->prepare("
        SELECT city, start_time, end_time, order_index
        FROM flight_itinerary
        WHERE flight_id = ?
        ORDER BY order_index ASC
    ");

    foreach ($flights as &$flight) {
        $stmtItinerary->execute([$flight['id']]);
        $flight['itinerary'] = $stmtItinerary->fetchAll();
    }

    successResponse("Flights fetched successfully", $flights);
} catch (Exception $e) {
    errorResponse("Failed to fetch flights", $e, 500);
}
