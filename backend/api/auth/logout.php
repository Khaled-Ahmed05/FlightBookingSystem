<?php
require_once '../../core/response.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

session_destroy();

successResponse("Logged out successfully");
