<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('company');

$user = currentUser();
$userId = $user['id'];

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['name']) ||
    empty($data['fees']) ||
    empty($data['max_passengers']) ||
    empty($data['itinerary']) ||
    !is_array($data['itinerary'])
) {
    errorResponse("Missing or invalid fields");
}

try {
    $stmt = $pdo->prepare("SELECT id FROM companies WHERE user_id = ?");
    $stmt->execute([$userId]);
    $company = $stmt->fetch();

    if (!$company) {
        errorResponse("Company not found", null, 404);
    }

    $companyId = $company['id'];

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO flights (company_id, name, fees, max_passengers)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $companyId,
        $data['name'],
        $data['fees'],
        $data['max_passengers']
    ]);

    $flightId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO flight_itinerary (flight_id, city, start_time, end_time, order_index)
        VALUES (?, ?, ?, ?, ?)
    ");

    $order = 1;
    foreach ($data['itinerary'] as $stop) {

        if (
            empty($stop['city']) ||
            empty($stop['start_time']) ||
            empty($stop['end_time'])
        ) {
            throw new Exception("Invalid itinerary data");
        }

        $stmt->execute([
            $flightId,
            $stop['city'],
            $stop['start_time'],
            $stop['end_time'],
            $order++
        ]);
    }

    $pdo->commit();

    successResponse("Flight added successfully", [
        "flight_id" => $flightId
    ], 201);
} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    errorResponse("Failed to add flight", $e, 500);
}
