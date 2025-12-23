<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('passenger');

$fromCity = $_GET['from'] ?? null;
$toCity = $_GET['to'] ?? null;

if (!$fromCity || !$toCity) {
    errorResponse("From and To cities are required");
}

try {
    $stmt = $pdo->prepare("
        SELECT f.id, f.name, f.fees, f.max_passengers, f.is_completed, u.name AS company_name, c.logo AS company_logo
        FROM flights f
        JOIN companies c ON c.id = f.company_id
        JOIN users u ON u.id = c.user_id
        JOIN flight_itinerary fi_from ON fi_from.flight_id = f.id
        JOIN flight_itinerary fi_to   ON fi_to.flight_id = f.id
        WHERE fi_from.city = ?
          AND fi_to.city = ?
          AND fi_from.order_index < fi_to.order_index
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$fromCity, $toCity]);

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
    errorResponse("Failed to search flights", $e, 500);
}
