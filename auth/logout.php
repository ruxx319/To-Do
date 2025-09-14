<?php
require_once '../includes/header.php';

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::validateRequest();
}

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit();
?>