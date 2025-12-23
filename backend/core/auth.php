<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//  Check if user is logged in
function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

// Require login
function requireLogin()
{
    if (!isLoggedIn()) {
        errorResponse("Unauthorized access", null, 401);
    }
}

// Get logged-in user
function currentUser()
{
    return $_SESSION['user'] ?? null;
}

// Require specific role
function requireRole(string $role)
{
    requireLogin();

    if ($_SESSION['user']['type'] !== $role) {
        errorResponse("Forbidden", null, 403);
    }
}
