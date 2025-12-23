<?php
require_once '../../core/auth.php';
require_once '../../core/response.php';

requireLogin();

successResponse("Authenticated user", currentUser());
