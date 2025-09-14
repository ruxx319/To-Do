<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'csrf.php'; // Add this line

$auth = new Auth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="<?php echo BASE_URL; ?>"><?php echo APP_NAME; ?></a></h1>
            </div>
            <nav>
                <ul>
                    <?php if ($auth->isLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Admin Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/users.php">Manage Users</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/tasks.php">Manage Tasks</a></li>
                            <li><a href="<?php echo BASE_URL; ?>admin/organizations.php">Manage Organizations</a></li>
                        <?php elseif ($_SESSION['role'] === 'org'): ?>
                            <li><a href="<?php echo BASE_URL; ?>org/dashboard.php">Organization Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>org/members.php">Members</a></li>
                            <li><a href="<?php echo BASE_URL; ?>org/tasks.php">Organization Tasks</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>user/dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo BASE_URL; ?>user/tasks.php">My Tasks</a></li>
                            <li><a href="<?php echo BASE_URL; ?>user/profile.php">Profile</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>auth/logout.php">Logout (<?php echo $_SESSION['name']; ?>)</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>auth/login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>auth/register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                ?>
            </div>
        <?php endif; ?>