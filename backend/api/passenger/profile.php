<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('passenger');

$user = currentUser();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            u.email,
            u.tel,
            p.photo,
            p.passport_img,
            p.account_balance
        FROM users u
        JOIN passengers p ON p.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $passenger = $stmt->fetch();

    if (!$passenger) {
        errorResponse("Passenger profile not found", null, 404);
    }

    successResponse("Passenger profile fetched", $passenger);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['name'] ?? null;
    $tel  = $_POST['tel'] ?? null;
    $account_balance = $_POST['account_balance'] ?? null;

    $photo = $_FILES['photo']['name'] ?? null;
    $passport_img = $_FILES['passport_img']['name'] ?? null;

    try {
        // Handle file uploads
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $target = __DIR__ . "/../../uploads/passengers/" . basename($_FILES['photo']['name']);
            move_uploaded_file($_FILES['photo']['tmp_name'], $target);
        }

        if (isset($_FILES['passport_img']) && $_FILES['passport_img']['error'] === UPLOAD_ERR_OK) {
            $target = __DIR__ . "/../../uploads/passports/" . basename($_FILES['passport_img']['name']);
            move_uploaded_file($_FILES['passport_img']['tmp_name'], $target);
        }

        if ($name || $tel) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET name = COALESCE(?, name),
                    tel  = COALESCE(?, tel)
                WHERE id = ?
            ");
            $stmt->execute([$name, $tel, $userId]);
        }

        $stmt = $pdo->prepare("
            UPDATE passengers
            SET photo = COALESCE(?, photo),
                passport_img = COALESCE(?, passport_img),
                account_balance = COALESCE(?, account_balance)
            WHERE user_id = ?
        ");
        $stmt->execute([$photo, $passport_img, $account_balance, $userId]);

        $stmt = $pdo->prepare("
            SELECT u.name, u.email, u.tel, p.photo, p.passport_img, p.account_balance
            FROM users u
            JOIN passengers p ON p.user_id = u.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $updated = $stmt->fetch();

        successResponse("Profile updated successfully", $updated);
    } catch (Exception $e) {
        errorResponse("Failed to update profile", $e, 500);
    }
}

errorResponse("Invalid request method", null, 405);
