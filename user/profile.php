<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Only allow users to access this page
if (!$auth->hasPermission('user')) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$user_id = $_SESSION['user_id'];
$error = '';

// Get user details
$query = "SELECT * FROM users WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    CSRF::validateRequest(); // CSRF validation
    
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $bio = sanitizeInput($_POST['bio']);
    $password = sanitizeInput($_POST['password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    
    // Check if email already exists (excluding current user)
    $query = "SELECT id FROM users WHERE email = :email AND id != :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = 'Email already exists.';
    } else {
        if (!empty($password)) {
            if ($password !== $confirm_password) {
                $error = 'Passwords do not match.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET name=:name, email=:email, bio=:bio, password_hash=:password_hash WHERE id=:id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':password_hash', $password_hash);
            }
        } else {
            $query = "UPDATE users SET name=:name, email=:email, bio=:bio WHERE id=:id";
            $stmt = $conn->prepare($query);
        }
        
        if (empty($error)) {
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                // Update session data
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['bio'] = $bio;
                
                $_SESSION['message'] = 'Profile updated successfully.';
                header('Location: profile.php');
                exit();
            } else {
                $error = 'Failed to update profile.';
            }
        }
    }
}
?>

<div class="card">
    <h2>My Profile</h2>
    
    <?php if ($error): ?>
        <div class="alert"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <?php echo CSRF::getTokenField(); ?>
        
        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password">
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
        
        <button type="submit" class="btn">Update Profile</button>
    </form>
</div>

<?php
require_once '../includes/footer.php';
?>