<?php
require_once '../includes/header.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    $auth->redirectBasedOnRole();
}

$name = $email = $password = $confirm_password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    CSRF::validateRequest(); // CSRF validation
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        if ($auth->register($name, $email, $password)) {
            $_SESSION['message'] = 'Registration successful. Please login.';
            header('Location: login.php');
            exit();
        } else {
            $error = 'Email already exists.';
        }
    }
}
?>

<div class="card">
    <h2>Register</h2>
    
    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRF::getTokenField(); ?>
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo $name; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

<?php
require_once '../includes/footer.php';
?>