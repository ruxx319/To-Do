<?php
require_once '../includes/header.php';

$auth = new Auth();

if ($auth->isLoggedIn()) {
    $auth->redirectBasedOnRole();
}

$email = $password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    CSRF::validateRequest(); // CSRF validation
    
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    
    if ($auth->login($email, $password)) {
        $auth->redirectBasedOnRole();
    } else {
        $error = 'Invalid email or password.';
    }
}
?>

<div class="card">
    <h2>Login</h2>
    
    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRF::getTokenField(); ?>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <p>Don't have an account? <a href="register.php">Register here</a></p>
</div>

<?php
require_once '../includes/footer.php';
?>