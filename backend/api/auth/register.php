<?php
require_once '../../config/db.php';
require_once '../../core/response.php';

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['name']) ||
    empty($data['email']) ||
    empty($data['password']) ||
    empty($data['type'])
) {
    errorResponse("Missing required fields");
}

if (!in_array($data['type'], ['company', 'passenger'])) {
    errorResponse("Invalid user type");
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);

    if ($stmt->fetch()) {
        errorResponse("Email already registered");
    }

    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password, tel, type)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['name'],
        $data['email'],
        $hashedPassword,
        $data['tel'] ?? null,
        $data['type']
    ]);

    $userId = $pdo->lastInsertId();

    if ($data['type'] === 'company') {
        $stmt = $pdo->prepare("
            INSERT INTO companies (user_id)
            VALUES (?)
        ");
        $stmt->execute([$userId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO passengers (user_id)
            VALUES (?)
        ");
        $stmt->execute([$userId]);
    }

    successResponse("Registered successfully");
} catch (Exception $e) {
    errorResponse("Registration failed", $e, 500);
}
