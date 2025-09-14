<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'todo_app');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'To-Do List App');
define('BASE_URL', 'http://localhost/todo-app/');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>