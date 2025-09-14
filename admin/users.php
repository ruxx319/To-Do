<?php
require_once '../includes/header.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();

// Only allow admins to access this page
if (!$auth->hasPermission('admin')) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

// Handle different actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_user'])) {
        $user_id = sanitizeInput($_POST['user_id']);
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $role = sanitizeInput($_POST['role']);
        $org_id = sanitizeInput($_POST['org_id']);
        $bio = sanitizeInput($_POST['bio']);
        
        // Check if email already exists (excluding current user)
        $query = "SELECT id FROM users WHERE email = :email AND id != :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['message'] = 'Email already exists.';
            header('Location: users.php?action=edit&id=' . $user_id);
            exit();
        } else {
            $query = "UPDATE users SET name=:name, email=:email, role=:role, org_id=:org_id, bio=:bio WHERE id=:id";
            $stmt = $conn->prepare($query);
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':org_id', $org_id);
            $stmt->bindParam(':bio', $bio);
            $stmt->bindParam(':id', $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'User updated successfully.';
                header('Location: users.php');
                exit();
            } else {
                $_SESSION['message'] = 'Failed to update user.';
                header('Location: users.php?action=edit&id=' . $user_id);
                exit();
            }
        }
    }
} elseif ($action == 'delete' && $user_id) {
    // Prevent admin from deleting themselves
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['message'] = 'You cannot delete your own account.';
    } else {
        $query = "DELETE FROM users WHERE id=:id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'User deleted successfully.';
        } else {
            $_SESSION['message'] = 'Failed to delete user.';
        }
    }
    
    header('Location: users.php');
    exit();
}

// Display appropriate view based on action
if ($action == 'edit' && $user_id) {
    // Get user details
    $query = "SELECT * FROM users WHERE id=:id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get all organizations
        $query = "SELECT * FROM organizations ORDER BY name";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        
        <div class="card">
            <h2>Edit User</h2>
            
            <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php echo $user['name']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="org" <?php echo $user['role'] == 'org' ? 'selected' : ''; ?>>Organization</option>
                        <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="org_id">Organization</label>
                    <select id="org_id" name="org_id">
                        <option value="">None</option>
                        <?php foreach ($organizations as $org): ?>
                            <option value="<?php echo $org['id']; ?>" <?php echo $user['org_id'] == $org['id'] ? 'selected' : ''; ?>>
                                <?php echo $org['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" rows="4"><?php echo $user['bio']; ?></textarea>
                </div>
                
                <button type="submit" name="update_user" class="btn">Update User</button>
                <a href="users.php" class="btn btn-danger">Cancel</a>
            </form>
        </div>
        
        <?php
    } else {
        $_SESSION['message'] = 'User not found.';
        header('Location: users.php');
        exit();
    }
} else {
    // List all users
    $query = "SELECT u.*, o.name as org_name FROM users u LEFT JOIN organizations o ON u.org_id = o.id ORDER BY u.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>
    
    <div class="card">
        <h2>Manage Users</h2>
        
        <?php if (count($users) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Organization</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['name']; ?></td>
                            <td><?php echo $user['email']; ?></td>
                            <td><?php echo ucfirst($user['role']); ?></td>
                            <td><?php echo $user['org_name'] ? $user['org_name'] : 'None'; ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No users found.</p>
        <?php endif; ?>
    </div>
    
    <?php
}

require_once '../includes/footer.php';
?>