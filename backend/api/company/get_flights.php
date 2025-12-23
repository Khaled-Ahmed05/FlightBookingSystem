<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('company');

$user = currentUser();
$userId = $user['id'];

try {
    $stmt = $pdo->prepare("
        SELECT id
        FROM companies
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $company = $stmt->fetch();

    if (!$company) {
        errorResponse("Company not found", null, 404);
    }

    $companyId = $company['id'];

    $stmt = $pdo->prepare("
        SELECT
            f.id,
            f.name,
            f.fees,
            f.max_passengers,
            f.is_completed,
            f.created_at,

            SUM(CASE WHEN b.status = 'registered' THEN 1 ELSE 0 END) AS registered_count,
            SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) AS pending_count

        FROM flights f
        LEFT JOIN bookings b ON b.flight_id = f.id
        WHERE f.company_id = ?
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$companyId]);

    $flights = $stmt->fetchAll();

    successResponse("Flights fetched successfully", $flights);
} catch (Exception $e) {
    errorResponse("Failed to fetch flights", $e, 500);
}
