<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('passenger');

$user = currentUser();
$userId = $user['id'];
$data = json_decode(file_get_contents("php://input"), true);

$flightId = $data['flight_id'] ?? null;
$paymentMethod = $data['payment_method'] ?? 'cash';

if (!$flightId) {
    errorResponse("Flight ID is required");
}

try {
    $stmt = $pdo->prepare("SELECT id, account_balance FROM passengers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $passenger = $stmt->fetch();

    if (!$passenger) {
        errorResponse("Passenger not found", null, 404);
    }
    $passengerId = $passenger['id'];
    $accountBalance = $passenger['account_balance'];

    $stmt = $pdo->prepare("
        SELECT fees, max_passengers, is_completed,
               (SELECT COUNT(*) FROM bookings WHERE flight_id = ? AND status='registered') AS registered_count
        FROM flights
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$flightId, $flightId]);
    $flight = $stmt->fetch();

    if (!$flight) {
        errorResponse("Flight not found", null, 404);
    }

    if ($flight['registered_count'] >= $flight['max_passengers']) {
        errorResponse("Flight is full");
    }

    if ($flight['is_completed']) {
        errorResponse("Cannot book a completed flight");
    }

    $status = 'pending';

    if ($paymentMethod === 'account') {
        if ($accountBalance < $flight['fees']) {
            errorResponse("Insufficient account balance");
        }
        $status = 'registered';

        // Begin transaction
        $pdo->beginTransaction();

        try {
            // Deduct passenger balance
            $stmt = $pdo->prepare("
                UPDATE passengers
                SET account_balance = account_balance - ?
                WHERE id = ?
            ");
            $stmt->execute([$flight['fees'], $passengerId]);

            // Insert booking
            $stmt = $pdo->prepare("
                INSERT INTO bookings (flight_id, passenger_id, status)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$flightId, $passengerId, $status]);

            // Get company ID of the flight
            $stmt = $pdo->prepare("SELECT company_id FROM flights WHERE id = ? LIMIT 1");
            $stmt->execute([$flightId]);
            $company = $stmt->fetch();
            if ($company && isset($company['company_id'])) {
                $companyId = $company['company_id'];
                // Add fees to company's balance
                $stmt = $pdo->prepare("
                    UPDATE companies
                    SET account_balance = account_balance + ?
                    WHERE id = ?
                ");
                $stmt->execute([$flight['fees'], $companyId]);
            }

            // Commit transaction
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e; // Will be caught by outer catch
        }
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO bookings (flight_id, passenger_id, status)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$flightId, $passengerId, $status]);
    }

    successResponse("Flight booked successfully", [
        "flight_id" => $flightId,
        "status" => $status
    ], 201);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    errorResponse("Failed to book flight", $e, 500);
}
