<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('company');

$user = currentUser();
$userId = $user['id'];
$data = json_decode(file_get_contents("php://input"), true);

$flightId = $data['flight_id'] ?? null;
if (!$flightId) {
    errorResponse("Flight ID is required");
}

try {
    $stmt = $pdo->prepare("SELECT id, account_balance FROM companies WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $company = $stmt->fetch();

    if (!$company) {
        errorResponse("Company not found", null, 404);
    }
    $companyId = $company['id'];
    $companyBalance = $company['account_balance'];

    $stmt = $pdo->prepare("SELECT fees FROM flights WHERE id = ? AND company_id = ? LIMIT 1");
    $stmt->execute([$flightId, $companyId]);
    $flight = $stmt->fetch();

    if (!$flight) {
        errorResponse("Flight not found or not owned by company", null, 404);
    }
    $flightFees = $flight['fees'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE flights SET is_completed = 1 WHERE id = ?");
    $stmt->execute([$flightId]);

    $stmt = $pdo->prepare("
        SELECT b.passenger_id
        FROM bookings b
        WHERE b.flight_id = ? AND b.status = 'registered'
    ");
    $stmt->execute([$flightId]);
    $passengers = $stmt->fetchAll();

    $totalRefund = $flightFees * count($passengers);

    if ($companyBalance < $totalRefund) {
        throw new Exception("Company does not have enough balance to refund passengers");
    }

    $stmt = $pdo->prepare("UPDATE companies SET account_balance = account_balance - ? WHERE id = ?");
    $stmt->execute([$totalRefund, $companyId]);

    $stmtUpdatePassenger = $pdo->prepare("UPDATE passengers SET account_balance = account_balance + ? WHERE id = ?");
    foreach ($passengers as $p) {
        $stmtUpdatePassenger->execute([$flightFees, $p['passenger_id']]);
    }

    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE flight_id = ?");
    $stmt->execute([$flightId]);

    $pdo->commit();

    successResponse("Flight canceled and passengers refunded successfully");
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    errorResponse("Failed to cancel flight", $e, 500);
}
