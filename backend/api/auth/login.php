<?php
require_once '../../config/db.php';
require_once '../../core/response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email']) || empty($data['password'])) {
    errorResponse("Email and password are required");
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, password, type
        FROM users
        WHERE email = ?
        LIMIT 1
    ");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch();

    if (!$user) {
        errorResponse("Invalid email or password", null, 401);
    }

    if (!password_verify($data['password'], $user['password'])) {
        errorResponse("Invalid email or password", null, 401);
    }

    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'type'  => $user['type']
    ];

    successResponse("Login successful", $_SESSION['user']);
} catch (Exception $e) {
    errorResponse("Login failed", $e, 500);
}
