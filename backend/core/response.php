<?php

header("Content-Type: application/json; charset=UTF-8");

function successResponse(string $message = "Success", $data = null, int $statusCode = 200)
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => true,
        "message" => $message,
        "data" => $data
    ]);

    exit;
}

function errorResponse(string $message = "Error", $errors = null, int $statusCode = 400)
{
    http_response_code($statusCode);

    echo json_encode([
        "success" => false,
        "message" => $message,
        "errors" => $errors
    ]);

    exit;
}
