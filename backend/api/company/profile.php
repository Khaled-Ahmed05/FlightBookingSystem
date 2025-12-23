<?php
require_once '../../config/db.php';
require_once '../../core/response.php';
require_once '../../core/auth.php';

requireRole('company');

$user = currentUser();
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            u.email,
            u.tel,
            c.bio,
            c.address,
            c.location,
            c.logo,
            c.account_balance
        FROM users u
        JOIN companies c ON c.user_id = u.id
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);

    $company = $stmt->fetch();

    if (!$company) {
        errorResponse("Company profile not found", null, 404);
    }

    successResponse("Company profile fetched", $company);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = $_POST['name']     ?? null;
    $tel      = $_POST['tel']      ?? null;
    $bio      = $_POST['bio']      ?? null;
    $address  = $_POST['address']  ?? null;
    $location = $_POST['location'] ?? null;
    $account_balance = $_POST['account_balance'] ?? null;

    $logo = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $logoName = 'company_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = '../../backend/uploads/companies/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName);
        $logo = $logoName;
    }

    try {
        if ($name || $tel) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET 
                    name = COALESCE(?, name),
                    tel  = COALESCE(?, tel)
                WHERE id = ?
            ");
            $stmt->execute([$name, $tel, $userId]);
        }

        $stmt = $pdo->prepare("
            UPDATE companies
            SET
                bio      = COALESCE(?, bio),
                address  = COALESCE(?, address),
                location = COALESCE(?, location),
                logo     = COALESCE(?, logo),
                account_balance     = COALESCE(?, account_balance)
            WHERE user_id = ?
        ");
        $stmt->execute([$bio, $address, $location, $logo, $account_balance, $userId]);

        successResponse("Company profile updated successfully");
    } catch (Exception $e) {
        errorResponse("Failed to update profile", $e, 500);
    }
}

errorResponse("Invalid request method", null, 405);
